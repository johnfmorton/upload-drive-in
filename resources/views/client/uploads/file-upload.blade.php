<x-app-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <h1>{{ __('messages.upload_files') }}</h1>

                @include('client.uploads.partials.upload-form')

                <div class="mt-4">
                    @include('client.uploads.partials.upload-history')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
