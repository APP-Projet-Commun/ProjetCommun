const API_BASE_URL = 'http://localhost/ProjetCommun/backend';

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = loginForm.username.value;
            const password = loginForm.password.value;
            const errorMessage = document.getElementById('error-message');

            try {
                const response = await fetch(`${API_BASE_URL}/connexion.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    window.location.href = 'dashboard.html'; // Redirection vers le tableau de bord
                } else {
                    errorMessage.textContent = result.message || 'Une erreur est survenue.';
                }
            } catch (error) {
                errorMessage.textContent = 'Erreur de connexion au serveur.';
            }
        });
    }

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = registerForm.username.value;
            const password = registerForm.password.value;
            const message = document.getElementById('message');
            message.classList.remove('error-text');

            try {
                const response = await fetch(`${API_BASE_URL}/inscription.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                const result = await response.json();
                message.textContent = result.message;

                if (response.ok && result.status === 'success') {
                    message.classList.remove('error-text');
                    message.classList.add('message-text');
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000); // Redirige après 2 secondes
                } else {
                    message.classList.add('error-text');
                }
            } catch (error) {
                message.textContent = 'Erreur de connexion au serveur.';
                message.classList.add('error-text');
            }
        });
    }
});