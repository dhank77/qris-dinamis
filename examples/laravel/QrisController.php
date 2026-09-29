<?php

/**
 * Contoh 8 — Integrasi Laravel.
 *
 * 1. composer require dhank77/qris-dinamis chillerlan/php-qrcode
 * 2. Tambahkan ke .env:
 *      QRIS_STATIC="00020101021126...6304ABCD"
 * 3. Tambahkan ke config/services.php:
 *      'qris' => ['static' => env('QRIS_STATIC')],
 * 4. Salin file ini ke app/Http/Controllers/QrisController.php
 * 5. routes/web.php:
 *      Route::get('/pay/{order}', [QrisController::class, 'show'])->name('pay');
 *    routes/api.php:
 *      Route::post('/qris', [QrisController::class, 'generate']);
 */

namespace App\Http\Controllers;

use App\Models\Order;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use QrisDinamis\Exception\InvalidQrisException;
use QrisDinamis\Qris;

class QrisController extends Controller
{
    /**
     * Halaman pembayaran untuk satu pesanan.
     */
    public function show(Order $order)
    {
        $qris = Qris::convert(config('services.qris.static'), $order->total);

        return view('pay', [
            'order'   => $order,
            'qris'    => $qris,
            'qrImage' => (new QRCode())->render($qris),   // data URI untuk <img src>
        ]);
    }

    /**
     * API: POST /api/qris  { "amount": 25000, "fee_type": "fixed", "fee_value": 1000 }
     */
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount'    => ['required', 'integer', 'min:1'],
            'fee_type'  => ['nullable', 'in:fixed,percentage'],
            'fee_value' => ['nullable', 'numeric', 'min:0', 'required_with:fee_type'],
        ]);

        try {
            $qris = Qris::convert(
                config('services.qris.static'),
                $data['amount'],
                $data['fee_type'] ?? null,
                $data['fee_value'] ?? null,
            );
        } catch (InvalidQrisException $e) {
            return response()->json(['errors' => $e->getErrors()], 422);
        }

        return response()->json([
            'qris'  => $qris,
            'image' => (new QRCode())->render($qris),
        ]);
    }
}
