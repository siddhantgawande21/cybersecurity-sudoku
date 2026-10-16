
const buttons = document.querySelectorAll('.start-button');
const statusMessage = document.getElementById('status-message');
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

buttons.forEach(button => {
    button.addEventListener('click', async () => {
        buttons.forEach(item => item.disabled = true);
        statusMessage.textContent = 'Starting your game...';

        try {
            const formData = new FormData();
            formData.append('level', button.dataset.level);

            const response = await fetch('api/start_game.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Could not start the game.');
            }

            window.location.href = 'game.php';
        } catch (error) {
            statusMessage.textContent = error.message;
            buttons.forEach(item => item.disabled = false);
        }
    });
});
