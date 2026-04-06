<?php

return [

    'bootstrap' => [
        'users' => [
            'admin' => [
                'name' => env('BACKOFFICE_ADMIN_NAME'),
                'email' => env('BACKOFFICE_ADMIN_EMAIL'),
                'password' => env('BACKOFFICE_ADMIN_PASSWORD'),
            ],

            'manager' => [
                'name' => env('BACKOFFICE_MANAGER_NAME'),
                'email' => env('BACKOFFICE_MANAGER_EMAIL'),
                'password' => env('BACKOFFICE_MANAGER_PASSWORD'),
            ],
        ],
    ],

];
