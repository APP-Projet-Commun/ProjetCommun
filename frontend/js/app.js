const API_BASE_URL = '../backend';
let previewChartInstance;
let oledUpdateInterval = null; // Variable pour stocker notre timer


async function apiRequest(endpoint, options = {}) {
    options.credentials = 'include';
    try {
        const response = await fetch(`${API_BASE_URL}/${endpoint}`, options);
        if (response.status === 401) { window.location.href = 'login.html'; return; }
        if (!response.ok) { throw new Error( (await response.json()).message || 'Erreur réseau'); }
        if (response.status === 204) { return { status: 'success' }; }
        return await response.json();
    } catch (error) {
        console.error(`Erreur API sur ${endpoint}:`, error);
        if(typeof showNotification === 'function') { showNotification(error.message, 'error'); }
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // --- Éléments du DOM ---
    const welcomeUser = document.getElementById('welcome-user');
    const logoutBtn = document.getElementById('logout-btn');
    const sensorDataContainer = document.getElementById('sensor-data-container');
    const actuatorForm = document.getElementById('actuator-form');

    const tempSlider = document.getElementById('temperature');
    const humSlider = document.getElementById('humidite');
    const tempValueSpan = document.getElementById('temp-value');
    const humValueSpan = document.getElementById('hum-value');
    const gazSlider = document.getElementById('gaz');
    const gazValueSpan = document.getElementById('gaz-value');
    const buzzerCheckbox = document.getElementById('buzzer');

    const startOledBtn = document.getElementById('start-oled-update');
    const stopOledBtn = document.getElementById('stop-oled-update');
    const oledStatusDiv = document.getElementById('oled-status');
    // const addSensorForm = document.getElementById('add-sensor-form');
    // const sensorsList = document.getElementById('sensors-list');
    
        // --- Initialisation du Dashboard ---
    async function initializeDashboard() {
        try {
            await checkAuth();
            // On retire la requête vers gestionCapteurs.php
            const [latestSensorData, historyData] = await Promise.all([
                apiRequest('affichageCapteurs.php'),
                apiRequest('historiqueCapteurs.php?limit=30') 
            ]);

            loadSensorData(latestSensorData);
            // On retire l'appel à la fonction obsolète
            // loadSensors(sensorListData); 
            renderPreviewChart(historyData);
        } catch (error) {
            console.error("Impossible d'initialiser le tableau de bord:", error);
        }
    }
    initializeDashboard();

    // --- Fonctions Principales ---
    async function checkAuth() {
        const result = await apiRequest('connexion.php', { method: 'GET' });
        if (result.isLoggedIn) {
            welcomeUser.textContent = `Bienvenue, ${result.username}`;
        }
    }

    logoutBtn.addEventListener('click', async () => {
        await apiRequest('deconnexion.php');
        window.location.href = 'login.html';
    });

    function loadSensorData(result) {
        if (result && result.status === 'success' && result.data.length > 0) {
            sensorDataContainer.innerHTML = ''; // On vide le conteneur
            
            result.data.forEach(sensor => {
                const item = document.createElement('div');
                // On applique une classe CSS en fonction du type de capteur (temperature, humidity, gaz, buzzer)
                item.className = `sensor-item ${sensor.type}`;
                
                // --- Logique améliorée pour les unités et l'affichage des valeurs ---
                let unit = '';
                let displayValue = parseFloat(sensor.value).toFixed(1);

                switch(sensor.type) {
                    case 'temperature':
                        unit = '°C';
                        break;
                    case 'humidity':
                        unit = '%';
                        break;
                    case 'gaz':
                        unit = ' ppm'; // Parts Per Million, une unité commune pour les gaz
                        displayValue = parseInt(sensor.value); // Pas de décimale pour le gaz
                        break;
                    case 'buzzer':
                        unit = ''; // Pas d'unité pour le buzzer
                        displayValue = (sensor.value == 1) ? 'Activé' : 'Désactivé'; // Affichage plus clair
                        break;
                }
                // --- Fin de la logique améliorée ---

                item.innerHTML = `
                    <p class="name"><strong>${sensor.name}</strong></p>
                    <p class="value">${displayValue}${unit}</p>
                    <p class="location">${sensor.location}</p>
                    <p class="time"><small>Lu le: ${new Date(sensor.reading_time).toLocaleString('fr-FR')}</small></p>
                `;
                
                sensorDataContainer.appendChild(item);
            });
        } else {
            sensorDataContainer.innerHTML = '<p>Aucune donnée de capteur disponible.</p>';
        }
    }

    // --- Fonctions des formulaires (inchangées) ---
    tempSlider.addEventListener('input', () => tempValueSpan.textContent = `${parseFloat(tempSlider.value).toFixed(1)}°C`);
    humSlider.addEventListener('input', () => humValueSpan.textContent = `${humSlider.value}%`);
    gazSlider.addEventListener('input', () => gazValueSpan.textContent = `${gazSlider.value} ppm`);

    actuatorForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // On récupère les 4 valeurs du formulaire
        const data = {
            temperature: parseFloat(tempSlider.value),
            humidite: parseInt(humSlider.value),
            gaz: parseInt(gazSlider.value),
            buzzer: buzzerCheckbox.checked ? 1 : 0 // On convertit true/false en 1/0
        };
        
        // Le reste de la fonction ne change pas. apiRequest s'occupe de tout envoyer.
        try {
            const result = await apiRequest('actionneur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            showNotification(result.message, 'success');
        } catch (error) {
            // La gestion d'erreur globale dans apiRequest affichera déjà une notification
            console.error("Erreur lors de l'envoi de la commande manuelle", error);
        }
    });

    
    function showNotification(message, type = 'success') {
        const notif = document.getElementById('notification');
        notif.textContent = message;
        notif.className = `notification ${type}`;
        setTimeout(() => { notif.className = 'notification'; }, 5000);
    }

    // --- GRAPHIQUE D'APERÇU ---
    function renderPreviewChart(response) {
        const ctx = document.getElementById('previewChart').getContext('2d');
        if (!response || response.status !== 'success' || response.history.length === 0) {
             ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
             ctx.font = "16px Arial";
             ctx.fillStyle = "#888";
             ctx.textAlign = "center";
             ctx.fillText("Pas de données suffisantes pour l'aperçu.", ctx.canvas.width / 2, 50);
             return;
        }

        const history = response.history;
        const tempData = history.filter(d => d.type === 'temperature').map(d => d.value);
        const humidityData = history.filter(d => d.type === 'humidity').map(d => d.value);

        if (previewChartInstance) previewChartInstance.destroy();
        previewChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: history.map(d => ''), // Pas de labels en bas pour un look plus épuré
                datasets: [
                    { label: 'Température', data: tempData, borderColor: 'rgba(255, 99, 132, 0.8)', tension: 0.3, pointRadius: 0 },
                    { label: 'Humidité', data: humidityData, borderColor: 'rgba(54, 162, 235, 0.8)', tension: 0.3, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end' } },
                scales: {
                    x: { display: false }, // On cache l'axe X
                    y: { display: true }   // On garde l'axe Y pour l'échelle
                }
            }
        });
    }

    // Fonction qui appelle le backend pour mettre à jour l'écran
    async function updateOledScreen() {
        try {
            console.log("Tentative de mise à jour de l'OLED...");
            const result = await apiRequest('update_oled.php', { method: 'POST' });
            oledStatusDiv.textContent = `Dernière mise à jour : ${new Date().toLocaleTimeString()}. Commande envoyée : ${result.command_sent}`;
            oledStatusDiv.className = 'status-message active';
        } catch (error) {
            console.error("Erreur lors de la mise à jour de l'OLED:", error);
            oledStatusDiv.textContent = `Erreur : ${error.message}`;
            oledStatusDiv.className = 'status-message error';
            stopAutoUpdate(); // Arrêter en cas d'erreur pour éviter de spammer
        }
    }

    // Fonction pour démarrer la mise à jour automatique
    function startAutoUpdate() {
        if (oledUpdateInterval) return; // Déjà en cours, on ne fait rien

        // Mise à jour immédiate au clic, puis toutes les 10 secondes
        updateOledScreen(); 
        oledUpdateInterval = setInterval(updateOledScreen, 10000); // 10000 ms = 10s

        // Mise à jour de l'interface
        startOledBtn.disabled = true;
        stopOledBtn.disabled = false;
        oledStatusDiv.textContent = 'Mise à jour automatique activée...';
        oledStatusDiv.className = 'status-message active';
    }

    // Fonction pour arrêter la mise à jour
    function stopAutoUpdate() {
        clearInterval(oledUpdateInterval);
        oledUpdateInterval = null;

        // Mise à jour de l'interface
        startOledBtn.disabled = false;
        stopOledBtn.disabled = true;
        oledStatusDiv.textContent = 'État : Inactif';
        oledStatusDiv.className = 'status-message';
    }

    // Ajout des écouteurs d'événements sur les boutons
    startOledBtn.addEventListener('click', startAutoUpdate);
    stopOledBtn.addEventListener('click', stopAutoUpdate);
});