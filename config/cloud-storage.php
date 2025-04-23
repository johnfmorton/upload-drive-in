<?php

return array (
  'default' => env('CLOUD_STORAGE_DEFAULT', 'google-drive'),
  'providers' =>
  array (
    'google-drive' =>
    array (
      'driver' => 'google-drive',
      'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
      'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
      'redirect_uri' => config('app.url') . '/admin/cloud-storage/google-drive/callback',
      'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),
    ),
    'microsoft-teams' =>
    array (
      'driver' => 'microsoft-teams',
      'client_id' => env('MICROSOFT_TEAMS_CLIENT_ID'),
      'client_secret' => env('MICROSOFT_TEAMS_CLIENT_SECRET'),
      'redirect_uri' => env('MICROSOFT_TEAMS_REDIRECT_URI'),
      'root_folder_id' => env('MICROSOFT_TEAMS_ROOT_FOLDER_ID'),
    ),
    'dropbox' =>
    array (
      'driver' => 'dropbox',
      'app_key' => env('DROPBOX_CLIENT_ID'),
      'app_secret' => env('DROPBOX_CLIENT_SECRET'),
      'redirect_uri' => env('DROPBOX_REDIRECT_URI'),
      'root_folder' => env('DROPBOX_ROOT_FOLDER'),
    ),
  ),
  'features' =>
  array (
    'google-drive' =>
    array (
      'folder_creation' => true,
      'file_upload' => true,
      'file_delete' => true,
      'folder_delete' => true,
      'max_file_size' => 5368709120,
    ),
    'microsoft-teams' =>
    array (
      'folder_creation' => true,
      'file_upload' => true,
      'file_delete' => true,
      'folder_delete' => true,
      'max_file_size' => 15728640,
    ),
    'dropbox' =>
    array (
      'folder_creation' => true,
      'file_upload' => true,
      'file_delete' => true,
      'folder_delete' => true,
      'max_file_size' => 2147483648,
    ),
  ),
);
