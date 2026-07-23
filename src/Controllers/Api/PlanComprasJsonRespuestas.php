<?php

namespace App\Controllers\Api;

/**
 * Envelope JSON del módulo Plan de Compras v2:
 * {"ok":true,"data":...} | {"ok":false,"error":{"code","message",...extra}}.
 */
trait PlanComprasJsonRespuestas
{
    private function ok(array $data): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['ok' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function fail(string $code, string $message, int $status, array $extra = []): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(
            ['ok' => false, 'error' => array_merge(['code' => $code, 'message' => $message], $extra)],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
