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
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// ---- SÉCURITÉ : On vérifie si l'utilisateur est connecté ----
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté pour effectuer cette action.']);
    exit();
}
// -----------------------------------------------------------

$data = json_decode(file_get_contents("php://input"));

$temperature = filter_var($data->temperature ?? null, FILTER_VALIDATE_FLOAT);
$humidite = filter_var($data->humidite ?? null, FILTER_VALIDATE_INT);

if ($temperature === false || $humidite === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Données invalides.']);
    exit();
}

$command = sprintf("T:%.1f,H:%d", $temperature, $humidite);

// Chemins pour Windows (à adapter si besoin)
$python_executable_path = 'C:/Users/rolli/AppData/Local/Programs/Python/Python313/python.exe';
$python_script_path = __DIR__ . '/send_to_serial.py'; // __DIR__ est plus robuste

$executable_command = '"' . $python_executable_path . '" "' . $python_script_path . '" ' . escapeshellarg($command);
$output = shell_exec($executable_command . " 2>&1");

if (strpos($output, 'Success') !== false) {
    echo json_encode(['status' => 'success', 'message' => 'Commande envoyée avec succès !', 'output' => htmlspecialchars($output)]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'envoi de la commande.', 'output' => htmlspecialchars($output)]);
}