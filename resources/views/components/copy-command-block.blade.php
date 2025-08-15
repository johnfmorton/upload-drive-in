@props([
    'command' => '',
    'description' => '',
    'id' => null
])

@php
$blockId = $id ?? 'cmd-' . md5($command);
@endphp

<div class="bg-gray-900 rounded-lg p-4 relative">
    <!-- Command -->
    <div class="flex items-center justify-between">
        <div class="flex-1">
            <code id="{{ $blockId }}" class="text-green-400 font-mono text-sm select-all">{{ $command }}</code>
        </div>
        <button type="button" 
                onclick="copyCommand('{{ $blockId }}')"
                class="ml-3 inline-flex items-center px-3 py-1 border border-gray-600 text-xs font-medium rounded text-gray-300 bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-900 focus:ring-blue-500 transition-colors">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            Copy
        </button>
    </div>
    
    @if($description)
        <div class="mt-2 text-xs text-gray-400">
            {{ $description }}
        </div>
    @endif
    
    <!-- Copy success indicator -->
    <div id="{{ $blockId }}-success" class="absolute top-2 right-2 hidden">
        <div class="bg-green-600 text-white text-xs px-2 py-1 rounded flex items-center">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
            Copied!
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
async function copyCommand(elementId) {
    const element = document.getElementById(elementId);
    const command = element.textContent;
    const successIndicator = document.getElementById(elementId + '-success');
    
    try {
        await navigator.clipboard.writeText(command);
        
        // Show success indicator
        successIndicator.classList.remove('hidden');
        
        // Hide after 2 seconds
        setTimeout(() => {
            successIndicator.classList.add('hidden');
        }, 2000);
        
    } catch (err) {
        console.error('Failed to copy command:', err);
        
        // Fallback: select the text
        const range = document.createRange();
        range.selectNodeContents(element);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        
        // Show a different message for fallback
        const fallbackMsg = document.createElement('div');
        fallbackMsg.className = 'absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded';
        fallbackMsg.textContent = 'Selected - Press Ctrl+C';
        element.parentNode.appendChild(fallbackMsg);
        
        setTimeout(() => {
            fallbackMsg.remove();
        }, 3000);
    }
}
</script>
@endpush
@endonce