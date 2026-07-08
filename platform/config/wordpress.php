<?php

return [
    'url' => env('WORDPRESS_URL'),
    'username' => env('WORDPRESS_USERNAME'),
    'application_password' => env('WORDPRESS_APPLICATION_PASSWORD'),
    'timeout' => (int) env('WORDPRESS_TIMEOUT', 15),
    'connect_timeout' => (int) env('WORDPRESS_CONNECT_TIMEOUT', 5),
    'allow_insecure' => filter_var(env('WORDPRESS_ALLOW_INSECURE', false), FILTER_VALIDATE_BOOL),
];
