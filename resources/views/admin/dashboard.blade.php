@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Admin Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <h5>Google Drive Connection</h5>
                        @if (Storage::exists('google-credentials.json'))
                            <p class="text-success">Google Drive is connected.</p>
                            <form action="{{ route('google-drive.disconnect') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger">Disconnect Google Drive</button>
                            </form>
                        @else
                            <p class="text-danger">Google Drive is not connected.</p>
                            <a href="{{ route('google-drive.connect') }}" class="btn btn-primary">Connect Google Drive</a>
                        @endif
                    </div>

                    <div class="mb-4">
                        <h5>Uploaded Files</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Filename</th>
                                    <th>Size</th>
                                    <th>Uploaded At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    <tr>
                                        <td>{{ $file->email }}</td>
                                        <td>{{ $file->original_filename }}</td>
                                        <td>{{ number_format($file->file_size / 1024, 2) }} KB</td>
                                        <td>{{ $file->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if ($file->google_drive_file_id)
                                                <a href="https://drive.google.com/file/d/{{ $file->google_drive_file_id }}/view" target="_blank" class="btn btn-sm btn-info">View in Drive</a>
                                            @endif
                                            <form action="{{ route('admin.files.destroy', $file) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this file?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $files->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
