<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté pour voir les données.']);
    exit();
}

// Connexion à la BDD distante PostgreSQL
include 'db_connect.php';

try {
    $sensors_data = [];

    // --- 1. Récupérer la dernière mesure de température et humidité ---
    $stmt_temp_hum = $pdo_pgsql->query("SELECT value, timestamp FROM temperature_humidite ORDER BY id DESC LIMIT 1");
    $last_temp_hum = $stmt_temp_hum->fetch();

    if ($last_temp_hum) {
        $values = json_decode($last_temp_hum['value'], true);
        
        // On crée deux "capteurs virtuels" à partir d'une seule ligne
        $sensors_data[] = [
            'name' => 'Température Ambiante',
            'type' => 'temperature',
            'location' => 'Brasserie',
            'value' => $values['temperature'] ?? 'N/A',
            'reading_time' => $last_temp_hum['timestamp']
        ];
        $sensors_data[] = [
            'name' => 'Humidité Ambiante',
            'type' => 'humidity',
            'location' => 'Brasserie',
            'value' => $values['humidite'] ?? 'N/A',
            'reading_time' => $last_temp_hum['timestamp']
        ];
    }

    // --- 2. Récupérer la dernière mesure de gaz ---
    $stmt_gaz = $pdo_pgsql->query("SELECT value, timestamp FROM gaz ORDER BY id DESC LIMIT 1");
    $last_gaz = $stmt_gaz->fetch();
    if ($last_gaz) {
        $sensors_data[] = [
            'name' => 'Niveau de Gaz',
            'type' => 'gaz',
            'location' => 'Salle de contrôle',
            'value' => $last_gaz['value'],
            'reading_time' => $last_gaz['timestamp']
        ];
    }

    // --- 3. Récupérer le dernier état du buzzer ---
    $stmt_buzzer = $pdo_pgsql->query("SELECT value, timestamp FROM buzzer ORDER BY id DESC LIMIT 1");
    $last_buzzer = $stmt_buzzer->fetch();
    if ($last_buzzer) {
        $sensors_data[] = [
            'name' => 'État du Buzzer',
            'type' => 'buzzer',
            'location' => 'Alerte',
            'value' => $last_buzzer['value'],
            'reading_time' => $last_buzzer['timestamp']
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $sensors_data]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur dans affichageCapteurs.php : " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la récupération des données des capteurs.']);
}