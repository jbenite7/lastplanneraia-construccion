<?php

namespace App\Controllers\Core;

use App\Controllers\BaseController;

class ContextController extends BaseController
{
    public function setWeek()
    {
        $this->requireAuth();

        // Get JSON Input or POST params
        $input = json_decode(file_get_contents('php://input'), true);
        $semana = $input['semana'] ?? $_POST['semana'] ?? null;

        if ($semana && is_numeric($semana)) {
            $_SESSION['semana'] = (int)$semana;

            echo json_encode(['success' => true, 'message' => 'Semana actualizada a ' . $semana]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Semana inválida']);
        }
        exit;
    }

    public function clearWeek()
    {
        $this->requireAuth();
        $_SESSION['semana'] = 0;
        echo json_encode(['success' => true, 'message' => 'Semana reiniciada']);
        exit;
    }
}
