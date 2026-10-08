{{-- FILE: resources/views/partials/_text-us.blade.php
     Floating "Text us" button + a friendly prompt bubble (like the reference site).
     Plain HTML/CSS/JS only, so it can be included on every public page and on the home page. --}}
<style>
  #azt-wrap{position:fixed;right:18px;bottom:18px;z-index:9999;font-family:'DM Sans',Arial,sans-serif;display:flex;flex-direction:column;align-items:flex-end;gap:10px}
  #azt-bubble{display:none;max-width:260px;background:#fff;color:#1A1A2E;border-radius:14px;box-shadow:0 6px 24px rgba(0,0,0,.22);padding:12px 34px 12px 14px;font-size:14px;line-height:1.4;position:relative;cursor:pointer}
  #azt-bubble:after{content:"";position:absolute;bottom:-6px;right:28px;width:12px;height:12px;background:#fff;transform:rotate(45deg)}
  #azt-bubble button{position:absolute;top:6px;right:8px;border:0;background:none;color:#8C96AC;font-size:16px;cursor:pointer;line-height:1}
  #azt-btn{display:flex;align-items:center;gap:8px;border:0;cursor:pointer;background:#C8960C;color:#0A1F5C;font-weight:700;font-size:15px;padding:12px 18px;border-radius:999px;box-shadow:0 6px 18px rgba(0,0,0,.28)}
  #azt-btn:hover{background:#E8C766}
  #azt-panel{display:none;width:290px;background:#fff;color:#1A1A2E;border-radius:14px;box-shadow:0 8px 30px rgba(0,0,0,.28);overflow:hidden}
  #azt-panel header{background:#0A1F5C;color:#fff;padding:12px 14px;font-weight:700;font-size:14px}
  #azt-panel .azt-row{padding:12px 14px;border-top:1px solid #eee}
  #azt-panel .azt-row b{display:block;font-size:13px;margin-bottom:6px;color:#0A1F5C}
  #azt-panel a{display:inline-block;margin:0 6px 4px 0;padding:7px 12px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:1.5px solid #0A1F5C;color:#0A1F5C}
  #azt-panel a.azt-wa{background:#22c55e;border-color:#22c55e;color:#fff}
  #azt-panel a:hover{opacity:.85}
</style>
<div id="azt-wrap">
  <div id="azt-panel" role="dialog" aria-label="Contact Auto Zenith Parts">
    <header>What parts are you looking for?</header>
    <div class="azt-row">
      <b>USA</b>
      <a class="azt-wa" href="https://wa.me/16822563201?text=Hi%2C%20I%20am%20looking%20for%20a%20part." target="_blank" rel="noopener">WhatsApp</a>
      <a href="sms:+16822563201">Text</a>
    </div>
    <div class="azt-row">
      <b>Nigeria / Ghana (Lagos hub)</b>
      <a class="azt-wa" href="https://wa.me/2349155688804?text=Hi%2C%20I%20am%20looking%20for%20a%20part." target="_blank" rel="noopener">WhatsApp</a>
      <a href="tel:+2349155688804">Call</a>
    </div>
  </div>
  <div id="azt-bubble" role="status">Hi there! What parts are you looking for?<button type="button" id="azt-close" aria-label="Dismiss">&times;</button></div>
  <button type="button" id="azt-btn" aria-expanded="false" aria-controls="azt-panel">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    Text us
  </button>
</div>
<script>
(function () {
  var btn = document.getElementById('azt-btn'), panel = document.getElementById('azt-panel'),
      bubble = document.getElementById('azt-bubble'), close = document.getElementById('azt-close');
  if (!btn) return;
  var seen = false; try { seen = sessionStorage.getItem('azt-dismissed') === '1'; } catch (e) {}
  function openPanel(open) {
    panel.style.display = open ? 'block' : 'none';
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) bubble.style.display = 'none';
  }
  btn.addEventListener('click', function () { openPanel(panel.style.display !== 'block'); });
  bubble.addEventListener('click', function (e) { if (e.target !== close) openPanel(true); });
  close.addEventListener('click', function () { bubble.style.display = 'none'; try { sessionStorage.setItem('azt-dismissed', '1'); } catch (e) {} });
  if (!seen) setTimeout(function () { if (panel.style.display !== 'block') bubble.style.display = 'block'; }, 6000);
})();
</script>
