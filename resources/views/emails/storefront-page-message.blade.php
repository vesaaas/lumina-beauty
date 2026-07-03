<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New {{ $page }} Message</title>
  </head>
  <body style="margin:0;background:#fffafa;color:#21191d;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fffafa;padding:28px 14px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #eadfe3;">
            <tr>
              <td style="padding:34px 34px 22px;border-bottom:1px solid #eadfe3;">
                <p style="margin:0 0 10px;color:#8e294d;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Lumina Beauty</p>
                <h1 style="margin:0;color:#21191d;font-family:Georgia,'Times New Roman',serif;font-size:34px;line-height:1;">New {{ $page }} Message</h1>
                <p style="margin:16px 0 0;color:#756a70;font-size:15px;line-height:1.7;">A visitor submitted this message from the {{ $page }} page.</p>
              </td>
            </tr>
            <tr>
              <td style="padding:26px 34px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:24px;">
                  <tr>
                    <td width="50%" valign="top" style="padding:16px;background:#fff7f4;">
                      <strong style="display:block;margin-bottom:8px;">Name</strong>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $attributes['name'] }}</span>
                    </td>
                    <td width="50%" valign="top" style="padding:16px;background:#fff7f4;border-left:8px solid #ffffff;">
                      <strong style="display:block;margin-bottom:8px;">Email</strong>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $attributes['email'] }}</span>
                    </td>
                  </tr>
                </table>

                @if (! empty($attributes['topic']))
                  <p style="margin:0 0 18px;">
                    <strong style="display:block;margin-bottom:6px;">Topic</strong>
                    <span style="display:block;color:#756a70;line-height:1.6;">{{ $attributes['topic'] }}</span>
                  </p>
                @endif

                <p style="margin:0;">
                  <strong style="display:block;margin-bottom:6px;">Message</strong>
                  <span style="display:block;color:#756a70;line-height:1.7;white-space:pre-line;">{{ $attributes['message'] }}</span>
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
