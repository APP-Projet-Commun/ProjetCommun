const API_BASE_URL = 'http://localhost:8888/ProjetCommun-main/backend';
let previewChartInstance;

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
    const addSensorForm = document.getElementById('add-sensor-form');
    const sensorsList = document.getElementById('sensors-list');
    
    // --- Initialisation du Dashboard ---
    async function initializeDashboard() {
        try {
            await checkAuth();
            const [sensorListData, latestSensorData, historyData] = await Promise.all([
                apiRequest('gestionCapteurs.php', { method: 'GET' }),
                apiRequest('affichageCapteurs.php'),
                // IMPORTANT : On ne charge que les 30 derniers points pour l'aperçu
                apiRequest('historiqueCapteurs.php?limit=30') 
            ]);

            loadSensorData(latestSensorData);
            loadSensors(sensorListData);
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
            sensorDataContainer.innerHTML = '';
            result.data.forEach(sensor => {
                const item = document.createElement('div');
                item.className = `sensor-item ${sensor.type}`;
                item.innerHTML = `
                    <p class="name"><strong>${sensor.name}</strong></p>
                    <p class="value">${parseFloat(sensor.value).toFixed(1)} ${sensor.type === 'temperature' ? '°C' : '%'}</p>
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
    actuatorForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = { temperature: parseFloat(tempSlider.value), humidite: parseInt(humSlider.value) };
        const result = await apiRequest('actionneur.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        showNotification(result.message, 'success');
    });

    function loadSensors(result) {
        if (result && result.status === 'success') {
            sensorsList.innerHTML = '';
            result.sensors.forEach(s => {
                const li = document.createElement('li');
                li.textContent = `${s.name} (${s.type}) - ${s.location}`;
                sensorsList.appendChild(li);
            });
        }
    }

    addSensorForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = { name: document.getElementById('sensor-name').value, type: document.getElementById('sensor-type').value, location: document.getElementById('sensor-location').value };
        const result = await apiRequest('gestionCapteurs.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
        if(result.status === 'success') {
            showNotification(result.message, 'success');
            addSensorForm.reset();
            const newSensorListData = await apiRequest('gestionCapteurs.php', { method: 'GET' });
            loadSensors(newSensorListData);
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
});