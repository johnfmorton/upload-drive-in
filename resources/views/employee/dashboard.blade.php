@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1>EMPLOYEE{{ __('messages.employee_dashboard_title') }}</h1>

            @include('employee.partials.stats-overview')

            <div class="mt-4">
                @include('employee.partials.recent-activities')
            </div>

            <div class="mt-4">
                @include('employee.partials.pending-tasks')
            </div>
        </div>
    </div>
</div>
@endsection
