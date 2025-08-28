<?php

return [

    /*
    |--------------------------------------------------------------------------
    | File Manager Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the pagination behavior for the file manager
    | interface. The items_per_page setting can be overridden using the
    | FILE_MANAGER_ITEMS_PER_PAGE environment variable.
    |
    */

    'pagination' => [
        /*
        |--------------------------------------------------------------------------
        | Items Per Page
        |--------------------------------------------------------------------------
        |
        | The default number of files to display per page in the file manager.
        | This value can be overridden by setting the FILE_MANAGER_ITEMS_PER_PAGE
        | environment variable. If an invalid value is provided, the system will
        | fall back to this default value.
        |
        | Default: 10
        |
        */
        'items_per_page' => \App\Helpers\PaginationConfigHelper::validatePaginationValue(
            env('FILE_MANAGER_ITEMS_PER_PAGE'),
            10, // default
            1,  // min
            100 // max
        ),

        /*
        |--------------------------------------------------------------------------
        | Maximum Items Per Page
        |--------------------------------------------------------------------------
        |
        | The maximum number of items that can be displayed per page to prevent
        | performance issues. Values exceeding this limit will be capped at this
        | maximum value.
        |
        | Default: 100
        |
        */
        'max_items_per_page' => 100,

        /*
        |--------------------------------------------------------------------------
        | Minimum Items Per Page
        |--------------------------------------------------------------------------
        |
        | The minimum number of items that can be displayed per page. Values
        | below this limit will be set to this minimum value.
        |
        | Default: 1
        |
        */
        'min_items_per_page' => 1,
    ],

];