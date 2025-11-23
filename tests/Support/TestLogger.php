<?php

use Psr\Log\LoggerInterface;

class TestLogger implements LoggerInterface
{
    private string $name;

    public function __construct(string $name = 'TestLogger')
    {
        $this->name = $name;
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->out('EMERGENCY', $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->out('ALERT', $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->out('CRITICAL', $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->out('ERROR', $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->out('WARNING', $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->out('NOTICE', $message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->out('INFO', $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->out('DEBUG', $message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->out(strtoupper((string) $level), $message, $context);
    }

    private function out(string $level, Stringable|string $message, array $context): void
    {
        $ctx = $this->sanitize($context);
        $line = '['.$this->name.'] '.$level.' '.(string) $message;
        if (! empty($ctx)) {
            $line .= ' '.json_encode($ctx, JSON_UNESCAPED_SLASHES);
        }
        fwrite(STDOUT, $line."\n");
    }

    private function sanitize(array $context): array
    {
        $redactKeys = ['access-token', 'authorization', 'Authorization', 'api_key', 'token'];
        foreach ($context as $k => $v) {
            if (in_array($k, $redactKeys, true)) {
                $context[$k] = '***';
            }
        }
        if (isset($context['trace'])) {
            unset($context['trace']);
        }
        if (isset($context['error']) && is_string($context['error'])) {
            $context['error'] = mb_strlen($context['error']) > 300 ? (mb_substr($context['error'], 0, 300).'…') : $context['error'];
        }

        return $context;
    }
}
