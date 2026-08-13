<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .muted { color: #64748b; }
        .header { margin-bottom: 24px; }
        .company { font-size: 18px; font-weight: bold; color: #0f766e; }
        .title { font-size: 16px; font-weight: bold; margin: 16px 0 8px; }
        table.info { width: 100%; margin-bottom: 16px; }
        table.info td { vertical-align: top; padding: 2px 0; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lines th, table.lines td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        table.lines th { background: #f1f5f9; text-align: left; font-size: 11px; }
        .right { text-align: right; }
        .totals { margin-top: 12px; width: 100%; }
        .totals td { padding: 4px 0; }
        .signatures { width: 100%; margin-top: 36px; border-collapse: separate; border-spacing: 12px 0; }
        .signatures td { width: 33%; vertical-align: top; text-align: center; }
        .sig-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 10px 8px 12px;
            min-height: 120px;
        }
        .sig-title { font-size: 11px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.03em; }
        .sig-space { height: 56px; }
        .sig-line { border-top: 1px solid #94a3b8; margin: 0 8px 8px; }
        .sig-meta { font-size: 11px; text-align: left; padding: 0 4px; line-height: 1.45; }
        .footer { margin-top: 20px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">{{ $company['name'] }}</div>
        <div class="muted">{{ $company['address'] }}</div>
        <div class="muted">{{ $company['phone'] }}</div>
    </div>

    <div class="title">Purchase Order</div>

    <table class="info">
        <tr>
            <td width="55%">
                <strong>Nomor:</strong> {{ $order->number }}<br>
                <strong>Tanggal:</strong> {{ optional($order->order_date)->format('d/m/Y') }}<br>
                <strong>Estimasi datang:</strong> {{ optional($order->expected_date)->format('d/m/Y') ?: '—' }}<br>
                <strong>Status:</strong> {{ $statusLabel }}
            </td>
            <td width="45%">
                <strong>Vendor</strong><br>
                {{ $order->vendor?->code }} — {{ $order->vendor?->name }}<br>
                {{ $order->vendor?->contact_person }}<br>
                {{ $order->vendor?->phone }} {{ $order->vendor?->email ? '· '.$order->vendor->email : '' }}<br>
                <span class="muted">{{ $order->vendor?->address }}</span>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">SKU</th>
                <th>Nama item</th>
                <th width="8%">UOM</th>
                <th width="10%" class="right">Qty</th>
                <th width="14%" class="right">Harga</th>
                <th width="14%" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $index => $line)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $line->item?->sku }}</td>
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $line->item?->uom?->code }}</td>
                    <td class="right">{{ number_format((float) $line->qty_ordered, 3, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $line->unit_price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format((float) $line->line_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td width="70%"></td>
            <td width="15%"><strong>Total</strong></td>
            <td width="15%" class="right"><strong>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if ($order->notes)
        <p><strong>Catatan:</strong> {{ $order->notes }}</p>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-box">
                    <div class="sig-title">Dibuat oleh</div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-meta">
                        Nama: {{ $signatures['prepared']['name'] ?: '—' }}<br>
                        Tanggal: {{ $signatures['prepared']['date'] ?: '—' }}
                    </div>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <div class="sig-title">Disetujui oleh</div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-meta">
                        Nama: {{ $signatures['approved']['name'] ?: '—' }}<br>
                        Tanggal: {{ $signatures['approved']['date'] ?: '—' }}
                    </div>
                </div>
            </td>
            <td>
                <div class="sig-box">
                    <div class="sig-title">Vendor</div>
                    <div class="sig-space"></div>
                    <div class="sig-line"></div>
                    <div class="sig-meta">
                        Nama: ________________<br>
                        Tanggal: ________________
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Diekspor: {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
    </div>
</body>
</html>
