<?php

return [
    'disk' => env('PRODUCT_IMAGE_DISK', 'b2'),
    'temporary_url_minutes' => (int) env('PRODUCT_IMAGE_TEMPORARY_URL_MINUTES', 120),
    'remote_upload_timeout_seconds' => (int) env('PRODUCT_IMAGE_REMOTE_UPLOAD_TIMEOUT_SECONDS', 15),
    'max_upload_kilobytes' => (int) env('PRODUCT_IMAGE_MAX_UPLOAD_KB', 2048),
];
