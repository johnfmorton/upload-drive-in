@php
$recentUploads = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();
@endphp

<div class="border border-cream-200 rounded-2xl bg-white overflow-hidden">
    <div class="p-6 sm:p-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="font-display text-xl text-warm-900">{{ __('Recent Uploads') }}</h2>
            <a href="{{ route('client.upload-files') }}" class="inline-flex items-center px-5 py-2.5 bg-warm-900 border border-transparent rounded-xl font-medium text-xs text-cream-50 uppercase tracking-widest hover:bg-warm-800 transition-all duration-200">
                {{ __('Upload New Files') }}
            </a>
        </div>

        @if($recentUploads->isEmpty())
            <p class="text-gray-500 text-center py-4">{{ __('You haven\'t uploaded any files yet.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-cream-200">
                    <thead>
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-medium text-warm-400 uppercase tracking-[0.15em]">File Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-medium text-warm-400 uppercase tracking-[0.15em]">Size</th>
                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-medium text-warm-400 uppercase tracking-[0.15em]">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-[10px] font-medium text-warm-400 uppercase tracking-[0.15em]">Uploaded At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cream-200">
                        @foreach($recentUploads as $upload)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $upload->original_filename }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($upload->file_size / 1024, 2) }} KB
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($upload->google_drive_file_id)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Uploaded to Drive
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $upload->created_at->format('M j, Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
