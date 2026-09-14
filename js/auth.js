document.addEventListener('DOMContentLoaded', () => {

    // 1. Sign Up / Register Form
    const registerForm = document.getElementById('registerForm') || document.querySelector('form');
    
    // ربط نموذج إنشاء الحساب
    const signUpBtn = document.getElementById('signUpBtn') || registerForm?.querySelector('button[type="submit"]');

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // قراءة القيم بناءً على الـ IDs الموجودة في الـ HTML عندك
            const fullNameInput = document.getElementById('fullName');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');

            const name = fullNameInput ? fullNameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const password = passwordInput ? passwordInput.value : '';

            // فحص مبدئي في الـ Frontend
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
                    headers: { 
                        'Content-Type': 'application/json' 
                    },
                    credentials: 'include', // مهم جداً عشان الـ Sessions
                    body: JSON.stringify({
                        name: name,
                        email: email,
                        password: password,
                        role: 'client'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Account created successfully!');
                    window.location.href = result.role === 'admin' ? 'admin.html' : 'index.html';
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