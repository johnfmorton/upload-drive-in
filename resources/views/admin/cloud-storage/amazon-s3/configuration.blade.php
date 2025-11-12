{{-- Amazon S3 Configuration Component --}}
@php
    $settingsService = app(\App\Services\CloudStorageSettingsService::class);
    $s3Config = $settingsService->getS3Configuration();
    $s3Status = $settingsService->getS3ConfigurationStatus();
    $s3Connected = $s3Status['is_configured'];
    
    // Common AWS regions
    $awsRegions = [
        'us-east-1' => 'US East (N. Virginia)',
        'us-east-2' => 'US East (Ohio)',
        'us-west-1' => 'US West (N. California)',
        'us-west-2' => 'US West (Oregon)',
        'ca-central-1' => 'Canada (Central)',
        'eu-west-1' => 'EU (Ireland)',
        'eu-west-2' => 'EU (London)',
        'eu-west-3' => 'EU (Paris)',
        'eu-central-1' => 'EU (Frankfurt)',
        'eu-north-1' => 'EU (Stockholm)',
        'ap-south-1' => 'Asia Pacific (Mumbai)',
        'ap-northeast-1' => 'Asia Pacific (Tokyo)',
        'ap-northeast-2' => 'Asia Pacific (Seoul)',
        'ap-northeast-3' => 'Asia Pacific (Osaka)',
        'ap-southeast-1' => 'Asia Pacific (Singapore)',
        'ap-southeast-2' => 'Asia Pacific (Sydney)',
        'sa-east-1' => 'South America (São Paulo)',
    ];
@endphp

