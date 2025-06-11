@php
$pendingTasks = \App\Models\FileUpload::where('email', auth()->user()->email)
    ->where('validation_method', 'employee')
    ->where(function($query) {
        $query->whereNull('google_drive_file_id')
            ->orWhere('google_drive_file_id', '');
    })
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <h2 class="text-xl font-semibold mb-4">{{ __('Pending Tasks') }}</h2>

        @if($pendingTasks->isEmpty())
            <p class="text-gray-500 text-center py-4">{{ __('No pending tasks found.') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waiting Since</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingTasks as $task)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $task->original_filename }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($task->file_size / 1024, 2) }} KB
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="max-w-[200px] truncate">
                                        {{ $task->message ?: 'No message' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $task->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
