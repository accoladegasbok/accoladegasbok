@verbatim
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>AutoMatch AI — AI-Powered Parts Compatibility Matching | Auto Zenith Parts</title>
<meta name="description" content="AutoMatch AI is Auto Zenith Parts' premium AI-powered compatibility matching engine — the same intelligence behind our internal system, built for repair shops and dealers who need it at scale. Coming soon.">
<link rel="canonical" href="https://autozenithparts.com/automatch-ai">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@600;700;800;900&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<style>
  :root{
    --navy:#0A1F5C; --navy-deep:#0D1B2A; --gold:#C8960C; --gold-light:#E8C766;
    --cream:#F7F4EC; --steel:#4A5568; --ink:#1A1A2E; --line:#D9D2C0;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  body{ font-family:'IBM Plex Sans', sans-serif; color:var(--ink); background:var(--cream); -webkit-font-smoothing:antialiased; }
  .display{ font-family:'Big Shoulders Display', sans-serif; text-transform:uppercase; }
  .mono{ font-family:'IBM Plex Mono', monospace; }
  a{ color:inherit; text-decoration:none; }
  a:focus-visible, button:focus-visible, input:focus-visible{ outline:3px solid var(--gold); outline-offset:2px; }
  .wrap{ max-width:820px; margin:0 auto; padding:0 24px; }

  header{ padding:20px 0; border-bottom:1px solid var(--line); }
  .back{ font-size:13px; color:var(--steel); display:inline-flex; align-items:center; gap:6px; }
  .back:hover{ color:var(--navy); }

  .hero{ padding:80px 0 50px; text-align:center; }
  .badge{ display:inline-block; background:var(--gold); color:var(--navy-deep); font-family:'IBM Plex Mono'; font-size:11px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:6px 16px; border-radius:20px; margin-bottom:24px; }
  h1{ font-size:clamp(38px,6vw,58px); font-weight:800; color:var(--navy-deep); line-height:0.98; margin-bottom:20px; }
  h1 em{ font-style:normal; color:var(--gold); }
  .hero p.lead{ font-size:17px; color:var(--steel); max-width:56ch; margin:0 auto; line-height:1.65; }

  .diagram{ background:#fff; border:1.5px solid var(--navy-deep); border-radius:16px; padding:36px; margin:50px 0; box-shadow:6px 8px 0 rgba(10,31,92,0.06); }
  .flow{ display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .flow-step{ text-align:center; flex:1; min-width:130px; }
  .flow-step .n{ width:52px; height:52px; border-radius:50%; background:var(--navy-deep); color:var(--gold-light); font-family:'Big Shoulders Display'; font-weight:800; font-size:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; }
  .flow-step p{ font-size:12.5px; color:var(--steel); font-weight:500; }
  .flow-arrow{ font-size:20px; color:var(--gold); flex-shrink:0; }

  .features{ display:grid; grid-template-columns:repeat(2,1fr); gap:20px; margin-bottom:50px; }
  .feature{ background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px; }
  .feature h3{ font-family:'Big Shoulders Display'; font-size:18px; font-weight:700; color:var(--navy-deep); text-transform:uppercase; margin-bottom:8px; }
  .feature p{ font-size:13.5px; color:var(--steel); line-height:1.6; }

  .cta-band{ background:var(--navy-deep); color:#fff; border-radius:16px; padding:44px 36px; text-align:center; margin-bottom:60px; }
  .cta-band h2{ font-family:'Big Shoulders Display'; font-size:28px; font-weight:800; margin-bottom:12px; }
  .cta-band p{ color:#B9C2D6; font-size:14.5px; max-width:50ch; margin:0 auto 26px; line-height:1.6; }

  form.notify{ display:flex; gap:10px; max-width:420px; margin:0 auto; flex-wrap:wrap; justify-content:center; }
  form.notify input[type=email]{ flex:1; min-width:220px; padding:13px 16px; border-radius:8px; border:none; font-size:14px; font-family:'IBM Plex Sans'; }
  form.notify button{ background:var(--gold); color:var(--navy-deep); border:none; padding:13px 24px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; }
  form.notify button:hover{ background:var(--gold-light); }
  .notify-note{ font-size:11.5px; color:#7C88A0; margin-top:12px; }
  #notifyFeedback{ font-size:13px; margin-top:12px; }

  footer{ text-align:center; padding:30px 0 50px; font-size:12.5px; color:var(--steel); }

  @media (max-width:640px){
    .features{ grid-template-columns:1fr; }
    .flow{ flex-direction:column; }
    .flow-arrow{ transform:rotate(90deg); }
  }
</style>
</head>
<body>

<header>
  <div class="wrap">
    <a href="/home" class="back">← Back to Auto Zenith Parts</a>
  </div>
</header>

<main>
  <section class="hero">
    <div class="wrap">
      <div class="badge">Premium — In Development</div>
      <h1>Parts compatibility,<br>solved by <em>AI.</em></h1>
      <p class="lead">AutoMatch AI is the compatibility engine already running inside Auto Zenith Parts' own admin system — the exact logic our staff use to match engine codes, gear ratios, and interchange data — now being built into a standalone tool you can subscribe to.</p>
    </div>
  </section>

  <div class="wrap">
    <div class="diagram">
      <div class="flow">
        <div class="flow-step"><div class="n">1</div><p>Enter VIN or vehicle details</p></div>
        <div class="flow-arrow">→</div>
        <div class="flow-step"><div class="n">2</div><p>AI cross-references verified interchange data</p></div>
        <div class="flow-arrow">→</div>
        <div class="flow-step"><div class="n">3</div><p>Get a confirmed match — not a guess</p></div>
      </div>
    </div>

    <div class="features">
      <div class="feature">
        <h3>Fewer Wrong Orders</h3>
        <p>Built to cut incorrect parts orders and the return costs that come with them — matching is based on real interchange data, not a lookalike part number.</p>
      </div>
      <div class="feature">
        <h3>Less Downtime</h3>
        <p>Confirm fitment before you order, not after installation — designed to reduce the diagnostic and re-order cycles that cost repair shops billable hours.</p>
      </div>
      <div class="feature">
        <h3>Built On Real Stock Data</h3>
        <p>Not a generic compatibility database — trained on the same verified engine codes, pin counts, and fitment notes already powering our own inventory.</p>
      </div>
      <div class="feature">
        <h3>For Shops &amp; Dealers</h3>
        <p>A subscription tool aimed at repair shops and dealers who need compatibility checks at volume, beyond what the free single-part checker covers.</p>
      </div>
    </div>

    <div class="cta-band">
      <h2>Get notified at launch</h2>
      <p>AutoMatch AI is in active development. Leave your email and we'll reach out when early access opens — before public pricing goes live.</p>
      <form class="notify" id="notifyForm">
        <input type="email" name="email" placeholder="you@example.com" required aria-label="Email address">
        <button type="submit">Notify Me</button>
      </form>
      <div class="notify-note">No spam — one email when AutoMatch AI is ready for early access.</div>
      <div id="notifyFeedback"></div>
    </form>
    </div>
  </div>
</main>

<footer>
  <div class="wrap">
    In the meantime, try our free <a href="/parts/compatibility" style="color:var(--navy); font-weight:600;">VIN &amp; Part Compatibility Checker</a> — no waitlist required.<br>
    Questions? <a href="mailto:info@autozenithparts.com" style="color:var(--navy); font-weight:600;">info@autozenithparts.com</a>
  </div>
</footer>

<script>
// NOTE: this posts to a placeholder endpoint — wire up a real
// notify-list route/table before relying on this to actually capture
// emails. Left functional-looking but inert until that exists.
document.getElementById('notifyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('notifyFeedback').innerHTML =
        '<span style="color:#4ade80;">Thanks — we\'ll be in touch when AutoMatch AI opens up.</span>';
    this.reset();
});
</script>
</body>
</html>
@endverbatim
