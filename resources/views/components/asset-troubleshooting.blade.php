@props([
    'expanded' => false
])

<div class="bg-yellow-50 border border-yellow-200 rounded-lg overflow-hidden">
    <button type="button" 
            onclick="toggleTroubleshooting()"
            class="w-full px-4 py-3 text-left bg-yellow-100 hover:bg-yellow-150 focus:outline-none focus:bg-yellow-150 transition-colors">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <span class="font-medium text-yellow-800">Troubleshooting Common Issues</span>
            </div>
            <svg id="troubleshooting-chevron" class="h-5 w-5 text-yellow-600 transform transition-transform {{ $expanded ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>
    
    <div id="troubleshooting-content" class="px-4 py-3 {{ $expanded ? '' : 'hidden' }}">
        <div class="space-y-6">
            
            <!-- Node.js Not Installed -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">Node.js Not Installed</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If you get "command not found" errors when running npm commands, Node.js may not be installed.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solution:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Download and install Node.js from <a href="https://nodejs.org/" target="_blank" rel="noopener" class="underline hover:text-yellow-800">nodejs.org</a></li>
                        <li>Use a version manager like <code class="bg-yellow-200 px-1 rounded">nvm</code> for easier management</li>
                        <li>Restart your terminal after installation</li>
                        <li>Verify installation with: <code class="bg-yellow-200 px-1 rounded">node --version</code></li>
                    </ul>
                </div>
            </div>

            <!-- Permission Errors -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">Permission Errors</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If you get permission errors when running npm commands, especially on Linux/macOS.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solutions:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Use <code class="bg-yellow-200 px-1 rounded">sudo npm ci</code> (not recommended for security)</li>
                        <li>Configure npm to use a different directory: <code class="bg-yellow-200 px-1 rounded">npm config set prefix ~/.npm-global</code></li>
                        <li>Use a Node.js version manager like nvm (recommended)</li>
                        <li>Check file ownership: <code class="bg-yellow-200 px-1 rounded">ls -la</code></li>
                    </ul>
                </div>
            </div>

            <!-- Network/Proxy Issues -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">Network or Proxy Issues</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If npm fails to download packages due to network restrictions or corporate firewalls.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solutions:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Configure npm proxy: <code class="bg-yellow-200 px-1 rounded">npm config set proxy http://proxy:port</code></li>
                        <li>Use different registry: <code class="bg-yellow-200 px-1 rounded">npm config set registry https://registry.npmjs.org/</code></li>
                        <li>Clear npm cache: <code class="bg-yellow-200 px-1 rounded">npm cache clean --force</code></li>
                        <li>Try using yarn instead: <code class="bg-yellow-200 px-1 rounded">yarn install && yarn build</code></li>
                    </ul>
                </div>
            </div>

            <!-- Build Failures -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">Build Process Failures</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If the build process fails with errors about missing dependencies or compilation issues.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solutions:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Delete node_modules and reinstall: <code class="bg-yellow-200 px-1 rounded">rm -rf node_modules && npm ci</code></li>
                        <li>Clear npm cache: <code class="bg-yellow-200 px-1 rounded">npm cache clean --force</code></li>
                        <li>Check Node.js version compatibility (requires Node.js 16+)</li>
                        <li>Run build with verbose output: <code class="bg-yellow-200 px-1 rounded">npm run build -- --verbose</code></li>
                    </ul>
                </div>
            </div>

            <!-- Memory Issues -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">Memory or Resource Issues</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If the build process runs out of memory or takes too long on resource-constrained systems.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solutions:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Increase Node.js memory limit: <code class="bg-yellow-200 px-1 rounded">NODE_OPTIONS="--max-old-space-size=4096" npm run build</code></li>
                        <li>Close other applications to free up memory</li>
                        <li>Use a machine with more RAM for the build process</li>
                        <li>Consider building on a different machine and copying the build files</li>
                    </ul>
                </div>
            </div>

            <!-- File Permission Issues -->
            <div class="border-l-4 border-yellow-400 pl-4">
                <h4 class="font-medium text-yellow-800 mb-2">File Permission Issues</h4>
                <p class="text-yellow-700 text-sm mb-3">
                    If the build process cannot write to the public/build directory.
                </p>
                <div class="space-y-2">
                    <p class="text-sm text-yellow-700"><strong>Solutions:</strong></p>
                    <ul class="list-disc list-inside text-sm text-yellow-700 space-y-1 ml-4">
                        <li>Check directory permissions: <code class="bg-yellow-200 px-1 rounded">ls -la public/</code></li>
                        <li>Create build directory manually: <code class="bg-yellow-200 px-1 rounded">mkdir -p public/build</code></li>
                        <li>Fix permissions: <code class="bg-yellow-200 px-1 rounded">chmod 755 public/build</code></li>
                        <li>Ensure web server user can write to the directory</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- Additional Help -->
        <div class="mt-6 p-4 bg-yellow-100 rounded-lg">
            <h4 class="font-medium text-yellow-800 mb-2">Still Having Issues?</h4>
            <div class="text-sm text-yellow-700 space-y-2">
                <p>If you continue to experience problems:</p>
                <ul class="list-disc list-inside ml-4 space-y-1">
                    <li>Check the browser console for detailed error messages</li>
                    <li>Review the terminal output for specific error details</li>
                    <li>Ensure you're running commands from the project root directory</li>
                    <li>Try running commands with elevated privileges if necessary</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTroubleshooting() {
    const content = document.getElementById('troubleshooting-content');
    const chevron = document.getElementById('troubleshooting-chevron');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}
</script>