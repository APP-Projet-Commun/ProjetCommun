<?php
// Fichier de connexion à la base de données

header('Content-Type: application/json');

try {
  $db = new PDO(
    "mysql:host=localhost;dbname=app_commun;charset=utf8",
    "root",
    "", // Mot de passe vide par défaut sur XAMPP, sinon mettez "root"
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
  );
} catch (Exception $e) {
  // En cas d'erreur de connexion, on envoie une réponse JSON et on arrête tout.
  http_response_code(500); // Internal Server Error
  echo json_encode(["status" => "error", "message" => "Erreur de connexion à la base de données."]);
  die();
}