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

include 'bdd.php';

// Le script intelligent qui gère les deux cas
$sql = "";
$params = [];

// CAS 1 : Pour le tableau de bord (dashboard.html)
if (isset($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
    if ($limit <= 0 || $limit > 2000) {
        $limit = 30; // une valeur par défaut sûre
    }
    
    $sql = "
        SELECT s.name, s.type, sd.value, sd.reading_time
        FROM sensor_data sd
        JOIN sensors s ON sd.sensor_id = s.id
        ORDER BY sd.reading_time DESC
        LIMIT :limit
    ";
    $params = [':limit' => $limit];

// CAS 2 : Pour la page d'analyse (analytics.html)
} else if (isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
    $date_debut_str = $_GET['date_debut'];
    $date_fin_str = $_GET['date_fin'];

    $timestamp_debut = strtotime($date_debut_str);
    $timestamp_fin = strtotime($date_fin_str);

    if ($timestamp_debut === false || $timestamp_fin === false) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Format de date invalide."]);
        exit();
    }
    
    $date_debut_sql = date('Y-m-d 00:00:00', $timestamp_debut);
    $date_fin_sql = date('Y-m-d 23:59:59', $timestamp_fin);

    $sql = "
        SELECT s.name, s.type, sd.value, sd.reading_time
        FROM sensor_data sd
        JOIN sensors s ON sd.sensor_id = s.id
        WHERE sd.reading_time BETWEEN :date_debut AND :date_fin
        ORDER BY sd.reading_time ASC
        LIMIT 5000 
    ";
    $params = [
        ':date_debut' => $date_debut_sql,
        ':date_fin' => $date_fin_sql
    ];

// CAS 3 : Erreur, aucun paramètre valide n'a été fourni
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Paramètres manquants : veuillez fournir 'limit' ou 'date_debut' et 'date_fin'."]);
    exit();
}


// Exécution de la requête SQL
try {
    $stmt = $db->prepare($sql);
    
    // PDO va lier les paramètres correctement, qu'ils soient INT ou STRING
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Important : on inverse le tableau UNIQUEMENT pour le cas 'limit' qui trie en DESC
    if (isset($_GET['limit'])) {
        $history = array_reverse($history);
    }

    echo json_encode(['status' => 'success', 'history' => $history]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur SQL dans historiqueCapteurs.php : " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Une erreur de base de données est survenue.']);
}