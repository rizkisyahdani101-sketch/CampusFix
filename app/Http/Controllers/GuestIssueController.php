<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\GuestIssueReporter;
use App\Models\Issue;
use App\Models\Location;
use App\Models\PendingGuestReport;
use App\Notifications\GuestReportVerification;
use App\Services\IssueWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

final class GuestIssueController extends Controller
{
    public function create(): View
    {
        $campusId = Location::query()
            ->where('type', 'campus')
            ->whereNull('parent_id')
            ->where('name', config('campus.location_name'))
            ->value('id');

        return view('guest.issues.create', [
            'categories' => Category::query()->orderForReporting()->get(),
            'locations' => $campusId === null
                ? collect()
                : Location::query()
                    ->whereIn('type', ['faculty', 'building'])
                    ->where('name', 'not like', 'Lokasi contoh lama%')
                    ->where(function ($query) use ($campusId): void {
                        $query->where('parent_id', $campusId)
                            ->orWhereHas('parent', fn ($parent) => $parent
                                ->where('type', 'faculty')
                                ->where('parent_id', $campusId));
                    })
                    ->with('parent.parent.parent.parent')
                    ->orderBy('name')
                    ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id'),
            ],
            'location_detail' => ['nullable', 'string', 'max:255'],
            'safety_flag' => ['sometimes', 'boolean'],
            'class_blocked' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $location = Location::query()
            ->with('parent.parent.parent.parent')
            ->findOrFail($validated['location_id']);

        if (! $this->hasCampusLocationHierarchy($location)) {
            return back()->withInput()->withErrors([
                'location_id' => 'Pilih fakultas, gedung, atau fasilitas di lingkungan UNIMED.',
            ]);
        }

        $photo = $request->file('photo');
        $photoPath = $photo?->store('pending-guest-reports', 'local');
        $token = bin2hex(random_bytes(32));

        try {
            $pending = PendingGuestReport::query()->create([
                'name' => $validated['name'],
                'email' => mb_strtolower($validated['email']),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'location_id' => $validated['location_id'],
                'location_detail' => filled($validated['location_detail'] ?? null)
                    ? trim($validated['location_detail'])
                    : null,
                'safety_flag' => $request->boolean('safety_flag'),
                'class_blocked' => $request->boolean('class_blocked'),
                'photo_path' => $photoPath,
                'photo_original_name' => $photo?->getClientOriginalName(),
                'photo_mime_type' => $photo?->getMimeType(),
                'photo_file_size' => $photo?->getSize(),
                'verification_token_hash' => hash('sha256', $token),
                'expires_at' => now()->addHours(24),
            ]);

            $verificationUrl = URL::temporarySignedRoute(
                'guest.issues.verify',
                now()->addHours(24),
                ['token' => $token],
            );

            Notification::route('mail', $pending->email)
                ->notify(new GuestReportVerification($verificationUrl));
        } catch (Throwable $exception) {
            if (isset($pending)) {
                $pending->delete();
            }

            if ($photoPath !== null) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }

        return redirect()->route('guest.issues.create')
            ->with('status', 'Laporan disimpan sementara. Buka tautan verifikasi yang dikirim ke email dalam 24 jam untuk mengirimnya ke koordinator.');
    }

    public function verify(string $token, IssueWorkflow $workflow): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);
        $pending = PendingGuestReport::query()
            ->where('verification_token_hash', $tokenHash)
            ->first();

        if ($pending === null) {
            $verifiedReporter = GuestIssueReporter::query()
                ->where('verification_token_hash', $tokenHash)
                ->whereNotNull('email_verified_at')
                ->firstOrFail();

            return redirect()->to($this->trackingUrl($verifiedReporter));
        }

        if ($pending->expires_at->isPast()) {
            if ($pending->photo_path !== null) {
                Storage::disk('local')->delete($pending->photo_path);
            }

            $pending->delete();

            return redirect()->route('guest.issues.create')->withErrors([
                'email' => 'Tautan verifikasi sudah kedaluwarsa. Silakan kirim ulang laporan.',
            ]);
        }

        $publicPhotoPath = null;

        if ($pending->photo_path !== null && Storage::disk('local')->exists($pending->photo_path)) {
            $extension = pathinfo($pending->photo_path, PATHINFO_EXTENSION);
            $publicPhotoPath = 'issues/'.Str::uuid().($extension === '' ? '' : '.'.$extension);
            Storage::disk('public')->put(
                $publicPhotoPath,
                Storage::disk('local')->get($pending->photo_path),
            );
        }

