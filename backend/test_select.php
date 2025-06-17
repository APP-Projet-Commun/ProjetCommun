<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard des Capteurs</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f7f6;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #eaf2f8;
        }
        .status {
            font-weight: bold;
            padding: 5px;
            border-radius: 4px;
            color: white;
        }
        .status.success { background-color: #2ecc71; }
        .status.error { background-color: #e74c3c; }
        .no-data {
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Dashboard des Données Capteurs</h1>

        <?php
        // 1. Paramètres de connexion (ceux de votre image)
        $host = 'app.garageisep.com';
        $port = '5432';
        $db_name = 'app_db';      // Utilisation de '_' comme sur l'image
        $username = 'app_user';   // Utilisation de '_' comme sur l'image
        $password = 'apppassword';

        try {
            // 2. Connexion à la base de données avec PDO
            $dsn = "pgsql:host=$host;port=$port;dbname=$db_name";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo "<p class='status success'>Connexion à la base de données réussie !</p>";

            // 3. Liste des tables de capteurs que nous voulons afficher
            $sensor_tables = ['temperature_humidite', 'gaz', 'buzzer'];

            // 4. Boucle sur chaque table pour l'afficher
            foreach ($sensor_tables as $table) {
                echo "<h2>Données du capteur : " . htmlspecialchars($table) . "</h2>";

                // Requête pour obtenir les 10 dernières entrées, les plus récentes en premier
                $sql = "SELECT * FROM " . $table . " ORDER BY id DESC LIMIT 10";

                $stmt = $pdo->query($sql);
                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // 5. Affichage des résultats dans un tableau HTML
                if ($results) {
                    echo "<table>";
                    
                    // Entête du tableau (générée dynamiquement)
                    echo "<thead><tr>";
                    foreach (array_keys($results[0]) as $column_name) {
                        echo "<th>" . htmlspecialchars($column_name) . "</th>";
                    }
                    echo "</tr></thead>";
                    
                    // Corps du tableau
                    echo "<tbody>";
                    foreach ($results as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            // On utilise htmlspecialchars pour la sécurité (éviter les failles XSS)
                            echo "<td>" . htmlspecialchars($value ?? 'N/A') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    
                    echo "</table>";
                } else {
                    echo "<p class='no-data'>La table '" . htmlspecialchars($table) . "' est vide ou aucune donnée n'a été trouvée.</p>";
                }
            }

        } catch (PDOException $e) {
            // 6. En cas d'erreur, afficher un message clair
            echo "<p class='status error'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
            die();
        }
        ?>
    </div>

</body>
</html>