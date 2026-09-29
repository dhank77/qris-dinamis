{{-- resources/views/pay.blade.php --}}
<div style="max-width:360px;margin:2rem auto;text-align:center;font-family:system-ui">
    <h2>Pesanan #{{ $order->id }}</h2>
    <img src="{{ $qrImage }}" alt="QRIS" width="260">
    <p style="font-size:1.5rem;font-weight:700">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
    <p style="color:#6b7280">Scan dengan aplikasi bank / e-wallet yang mendukung QRIS</p>
</div>
