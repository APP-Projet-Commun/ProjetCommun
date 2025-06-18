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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// ---- SÉCURITÉ : On vérifie si l'utilisateur est connecté ----
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']);
    exit();
}
// -----------------------------------------------------------

include 'bdd.php';

// ---- OBTENIR LA LISTE DES CAPTEURS (GET) ----
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    try {
        $stmt = $db->query("SELECT id, name, type, location FROM sensors ORDER BY name");
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'sensors' => $sensors]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données.']);
    }
}

// ---- AJOUTER UN NOUVEAU CAPTEUR (POST) ----
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    $name = trim($data->name ?? '');
    $type = trim($data->type ?? '');
    $location = trim($data->location ?? '');

    if (empty($name) || empty($type) || empty($location)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont requis.']);
        exit();
    }

    try {
        $stmt = $db->prepare("INSERT INTO sensors (name, type, location) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $location]);
        // On renvoie le nouvel objet capteur avec son ID
        $newSensorId = $db->lastInsertId();
        echo json_encode([
            'status' => 'success',
            'message' => 'Capteur ajouté avec succès.',
            'sensor' => [
                'id' => $newSensorId,
                'name' => $name,
                'type' => $type,
                'location' => $location
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du capteur.']);
    }
}