<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('css/triple-m3.css') }}" rel="stylesheet">
    <link href="{{ asset('css/triple-m3-theme.css') }}" rel="stylesheet">
</head>

<body style="display:grid;min-height:100vh;place-items:center;padding:24px">
    <main class="tm-card" style="width:min(100%,460px);padding:34px">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/admin') }}"
           style="display:inline-flex;align-items:center;gap:6px;margin-bottom:22px;font-size:13px;font-weight:800;color:var(--teal);text-decoration:none">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back
        </a>

        <img src="{{ asset('images/triplem3.jpg') }}" alt="Triple-M3 logo" style="display:block;width:76px;height:76px;object-fit:contain;border-radius:50%;margin:auto">
        <p class="tm-eyebrow" style="margin-top:28px">TRIPLE-M3 CUSTOMER PORTAL</p>
        <h1 style="margin:8px 0;font-size:28px">Create your account</h1>
        <p style="font-size:14px;opacity:.7">Register once to request a service appointment.</p>

        @if($errors->any())
            <p style="margin-top:18px;color:var(--teal);font-size:13px">{{ $errors->first() }}</p>
        @endif

        <form method="POST" style="display:grid;gap:14px;margin-top:26px">
            @csrf
            @foreach(['name'=>'Full name','email'=>'Email','phone'=>'Phone'] as $key=>$label)
                <label style="font-size:13px;font-weight:800">{{ $label }}
                    <input class="tm-input" name="{{ $key }}" type="{{ $key==='email'?'email':'text' }}" value="{{ old($key) }}" required style="margin-top:7px">
                </label>
            @endforeach
            <label style="font-size:13px;font-weight:800">Password
                <input class="tm-input" name="password" type="password" required style="margin-top:7px">
            </label>
            <label style="font-size:13px;font-weight:800">Confirm password
                <input class="tm-input" name="password_confirmation" type="password" required style="margin-top:7px">
            </label>
            <button class="tm-button">Create account & book</button>
        </form>

        <p style="margin-top:24px;font-size:13px">Already registered? <a href="{{ route('login') }}" style="color:var(--teal);font-weight:800">Sign in</a></p>
    </main>
</body>

</html>