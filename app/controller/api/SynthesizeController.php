<?php

declare(strict_types=1);

namespace app\controller\api;

use Webman\Http\Request;
use Webman\Http\Response;

class SynthesizeController
{
    public function index(Request $request): Response
    {
        $data = json_decode($request->rawBody(), true);
        $voice = $data['voice'] ?? '';
        $text = trim($data['text'] ?? '');
        $speed = (float)($data['speed'] ?? 1.0);

        if ($voice === '' || $text === '') {
            return json(['error' => 'voice and text are required'], 400);
        }

        if (strlen($text) > 500) {
            return json(['error' => 'text must be max 500 characters'], 400);
        }

        try {
            $sampleRate = 0;
            $first = true;

            $headers = "HTTP/1.1 200 OK\r\nContent-Type: audio/wav\r\nTransfer-Encoding: chunked\r\nX-Accel-Buffering: no\r\n\r\n";
            $request->connection->send($headers, true);

            foreach (piper()->synthesizeStreaming($voice, $text, $speed) as $chunk) {
                $sampleRate = $chunk['sampleRate'];

                if ($first) {
                    $wavHeader = 'RIFF'
                        . pack('V', 0xFFFFFFFF)
                        . 'WAVE'
                        . 'fmt '
                        . pack('V', 16)
                        . pack('v', 1)
                        . pack('v', 1)
                        . pack('V', $sampleRate)
                        . pack('V', $sampleRate * 2)
                        . pack('v', 2)
                        . pack('v', 16)
                        . 'data'
                        . pack('V', 0xFFFFFFFF);
                    $first = false;
                    $body = $wavHeader . $chunk['pcmData'];
                } else {
                    $body = $chunk['pcmData'];
                }

                $hexLen = dechex(strlen($body));
                $request->connection->send("$hexLen\r\n$body\r\n", true);

                if ($chunk['isLast']) {
                    break;
                }
            }

            $request->connection->send("0\r\n\r\n", true);
            $request->connection->close();
            return new Response(200);
        } catch (\Throwable $e) {
            return json(['error' => $e->getMessage()], 500);
        }
    }
}
