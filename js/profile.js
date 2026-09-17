document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profileForm');

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const fullNameInput = document.getElementById('full_name') || document.getElementById('name') || document.getElementById('reg-name');
            const name = fullNameInput ? fullNameInput.value.trim() : '';

            if (!name) {
                alert('Please enter your name.');
                return;
            }

            try {
                const response = await fetch('api/update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ name: name })
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ ' + result.message);
                } else {
                    alert('❌ Error: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('❌ Server connection error. Please try again.');
            }
        });
    }
});