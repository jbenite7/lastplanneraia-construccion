<?php
	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/src/Database.php'; // Incluir la nueva clase

	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
	$dotenv->load();

	$server = $_ENV['DB_HOST'];
	$user = $_ENV['DB_USER'];
	$password = $_ENV['DB_PASS'];
	$bd = $_ENV['DB_NAME'];

	$dsn = "mysql:host=$server;dbname=$bd;charset=utf8mb4";
	$options = [
    	PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    	PDO::ATTR_EMULATE_PREPARES   => false,
	];

	// Crear una única instancia de nuestra clase Database
	$db = new Database($dsn, $user, $password, $options);
?>
