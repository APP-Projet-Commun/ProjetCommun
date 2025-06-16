<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000"); // Autorise React à communiquer
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Pour les requêtes OPTIONS (pré-vérification par le navigateur)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'bdd.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->username) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Veuillez fournir un nom d\'utilisateur et un mot de passe.']);
    exit();
}

$username = trim($data->username);
$password = $data->password;

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Les champs ne peuvent pas être vides.']);
    exit();
}

// Hachage sécurisé du mot de passe
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $password_hash]);
    echo json_encode(['status' => 'success', 'message' => 'Inscription réussie. Vous pouvez maintenant vous connecter.']);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Code d'erreur pour entrée dupliquée (username existe déjà)
        http_response_code(409); // Conflict
        echo json_encode(['status' => 'error', 'message' => 'Ce nom d\'utilisateur est déjà pris.']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'inscription.']);
    }
}