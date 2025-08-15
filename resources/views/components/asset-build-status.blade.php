@props([
    'status' => []
])

@php
$defaultStatus = [
    'vite_manifest_exists' => false,
    'build_directory_exists' => false,
    'node_modules_exists' => false,
    'package_json_exists' => true,
    'npm_available' => true,
];

$checks = array_merge($defaultStatus, $status);
$allPassed = collect($checks)->every(fn($value) => $value === true);
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Asset Build Status
    </h3>
    
    <div class="space-y-3">
        <!-- Package.json Check -->
        <div class="flex items-center justify-between p-3 rounded-lg border 
            {{ $checks['package_json_exists'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    @if($checks['package_json_exists'])
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium {{ $checks['package_json_exists'] ? 'text-green-800' : 'text-red-800' }}">
                        Package Configuration
                    </p>
                    <p class="text-xs {{ $checks['package_json_exists'] ? 'text-green-600' : 'text-red-600' }}">
                        package.json file exists
                    </p>
                </div>
            </div>
            <div class="text-sm font-medium {{ $checks['package_json_exists'] ? 'text-green-800' : 'text-red-800' }}">
                {{ $checks['package_json_exists'] ? 'Found' : 'Missing' }}
            </div>
        </div>

        <!-- NPM Availability Check -->
        <div class="flex items-center justify-between p-3 rounded-lg border 
            {{ $checks['npm_available'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    @if($checks['npm_available'])
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium {{ $checks['npm_available'] ? 'text-green-800' : 'text-red-800' }}">
                        NPM Package Manager
                    </p>
                    <p class="text-xs {{ $checks['npm_available'] ? 'text-green-600' : 'text-red-600' }}">
                        npm command is available
                    </p>
                </div>
            </div>
            <div class="text-sm font-medium {{ $checks['npm_available'] ? 'text-green-800' : 'text-red-800' }}">
                {{ $checks['npm_available'] ? 'Available' : 'Not Found' }}
            </div>
        </div>

        <!-- Node Modules Check -->
        <div class="flex items-center justify-between p-3 rounded-lg border 
            {{ $checks['node_modules_exists'] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    @if($checks['node_modules_exists'])
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium {{ $checks['node_modules_exists'] ? 'text-green-800' : 'text-yellow-800' }}">
                        Dependencies Installed
                    </p>
                    <p class="text-xs {{ $checks['node_modules_exists'] ? 'text-green-600' : 'text-yellow-600' }}">
                        node_modules directory exists
                    </p>
                </div>
            </div>
            <div class="text-sm font-medium {{ $checks['node_modules_exists'] ? 'text-green-800' : 'text-yellow-800' }}">
                {{ $checks['node_modules_exists'] ? 'Installed' : 'Run npm ci' }}
            </div>
        </div>

        <!-- Build Directory Check -->
        <div class="flex items-center justify-between p-3 rounded-lg border 
            {{ $checks['build_directory_exists'] ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    @if($checks['build_directory_exists'])
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium {{ $checks['build_directory_exists'] ? 'text-green-800' : 'text-yellow-800' }}">
                        Build Directory
                    </p>
                    <p class="text-xs {{ $checks['build_directory_exists'] ? 'text-green-600' : 'text-yellow-600' }}">
                        public/build directory exists
                    </p>
                </div>
            </div>
            <div class="text-sm font-medium {{ $checks['build_directory_exists'] ? 'text-green-800' : 'text-yellow-800' }}">
                {{ $checks['build_directory_exists'] ? 'Exists' : 'Run npm run build' }}
            </div>
        </div>

        <!-- Vite Manifest Check -->
        <div class="flex items-center justify-between p-3 rounded-lg border 
            {{ $checks['vite_manifest_exists'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
            <div class="flex items-center">
                <div class="flex-shrink-0 mr-3">
                    @if($checks['vite_manifest_exists'])
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium {{ $checks['vite_manifest_exists'] ? 'text-green-800' : 'text-red-800' }}">
                        Vite Manifest File
                    </p>
                    <p class="text-xs {{ $checks['vite_manifest_exists'] ? 'text-green-600' : 'text-red-600' }}">
                        public/build/manifest.json exists
                    </p>
                </div>
            </div>
            <div class="text-sm font-medium {{ $checks['vite_manifest_exists'] ? 'text-green-800' : 'text-red-800' }}">
                {{ $checks['vite_manifest_exists'] ? 'Ready' : 'Required' }}
            </div>
        </div>
    </div>

    <!-- Overall Status -->
    <div class="mt-6 p-4 rounded-lg {{ $allPassed ? 'bg-green-50 border border-green-200' : 'bg-orange-50 border border-orange-200' }}">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                @if($allPassed)
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                @else
                    <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                @endif
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium {{ $allPassed ? 'text-green-800' : 'text-orange-800' }}">
                    {{ $allPassed ? 'Assets Ready!' : 'Assets Need Building' }}
                </h3>
                <div class="mt-2 text-sm {{ $allPassed ? 'text-green-700' : 'text-orange-700' }}">
                    @if($allPassed)
                        <p>All frontend assets have been built successfully. You can continue with the setup process.</p>
                    @else
                        <p>Please complete the build steps below to generate the required frontend assets.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>