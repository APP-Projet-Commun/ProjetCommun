<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// ---- SÉCURITÉ : On vérifie si l'utilisateur est connecté ----
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Vous devez être connecté pour voir les données.']);
    exit();
}
// -----------------------------------------------------------

include 'bdd.php';

try {
    // Cette requête récupère la dernière valeur pour chaque capteur
    $stmt = $db->query("
        SELECT s.name, s.type, s.location, sd.value, sd.reading_time
        FROM sensor_data sd
        INNER JOIN (
            SELECT sensor_id, MAX(reading_time) AS max_reading_time
            FROM sensor_data
            GROUP BY sensor_id
        ) latest_sd ON sd.sensor_id = latest_sd.sensor_id AND sd.reading_time = latest_sd.max_reading_time
        INNER JOIN sensors s ON sd.sensor_id = s.id
        ORDER BY s.location, s.name;
    ");

    $sensors_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $sensors_data]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la récupération des données des capteurs.']);
}