<?php

if (! function_exists('format_bytes')) {
    /**
     * Format bytes to kb, mb, gb, tb
     *
     * @param  int $size The size in bytes
     * @param  int $precision The number of decimal places
     * @return string
     */
    function format_bytes(int $size, int $precision = 2): string
    {
        if ($size <= 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];

        $floorBase = floor($base);
        // Ensure the suffix index doesn't exceed the array bounds
        $suffixIndex = min($floorBase, count($suffixes) - 1);

        return round(pow(1024, $base - $floorBase), $precision) .' ' . $suffixes[$suffixIndex];
    }
}
