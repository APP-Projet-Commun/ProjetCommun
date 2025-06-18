<?php
session_start();
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    // Si la requête ne vient pas d'un navigateur (ex: Postman), on peut mettre une valeur par défaut
    header("Access-Control-Allow-Origin: *"); 
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Sécurité : Seuls les utilisateurs connectés peuvent interagir avec le matériel
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']);
    exit();
}

// Connexion à la BDD distante PostgreSQL pour les données des capteurs
include 'db_connect.php';

try {
    // --- 1. Récupérer les dernières valeurs de la BDD ---
    $stmt_temp_hum = $pdo_pgsql->query("SELECT value FROM temperature_humidite ORDER BY id DESC LIMIT 1");
    $json_temp_hum = $stmt_temp_hum->fetchColumn();
    $values_th = json_decode($json_temp_hum, true);
    $temperature = $values_th['temperature'] ?? 'N/A';
    $humidite = $values_th['humidite'] ?? 'N/A';
    
    $stmt_gaz = $pdo_pgsql->query("SELECT value FROM gaz ORDER BY id DESC LIMIT 1");
    $gaz = $stmt_gaz->fetchColumn() ?? 'N/A';
    
    $stmt_buzzer = $pdo_pgsql->query("SELECT value FROM buzzer ORDER BY id DESC LIMIT 1");
    $buzzer = $stmt_buzzer->fetchColumn() ?? 'N/A';

    // --- 2. Formater la chaîne de commande pour l'OLED ---
    // Préfixe "DATA:" pour indiquer que ce sont des données réelles
    $command = sprintf(
        "DATA:T:%.1f,H:%.1f,G:%d,B:%d",
        $temperature,
        $humidite,
        $gaz,
        $buzzer
    );

    // --- 3. Exécuter le script Python pour envoyer la commande ---
    $python_executable_path = 'C:/Users/rolli/AppData/Local/Programs/Python/Python313/python.exe';
    $python_script_path = __DIR__ . '/send_to_serial.py';
    
    $executable_command = '"' . $python_executable_path . '" "' . $python_script_path . '" ' . escapeshellarg($command);
    $output = shell_exec($executable_command . " 2>&1");
    
    if (strpos($output, 'Success') !== false) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Écran OLED mis à jour avec les dernières données.',
            'command_sent' => $command,
            'python_output' => htmlspecialchars($output)
        ]);
    } else {
        throw new Exception("Erreur du script Python : " . $output);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données.', 'details' => $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la mise à jour de l\'OLED.', 'details' => $e->getMessage()]);
}