<div x-data="s3ConfigurationHandler()" x-cloak>
    {{-- Header Section --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-medium text-gray-900">Amazon S3</h3>
            <p class="mt-1 text-sm text-gray-500">
                Configure AWS credentials for system-wide S3 storage
            </p>
        </div>
        <div class="flex items-center space-x-4">
            @if($s3Connected)
                <span class="px-3 py-1 text-sm text-green-800 bg-green-100 rounded-full">
                    {{ __('messages.connected') }}
                </span>
                <form action="{{ route('admin.cloud-storage.amazon-s3.disconnect') }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            onclick="return confirm('Are you sure you want to disconnect Amazon S3? This will remove all stored credentials.')">
                        {{ __('messages.disconnect') }}
                    </button>
                </form>
            @else
                <span class="px-3 py-1 text-sm text-gray-800 bg-gray-100 rounded-full">
                    {{ __('messages.not_connected') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Configuration Form --}}
    <form action="{{ route('admin.cloud-storage.amazon-s3.update') }}" 
          method="POST" 
          class="mt-6 space-y-4"
          @submit="handleSubmit">
        @csrf
        @method('PUT')

        {{-- AWS Access Key ID --}}
        <div>
            <x-label for="aws_access_key_id" value="AWS Access Key ID" />
            <x-input id="aws_access_key_id" 
                     name="aws_access_key_id" 
                     type="text" 
                     class="mt-1 block w-full"
                     :value="old('aws_access_key_id', $s3Config['access_key_id'] ?? '')"
                     placeholder="AKIAIOSFODNN7EXAMPLE"
                     maxlength="20"
                     pattern="[A-Z0-9]{20}"
                     x-model="formData.access_key_id"
                     @input="validateAccessKeyId"
                     required />
            <p class="mt-1 text-xs text-gray-500">
                Must be exactly 20 uppercase alphanumeric characters
            </p>
            <template x-if="errors.access_key_id">
                <p class="mt-1 text-sm text-red-600" x-text="errors.access_key_id"></p>
            </template>
            <x-input-error for="aws_access_key_id" class="mt-2" />
        </div>

        {{-- AWS Secret Access Key --}}
        <div>
            <x-label for="aws_secret_access_key" value="AWS Secret Access Key" />
            <x-input id="aws_secret_access_key" 
                     name="aws_secret_access_key" 
                     type="password" 
                     class="mt-1 block w-full"
                     placeholder="{{ !empty($s3Config['secret_access_key']) ? '••••••••••••••••••••••••••••••••••••••••' : 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY' }}"
                     minlength="40"
                     maxlength="40"
                     x-model="formData.secret_access_key"
                     @input="validateSecretAccessKey"
                     {{ empty($s3Config['secret_access_key']) ? 'required' : '' }} />
            <p class="mt-1 text-xs text-gray-500">
                Must be exactly 40 characters. Leave blank to keep existing secret key.
            </p>
            <template x-if="errors.secret_access_key">
                <p class="mt-1 text-sm text-red-600" x-text="errors.secret_access_key"></p>
            </template>
            <x-input-error for="aws_secret_access_key" class="mt-2" />
        </div>

        {{-- AWS Region --}}
        <div>
            <x-label for="aws_region" value="AWS Region" />
            <select id="aws_region" 
                    name="aws_region" 
                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                    x-model="formData.region"
                    @change="validateRegion"
                    required>
                <option value="">Select a region</option>
                @foreach($awsRegions as $regionCode => $regionName)
                    <option value="{{ $regionCode }}" 
                            {{ old('aws_region', $s3Config['region'] ?? '') === $regionCode ? 'selected' : '' }}>
                        {{ $regionName }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">
                Select the AWS region where your S3 bucket is located
            </p>
            <template x-if="errors.region">
                <p class="mt-1 text-sm text-red-600" x-text="errors.region"></p>
            </template>
            <x-input-error for="aws_region" class="mt-2" />
        </div>

        {{-- S3 Bucket Name --}}
        <div>
            <x-label for="aws_bucket" value="S3 Bucket Name" />
            <x-input id="aws_bucket" 
                     name="aws_bucket" 
                     type="text" 
                     class="mt-1 block w-full"
                     :value="old('aws_bucket', $s3Config['bucket'] ?? '')"
                     placeholder="my-file-intake-bucket"
                     pattern="[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]"
                     x-model="formData.bucket"
                     @input="validateBucketName"
                     required />
            <p class="mt-1 text-xs text-gray-500">
                Bucket name must be 3-63 characters, lowercase letters, numbers, hyphens, and periods only
            </p>
            <template x-if="errors.bucket">
                <p class="mt-1 text-sm text-red-600" x-text="errors.bucket"></p>
            </template>
            <x-input-error for="aws_bucket" class="mt-2" />
        </div>

        {{-- Custom Endpoint (Optional) --}}
        <div>
            <x-label for="aws_endpoint" value="Custom Endpoint (Optional)" />
            <x-input id="aws_endpoint" 
                     name="aws_endpoint" 
                     type="url" 
                     class="mt-1 block w-full"
                     :value="old('aws_endpoint', $s3Config['endpoint'] ?? '')"
                     placeholder="https://s3.example.com"
                     x-model="formData.endpoint"
                     @input="validateEndpoint" />
            <p class="mt-1 text-xs text-gray-500">
                For S3-compatible services like Cloudflare R2, Backblaze B2, or MinIO. Leave blank for standard AWS S3.
            </p>
            <template x-if="errors.endpoint">
                <p class="mt-1 text-sm text-red-600" x-text="errors.endpoint"></p>
            </template>
            <x-input-error for="aws_endpoint" class="mt-2" />
        </div>

        {{-- Test Connection Button --}}
        <div class="flex items-center space-x-4 pt-4 border-t border-gray-200">
            <button type="button"
                    @click="testConnection"
                    :disabled="isTesting || !isFormValid"
                    class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!isTesting">Test Connection</span>
                <span x-show="isTesting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Testing...
                </span>
            </button>

            {{-- Connection Test Result --}}
            <template x-if="testResult">
                <div class="flex items-center space-x-2">
                    <template x-if="testResult.success">
                        <div class="flex items-center text-green-600">
                            <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium">Connection successful!</span>
                        </div>
                    </template>
                    <template x-if="!testResult.success">
                        <div class="flex items-center text-red-600">
                            <svg class="h-5 w-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm font-medium" x-text="testResult.message"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Save Button --}}
        <div class="flex justify-end pt-4">
            <button type="submit" 
                    x-bind:disabled="isSaving || !isFormValid"
                    x-bind:class="{ 'opacity-50 cursor-not-allowed': isSaving || !isFormValid }"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
                <span x-show="!isSaving">{{ __('messages.save_configuration') }}</span>
                <span x-show="isSaving" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ __('messages.s3_saving_configuration') }}
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function s3ConfigurationHandler() {
    return {
        formData: {
            access_key_id: @json(old('aws_access_key_id', $s3Config['access_key_id'] ?? '')),
            secret_access_key: '',
            region: @json(old('aws_region', $s3Config['region'] ?? '')),
            bucket: @json(old('aws_bucket', $s3Config['bucket'] ?? '')),
            endpoint: @json(old('aws_endpoint', $s3Config['endpoint'] ?? ''))
        },
        errors: {},
        isTesting: false,
        isSaving: false,
        testResult: null,
        isFormValid: false,

        init() {
            this.validateForm();
        },

        validateAccessKeyId() {
            const value = this.formData.access_key_id;
            
            if (!value) {
                this.errors.access_key_id = 'Access Key ID is required';
            } else if (value.length !== 20) {
                this.errors.access_key_id = 'Access Key ID must be exactly 20 characters';
            } else if (!/^[A-Z0-9]{20}$/.test(value)) {
                this.errors.access_key_id = 'Access Key ID must contain only uppercase letters and numbers';
            } else {
                delete this.errors.access_key_id;
            }
            
            this.validateForm();
        },

        validateSecretAccessKey() {
            const value = this.formData.secret_access_key;
            const hasExisting = {{ !empty($s3Config['secret_access_key']) ? 'true' : 'false' }};
            
            if (!value && !hasExisting) {
                this.errors.secret_access_key = 'Secret Access Key is required';
            } else if (value && value.length !== 40) {
                this.errors.secret_access_key = 'Secret Access Key must be exactly 40 characters';
            } else {
                delete this.errors.secret_access_key;
            }
            
            this.validateForm();
        },

        validateRegion() {
            const value = this.formData.region;
            
            if (!value) {
                this.errors.region = 'Region is required';
            } else if (!/^[a-z0-9-]+$/.test(value)) {
                this.errors.region = 'Invalid region format';
            } else {
                delete this.errors.region;
            }
            
            this.validateForm();
        },

        validateBucketName() {
            const value = this.formData.bucket;
            
            if (!value) {
                this.errors.bucket = 'Bucket name is required';
            } else if (value.length < 3 || value.length > 63) {
                this.errors.bucket = 'Bucket name must be between 3 and 63 characters';
            } else if (!/^[a-z0-9][a-z0-9.-]{1,61}[a-z0-9]$/.test(value)) {
                this.errors.bucket = 'Bucket name must start and end with a letter or number, and contain only lowercase letters, numbers, hyphens, and periods';
            } else if (value.includes('..')) {
                this.errors.bucket = 'Bucket name cannot contain consecutive periods';
            } else if (/^\d+\.\d+\.\d+\.\d+$/.test(value)) {
                this.errors.bucket = 'Bucket name cannot be formatted as an IP address';
            } else {
                delete this.errors.bucket;
            }
            
            this.validateForm();
        },

        validateEndpoint() {
            const value = this.formData.endpoint;
            
            if (value && !/^https?:\/\/.+/.test(value)) {
                this.errors.endpoint = 'Endpoint must be a valid URL starting with http:// or https://';
            } else {
                delete this.errors.endpoint;
            }
            
            this.validateForm();
        },

        validateForm() {
            const hasExisting = {{ !empty($s3Config['secret_access_key']) ? 'true' : 'false' }};
            
            this.isFormValid = 
                this.formData.access_key_id &&
                (this.formData.secret_access_key || hasExisting) &&
                this.formData.region &&
                this.formData.bucket &&
                Object.keys(this.errors).length === 0;
        },

        async testConnection() {
            this.isTesting = true;
            this.testResult = null;

            try {
                const response = await fetch('{{ route("admin.cloud-storage.amazon-s3.test-connection") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        aws_access_key_id: this.formData.access_key_id,
                        aws_secret_access_key: this.formData.secret_access_key,
                        aws_region: this.formData.region,
                        aws_bucket: this.formData.bucket,
                        aws_endpoint: this.formData.endpoint
                    })
                });

                const data = await response.json();
                
                this.testResult = {
                    success: data.success || false,
                    message: data.message || 'Connection test failed'
                };

            } catch (error) {
                console.error('Connection test error:', error);
                this.testResult = {
                    success: false,
                    message: 'Failed to test connection. Please try again.'
                };
            } finally {
                this.isTesting = false;
            }
        },

        handleSubmit(event) {
            this.isSaving = true;
            // Form will submit normally
        }
    };
}
</script>
