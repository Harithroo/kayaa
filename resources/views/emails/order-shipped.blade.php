{{-- Plain, table-based and inline-styled on purpose: email clients are not browsers. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Kayaa order {{ $order->reference }} is on its way</title>
</head>
<body style="margin:0;padding:24px;background:#faf7f2;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;color:#2c2a27;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;">
    <tr>
        <td style="padding:28px;">
            <p style="margin:0 0 4px;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#8a8279;">Kayaa</p>
            <h1 style="margin:0 0 12px;font-size:22px;font-weight:600;">Good news, {{ $order->first_name }}.</h1>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                Your order <strong>{{ $order->reference }}</strong> has been handed to the courier.
            </p>
            @if ($order->courier || $order->tracking_number)
                <p style="margin:0 0 16px;font-size:15px;line-height:1.6;">
                    @if ($order->courier) Courier: <strong>{{ $order->courier }}</strong><br>@endif
                    @if ($order->tracking_number) Tracking number: <strong>{{ $order->tracking_number }}</strong>@endif
                </p>
            @endif
            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                Delivering to {{ $order->address }}, {{ $order->city }}, {{ $order->district }}.
                @if ($order->payment_method === 'cod' && $order->payment_status !== 'paid')
                    Please keep {{ money($order->total) }} ready for cash on delivery.
                @endif
            </p>
            <p style="margin:0;"><a href="{{ $trackUrl }}" style="display:inline-block;background:#6e5a8c;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-size:14px;">Track your order</a></p>
        </td>
    </tr>
</table>
</body>
</html>
