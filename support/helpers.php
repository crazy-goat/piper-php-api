<?php

declare(strict_types=1);

use app\service\PiperService;

if (!function_exists('piper')) {
    function piper(): PiperService
    {
        static $instance = null;
        if ($instance === null) {
            $modelsPath = base_path() . '/models';
            $instance = new PiperService($modelsPath);
        }
        return $instance;
    }
}
