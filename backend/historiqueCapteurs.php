<?php
session_start();
// Accepte dynamiquement l'origine de la requête du navigateur.
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
} else {
    header("Access-Control-Allow-Origin: *");
}
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Mettre ce header au début pour s'assurer que même en cas d'erreur
// inattendue, le navigateur sache que c'est du JSON.
// Cela ne résout pas l'erreur PHP, mais peut aider au débogage.
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    // On s'assure de terminer le script après avoir envoyé une erreur
    exit(json_encode(['status' => 'error', 'message' => 'Accès non autorisé.']));
}

// Connexion à la BDD distante PostgreSQL
include 'db_connect.php';

// Définition des paramètres de la requête
$where_clause = "";
$params = [];
$limit_clause = "";

if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
    $limit = ($limit > 0 && $limit <= 5000) ? $limit : 30; // Augmentation de la limite max
    $limit_clause = "LIMIT " . $limit; 
} else if (isset($_GET['date_debut']) && isset($_GET['date_fin'])) {
    // Validation simple des dates
    $date_debut_obj = date_create($_GET['date_debut']);
    $date_fin_obj = date_create($_GET['date_fin']);
    
    if ($date_debut_obj && $date_fin_obj) {
        $date_debut_sql = date_format($date_debut_obj, 'Y-m-d 00:00:00');
        $date_fin_sql = date_format($date_fin_obj, 'Y-m-d 23:59:59');
        $where_clause = "WHERE timestamp BETWEEN :date_debut AND :date_fin";
        $params = [':date_debut' => $date_debut_sql, ':date_fin' => $date_fin_sql];
        $limit_clause = "LIMIT 5000"; // Sécurité pour éviter de surcharger le serveur
    } else {
        http_response_code(400);
        exit(json_encode(['status' => 'error', 'message' => "Format de date invalide."]));
    }
} else {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => "Paramètres manquants (limit ou dates requis)."]));
}

try {
    $history = [];

    // --- 1. Requête pour Température/Humidité ---
    $sql_temp_hum = "SELECT value, timestamp FROM temperature_humidite $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_temp_hum = $pdo_pgsql->prepare($sql_temp_hum);
    $stmt_temp_hum->execute($params);
    while ($row = $stmt_temp_hum->fetch(PDO::FETCH_ASSOC)) {
        // CORRECTION : On décode et on vérifie que le résultat est bien un tableau
        $values = json_decode($row['value'], true);
        if (is_array($values)) {
            // On ajoute seulement si les clés existent
            if (isset($values['temperature'])) {
                $history[] = ['name' => 'Température', 'type' => 'temperature', 'value' => $values['temperature'], 'reading_time' => $row['timestamp']];
            }
            if (isset($values['humidite'])) {
                // CORRECTION de la coquille sur 'reading_time'
                $history[] = ['name' => 'Humidité', 'type' => 'humidity', 'value' => $values['humidite'], 'reading_time' => $row['timestamp']];
            }
        }
    }

    // --- 2. Requête pour Gaz ---
    $sql_gaz = "SELECT value, timestamp FROM gaz $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_gaz = $pdo_pgsql->prepare($sql_gaz);
    $stmt_gaz->execute($params);
    while ($row = $stmt_gaz->fetch(PDO::FETCH_ASSOC)) {
        // CORRECTION : On vérifie que la valeur n'est pas nulle avant de l'ajouter
        if ($row['value'] !== null) {
            $history[] = ['name' => 'Gaz', 'type' => 'gaz', 'value' => $row['value'], 'reading_time' => $row['timestamp']];
        }
    }

    // --- 3. Requête pour Buzzer ---
    $sql_buzzer = "SELECT value, timestamp FROM buzzer $where_clause ORDER BY timestamp DESC $limit_clause";
    $stmt_buzzer = $pdo_pgsql->prepare($sql_buzzer);
    $stmt_buzzer->execute($params);
    while ($row = $stmt_buzzer->fetch(PDO::FETCH_ASSOC)) {
        // CORRECTION : On vérifie que la valeur n'est pas nulle
        if ($row['value'] !== null) {
            $history[] = ['name' => 'Buzzer', 'type' => 'buzzer', 'value' => $row['value'], 'reading_time' => $row['timestamp']];
        }
    }

    // --- Fusion et Tri ---
    // On trie le tableau final par date pour que la chronologie soit correcte
    if (!empty($history)) {
        usort($history, function($a, $b) {
            return strtotime($a['reading_time']) <=> strtotime($b['reading_time']);
        });
    }

    echo json_encode(['status' => 'success', 'history' => $history]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Erreur SQL dans historiqueCapteurs.php : " . $e->getMessage());
    exit(json_encode(['status' => 'error', 'message' => 'Une erreur de base de données est survenue.']));
}