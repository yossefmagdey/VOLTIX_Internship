document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('registerForm') || document.querySelector('form');
    
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // متوافق مع الـ IDs الجديدة والقديمة لتجنب أي أخطاء
            const name = document.getElementById('reg-name')?.value.trim() || document.getElementById('fullName')?.value.trim() || '';
            const email = document.getElementById('reg-email')?.value.trim() || document.getElementById('email')?.value.trim() || '';
            const password = document.getElementById('reg-password')?.value || document.getElementById('password')?.value || '';

            if (!name || !email || !password) {
                alert('Please fill in all required fields.');
                return;
            }

            if (password.length < 6) {
                alert('Password must be at least 6 characters long.');
                return;
            }

            try {
                const response = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({
                        name: name,
                        email: email,
                        password: password,
                        role: 'user'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Account created successfully!');
                    window.location.href = 'admin.html';
                } else {
                    alert('Authentication Failed: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Server connection error. Please try again.');
            }
        });
    }
});