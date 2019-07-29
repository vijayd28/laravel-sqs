<?php

return [
    'allowed_aws_headers' => [
        'X-Aws-Sqsd-Queue', 'X-Aws-Sqsd-Msgid', 'X-Aws-Sqsd-Receive-Count'
    ],
    'worker_routes' => env('REGISTER_WORKER_ROUTES', true)
];
