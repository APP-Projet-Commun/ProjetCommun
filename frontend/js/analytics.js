document.addEventListener('DOMContentLoaded', () => {
    const API_BASE_URL = '../backend';

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
            
            const tempData = history.filter(d => d.type === 'temperature');
            const humidityData = history.filter(d => d.type === 'humidity');
            const gazData = history.filter(d => d.type === 'gaz');
            const buzzerData = history.filter(d => d.type === 'buzzer');

            renderTimeSeries(tempData, 'tempTimeSeriesChart', 'Température (°C)', 'rgba(255, 99, 132, 1)');
            renderDistribution(tempData, 'tempDistributionChart', 'Distribution Température', 'rgba(255, 99, 132, 0.7)');
            renderTimeSeries(humidityData, 'humidityTimeSeriesChart', 'Humidité (%)', 'rgba(54, 162, 235, 1)');
            renderDistribution(humidityData, 'humidityDistributionChart', 'Distribution Humidité', 'rgba(54, 162, 235, 0.7)');
            renderTimeSeries(gazData, 'gazTimeSeriesChart', 'Gaz (ppm)', 'rgba(243, 156, 18, 1)');
            renderDistribution(gazData, 'gazDistributionChart', 'Distribution Gaz', 'rgba(243, 156, 18, 0.7)');
            renderTimeSeries(buzzerData, 'buzzerTimeSeriesChart', 'État Buzzer (0/1)', 'rgba(142, 68, 173, 1)', true);
            renderDistribution(buzzerData, 'buzzerDistributionChart', 'Distribution États Buzzer', 'rgba(142, 68, 173, 0.7)');
            
            renderBoxPlot(tempData, 'tempBoxPlotChart', 'Statistiques Température');
            renderBoxPlot(humidityData, 'humidityBoxPlotChart', 'Statistiques Humidité');
            renderBoxPlot(gazData, 'gazBoxPlotChart', 'Statistiques Gaz');
            renderBoxPlot(buzzerData, 'buzzerBoxPlotChart', 'Statistiques États Buzzer');

        } catch (error) {
            console.error("Erreur lors du chargement des graphiques:", error);
            errorContainer.textContent = `Erreur : ${error.message}`;
            showLoadingMessage(false, true); 
        }
    }
    
    // --- Fonction pour calculer un quartile ---
    const getQuartile = (arr, q) => {
        const sorted = arr.slice().sort((a, b) => a - b);
        const pos = (sorted.length - 1) * q;
        const base = Math.floor(pos);
        const rest = pos - base;
        if (sorted[base + 1] !== undefined) {
            return sorted[base] + rest * (sorted[base + 1] - sorted[base]);
        } else {
            return sorted[base];
        }
    };

    // ===================================================================
    // VERSION FINALE DE renderBoxPlot
    // ===================================================================
    function renderBoxPlot(data, divId, title) {
        const container = document.getElementById(divId);
        container.innerHTML = ''; 

        if (!data || data.length === 0) {
            container.innerHTML = '<p style="text-align:center; padding-top: 50px; color: #888;">Aucune donnée.</p>';
            return;
        }

        const values = data.map(d => parseFloat(d.value)).filter(v => !isNaN(v));

        if (values.length < 1) {
            container.innerHTML = '<p style="text-align:center; padding-top: 50px; color: #888;">Données invalides.</p>';
            return;
        }
        
        // --- DÉBUT DE LA CORRECTION ---
        // 1. On calcule les quartiles pour ignorer les valeurs extrêmes
        const q1 = getQuartile(values, 0.25);
        const q3 = getQuartile(values, 0.75);
        const iqr = q3 - q1; // Interquartile Range

        // 2. On définit les limites "normales" des données. 
        // Tout ce qui est en dehors sera un "outlier".
        const lowerFence = q1 - 1.5 * iqr;
        const upperFence = q3 + 1.5 * iqr;

        // 3. On calcule l'échelle de l'axe Y en se basant sur ces limites, pas sur le min/max absolu.
        const padding = iqr * 0.2; // On prend une marge de 20% de l'IQR
        
        const yaxisConfig = {
            min: lowerFence - padding,
            max: upperFence + padding,
            labels: {
                formatter: function (value) {
                    // Arrondir à 1 décimale si ce n'est pas un entier
                    return value % 1 !== 0 ? value.toFixed(1) : value.toFixed(0);
                }
            }
        };
        
        // 4. Cas spécial pour le buzzer
        if (divId.includes('buzzer')) {
            yaxisConfig.min = -0.2;
            yaxisConfig.max = 1.2;
            yaxisConfig.tickAmount = 2;
        } else if (q1 === q3) { // Si la boîte est plate
            yaxisConfig.min = q1 - (Math.abs(q1 * 0.2) || 1);
            yaxisConfig.max = q3 + (Math.abs(q3 * 0.2) || 1);
        }
        // --- FIN DE LA CORRECTION ---
        
        const seriesData = [{
            type: 'boxPlot',
            data: [{
                x: title,
                y: values
            }]
        }];

        const options = {
            series: seriesData,
            chart: {
                type: 'boxPlot',
                height: 350,
                toolbar: { show: false }
            },
            yaxis: yaxisConfig,
            plotOptions: {
                boxPlot: {
                    colors: {
                        upper: '#008FFB',
                        lower: '#FEB019'
                    }
                }
            }
        };

        const chart = new ApexCharts(container, options);
        chart.render();
        charts[divId] = chart;
    }
    
    // Le reste des fonctions ne change pas...
    function showLoadingMessage(isLoading, isError = false) {
        const chartIds = [
            'tempTimeSeriesChart', 'tempDistributionChart', 'tempBoxPlotChart', 
            'humidityTimeSeriesChart', 'humidityDistributionChart', 'humidityBoxPlotChart',
            'gazTimeSeriesChart', 'gazDistributionChart', 'gazBoxPlotChart',
            'buzzerTimeSeriesChart', 'buzzerDistributionChart', 'buzzerBoxPlotChart'
        ];
        chartIds.forEach(id => {
            const container = document.getElementById(id);
            if (!container || charts[id]) return;

            let message = "Sélectionnez une période et cliquez sur 'Charger les Graphiques'.";
            if(isLoading) { message = "Chargement des données..."; }
            if(isError) { message = "Erreur de chargement des données." }

            if(container.tagName === 'CANVAS') {
                drawEmptyChartMessage(id, message);
            } else {
                container.innerHTML = `<p style="text-align:center; padding-top: 50px; color: #888;">${message}</p>`;
            }
        });
    }

    function clearAllCharts() {
        Object.keys(charts).forEach(key => {
            if (charts[key] && typeof charts[key].destroy === 'function') {
                charts[key].destroy();
            }
            const container = document.getElementById(key);
            if (container) container.innerHTML = '';
            delete charts[key];
        });
    }
    
    function drawEmptyChartMessage(canvasId, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || canvas.tagName !== 'CANVAS') return;
        const ctx = canvas.getContext('2d');
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
    
    function renderTimeSeries(data, canvasId, label, color, stepped = false) {
        if (!data || data.length === 0) {
            drawEmptyChartMessage(canvasId, "Aucune donnée à afficher.");
            return;
        }
        const ctx = document.getElementById(canvasId).getContext('2d');
        const chartData = data.map(d => ({ x: new Date(d.reading_time), y: d.value }));
        charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: { datasets: [{ label: label, data: chartData, borderColor: color, tension: 0.1, pointRadius: 1, stepped: stepped }] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                scales: { 
                    x: { type: 'time', time: { unit: 'hour', tooltipFormat: 'dd/MM/yy HH:mm' }, title: { display: true, text: 'Date' } }, 
                    y: { 
                        title: { display: true, text: 'Valeur' },
                        ticks: (canvasId.includes('buzzer')) ? { min: 0, max: 1, stepSize: 1 } : {}
                    } 
                } 
            }
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
});