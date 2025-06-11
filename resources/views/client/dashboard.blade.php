@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1>CLIENT {{ __('messages.client_dashboard_title') }}</h1>

            @include('components.upload-stats')

            <div class="mt-4">
                @include('client.partials.recent-uploads')
            </div>
        </div>
    </div>
</div>
@endsection
