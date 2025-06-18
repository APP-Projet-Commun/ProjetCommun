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
header("Content-Type: application/json");

// Détruit toutes les variables de session
$_SESSION = array();

// Détruit la session.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

echo json_encode(['status' => 'success', 'message' => 'Vous avez été déconnecté.']);