@php
$totalUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->where('validation_method', 'employee')
    ->count();
$successfulUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->where('validation_method', 'employee')
    ->whereNotNull('google_drive_file_id')
    ->where('google_drive_file_id', '!=', '')
    ->count();
$pendingUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->where('validation_method', 'employee')
    ->where(function($query) {
        $query->whereNull('google_drive_file_id')
            ->orWhere('google_drive_file_id', '');
    })
    ->count();
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">{{ __('Upload Statistics') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Total Uploads -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-gray-500 text-sm">Total Uploads</div>
                <div class="text-2xl font-bold">{{ $totalUploads }}</div>
            </div>

            <!-- Successful Uploads -->
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-green-600 text-sm">Successfully Uploaded</div>
                <div class="text-2xl font-bold text-green-700">{{ $successfulUploads }}</div>
            </div>

            <!-- Pending Uploads -->
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-yellow-600 text-sm">Pending Uploads</div>
                <div class="text-2xl font-bold text-yellow-700">{{ $pendingUploads }}</div>
            </div>
        </div>
    </div>
</div>
