<?php
session_start();
// Accepte dynamiquement l'origine de la requête du navigateur.
// C'est une solution sûre pour le développement qui fonctionne avec les credentials.
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    // Solution de secours si l'origine n'est pas envoyée
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'bdd.php';

// Si on demande les infos de la session existante
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'success', 'isLoggedIn' => true, 'username' => $_SESSION['username']]);
    } else {
        echo json_encode(['status' => 'success', 'isLoggedIn' => false]);
    }
    exit();
}


// Si on tente de se connecter
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!isset($data->username) || !isset($data->password)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Champs manquants.']);
        exit();
    }

    $username = $data->username;
    $password = $data->password;

    $stmt = $pdo_mysql->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Le mot de passe est correct
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['status' => 'success', 'message' => 'Connexion réussie.', 'username' => $user['username']]);
    } else {
        // Identifiants incorrects
        http_response_code(401); // Unauthorized
        echo json_encode(['status' => 'error', 'message' => 'Nom d\'utilisateur ou mot de passe incorrect.']);
    }
}