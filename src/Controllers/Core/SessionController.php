<?php

namespace App\Controllers\Core;

use App\Core\SessionMiddleware;

class SessionController
{
    public function touch()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'timeoutSeconds' => SessionMiddleware::idleTimeoutSeconds(),
        ]);
        exit;
    }
}
