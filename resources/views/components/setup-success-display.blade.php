@props([
    'message' => '',
    'title' => 'Success',
    'details' => [],
    'nextSteps' => [],
    'showProgress' => false,
    'progress' => 0
])

@if($message)
<div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-lg font-medium text-green-800 mb-2">{{ $title }}</h3>
            
            <!-- Success message -->
            <div class="text-green-700 mb-4">
                {{ $message }}
            </div>

            <!-- Progress indicator -->
            @if($showProgress && $progress > 0)
            <div class="mb-4">
                <div class="flex items-center justify-between text-sm text-green-700 mb-1">
                    <span>Setup Progress</span>
                    <span>{{ $progress }}%</span>
                </div>
                <div class="w-full bg-green-200 rounded-full h-2">
                    <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                </div>
            </div>
            @endif

            <!-- Success details -->
            @if(!empty($details))
            <div class="mb-4">
                <h4 class="text-md font-medium text-green-800 mb-2">What was completed:</h4>
                <ul class="list-disc list-inside space-y-1 text-sm text-green-700">
                    @foreach($details as $detail)
                    <li>{{ $detail }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Next steps -->
            @if(!empty($nextSteps))
            <div class="mb-4">
                <h4 class="text-md font-medium text-green-800 mb-2">Next steps:</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-green-700">
                    @foreach($nextSteps as $step)
                    <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </div>
            @endif
        </div>
    </div>
</div>
@endif