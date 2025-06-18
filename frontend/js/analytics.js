document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = '../backend';

    // ... (éléments du DOM et configuration des dates, inchangés) ...
    const loadBtn = document.getElementById('load-charts-btn');
    const dateDebutInput = document.getElementById('date-debut');
    const dateFinInput = document.getElementById('date-fin');
    const errorContainer = document.getElementById('analytics-error');
    const charts = {};
    const today = new Date();
    const weekAgo = new Date();
    weekAgo.setDate(today.getDate() - 7);
    dateFinInput.value = today.toISOString().split('T')[0];
    dateDebutInput.value = weekAgo.toISOString().split('T')[0];

    // ... (checkAuth, inchangé) ...
    async function checkAuth() {
        try {
            const authStatus = await apiRequest('connexion.php', {}, { method: 'GET' });
            if (!authStatus.isLoggedIn) { window.location.href = 'login.html'; }
        } catch (error) {
            console.error("Erreur d'authentification :", error);
            window.location.href = 'login.html';
        }
    }
    
    loadBtn.addEventListener('click', loadAndRenderCharts);
    initializeAnalytics();

    function initializeAnalytics() {
        checkAuth();
        showLoadingMessage(false);
    }

    async function loadAndRenderCharts() {
        const dateDebut = dateDebutInput.value;
        const dateFin = dateFinInput.value;
        if (!dateDebut || !dateFin) { errorContainer.textContent = "Veuillez sélectionner une date de début et de fin."; return; }
        errorContainer.textContent = "";
        clearAllCharts();
        showLoadingMessage(true);

        try {
            const params = { date_debut: dateDebut, date_fin: dateFin };
            const response = await apiRequest('historiqueCapteurs.php', params);

            if (response.status !== 'success') { throw new Error(response.message || "La réponse du serveur est invalide."); }

            const history = response.history;
            
            // On filtre les données pour chaque type
            const tempData = history.filter(d => d.type === 'temperature');
            const humidityData = history.filter(d => d.type === 'humidity');
            // --- AJOUTS ---
            const gazData = history.filter(d => d.type === 'gaz');
            const buzzerData = history.filter(d => d.type === 'buzzer');
            // --------------

            // Rendu des graphiques de Température
            renderTimeSeries(tempData, 'tempTimeSeriesChart', 'Température (°C)', 'rgba(255, 99, 132, 1)');
            renderDistribution(tempData, 'tempDistributionChart', 'Distribution Température', 'rgba(255, 99, 132, 0.7)');
            renderBoxPlot(tempData, 'tempBoxPlotChart', 'Statistiques Température');
            
            // Rendu des graphiques d'Humidité
            renderTimeSeries(humidityData, 'humidityTimeSeriesChart', 'Humidité (%)', 'rgba(54, 162, 235, 1)');
            renderDistribution(humidityData, 'humidityDistributionChart', 'Distribution Humidité', 'rgba(54, 162, 235, 0.7)');
            renderBoxPlot(humidityData, 'humidityBoxPlotChart', 'Statistiques Humidité');

            // --- AJOUTS : Rendu des nouveaux graphiques ---
            renderTimeSeries(gazData, 'gazTimeSeriesChart', 'Gaz (ppm)', 'rgba(243, 156, 18, 1)');
            renderDistribution(gazData, 'gazDistributionChart', 'Distribution Gaz', 'rgba(243, 156, 18, 0.7)');
            renderBoxPlot(gazData, 'gazBoxPlotChart', 'Statistiques Gaz');
            
            // Pour le buzzer, un graphique en escalier est plus adapté
            renderTimeSeries(buzzerData, 'buzzerTimeSeriesChart', 'État Buzzer (0/1)', 'rgba(142, 68, 173, 1)', true); // true pour 'stepped'
            renderDistribution(buzzerData, 'buzzerDistributionChart', 'Distribution États Buzzer', 'rgba(142, 68, 173, 0.7)');
            renderBoxPlot(buzzerData, 'buzzerBoxPlotChart', 'Statistiques États Buzzer');
            // --- FIN DES AJOUTS ---

        } catch (error) {
            console.error("Erreur lors du chargement des graphiques:", error);
            errorContainer.textContent = `Erreur : ${error.message}`;
            showLoadingMessage(false, true); 
        }
    }

    // ... (showLoadingMessage, clearAllCharts, drawEmptyChartMessage, apiRequest, inchangés) ...
    // ... (renderTimeSeries, renderDistribution, inchangés) ...

    // --- CHANGEMENT CLÉ : VERSION "BLINDÉE" DE renderBoxPlot ---
    function renderBoxPlot(data, canvasId, label) {
        if (!data || data.length === 0) {
            drawEmptyChartMessage(canvasId, "Aucune donnée à afficher.");
            return;
        }

        const ctx = document.getElementById(canvasId).getContext('2d');
        const values = data.map(d => parseFloat(d.value)).filter(v => !isNaN(v));

        if (values.length === 0) {
            drawEmptyChartMessage(canvasId, "Les données contiennent des valeurs invalides.");
            return;
        }
        
        if (charts[canvasId]) {
            charts[canvasId].destroy();
        }

        try {
            // Le type de graphique est maintenant 'boxplot' (en minuscules)
            charts[canvasId] = new Chart(ctx, {
                type: 'boxplot', 
                data: {
                    labels: [label],
                    datasets: [{
                        label: label,
                        data: values, // Le plugin officiel prend directement le tableau de valeurs
                        backgroundColor: 'rgba(153, 102, 255, 0.5)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        itemRadius: 2, // Pour afficher les points extrêmes (outliers)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }
                }
            });
        } catch (e) {
            console.error(`Erreur lors du rendu du Box Plot pour ${canvasId}:`, e);
            drawEmptyChartMessage(canvasId, "Erreur lors du rendu du graphique.");
        }
    }

    // --- Copiez/collez les autres fonctions ici (non modifiées) ---
    function showLoadingMessage(isLoading, isError = false) {
        // On ajoute les IDs des nouveaux canvas
        const chartIds = [
            'tempTimeSeriesChart', 'tempDistributionChart', 'tempBoxPlotChart', 
            'humidityTimeSeriesChart', 'humidityDistributionChart', 'humidityBoxPlotChart',
            'gazTimeSeriesChart', 'gazDistributionChart', 'gazBoxPlotChart',
            'buzzerTimeSeriesChart', 'buzzerDistributionChart', 'buzzerBoxPlotChart'
        ];
        chartIds.forEach(id => {
            let message = "Sélectionnez une période et cliquez sur 'Charger les Graphiques'.";
            if(isLoading) { message = "Chargement des données..."; }
            if(isError) { message = "Erreur de chargement des données." }
            if (charts[id]) return; 
            drawEmptyChartMessage(id, message);
        });
    }

    function clearAllCharts() {
        Object.keys(charts).forEach(key => {
            if (charts[key]) {
                charts[key].destroy();
                delete charts[key];
            }
        });
    }

    function drawEmptyChartMessage(canvasId, message) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
        ctx.save();
        ctx.font = "16px Arial";
        ctx.fillStyle = "#888";
        ctx.textAlign = "center";
        ctx.fillText(message, ctx.canvas.width / 2, ctx.canvas.height / 2);
        ctx.restore();
    }

    async function apiRequest(endpoint, params = {}, options = {}) {
        options.credentials = 'include';
        const queryParams = new URLSearchParams(params).toString();
        const url = `${API_BASE_URL}/${endpoint}${queryParams ? '?' + queryParams : ''}`;
        try {
            const response = await fetch(url, options);
            if (response.status === 401) { return Promise.reject(new Error("Non autorisé")); }
            const result = await response.json();
            if (!response.ok) { throw new Error(result.message || 'Erreur inconnue du serveur.'); }
            return result;
        } catch (error) {
            console.error(`Erreur API sur ${url}:`, error);
            return Promise.reject(error);
        }
    }

    function renderTimeSeries(data, canvasId, label, color) {
        if (!data || data.length === 0) {
            drawEmptyChartMessage(canvasId, "Aucune donnée à afficher pour cette période.");
            return;
        }
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chartData = data.map(d => ({ x: new Date(d.reading_time), y: d.value }));
        charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: { datasets: [{ label: label, data: chartData, borderColor: color, tension: 0.1, pointRadius: 1 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { type: 'time', time: { unit: 'hour', tooltipFormat: 'dd/MM/yy HH:mm' }, title: { display: true, text: 'Date' } }, y: { title: { display: true, text: 'Valeur' } } } }
        });
    }

    function renderDistribution(data, canvasId, label, color) {
        if (!data || data.length < 2) {
            drawEmptyChartMessage(canvasId, "Données insuffisantes pour une distribution.");
            return;
        }
        const ctx = document.getElementById(canvasId).getContext('2d');
        const values = data.map(d => parseFloat(d.value));
        const min = Math.min(...values);
        const max = Math.max(...values);
        if (min === max) {
            drawEmptyChartMessage(canvasId, "Toutes les valeurs sont identiques.");
            return;
        }
        const binCount = Math.min(Math.ceil(Math.sqrt(values.length)), 20);
        const binSize = (max - min) / binCount;
        const bins = Array(binCount).fill(0);
        const labels = [];
        for (let i = 0; i < binCount; i++) {
            const binStart = min + i * binSize;
            labels.push(`${binStart.toFixed(1)}-${(binStart + binSize).toFixed(1)}`);
        }
        values.forEach(value => {
            let binIndex = Math.floor((value - min) / binSize);
            if (binIndex === binCount) binIndex--;
            bins[binIndex]++;
        });
        charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: { labels: labels, datasets: [{ label: label, data: bins, backgroundColor: color }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { title: { display: true, text: 'Intervalles de valeur' } }, y: { title: { display: true, text: 'Fréquence' }, beginAtZero: true } } }
        });
    }

        // Ajout d'un paramètre 'stepped' à la fin
    function renderTimeSeries(data, canvasId, label, color, stepped = false) {
        if (!data || data.length === 0) {
            drawEmptyChartMessage(canvasId, "Aucune donnée à afficher pour cette période.");
            return;
        }
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chartData = data.map(d => ({ x: new Date(d.reading_time), y: d.value }));
        charts[canvasId] = new Chart(ctx, {
            type: 'line',
            // On ajoute la propriété 'stepped' ici
            data: { datasets: [{ label: label, data: chartData, borderColor: color, tension: 0.1, pointRadius: 1, stepped: stepped }] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { 
                    x: { type: 'time', time: { unit: 'hour', tooltipFormat: 'dd/MM/yy HH:mm' }, title: { display: true, text: 'Date' } }, 
                    y: { 
                        title: { display: true, text: 'Valeur' },
                        // Pour le buzzer, on force les ticks à 0 et 1
                        ticks: (canvasId.includes('buzzer')) ? { min: 0, max: 1, stepSize: 1 } : {}
                    } 
                } 
            }
        });
    }
});