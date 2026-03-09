<?php

// Requerir el autoloader centralizado del proyecto (Root)
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Core/Database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad();

// Obtener la instancia única de nuestra clase Database (Singleton)
$db = Database::getInstance();
