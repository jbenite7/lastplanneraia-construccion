<?php

namespace App\Controllers\Core;

class DashboardController
{
    public function index()
    {
        // 1. Verificar Sesión
        if (!isset($_SESSION['usuario'])) {
            header('Location: /login');
            exit;
        }

        // 2. Obtener Permiso
        $permiso = $_SESSION['permiso'] ?? '';

        // 3. Redirección Inteligente
        // Mapeo de permisos a rutas
        // V, A, P, R, OT, DCV -> Programa General
        // G, S, SG -> Programación Semanal (CIC)
        // C -> Programación Semanal

        switch ($permiso) {
            case 'V':
            case 'A':
            case 'P':
            case 'R':
            case 'OT':
            case 'DCV':
                // Ruta Migrada
                header("Location: /programa-general");
                break;

            case 'G':
            case 'S':
            case 'SG':
                // Ruta Migrada (CIC)
                header("Location: /programacion-semanal/cic");
                break;

            case 'C':
                // Ruta Migrada
                header("Location: /programacion-semanal");
                break;

            default:
                // Fallback por defecto
                header("Location: /programa-general");
                break;
        }
        exit;
    }
}
