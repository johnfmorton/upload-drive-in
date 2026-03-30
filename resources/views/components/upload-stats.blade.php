@php
$totalUploads = \App\Models\FileUpload::where('email', auth()->user()->email)->count();
$successfulUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->whereNotNull('google_drive_file_id')
    ->where('google_drive_file_id', '!=', '')
    ->count();
$pendingUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->where(function($query) {
        $query->whereNull('google_drive_file_id')
            ->orWhere('google_drive_file_id', '');
    })
    ->count();
@endphp

<div class="border border-cream-200 rounded-2xl bg-white overflow-hidden">
    <div class="p-6 sm:p-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Total Uploads -->
            <div class="relative p-5 rounded-xl bg-cream-50 border border-cream-200 overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-warm-400"></div>
                <div class="text-[10px] tracking-[0.15em] uppercase text-warm-500 font-medium mb-1.5">Total Uploads</div>
                <div class="font-display text-3xl text-warm-900">{{ $totalUploads }}</div>
            </div>

            <!-- Successful Uploads -->
            <div class="relative p-5 rounded-xl bg-cream-50 border border-cream-200 overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-accent-500"></div>
                <div class="text-[10px] tracking-[0.15em] uppercase text-accent-600 font-medium mb-1.5">Uploaded</div>
                <div class="font-display text-3xl text-warm-900">{{ $successfulUploads }}</div>
            </div>

            <!-- Pending Uploads -->
            <div class="relative p-5 rounded-xl bg-cream-50 border border-cream-200 overflow-hidden">
                <div class="absolute top-0 left-0 w-1 h-full bg-warm-300"></div>
                <div class="text-[10px] tracking-[0.15em] uppercase text-warm-500 font-medium mb-1.5">Pending</div>
                <div class="font-display text-3xl text-warm-900">{{ $pendingUploads }}</div>
            </div>
        </div>
    </div>
</div>
