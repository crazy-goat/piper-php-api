<?php

declare(strict_types=1);

namespace app\service;

use CrazyGoat\PiperTTS\LoadedModel;
use CrazyGoat\PiperTTS\PiperTTS;

final class PiperService
{
    private PiperTTS $piper;

    private const MAX_CACHED_VOICES = 5;

    /** @var array<string, LoadedModel> */
    private array $modelCache = [];

    /** @var array<string, int> */
    private array $cacheAccessOrder = [];

    /** @var array<string, array{key: string, name: string, language: string, languageCode: string, quality: string, path: string}> */
    private array $voiceList = [];

    public function __construct(string $modelsPath)
    {
        $espeakDataPath = '/usr/share/espeak-ng-data';
        if (!is_dir($espeakDataPath)) {
            $espeakDataPath = null;
        }
        $this->piper = new PiperTTS($modelsPath, null, null, $espeakDataPath);
        $this->loadVoiceList($modelsPath);
    }

    private function loadVoiceList(string $modelsPath): void
    {
        $jsonPath = $modelsPath . '/voices.json';
        if (!is_file($jsonPath)) {
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $info) {
            $files = $info['files'] ?? [];
            $modelPath = null;
            foreach ($files as $path => $f) {
                if (str_ends_with($path, '.onnx') && !str_ends_with($path, '.onnx.json')) {
                    $modelPath = $path;
                    break;
                }
            }
            if ($modelPath === null) {
                continue;
            }

            $this->voiceList[$key] = [
                'key' => $key,
                'name' => $info['name'] ?? $key,
                'language' => $info['language']['name_english'] ?? 'Unknown',
                'languageCode' => $info['language']['code'] ?? '',
                'quality' => $info['quality'] ?? 'unknown',
                'path' => preg_replace('/\.onnx$/', '', $modelPath),
            ];
        }
    }

    /**
     * @return array<string, array{code: string, name: string, voices: array<string, array{key: string, name: string, quality: string}>}>
     */
    public function getVoicesByLanguage(): array
    {
        $grouped = [];

        foreach ($this->voiceList as $voice) {
            $langCode = $voice['languageCode'];
            if ($langCode === '') {
                continue;
            }

            $family = explode('_', $langCode)[0];

            if (!isset($grouped[$family])) {
                $grouped[$family] = [
                    'code' => $family,
                    'name' => $voice['language'],
                    'voices' => [],
                ];
            }

            $grouped[$family]['voices'][$voice['key']] = [
                'key' => $voice['key'],
                'name' => $voice['name'],
                'quality' => $voice['quality'],
            ];
        }

        uasort($grouped, fn($a, $b) => $a['name'] <=> $b['name']);

        return $grouped;
    }

    private function loadModelWithCache(string $voiceKey): LoadedModel
    {
        if (isset($this->modelCache[$voiceKey])) {
            $this->cacheAccessOrder[$voiceKey] = time();
            return $this->modelCache[$voiceKey];
        }

        if (count($this->modelCache) >= self::MAX_CACHED_VOICES) {
            $oldestKey = array_key_first($this->cacheAccessOrder);
            if ($oldestKey !== null) {
                unset($this->modelCache[$oldestKey]);
                unset($this->cacheAccessOrder[$oldestKey]);
            }
        }

        $model = $this->piper->loadModel($this->voiceList[$voiceKey]['path'], warmUp: true);
        $this->modelCache[$voiceKey] = $model;
        $this->cacheAccessOrder[$voiceKey] = time();

        return $model;
    }

    public function synthesize(string $voiceKey, string $text, float $speed = 1.0): string
    {
        if (!isset($this->voiceList[$voiceKey])) {
            throw new \RuntimeException("Voice not found: {$voiceKey}");
        }

        $model = $this->loadModelWithCache($voiceKey);
        return $model->speak($text, $speed);
    }

    /**
     * @return \Generator<int, array{pcmData: string, sampleRate: int, isLast: bool}>
     */
    public function synthesizeStreaming(string $voiceKey, string $text, float $speed = 1.0): \Generator
    {
        if (!isset($this->voiceList[$voiceKey])) {
            throw new \RuntimeException("Voice not found: {$voiceKey}");
        }

        $model = $this->loadModelWithCache($voiceKey);

        foreach ($model->speakStreaming($text, $speed) as $chunk) {
            yield [
                'pcmData' => $chunk->pcmData,
                'sampleRate' => $chunk->sampleRate,
                'isLast' => $chunk->isLast,
            ];
        }
    }
}
