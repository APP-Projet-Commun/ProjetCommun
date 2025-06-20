<?php
// Fichier de connexion à la base de données LOCALE (pour les utilisateurs)

header('Content-Type: application/json');

try {
  // On renomme la variable pour plus de clarté
  $pdo_mysql = new PDO(
    "mysql:host=localhost;dbname=app_commun;charset=utf8",
    "root",
    "root",
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "Erreur de connexion à la base de données des utilisateurs."]);
  die();
}
