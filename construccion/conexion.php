<?php
	require_once __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/src/Database.php'; // Incluir la nueva clase

	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
	$dotenv->load();

	// Obtener la instancia única de nuestra clase Database (Singleton)
	$db = Database::getInstance();
?>
