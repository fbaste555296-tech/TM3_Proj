<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sign In · Triple-M3</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <link href="{{ asset('css/triple-m3-theme.css') }}" rel="stylesheet">
    <style>
        :root { --accent: var(--teal, #88736d); }
        * { box-sizing: border-box; }
        body { margin: 0; display: grid; min-height: 100vh; place-items: center; padding: 24px; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; background: #f6f5f3; color: #242323; }

        .card { width: min(100%, 400px); padding: 32px; background: #fff; border: 1px solid #dedbd8; border-radius: 16px; }
        .back { display: inline-block; margin-bottom: 20px; font-size: 13px; font-weight: 700; color: #6b7877; text-decoration: none; }
        .back:hover { color: var(--accent); }

        h1 { margin: 0 0 6px; font-size: 24px; font-weight: 800; }
        .sub { margin: 0 0 24px; font-size: 14px; color: #6b7877; }

        .error { margin-bottom: 18px; padding: 10px 12px; border-radius: 8px; background: #fdeeee; color: #a12a2a; font-size: 13px; }

        label { display: block; font-size: 13px; font-weight: 700; }
        input[type=email], input[type=password] { width: 100%; margin: 6px 0 16px; padding: 12px; border: 1px solid #d5dddc; border-radius: 8px; font: inherit; font-size: 14px; outline: none; }
        input[type=email]:focus, input[type=password]:focus { border-color: var(--accent); }

        .remember { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-weight: 500; color: #6b7877; }

        button { width: 100%; padding: 13px; border: 0; border-radius: 8px; background: var(--accent); color: #fff; font: inherit; font-size: 15px; font-weight: 700; cursor: pointer; }
        button:hover { opacity: .92; }

        .alt { margin: 22px 0 0; text-align: center; font-size: 13px; color: #6b7877; }
        .alt a { color: var(--accent); font-weight: 700; text-decoration: none; }
    </style>
</head>

<body>
    <main class="card">
        <a class="back" href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">← Back</a>

        <img src="{{ asset('images/triplem3.jpg') }}" alt="Triple-M3 logo" style="display:block;width:76px;height:76px;object-fit:contain;border-radius:50%;margin-bottom:20px">
        <h1>Sign In</h1>
        <p class="sub">Sign in to continue to your Triple-M3 account.</p>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <label class="remember">
                <input type="checkbox" name="remember"> Keep me signed in
            </label>

            <button type="submit">Sign in</button>
        </form>

        <p class="alt">New customer? <a href="{{ route('register') }}">Create an account to book</a></p>
    </main>
</body>

</html>