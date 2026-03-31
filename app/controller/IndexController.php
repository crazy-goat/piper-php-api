<?php

declare(strict_types=1);

namespace app\controller;

use Webman\Http\Request;
use Webman\Http\Response;

class IndexController
{
    public function index(Request $request): Response
    {
        return raw_view('index');
    }
}
