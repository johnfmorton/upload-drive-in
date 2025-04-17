### {{ __('messages.file_label') }} {{ $loop->iteration }}

- **{{ __('messages.file_name') }}**: {{ $file->original_filename ?? 'N/A' }}
- **{{ __('messages.file_size') }}**: {{ format_bytes($file->file_size ?? 0) }}
@if($file->message)
- **{{ __('messages.file_message') }}**: {{ $file->message }}
@endif
