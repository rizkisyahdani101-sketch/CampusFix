<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampusAnnouncement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => CampusAnnouncement::query()->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        CampusAnnouncement::query()->create([
            'message' => trim($validated['message']),
            'is_active' => $validated['is_active'] ?? false,
            'created_by' => $request->user()->getKey(),
        ]);

        return to_route('admin.announcements.index')->with('success', 'Informasi kampus berhasil ditambahkan.');
    }

    public function update(Request $request, CampusAnnouncement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $announcement->update([
            'message' => trim($validated['message']),
            'is_active' => $validated['is_active'] ?? false,
        ]);

        return to_route('admin.announcements.index')->with('success', 'Informasi kampus berhasil diperbarui.');
    }

    public function destroy(CampusAnnouncement $announcement): RedirectResponse
    {
        $announcement->delete();

        return to_route('admin.announcements.index')->with('success', 'Informasi kampus berhasil dihapus.');
    }
}
