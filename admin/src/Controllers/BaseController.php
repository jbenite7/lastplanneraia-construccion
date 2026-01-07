<?php

namespace Admin\Controllers;

abstract class BaseController
{
    /**
     * Render a view file.
     *
     * @param string $view Name of the view file (without .php)
     * @param array $data Data to be passed to the view
     */
    protected function render($view, $data = [])
    {
        // Extract data to make variables available in the view
        extract($data);

        // Define path to view file
        $viewFile = __DIR__ . "/../../views/pages/$view.php";

        if (file_exists($viewFile)) {
            // Start output buffering
            ob_start();
            include $viewFile;
            $content = ob_get_clean();

            // Include the main layout
            include __DIR__ . "/../../views/layouts/main.php";
        } else {
            die("Error: La vista $view no existe en $viewFile");
        }
    }

    /**
     * Send a JSON response.
     *
     * @param mixed $data
     */
    protected function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