        try {
            [$issue, $guestReporter] = DB::transaction(function () use (
                $pending,
                $tokenHash,
                $publicPhotoPath,
                $workflow,
            ): array {
                $lockedPending = PendingGuestReport::query()
                    ->whereKey($pending->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPending->expires_at->isPast()) {
                    throw new \DomainException('The guest report verification link has expired.');
                }

                $issue = Issue::query()->create([
                    'title' => $lockedPending->title,
                    'description' => $lockedPending->description,
                    'category_id' => $lockedPending->category_id,
                    'location_id' => $lockedPending->location_id,
                    'location_detail' => $lockedPending->location_detail,
                    'status' => IssueStatus::Reported->value,
                    'safety_flag' => $lockedPending->safety_flag,
                    'class_blocked' => $lockedPending->class_blocked,
                ]);

                $guestReporter = GuestIssueReporter::query()->create([
                    'issue_id' => $issue->getKey(),
                    'name' => $lockedPending->name,
                    'email' => $lockedPending->email,
                    'verification_token_hash' => $tokenHash,
                    'email_verified_at' => now(),
                ]);

                $workflow->recordGuestInitialReported($issue);

                if ($publicPhotoPath !== null) {
                    Attachment::query()->create([
                        'issue_id' => $issue->getKey(),
                        'original_name' => $lockedPending->photo_original_name ?? 'lampiran',
                        'file_path' => $publicPhotoPath,
                        'mime_type' => $lockedPending->photo_mime_type,
                        'file_size' => $lockedPending->photo_file_size,
                    ]);
                }

                $lockedPending->delete();

                return [$issue, $guestReporter];
            });
        } catch (Throwable $exception) {
            if ($publicPhotoPath !== null) {
                Storage::disk('public')->delete($publicPhotoPath);
            }

            throw $exception;
        }

        if ($pending->photo_path !== null) {
            Storage::disk('local')->delete($pending->photo_path);
        }

        $trackingUrl = $this->trackingUrl($guestReporter);

        return redirect()->to($trackingUrl)->with('status', 'Email berhasil dikonfirmasi. Laporan sudah masuk ke antrean koordinator.');
    }

    public function show(GuestIssueReporter $guestIssueReporter): View
    {
        $this->ensureVerifiedReporter($guestIssueReporter);

        $guestIssueReporter->load([
            'issue.category',
            'issue.location.parent.parent.parent.parent',
            'issue.attachments',
            'issue.statusHistories.actor',
            'issue.resolutions.resolvedBy',
        ]);

        return view('guest.issues.show', compact('guestIssueReporter'));
    }

    public function close(
        GuestIssueReporter $guestIssueReporter,
        IssueWorkflow $workflow,
    ): RedirectResponse {
        $this->ensureVerifiedReporter($guestIssueReporter);
        $workflow->closeAsGuest($guestIssueReporter->issue, $guestIssueReporter);

        return redirect()->to($this->trackingUrl($guestIssueReporter))
            ->with('status', 'Penyelesaian dikonfirmasi. Terima kasih.');
    }

    public function reopen(
        Request $request,
        GuestIssueReporter $guestIssueReporter,
        IssueWorkflow $workflow,
    ): RedirectResponse {
        $this->ensureVerifiedReporter($guestIssueReporter);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $workflow->reopenAsGuest($guestIssueReporter->issue, $guestIssueReporter, $validated['reason']);

        return redirect()->to($this->trackingUrl($guestIssueReporter))
            ->with('status', 'Laporan dibuka kembali untuk ditindaklanjuti.');
    }

    private function ensureVerifiedReporter(GuestIssueReporter $guestReporter): void
    {
        abort_unless($guestReporter->email_verified_at !== null, 404);
    }

    private function trackingUrl(GuestIssueReporter $guestReporter): string
    {
        return URL::signedRoute('guest.issues.show', ['guestIssueReporter' => $guestReporter]);
    }

    private function hasCampusLocationHierarchy(Location $location): bool
    {
        if ($location->type === 'campus') {
            return false;
        }

        $location->loadMissing('parent.parent.parent.parent');

        while ($location->parent !== null) {
            $location = $location->parent;
        }

        return $location->type === 'campus'
            && $location->name === config('campus.location_name');
    }
}
