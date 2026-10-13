<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Location;
use App\Services\IssueWorkflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

new class extends Component {
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public $category_id = '';
    public $location_id = '';
    public string $location_detail = '';
    public bool $safety_flag = false;
    public bool $class_blocked = false;
    public $photo;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:10'],
            'category_id' => ['required', 'exists:categories,id'],

            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'location_detail' => ['nullable', 'string', 'max:255'],

            'safety_flag' => ['boolean'],
            'class_blocked' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }


    public function save(IssueWorkflow $workflow): void
    {
        $validated = $this->validate();

        $selectedLocation = Location::query()
            ->with('parent.parent.parent.parent')
            ->find($validated['location_id']);

        if ($selectedLocation === null || ! $this->belongsToCampus($selectedLocation)) {
            $this->addError(
                'location_id',
                'Pilih fakultas, gedung, atau fasilitas di lingkungan UNIMED.'
            );

            return;
        }

        // Pelapor harus sudah login.
        if (!Auth::check()) {
            $this->addError(
                'form',
                'Anda harus login sebelum mengirim laporan.'
            );

            return;
        }

        $photoPath = null;

        try {
            // Simpan foto jika pengguna mengunggahnya.
            if ($this->photo) {
                $photoPath = $this->photo->store('issues', 'public');
            }

            DB::transaction(function () use ($validated, $photoPath, $workflow) {
                // Simpan laporan.
                $issue = Issue::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'],
                    'category_id' => $validated['category_id'],
                    'location_id' => $validated['location_id'],
                    'location_detail' => filled($validated['location_detail'] ?? null)
                        ? trim($validated['location_detail'])
                        : null,
                    'status' => 'reported',
                    'safety_flag' => $validated['safety_flag'],
                    'class_blocked' => $validated['class_blocked'],
                ]);

                // Hubungkan pengguna sebagai pelapor melalui tabel issue_user.
                $issue->reporters()->attach(Auth::id(), [
                    'relationship' => 'reporter',
                ]);

                $workflow->recordInitialReported($issue, Auth::user());

                // Simpan metadata foto jika ada.
                if ($photoPath) {
                    Attachment::create([
                        'issue_id' => $issue->id,
                        'original_name' => $this->photo->getClientOriginalName(),
                        'file_path' => $photoPath,
                        'mime_type' => $this->photo->getMimeType(),
                        'file_size' => $this->photo->getSize(),
                    ]);
                }
            });

            session()->flash(
                'success',
                'Laporan berhasil disimpan.'
            );

            $this->reset([
                'title',
                'description',
                'category_id',
                'location_id',
                'location_detail',
                'safety_flag',
                'class_blocked',
                'photo',
            ]);
        } catch (\Throwable $e) {
            // Hapus foto jika penyimpanan database gagal.
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $e;
        }
    }

    public function render()
    {
        $possibleDuplicates = collect();
        $searchTerms = array_values(array_filter(
            preg_split('/\s+/', mb_strtolower(trim($this->title))) ?: [],
            static fn (string $term): bool => mb_strlen($term) >= 3,
        ));

        if ($searchTerms !== [] && $this->category_id && $this->location_id) {
            $possibleDuplicates = Issue::query()
                ->with(['category', 'location'])
                ->where('category_id', $this->category_id)
                ->where('location_id', $this->location_id)
                ->whereNull('merged_into_issue_id')
                ->whereIn('status', ['reported', 'verified', 'assigned', 'in_progress', 'on_hold', 'reopened'])
                ->where(function ($query) use ($searchTerms): void {
                    foreach ($searchTerms as $term) {
                        $query->orWhere('title', 'like', '%'.$term.'%');
                    }
                })
                ->latest()
                ->limit(5)
                ->get();
        }

        $campusId = Location::query()
            ->where('type', 'campus')
            ->whereNull('parent_id')
            ->where('name', config('campus.location_name'))
            ->value('id');

        $locations = $campusId === null
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
                ->get();

        return $this->view([
            'categories' => Category::query()->orderForReporting()->get(),

            'locations' => $locations,
            'possibleDuplicates' => $possibleDuplicates,
        ]);
    }

    private function belongsToCampus(Location $location): bool
    {
        if (! in_array($location->type, ['faculty', 'building'], true)) {
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
?>

<div class="cf-page cf-page--narrow space-y-6">
    @if (session()->has('success'))
        <div class="rounded-xl border border-teal-200 bg-teal-50 p-4 text-teal-900">
            {{ session('success') }}
        </div>
    @endif

    @error('form')
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900">
            {{ $message }}
        </div>
    @enderror

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900">
            <p class="font-bold">Laporan belum berhasil disimpan:</p>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="cf-report-heading">
        <a href="{{ route('member.dashboard') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-semibold text-teal-800 hover:underline"><span aria-hidden="true">←</span> Kembali ke beranda</a>
        <h1 class="text-2xl font-bold tracking-tight text-[#17343a]">Apa kendalanya?</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-600">Ceritakan masalahnya. Tim fasilitas siap membantu menindaklanjuti.</p>
    </div>

    @if (session()->has('info'))
        <div class="rounded-lg bg-green-50 p-4 text-green-800">
            {{ session('info') }}
        </div>
    @endif

    <form wire:submit="save" class="cf-form cf-report-form cf-panel cf-panel-body space-y-6">
        <div>
            <label class="mb-2 block text-sm font-semibold text-slate-800">Kategori masalah <span class="text-rose-600">*</span></label>
            @if ($categories->isEmpty())
                <p class="cf-alert cf-alert--warning text-sm">Kategori laporan belum tersedia. Admin perlu menjalankan seeder kategori.</p>
            @else
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
                @foreach ($categories as $category)
                    @php
                        $categoryName = str($category->name)->lower()->toString();
                        $categoryIcon = match (true) {
                            str_contains($categoryName, 'wi-fi'), str_contains($categoryName, 'wifi'), str_contains($categoryName, 'jaringan') => '⌁',
                            str_contains($categoryName, 'lampu'), str_contains($categoryName, 'listrik') => '☼',
                            str_contains($categoryName, 'ac'), str_contains($categoryName, 'udara') => '❄',
                            str_contains($categoryName, 'proyektor') => '▣',
                            str_contains($categoryName, 'kursi'), str_contains($categoryName, 'mebel') => '▤',
                            str_contains($categoryName, 'toilet'), str_contains($categoryName, 'air') => '♧',
                            default => '＋',
                        };
                    @endphp
                    <label for="category-{{ $category->id }}" class="cursor-pointer">
                        <input id="category-{{ $category->id }}" type="radio" wire:model="category_id" value="{{ $category->id }}" class="peer sr-only">
                        <span class="flex min-h-12 items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-3 text-sm font-medium text-slate-700 transition hover:border-teal-300 hover:bg-teal-50/50 peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-900 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-600/15">
                            <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg text-teal-800">{{ $categoryIcon }}</span>{{ $category->name }}
                        </span>
                    </label>
                @endforeach
            </div>
            @endif

            @error('category_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="location_id" class="mb-1 block font-medium">Fakultas, gedung, atau fasilitas</label>
            <select id="location_id" wire:model="location_id"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
                <option value="">Pilih lokasi di kampus UNIMED</option>
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->full_path }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Pilih unit yang paling sesuai. Lantai atau nomor ruang dapat ditulis pada detail lokasi.</p>
            @error('location_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="location_detail" class="mb-1 block font-medium">Detail lokasi <span class="font-normal text-slate-500">(opsional)</span></label>
            <input id="location_detail" type="text" wire:model="location_detail" maxlength="255" placeholder="Contoh: lantai 2, ruang 204, dekat tangga"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            @error('location_detail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="title" class="mb-1 block font-medium">Judul laporan</label>
            <input id="title" type="text" wire:model="title" placeholder="Contoh: Wi-Fi lantai 3 tidak berfungsi"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @if ($possibleDuplicates->isNotEmpty())
                <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="font-medium text-amber-900">Mungkin ada laporan yang sama di lokasi ini</p>
                    <p class="mt-1 text-sm text-amber-800">Buka salah satu laporan. Jika masalahnya sama, gunakan tombol “Saya juga terdampak”.</p>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($possibleDuplicates as $duplicate)
                            <li>
                                <a class="font-medium text-teal-800 underline" href="{{ route('issues.show', $duplicate) }}">#{{ $duplicate->getKey() }} · {{ $duplicate->title }}</a>
                                <span class="text-slate-600">({{ str($duplicate->status)->replace('_', ' ')->title() }})</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div>
            <label for="description" class="mb-1 block font-medium">
                Deskripsi Masalah
            </label>

            <textarea id="description" wire:model="description" rows="4"
                placeholder="Jelaskan masalah yang ditemukan..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10"></textarea>

            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="photo" class="mb-1 block font-medium">
                Foto Bukti (Opsional)
            </label>

            <input id="photo" type="file" wire:model="photo" accept="image/*"
                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none transition focus:border-teal-600 focus:bg-white focus:ring-4 focus:ring-teal-600/10">

            <p class="mt-1 text-sm text-gray-500">
                Maksimal ukuran foto 2 MB.
            </p>

            @error('photo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div wire:loading wire:target="photo" class="mt-2 text-sm text-gray-500">
                Memproses foto...
            </div>

            @if ($photo)
                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto laporan"
                    class="mt-3 max-h-64 rounded-lg object-cover">
            @endif
        </div>

        <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <label class="flex items-start gap-3">
                <input type="checkbox" wire:model="safety_flag" class="mt-1">

                <span>
                    <span class="font-medium">Masalah keselamatan</span>
                    <span class="block text-sm text-gray-600">
                        Masalah ini berpotensi membahayakan pengguna kampus.
                    </span>
                </span>
            </label>

            <label class="flex items-start gap-3">
                <input type="checkbox" wire:model="class_blocked" class="mt-1">

                <span>
                    <span class="font-medium">Kegiatan kelas terganggu</span>
                    <span class="block text-sm text-gray-600">
                        Masalah ini menghambat kegiatan belajar atau mengajar.
                    </span>
                </span>
            </label>
        </div>


        <button type="submit" wire:loading.attr="disabled" wire:target="save"
            class="cf-button cf-button--primary">
            <span wire:loading.remove wire:target="save">
                Kirim Laporan
            </span>
            <span wire:loading wire:target="save">
                Memeriksa...
            </span>
        </button>
    </form>
</div>
