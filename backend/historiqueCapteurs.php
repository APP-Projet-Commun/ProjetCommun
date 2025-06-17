<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']);
    exit();
}

// Connexion à la BDD distante PostgreSQL
include 'db_connect.php';

// Définition des paramètres de la requête (limit ou date)
$where_clause = "";
$params = [];
$limit_clause = "";

if (isset($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
    $limit = ($limit > 0 && $limit <= 2000) ? $limit : 30;
    $limit_clause = "LIMIT " . $limit; // LIMIT ne peut pas être un paramètre préparé

} else if (isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
    // ... la logique de validation des dates reste la même
    $date_debut_sql = date('Y-m-d 00:00:00', strtotime($_GET['date_debut']));
    $date_fin_sql = date('Y-m-d 23:59:59', strtotime($_GET['date_fin']));
    $where_clause = "WHERE timestamp BETWEEN :date_debut AND :date_fin";
    $params = [':date_debut' => $date_debut_sql, ':date_fin' => $date_fin_sql];
    $limit_clause = "LIMIT 5000"; // Sécurité

} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Paramètres manquants."]);
    exit();
}

try {
    $history = [];

    // --- 1. Requête pour Température/Humidité ---
    $sql_temp_hum = "SELECT value, timestamp FROM temperature_humidite $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_temp_hum = $pdo_pgsql->prepare($sql_temp_hum);
    $stmt_temp_hum->execute($params);
    while ($row = $stmt_temp_hum->fetch()) {
        $values = json_decode($row['value'], true);
        $history[] = ['name' => 'Température', 'type' => 'temperature', 'value' => $values['temperature'], 'reading_time' => $row['timestamp']];
        $history[] = ['name' => 'Humidité', 'type' => 'humidity', 'value' => $values['humidite'], 'reading_time' => $row['timestamp']];
    }

    // --- 2. Requête pour Gaz ---
    $sql_gaz = "SELECT value, timestamp FROM gaz $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_gaz = $pdo_pgsql->prepare($sql_gaz);
    $stmt_gaz->execute($params);
    while ($row = $stmt_gaz->fetch()) {
        $history[] = ['name' => 'Gaz', 'type' => 'gaz', 'value' => $row['value'], 'reading_time' => $row['timestamp']];
    }

    // --- 3. Requête pour Buzzer ---
    $sql_buzzer = "SELECT value, timestamp FROM buzzer $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_buzzer = $pdo_pgsql->prepare($sql_buzzer);
    $stmt_buzzer->execute($params);
    while ($row = $stmt_buzzer->fetch()) {
        $history[] = ['name' => 'Buzzer', 'type' => 'buzzer', 'value' => $row['value'], 'reading_time' => $row['timestamp']];
    }

    // --- Fusion et Tri ---
    // On trie le tableau final par date pour que la chronologie soit correcte
    usort($history, function($a, $b) {
        return strtotime($a['reading_time']) <=> strtotime($b['reading_time']);
    });

    echo json_encode(['status' => 'success', 'history' => $history]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur SQL dans historiqueCapteurs.php : " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Une erreur de base de données est survenue.']);
}