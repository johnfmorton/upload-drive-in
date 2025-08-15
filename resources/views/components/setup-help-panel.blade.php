@props([
    'step' => '',
    'title' => 'Need Help?',
    'expanded' => false
])

@php
$helpContent = match($step) {
    'assets' => [
        'title' => 'Frontend Asset Building',
        'description' => 'Build the frontend assets (CSS and JavaScript) required for the application to function properly.',
        'tips' => [
            'Ensure Node.js version 16 or higher is installed on your system',
            'Run commands from the project root directory where package.json is located',
            'Use npm ci instead of npm install for faster, reliable builds',
            'The build process creates optimized files in the public/build directory'
        ],
        'links' => [
            ['title' => 'Node.js Installation', 'url' => 'https://nodejs.org/', 'external' => true],
            ['title' => 'NPM Documentation', 'url' => 'https://docs.npmjs.com/', 'external' => true],
            ['title' => 'Vite Build Guide', 'url' => 'https://vitejs.dev/guide/build.html', 'external' => true]
        ]
    ],
    'welcome' => [
        'title' => 'Getting Started',
        'description' => 'This setup wizard will guide you through configuring Upload Drive-in for first use.',
        'tips' => [
            'Ensure you have administrator access to your server',
            'Have your database credentials ready',
            'Prepare your Google Drive API credentials from Google Cloud Console',
            'Make sure your server meets all system requirements'
        ],
        'links' => [
            ['title' => 'System Requirements', 'url' => '/docs/requirements'],
            ['title' => 'Installation Guide', 'url' => '/docs/installation']
        ]
    ],
    'database' => [
        'title' => 'Database Configuration',
        'description' => 'Configure your database connection. You can use either MySQL or SQLite.',
        'tips' => [
            'SQLite is easier to set up and perfect for small to medium deployments',
            'MySQL offers better performance for larger deployments',
            'Ensure your database user has CREATE, ALTER, and DROP permissions',
            'Test your database connection before proceeding'
        ],
        'links' => [
            ['title' => 'Database Setup Guide', 'url' => '/docs/database-setup'],
            ['title' => 'MySQL Configuration', 'url' => '/docs/mysql-setup'],
            ['title' => 'SQLite Configuration', 'url' => '/docs/sqlite-setup']
        ]
    ],
    'admin' => [
        'title' => 'Administrator Account',
        'description' => 'Create the initial administrator account that will manage the system.',
        'tips' => [
            'Use a strong, unique password for security',
            'Choose an email address you have access to',
            'This account will have full system access',
            'You can create additional admin accounts later'
        ],
        'links' => [
            ['title' => 'User Management Guide', 'url' => '/docs/user-management'],
            ['title' => 'Security Best Practices', 'url' => '/docs/security']
        ]
    ],
    'storage' => [
        'title' => 'Cloud Storage Setup',
        'description' => 'Connect your Google Drive account to store uploaded files in the cloud.',
        'tips' => [
            'You need a Google Cloud Console project with Drive API enabled',
            'Create OAuth 2.0 credentials (Web application type)',
            'Add your domain to authorized origins',
            'Keep your credentials secure and never share them'
        ],
        'links' => [
            ['title' => 'Google Drive Setup Guide', 'url' => '/docs/google-drive-setup'],
            ['title' => 'Google Cloud Console', 'url' => 'https://console.developers.google.com/', 'external' => true],
            ['title' => 'OAuth 2.0 Setup', 'url' => '/docs/oauth-setup']
        ]
    ],
    'complete' => [
        'title' => 'Setup Complete',
        'description' => 'Congratulations! Your Upload Drive-in installation is now ready to use.',
        'tips' => [
            'Connect your Google Drive account in the admin panel',
            'Create employee accounts to help manage uploads',
            'Configure upload settings and file type restrictions',
            'Test the upload process with a sample file'
        ],
        'links' => [
            ['title' => 'Getting Started Guide', 'url' => '/docs/getting-started'],
            ['title' => 'Admin Dashboard Guide', 'url' => '/docs/admin-dashboard'],
            ['title' => 'User Guide', 'url' => '/docs/user-guide']
        ]
    ],
    default => [
        'title' => 'Setup Help',
        'description' => 'Get help with the setup process.',
        'tips' => [
            'Follow each step carefully',
            'Check system requirements if you encounter issues',
            'Review error messages for specific guidance',
            'Contact support if you need additional help'
        ],
        'links' => [
            ['title' => 'Documentation', 'url' => '/docs'],
            ['title' => 'Support', 'url' => '/support']
        ]
    ]
};

$content = $helpContent;
@endphp

<div class="bg-blue-50 border border-blue-200 rounded-lg overflow-hidden">
    <button type="button" 
            onclick="toggleHelpPanel()"
            class="w-full px-4 py-3 text-left bg-blue-100 hover:bg-blue-150 focus:outline-none focus:bg-blue-150 transition-colors">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-blue-800">{{ $content['title'] }}</span>
            </div>
            <svg id="help-chevron" class="h-5 w-5 text-blue-600 transform transition-transform {{ $expanded ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </button>
    
    <div id="help-content" class="px-4 py-3 {{ $expanded ? '' : 'hidden' }}">
        <!-- Description -->
        <p class="text-blue-700 mb-4">{{ $content['description'] }}</p>
        
        <!-- Tips -->
        @if(!empty($content['tips']))
        <div class="mb-4">
            <h4 class="font-medium text-blue-800 mb-2">Tips:</h4>
            <ul class="list-disc list-inside space-y-1 text-sm text-blue-700">
                @foreach($content['tips'] as $tip)
                <li>{{ $tip }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        
        <!-- Documentation links -->
        @if(!empty($content['links']))
        <div>
            <h4 class="font-medium text-blue-800 mb-2">Helpful Resources:</h4>
            <ul class="space-y-1">
                @foreach($content['links'] as $link)
                <li>
                    <a href="{{ $link['url'] }}" 
                       class="text-blue-600 hover:text-blue-800 underline text-sm inline-flex items-center"
                       @if(!empty($link['external'])) target="_blank" rel="noopener noreferrer" @endif>
                        {{ $link['title'] }}
                        @if(!empty($link['external']))
                        <svg class="h-3 w-3 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        @endif
                    </a>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

<script>
function toggleHelpPanel() {
    const content = document.getElementById('help-content');
    const chevron = document.getElementById('help-chevron');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        chevron.classList.add('rotate-180');
    } else {
        content.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}
</script>