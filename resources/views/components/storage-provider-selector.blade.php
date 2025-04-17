<div>
    <label for="storage_provider" class="block text-sm font-medium text-gray-700">Storage Provider</label>
    <select id="storage_provider" name="storage_provider" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
        @foreach(config('cloud-storage.providers') as $key => $provider)
            <option value="{{ $key }}" @if($key === $selected) selected @endif>
                {{ ucfirst(str_replace('-', ' ', $key)) }}
                @if(config("cloud-storage.features.{$key}.max_file_size"))
                    (Max: {{ formatBytes(config("cloud-storage.features.{$key}.max_file_size")) }})
                @endif
            </option>
        @endforeach
    </select>
    @error('storage_provider')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@php
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
@endphp
