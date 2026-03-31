<?php

declare(strict_types=1);

use Webman\Route;

Route::get('/', [app\controller\IndexController::class, 'index']);
Route::get('/api/voices', [app\controller\api\VoiceController::class, 'index']);
Route::post('/api/synthesize', [app\controller\api\SynthesizeController::class, 'index']);
