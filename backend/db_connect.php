<?php
// Fichier de connexion à la base de données DISTANTE (pour les capteurs)

// Paramètres de connexion
$host = 'app.garageisep.com';
$port = '5432';
$db   = 'app_db'; // Le nom sur l'image est app-db, pas app_db
$user = 'app_user'; // Le nom sur l'image est app-user, pas app_user
$pass = 'apppassword';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // On renomme la variable pour plus de clarté
     $pdo_pgsql = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     http_response_code(500);
     echo json_encode(['error' => 'Erreur de connexion à la base de données des capteurs.']);
     exit();
}