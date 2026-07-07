<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p>Register to access the stock dashboard.</p>

            @if ($errors->any())
                <div class="error-box">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="/register">
                @csrf

                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="phone_number" placeholder="Phone Number" required>
                <div class="password-wrapper">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Password"
                        required
                    >

                    <button type="button" class="toggle-password" data-target="password">
                        <svg class="eye-open" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>

                        <svg class="eye-closed" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M4 4L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10.5 10.5A3 3 0 0 0 13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M6.5 6.8C3.7 8.6 2 12 2 12s3.5 6 10 6c1.8 0 3.3-.4 4.6-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M17.5 15.2C20.3 13.4 22 12 22 12s-3.5-6-10-6c-1.2 0-2.3.2-3.3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>
                <div class="password-wrapper">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Confirm Password"
                        required
                    >

                    <button type="button" class="toggle-password" data-target="password_confirmation">
                        <svg class="eye-open" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                        </svg>

                        <svg class="eye-closed" width="22" height="22" viewBox="0 0 24 24" fill="none">
                            <path d="M4 4L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10.5 10.5A3 3 0 0 0 13.5 13.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M6.5 6.8C3.7 8.6 2 12 2 12s3.5 6 10 6c1.8 0 3.3-.4 4.6-1" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M17.5 15.2C20.3 13.4 22 12 22 12s-3.5-6-10-6c-1.2 0-2.3.2-3.3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                <button type="submit">Register</button>
            </form>

            <p class="auth-link">
                Already have an account? <a href="/login">Login</a>
            </p>
        </div>
    </div>
    <script>
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);

                if (!input) {
                    return;
                }

                if (input.type === 'password') {
                    input.type = 'text';
                    button.classList.add('showing');
                } else {
                    input.type = 'password';
                    button.classList.remove('showing');
                }
            });
        });
    </script>
</body>
</html>