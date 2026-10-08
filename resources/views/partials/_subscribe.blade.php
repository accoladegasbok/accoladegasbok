{{-- FILE: resources/views/partials/_subscribe.blade.php
     "Subscribe to our emails" box for the footer. Posts to /subscribe (saved in email_subscribers).
     Inline styles only, so it looks right on the home page and on the Tailwind layout alike. --}}
<div id="subscribe" style="max-width:420px;font-family:'DM Sans',Arial,sans-serif;">
  <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:10px;">Subscribe to our emails</div>
  @if(session('subscribed'))
    <div style="background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.5);color:#bbf7d0;border-radius:8px;padding:10px 12px;font-size:13px;">{{ session('subscribed') }}</div>
  @else
  <form method="POST" action="{{ route('subscribe.store') }}" style="position:relative;">
    @csrf
    <div style="position:absolute;left:-9999px;" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
    <input type="email" name="email" required maxlength="150" placeholder="Email" aria-label="Email address"
           style="width:100%;box-sizing:border-box;background:transparent;border:1px solid #8C96AC;color:#fff;border-radius:6px;padding:12px 46px 12px 14px;font-size:14px;outline:none;">
    <button type="submit" aria-label="Subscribe"
            style="position:absolute;right:6px;top:6px;height:34px;width:34px;border:0;border-radius:6px;background:#C8960C;color:#0A1F5C;font-size:18px;cursor:pointer;">&rarr;</button>
  </form>
  @error('email')<div style="color:#fca5a5;font-size:12px;margin-top:6px;">{{ $message }}</div>@enderror
  <div style="font-size:11px;color:#8C96AC;margin-top:8px;">New arrivals and offers, now and then. Unsubscribe any time.</div>
  @endif
</div>
