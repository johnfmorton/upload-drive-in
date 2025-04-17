<?php

return array (
  'default' => 'google-drive',
  'providers' => 
  array (
    'google-drive' => 
    array (
      'driver' => 'google-drive',
    ),
    'microsoft-teams' => 
    array (
      'driver' => 'microsoft-teams',
      'client_id' => NULL,
      'client_secret' => NULL,
      'redirect_uri' => NULL,
      'root_folder_id' => NULL,
    ),
    'dropbox' => 
    array (
      'driver' => 'dropbox',
      'app_key' => NULL,
      'app_secret' => NULL,
      'redirect_uri' => NULL,
      'root_folder' => '/UploadDriveIn',
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
  'services' => 
  array (
    'google_drive' => 
    array (
      'client_id' => '110433282840-osl7r8dd1da2nqr7j633qlfpvrpdr4rg.apps.googleusercontent.com',
      'client_secret' => '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',
      'redirect_uri' => 'https://upload-drive-in.ddev.site/google-drive/callback',
      'root_folder_id' => '1Ci3lDEEaf5EmTfh3t9SvSLrL1KsNz6xr',
    ),
  ),
);
