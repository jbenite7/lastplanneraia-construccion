<?php

namespace App\Controllers\Api;

class PdcAutoGenerateController
{
    public function applyFromActividades(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(409);
        echo json_encode([
            'respuesta' => 'ERROR',
            'mensaje' => 'Usa el asistente guiado de Plan de Compras para revisar y aplicar cambios seleccionados.',
        ], JSON_UNESCAPED_UNICODE);
    }
}
