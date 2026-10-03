{{-- Plain, table-based and inline-styled on purpose: email clients are not browsers.
     Front end owns the storefront views; this one is self-contained. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Kayaa order {{ $order->reference }}</title>
</head>
<body style="margin:0;padding:24px;background:#faf7f2;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;color:#2c2a27;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;">
    <tr>
        <td style="padding:28px 28px 8px;">
            <p style="margin:0 0 4px;font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#8a8279;">Kayaa</p>
            <h1 style="margin:0 0 12px;font-size:22px;font-weight:600;">Thank you, {{ $order->first_name }}.</h1>
            <p style="margin:0 0 16px;font-size:15px;line-height:1.5;">
                We have your order <strong>{{ $order->reference }}</strong>. We'll let you know as soon as it's on its way.
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:0 28px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-top:1px solid #efe9e0;">
                @foreach ($order->items as $item)
                    <tr>
                        <td style="padding:10px 0;border-bottom:1px solid #f6f1ea;">
                            {{ $item->product_name }}<br>
                            <span style="color:#8a8279;font-size:13px;">{{ $item->variant_label }} &middot; Qty {{ $item->qty }}</span>
                        </td>
                        <td align="right" style="padding:10px 0;border-bottom:1px solid #f6f1ea;white-space:nowrap;">
                            {{ money($item->line_total) }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <td style="padding:10px 0 2px;color:#8a8279;">Subtotal</td>
                    <td align="right" style="padding:10px 0 2px;">{{ money($order->subtotal) }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#8a8279;">Delivery</td>
                    <td align="right" style="padding:2px 0;">{{ $order->shipping === 0 ? 'Free' : money($order->shipping) }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-weight:600;border-top:1px solid #efe9e0;">Total</td>
                    <td align="right" style="padding:8px 0;font-weight:600;border-top:1px solid #efe9e0;">{{ money($order->total) }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 28px 4px;font-size:14px;line-height:1.6;">
            <p style="margin:0 0 4px;color:#8a8279;">Delivering to</p>
            <p style="margin:0 0 16px;">
                {{ $order->customerName() }}<br>
                {{ $order->address }}<br>
                {{ $order->city }}, {{ $order->district }}<br>
                {{ $order->phone }}
            </p>
            <p style="margin:0 0 16px;">
                Payment: {{ $order->paymentMethodLabel() }}@if ($order->payment_status === 'paid') &mdash; paid @endif
            </p>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 28px 28px;">
            <a href="{{ $trackUrl }}" style="display:inline-block;padding:12px 20px;background:#2c2a27;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;">Track this order</a>
            <p style="margin:16px 0 0;font-size:13px;color:#8a8279;line-height:1.5;">
                This link is personal to your order, so please don't forward it.
                Any questions, just reply to this email.
            </p>
        </td>
    </tr>
</table>
</body>
</html>
