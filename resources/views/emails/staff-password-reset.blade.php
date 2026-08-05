<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0; padding:0; background:#F7F4EC; font-family:Arial, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
    <tr>
      <td align="center">
        <table width="440" cellpadding="0" cellspacing="0" style="background:#fff; border:1.5px solid #0D1B2A; border-radius:14px; padding:36px;">
          <tr>
            <td style="text-align:center; padding-bottom:20px;">
              <div style="font-size:20px; font-weight:800; color:#0A1F5C; letter-spacing:0.02em;">AUTO <span style="color:#C8960C;">ZENITH</span> PARTS</div>
              <div style="font-size:11px; color:#8C96AC; letter-spacing:0.08em; text-transform:uppercase; margin-top:4px;">Staff Admin Panel</div>
            </td>
          </tr>
          <tr>
            <td style="font-size:14px; color:#3A4556; line-height:1.6; padding-bottom:20px;">
              Hi {{ $staffName }},<br><br>
              Someone requested a password reset for your staff account. Click below to set a new password — this link expires in 60 minutes.
            </td>
          </tr>
          <tr>
            <td style="text-align:center; padding-bottom:20px;">
              <a href="{{ route('admin.password.reset.form', $token) }}"
                style="display:inline-block; background:#C8960C; color:#0D1B2A; font-weight:700; font-size:14px; padding:12px 28px; border-radius:8px; text-decoration:none;">
                Reset Password
              </a>
            </td>
          </tr>
          <tr>
            <td style="text-align:center; font-size:12px; color:#8C96AC; line-height:1.6;">
              If you didn't request this, you can safely ignore this email — your password won't change unless you click the link above.
            </td>
          </tr>
        </table>
        <div style="font-size:11px; color:#A0A8B8; margin-top:20px;">Auto Zenith Parts · autozenithparts.com</div>
      </td>
    </tr>
  </table>
</body>
</html>
