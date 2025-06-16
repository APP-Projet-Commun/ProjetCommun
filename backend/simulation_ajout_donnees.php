<?php
echo "<h1>Simulation d'ajout de données</h1>";

// --- CONFIGURATION DE LA SIMULATION ---
// !! MODIFIEZ CES IDs pour qu'ils correspondent à VOS capteurs dans la BDD !!
$id_capteur_temperature = 1;
$id_capteur_humidite = 2; 
$nombre_de_points_a_ajouter = 20;
// ------------------------------------

// L'URL de notre nouveau script de réception
$url_reception = "http://localhost/ProjetCommun/backend/reception_donnees.php";
$api_key = "MaCleTresSecurisee123";

for ($i = 0; $i < $nombre_de_points_a_ajouter; $i++) {
    // Génère des valeurs aléatoires réalistes
    $temp_value = round(rand(180, 250) / 10, 1); // Température entre 18.0 et 25.0
    $hum_value = rand(40, 65);                   // Humidité entre 40 et 65

    // Construit l'URL complète avec les paramètres GET
    $url_temp = sprintf("%s?api_key=%s&sensor_id=%d&value=%.1f", $url_reception, $api_key, $id_capteur_temperature, $temp_value);
    $url_hum = sprintf("%s?api_key=%s&sensor_id=%d&value=%d", $url_reception, $api_key, $id_capteur_humidite, $hum_value);

    // Appelle l'URL pour la température (simule l'envoi par le capteur)
    echo "<p>Ajout température ($temp_value °C)... ";
    $response_temp = file_get_contents($url_temp);
    echo "Réponse du serveur : " . htmlspecialchars($response_temp) . "</p>";

    // Appelle l'URL pour l'humidité
    echo "<p>Ajout humidité ($hum_value %)... ";
    $response_hum = file_get_contents($url_hum);
    echo "Réponse du serveur : " . htmlspecialchars($response_hum) . "</p>";
    
    echo "<hr>";
    
    // Petite pause pour que les timestamps ne soient pas tous identiques
    sleep(1); 
}

echo "<h2>Simulation terminée. " . ($nombre_de_points_a_ajouter * 2) . " points ont été ajoutés.</h2>";
echo "<a href='../frontend/analytics.html'>Voir les graphiques mis à jour</a>";