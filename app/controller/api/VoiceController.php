<?php

declare(strict_types=1);

namespace app\controller\api;

use Webman\Http\Request;
use Webman\Http\Response;

class VoiceController
{
    public function index(Request $request): Response
    {
        $voices = piper()->getVoicesByLanguage();
        return json($voices);
    }
}
