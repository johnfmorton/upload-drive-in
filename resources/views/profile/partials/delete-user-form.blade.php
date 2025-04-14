@if(auth()->user()->isAdmin())
    @include('profile.partials.delete-user-form-admin')
@else
    @include('profile.partials.delete-user-form-client')
@endif
