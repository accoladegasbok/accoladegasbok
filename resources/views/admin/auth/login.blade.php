{{-- FILE: resources/views/admin/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Login — Auto Zenith Parts</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    body { font-family:'DM Sans',sans-serif; background:#0A1F5C; min-height:100vh; display:flex; align-items:center; justify-content:center; }
    .login-card { background:#fff; border-radius:20px; width:100%; max-width:400px; overflow:hidden; }
    .login-header { background:#0A1F5C; padding:2rem; text-align:center; }
    .login-body { padding:2rem; }
    input:focus { outline:none; border-color:#C8960C; box-shadow:0 0 0 2px rgba(200,150,12,.2); }
  </style>
</head>
<body>
  <div class="login-card shadow-2xl">
    <div class="login-header">
      <div class="font-family:Barlow Condensed" style="font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:26px; color:#fff; letter-spacing:.05em;">AUTO ZENITH PARTS</div>
      <div style="color:#C8960C; font-size:11px; font-weight:500; letter-spacing:.15em; text-transform:uppercase; margin-top:4px;">Staff Portal</div>
    </div>

    <div class="login-body">
      <h2 class="text-navy font-body font-500 text-lg mb-1" style="color:#0A1F5C;">Welcome back</h2>
      <p class="text-gray-400 text-sm font-body mb-5">Sign in to access the admin panel.</p>

      @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
          {{ $errors->first() }}
        </div>
      @endif

      @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf
        <div class="mb-4">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Email address</label>
          <input type="email" name="email" value="{{ old('email') }}" required autofocus
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body transition-all"
            placeholder="staff@autozenithparts.com">
        </div>

        <div class="mb-6">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
          <input type="password" name="password" required
            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-body transition-all"
            placeholder="••••••••">
          <a href="{{ route('admin.password.request') }}" class="block text-right text-xs font-body text-gray-400 hover:text-navy mt-1.5 transition-colors">
            Forgot password?
          </a>
        </div>

        <button type="submit"
          class="w-full font-body font-500 text-sm py-3.5 rounded-xl transition-colors text-white"
          style="background:#0A1F5C;"
          onmouseover="this.style.background='#132474'" onmouseout="this.style.background='#0A1F5C'">
          Sign in to Admin Panel
        </button>
      </form>

      <p class="text-center text-xs text-gray-400 font-body mt-5">
        Auto Zenith Parts · RC: 1135830
      </p>
    </div>
  </div>
</body>
</html>
