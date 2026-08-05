<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Auto Zenith Parts Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;800&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'IBM Plex Sans', sans-serif; background: #0A1F5C; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
  .card { background: #fff; border-radius: 16px; padding: 36px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
  .brand { text-align: center; font-family: 'Big Shoulders Display', sans-serif; font-weight: 800; font-size: 22px; color: #0A1F5C; margin-bottom: 4px; }
  .brand span { color: #C8960C; }
  .sub { text-align: center; font-size: 13px; color: #8C96AC; margin-bottom: 24px; }
  label { display: block; font-size: 12px; color: #4A5568; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
  input[type=email] { width: 100%; border: 1.5px solid #E2E8F0; border-radius: 10px; padding: 12px 14px; font-size: 14px; margin-bottom: 18px; }
  input[type=email]:focus { outline: none; border-color: #C8960C; }
  button { width: 100%; background: #C8960C; color: #0D1B2A; border: none; border-radius: 10px; padding: 13px; font-weight: 700; font-size: 14px; cursor: pointer; }
  button:hover { background: #E8C766; }
  .msg { border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 18px; }
  .success { background: #E8F5E9; color: #2E7D32; }
  .error { background: #FDECEA; color: #A32D2D; }
  .back { display: block; text-align: center; margin-top: 18px; font-size: 13px; color: #8C96AC; text-decoration: none; }
  .back:hover { color: #0A1F5C; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">AUTO <span>ZENITH</span> PARTS</div>
    <div class="sub">Reset your staff account password</div>

    @if(session('success'))<div class="msg success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="msg error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="msg error">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.password.email') }}">
      @csrf
      <label>Staff Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@autozenithparts.com">
      <button type="submit">Send Reset Link</button>
    </form>

    <a href="{{ route('admin.login') }}" class="back">← Back to Login</a>
  </div>
</body>
</html>
