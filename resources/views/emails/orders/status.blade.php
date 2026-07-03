<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $headline }}</title>
  </head>
  <body style="margin:0;background:#fffafa;color:#21191d;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fffafa;padding:28px 14px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #eadfe3;">
            <tr>
              <td style="padding:34px 34px 22px;border-bottom:1px solid #eadfe3;">
                <p style="margin:0 0 10px;color:#8e294d;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Lumina Beauty</p>
                <h1 style="margin:0;color:#21191d;font-family:Georgia,'Times New Roman',serif;font-size:34px;line-height:1;">{{ $headline }}</h1>
                <p style="margin:16px 0 0;color:#756a70;font-size:15px;line-height:1.7;">{{ $intro }}</p>
              </td>
            </tr>

            <tr>
              <td style="padding:26px 34px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                  <tr>
                    <td style="padding:0 0 14px;">
                      <strong style="display:block;margin-bottom:4px;">Order {{ $order->order_number }}</strong>
                      <span style="display:block;color:#756a70;text-transform:capitalize;">Status: {{ $order->status }}</span>
                    </td>
                  </tr>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:10px 0 24px;">
                  <tr>
                    <td width="50%" valign="top" style="padding:16px;background:#fff7f4;">
                      <strong style="display:block;margin-bottom:8px;">Customer</strong>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $order->customer_name }}</span>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $order->customer_email }}</span>
                      @if ($order->customer_phone)
                        <span style="display:block;color:#756a70;line-height:1.6;">{{ $order->customer_phone }}</span>
                      @endif
                    </td>
                    <td width="50%" valign="top" style="padding:16px;background:#fff7f4;border-left:8px solid #ffffff;">
                      <strong style="display:block;margin-bottom:8px;">Shipping</strong>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $order->shipping_address }}</span>
                      <span style="display:block;color:#756a70;line-height:1.6;">{{ $order->shipping_city }}, {{ $order->shipping_country }}</span>
                    </td>
                  </tr>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
                  <thead>
                    <tr>
                      <th align="left" style="padding:0 0 10px;color:#756a70;font-size:12px;text-transform:uppercase;">Product</th>
                      <th align="center" style="padding:0 0 10px;color:#756a70;font-size:12px;text-transform:uppercase;">Qty</th>
                      <th align="right" style="padding:0 0 10px;color:#756a70;font-size:12px;text-transform:uppercase;">Price</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($order->items as $item)
                      <tr>
                        <td style="padding:14px 0;border-top:1px solid #eadfe3;">
                          <strong style="display:block;">{{ $item->product_name }}</strong>
                          <span style="display:block;color:#756a70;font-size:13px;">{{ $item->brand_name }} / {{ $item->category_name }}</span>
                        </td>
                        <td align="center" style="padding:14px 0;border-top:1px solid #eadfe3;">{{ $item->quantity }}</td>
                        <td align="right" style="padding:14px 0;border-top:1px solid #eadfe3;">
                          {{ Number::currency($item->line_total, 'EUR') }}
                          <span style="display:block;color:#756a70;font-size:12px;">{{ Number::currency($item->unit_price, 'EUR') }} each</span>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:20px;">
                  <tr>
                    <td align="right" style="padding-top:16px;border-top:2px solid #21191d;">
                      <span style="display:block;color:#756a70;font-size:13px;">Order total</span>
                      <strong style="display:block;font-size:24px;">{{ Number::currency($order->total, 'EUR') }}</strong>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
