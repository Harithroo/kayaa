<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Packing slip {{ $order->reference }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px; font: 14px/1.5 -apple-system, "Segoe UI", Helvetica, Arial, sans-serif; color: #3d3547; background: #fff; }
        .slip { max-width: 720px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6e5a8c; padding-bottom: 12px; margin-bottom: 20px; }
        h1 { margin: 0; font-size: 22px; color: #6e5a8c; letter-spacing: .04em; }
        .ref { font-size: 18px; font-weight: 700; text-align: right; }
        .muted { color: #635c71; font-size: 13px; }
        .cols { display: flex; gap: 32px; margin-bottom: 20px; }
        .cols > div { flex: 1; }
        h2 { margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #635c71; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #635c71; border-bottom: 1px solid #d9cbe9; padding: 6px 0; }
        td { padding: 9px 0; border-bottom: 1px solid #e9e1f0; vertical-align: top; }
        .num { text-align: right; white-space: nowrap; }
        .box { display: inline-block; width: 14px; height: 14px; border: 1.5px solid #3d3547; border-radius: 3px; vertical-align: middle; }
        .totals { margin-top: 12px; margin-left: auto; width: 260px; }
        .totals td { border: 0; padding: 3px 0; }
        .totals tr:last-child td { font-weight: 700; border-top: 1px solid #3d3547; padding-top: 8px; }
        .note { margin-top: 20px; padding: 10px 12px; border: 1px dashed #c3afda; border-radius: 6px; }
        .cod { margin-top: 20px; padding: 12px; background: #f2ebf7; border-radius: 6px; font-weight: 700; }
        .print-btn { position: fixed; top: 16px; right: 16px; padding: 8px 14px; border: 0; border-radius: 6px; background: #6e5a8c; color: #fff; font-size: 14px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
<button class="print-btn" type="button" onclick="window.print()">Print</button>
<div class="slip">
    <header>
        <div>
            <h1>KAYAA</h1>
            <div class="muted">Packing slip</div>
        </div>
        <div>
            <div class="ref">{{ $order->reference }}</div>
            <div class="muted" style="text-align:right;">Placed {{ $order->created_at->format('d M Y, H:i') }}</div>
        </div>
    </header>

    <div class="cols">
        <div>
            <h2>Deliver to</h2>
            <strong>{{ $order->customerName() }}</strong><br>
            {{ $order->address }}<br>
            {{ $order->city }}, {{ $order->district }}<br>
            Tel: {{ $order->phone }}
        </div>
        <div>
            <h2>Shipment</h2>
            Payment: {{ $order->paymentMethodLabel() }} ({{ $order->payment_status }})<br>
            @if ($order->courier) Courier: {{ $order->courier }}<br>@endif
            @if ($order->tracking_number) Tracking: {{ $order->tracking_number }}<br>@endif
            @if (! $order->courier && ! $order->tracking_number) <span class="muted">Courier and tracking not set yet</span> @endif
        </div>
    </div>

    <table>
        <thead>
        <tr><th style="width:28px;"></th><th>Item</th><th>SKU</th><th class="num">Qty</th><th class="num">Price</th></tr>
        </thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr>
                <td><span class="box"></span></td>
                <td>{{ $item->product_name }}<br><span class="muted">{{ $item->variant_label }}</span></td>
                <td>{{ $item->sku ?: '—' }}</td>
                <td class="num">{{ $item->qty }}</td>
                <td class="num">{{ money($item->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">{{ money($order->subtotal) }}</td></tr>
        <tr><td>Delivery</td><td class="num">{{ $order->shipping === 0 ? 'Free' : money($order->shipping) }}</td></tr>
        <tr><td>Total</td><td class="num">{{ money($order->total) }}</td></tr>
    </table>

    @if ($order->payment_method === 'cod' && $order->payment_status !== 'paid')
        <div class="cod">Cash on delivery: collect {{ money($order->total) }}</div>
    @endif

    @if ($order->note)
        <div class="note"><strong>Customer note:</strong> {{ $order->note }}</div>
    @endif
</div>
</body>
</html>
