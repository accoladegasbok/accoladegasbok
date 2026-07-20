{{-- FILE: resources/views/unsubscribe.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unsubscribe — Auto Zenith Parts</title>
<style>
    body { font-family: Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
    .card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 40px; max-width: 420px; text-align: center; }
    .icon { font-size: 40px; margin-bottom: 12px; }
    h1 { font-size: 20px; color: #0d1b2a; margin-bottom: 8px; }
    p { font-size: 14px; color: #666; line-height: 1.5; }
    .brand { color: #c9a84c; font-weight: bold; }
</style>
</head>
<body>
    <div class="card">
        @if($success)
        <div class="icon">✓</div>
        <h1>You've been unsubscribed</h1>
        <p>You won't receive further {{ $channel }} notifications from <span class="brand">Auto Zenith Parts</span>. If you change your mind, just get in touch and we can re-enable it.</p>
        @else
        <div class="icon">⚠</div>
        <h1>Link not valid</h1>
        <p>{{ $error ?? 'This unsubscribe link could not be verified.' }} If you'd like to stop receiving messages, please contact us directly.</p>
        @endif
    </div>
</body>
</html>
