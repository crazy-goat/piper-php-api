<?php

declare(strict_types=1);

namespace app\controller\api;

use Webman\Http\Request;
use Webman\Http\Response;

class HealthController
{
    public function index(Request $request): Response
    {
        return json(['status' => 'ok']);
    }
}
