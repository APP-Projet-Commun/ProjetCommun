<?php
session_start();
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    // Si la requête ne vient pas d'un navigateur (ex: Postman), on peut mettre une valeur par défaut
    header("Access-Control-Allow-Origin: *"); 
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { exit(0); }
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté.']);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$temperature = filter_var($data->temperature ?? null, FILTER_VALIDATE_FLOAT);
$humidite = filter_var($data->humidite ?? null, FILTER_VALIDATE_INT);
$gaz = filter_var($data->gaz ?? null, FILTER_VALIDATE_INT);
$buzzer = filter_var($data->buzzer ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);

if ($temperature === false || $humidite === false) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Données invalides.']);
    exit();
}

// Construction de la commande manuelle dynamique
$command_parts = [];
$command_parts[] = "T:" . sprintf("%.1f", $temperature);
$command_parts[] = "H:" . $humidite;

if ($gaz !== false && $gaz !== null) {
    $command_parts[] = "G:" . $gaz;
}
if ($buzzer !== false && $buzzer !== null) {
    $command_parts[] = "B:" . $buzzer;
}

// On assemble les parties avec des virgules et on ajoute le préfixe
$command = "MANUAL:" . implode(",", $command_parts);


// Le reste du script pour appeler Python ne change pas
$python_executable_path = 'C:/Users/rolli/AppData/Local/Programs/Python/Python313/python.exe';
$python_script_path = __DIR__ . '/send_to_serial.py';
$executable_command = '"' . $python_executable_path . '" "' . $python_script_path . '" ' . escapeshellarg($command);
$output = shell_exec($executable_command . " 2>&1");

if (strpos($output, 'Success') !== false) {
    echo json_encode(['status' => 'success', 'message' => 'Consigne manuelle envoyée avec succès !', 'output' => htmlspecialchars($output)]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'envoi de la consigne manuelle.', 'output' => htmlspecialchars($output)]);
}