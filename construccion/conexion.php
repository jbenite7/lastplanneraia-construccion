<?php
	require_once __DIR__ . '/vendor/autoload.php';

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

	try {
    	$pdo = new PDO($dsn, $user, $password, $options);
	} catch (\PDOException $e) {
    	throw new \PDOException($e->getMessage(), (int)$e->getCode());
	}
?>
