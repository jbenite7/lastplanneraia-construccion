<?php

namespace App\Core;

class ProgressTracker
{
    private string $token;
    private string $filePath;
    private array $data;

    private const STALE_THRESHOLD = 900;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? bin2hex(random_bytes(16));
        $this->filePath = sys_get_temp_dir() . "/lps-consolidation-{$this->token}.json";
        $this->data = $this->read();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public static function fromToken(string $token): ?self
    {
        $filePath = sys_get_temp_dir() . "/lps-consolidation-{$token}.json";
        if (!file_exists($filePath)) {
            return null;
        }
        $instance = new self($token);
        $instance->data = $instance->read();
        return $instance;
    }

    public function init(int $totalSteps): void
    {
        $this->data = [
            'token' => $this->token,
            'status' => 'running',
            'total_steps' => $totalSteps,
            'processed_steps' => 0,
            'current_step' => '',
            'current_project' => '',
            'current_index' => 0,
            'current_total' => 0,
            'percent' => 0,
            'history' => [],
            'message' => '',
            'updated_at' => time(),
            'started_at' => time(),
            'finished_at' => null,
        ];
        $this->write();
    }

    public function update(string $stepLabel, string $project, int $index, int $total, string $status, ?string $message = null): void
    {
        $this->data['current_step'] = $stepLabel;
        $this->data['current_project'] = $project;
        $this->data['current_index'] = $index;
        $this->data['current_total'] = $total;
        $this->data['processed_steps']++;
        $this->data['percent'] = min(99, round(($this->data['processed_steps'] / max(1, $this->data['total_steps'])) * 100, 1));
        $this->data['updated_at'] = time();

        $historyEntry = [
            'step' => $stepLabel,
            'project' => $project,
            'status' => $status,
            'time' => time(),
        ];
        if ($message !== null) {
            $historyEntry['message'] = $message;
        }
        $this->data['history'][] = $historyEntry;

        if ($status === 'error') {
            if ($this->data['status'] === 'running') {
                $this->data['status'] = 'running_with_errors';
            }
        }

        $this->write();
    }

    public function complete(bool $success, ?string $message = null): void
    {
        $this->data['status'] = $success ? 'completed' : 'completed_with_errors';
        $this->data['percent'] = 100;
        $this->data['message'] = $message ?? '';
        $this->data['finished_at'] = time();
        $this->data['updated_at'] = time();
        $this->write();
    }

    public function read(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }
        $content = @file_get_contents($this->filePath);
        if ($content === false) {
            return [];
        }
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }

    public function toArray(): array
    {
        if (empty($this->data)) {
            return [];
        }
        if ($this->data['status'] === 'running' && (time() - ($this->data['updated_at'] ?? 0)) > self::STALE_THRESHOLD) {
            $this->data['status'] = 'stale';
            $this->write();
        }
        return $this->data;
    }

    public static function cleanup(): void
    {
        $files = glob(sys_get_temp_dir() . '/lps-consolidation-*.json');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > 600) {
                @unlink($file);
            }
        }
    }

    private function write(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->filePath, json_encode($this->data, JSON_PRETTY_PRINT), LOCK_EX);
    }
}
