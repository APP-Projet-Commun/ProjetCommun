<?php
// On n'a pas besoin de session_start() ici car c'est le matériel qui appelle, pas un utilisateur.
header("Content-Type: application/json");

// Clé API simple pour s'assurer que seul notre matériel peut envoyer des données.
// NOTE : Dans un vrai projet, cette clé serait plus complexe et stockée ailleurs.
define('API_KEY', 'MaCleTresSecurisee123');

// --- SÉCURITÉ : Vérification de la clé API ---
if (!isset($_GET['api_key']) || $_GET['api_key'] !== API_KEY) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé. Clé API invalide.']);
    exit();
}

// --- Validation des données reçues ---
$sensor_id = filter_input(INPUT_GET, 'sensor_id', FILTER_VALIDATE_INT);
$value = filter_input(INPUT_GET, 'value', FILTER_VALIDATE_FLOAT);

if ($sensor_id === false || $value === false) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Données invalides ou manquantes.']);
    exit();
}

// --- Insertion en Base de Données ---
include 'bdd.php';

try {
    // On insère la nouvelle lecture dans la table sensor_data
    $stmt = $db->prepare("INSERT INTO sensor_data (sensor_id, value) VALUES (?, ?)");
    $stmt->execute([$sensor_id, $value]);

    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Donnée enregistrée avec succès.']);

} catch (PDOException $e) {
    http_response_code(500);
    // On vérifie si l'erreur est une violation de clé étrangère (sensor_id n'existe pas)
    if ($e->getCode() == '23000') {
         echo json_encode(['status' => 'error', 'message' => 'Erreur : Le sensor_id fourni n\'existe pas dans la table `sensors`.']);
    } else {
         echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données lors de l\'enregistrement.']);
    }
}