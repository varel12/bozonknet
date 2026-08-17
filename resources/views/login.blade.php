<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — BozonkNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <main class="login-page">
        <div class="ambient ambient-one"></div>
        <div class="ambient ambient-two"></div>
        <div class="ambient ambient-three"></div>

        <section class="login-stage" aria-label="Halaman login BozonkNet">
            <div class="shape shape-one"></div>
            <div class="shape shape-two"></div>
            <div class="shape shape-three"></div>
            <div class="shape shape-four"></div>

            <a class="back-home" href="{{ route('home') }}">← Beranda</a>

            <form class="login-card" method="POST" action="{{ route('login.store') }}" autocomplete="off">
                @csrf
                <p class="login-kicker">BozonkNet Area</p>
                <h1>Login</h1>

                @if (session('status'))
                    <p class="login-alert success">{{ session('status') }}</p>
                @endif

                @if ($errors->any())
                    <p class="login-alert error">{{ $errors->first() }}</p>
                @endif

                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="" placeholder="username@gmail.com" autocomplete="off" required>

                <label for="password">Password</label>
                <div class="password-field">
                    <input id="password" type="password" name="password" value="" placeholder="Password" autocomplete="new-password" required>
                    <button class="password-toggle" type="button" aria-label="Lihat password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5c5.2 0 8.6 4.5 9.7 6.2.2.3.2.8 0 1.1C20.6 14 17.2 18.5 12 18.5S3.4 14 2.3 12.3a1 1 0 0 1 0-1.1C3.4 9.5 6.8 5 12 5Zm0 11.5c3.9 0 6.7-3.1 7.7-4.7C18.7 10.2 15.9 7 12 7s-6.7 3.1-7.7 4.8c1 1.6 3.8 4.7 7.7 4.7Zm0-7.4a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4Z"/></svg>
                    </button>
                </div>

                <button class="signin-button" type="submit">Sign in</button>
            </form>
        </section>
    </main>
    <script>
        window.addEventListener('pageshow', () => {
            document.getElementById('email').value = '';
            document.getElementById('password').value = '';
        });

        document.querySelector('.password-toggle')?.addEventListener('click', (event) => {
            const button = event.currentTarget;
            const input = document.getElementById('password');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-pressed', String(!visible));
            button.setAttribute('aria-label', visible ? 'Lihat password' : 'Sembunyikan password');
        });
    </script>
</body>
</html>
