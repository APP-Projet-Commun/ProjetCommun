<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APP Brassage - L'Art du Brassage de Précision</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Slab:wght@400;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
</head>

<body class="home-page">

    <header class="home-header">
        <div class="logo">
            <span class="logo-text">APP Brassage</span>
        </div>
        <nav>
            <a href="frontend/login.html" class="btn btn-secondary">Connexion</a>
            <a href="frontend/register.html" class="btn">S'inscrire</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Le Brassage est un Art. La Précision est la Clé.</h1>
                <p class="subtitle">Maîtrisez chaque étape de votre production, de l'empâtage à la fermentation. Notre plateforme vous donne les outils pour créer une bière d'exception, à chaque brassin.</p>
                <a href="frontend/login.html" class="btn btn-large">Accéder à mon tableau de bord</a>
            </div>
        </section>

        <section class="features">
            <div class="container">
                <h2>Une Plateforme Conçue pour les Brasseurs Exigeants</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Suivi en Temps Réel</h3>
                        <p>Gardez un œil constant sur la température et l'humidité, deux facteurs cruciaux pour une fermentation parfaite et des saveurs maîtrisées.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Contrôle à Distance</h3>
                        <p>Ajustez les consignes de votre installation directement depuis votre tableau de bord, que vous soyez au bureau ou en déplacement.</p>
                    </div>
                    <div class="feature-card">
                        <h3>Historique des Données</h3>
                        <p>Analysez les données de vos brassins précédents pour comprendre, affiner vos recettes et garantir une qualité constante.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="alternating-section">
            <div class="container-flex">
                <div class="image-container">
                    <img src="./frontend/img/biere1.jpg" alt="Tonneau de brassage">
                </div>
                <div class="text-container">
                    <h2>Un Contrôle Absolu, Goutte par Goutte</h2>
                    <p>La qualité d'une bière se joue sur des détails. Une variation de quelques degrés peut transformer un chef-d'œuvre en une production médiocre. Notre système vous permet de définir des consignes précises pour la température et l'humidité, et de les maintenir avec une fiabilité à toute épreuve.</p>
                    <p>Recevez des alertes en cas d'écart et intervenez avant que la qualité de votre brassin ne soit compromise. C'est l'assurance de la tranquillité d'esprit et la garantie d'un résultat optimal.</p>
                </div>
            </div>
        </section>

        <section class="alternating-section alt-bg">
            <div class="container-flex reversed">
                <div class="image-container">
                    <img src="https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1" alt="Personnes analysant des graphiques de données">
                </div>
                <div class="text-container">
                    <h2>De la Donnée naît la Meilleure Bière</h2>
                    <p>Pourquoi ce brassin était-il exceptionnel ? Quelle a été l'influence de cette légère baisse de température pendant la garde ? Avec l'historique détaillé de chaque cycle, vous ne naviguez plus à vue.</p>
                    <p>Comparez vos lots, identifiez les schémas de réussite et reproduisez vos meilleures bières avec une précision scientifique. Transformez votre intuition de brasseur en une science exacte pour continuellement perfectionner votre art.</p>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Prêt à Révolutionner votre Brassage ?</h2>
                <p>Créez votre compte gratuitement et découvrez comment la technologie peut servir votre passion.</p>
                <a href="frontend/register.html" class="btn btn-large">Commencer l'aventure</a>
            </div>
        </section>
    </main>

    <footer class="home-footer">
        <p>© <?php echo date("Y"); ?> APP Brassage. Tous droits réservés.</p>
    </footer>

</body>

</html>