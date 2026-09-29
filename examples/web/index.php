<?php

declare(strict_types=1);

// Autoload: vendor Composer jika ada, jika tidak pakai autoloader mandiri.
require is_file(__DIR__ . '/../../vendor/autoload.php')
    ? __DIR__ . '/../../vendor/autoload.php'
    : __DIR__ . '/../../autoload.php';

use QrisDinamis\Qris;
use QrisDinamis\QrisData;

const CURRENCY_MAP = [
    '360' => 'IDR (Rupiah)',
    '840' => 'USD (Dollar)',
];

const MCC_MAP = [
    '4111' => 'Transportation',
    '4121' => 'Taxi',
    '4814' => 'Telecommunication',
    '5311' => 'Department Store',
    '5411' => 'Grocery Store',
    '5499' => 'Food Store',
    '5812' => 'Restaurant / Eating Places',
    '5814' => 'Fast Food',
    '5912' => 'Pharmacy',
    '5999' => 'Retail Store',
    '7299' => 'Other Services',
    '8011' => 'Medical',
    '8999' => 'Professional Services',
];

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function rupiah(string|int|float|null $n): string
{
    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

// ---------- Proses request ----------
$qris = trim((string) ($_POST['qris'] ?? ''));
$amount = trim((string) ($_POST['amount'] ?? ''));
$feeType = (string) ($_POST['fee_type'] ?? 'none');
$feeValue = trim((string) ($_POST['fee_value'] ?? ''));
$action = (string) ($_POST['action'] ?? '');

$errors = [];
/** @var QrisData|null $parsed */
$parsed = null;
$result = '';
$resultData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $qris !== '') {
    $validation = Qris::validate($qris);
    if (!$validation['valid']) {
        $errors = $validation['errors'];
    } else {
        $parsed = Qris::parse($qris);

        if ($action === 'convert' && !$parsed->isDynamic()) {
            if (!ctype_digit($amount) || (int) $amount <= 0) {
                $errors[] = 'Invalid amount';
            } else {
                $ft = null;
                $fv = null;
                if (in_array($feeType, ['fixed', 'percentage'], true) && is_numeric($feeValue) && (float) $feeValue > 0) {
                    $ft = $feeType;
                    $fv = (float) $feeValue;
                }
                try {
                    $result = Qris::convert($qris, (int) $amount, $ft, $fv);
                    $resultData = Qris::parse($result);
                } catch (\Throwable) {
                    $errors[] = 'Failed to convert QRIS';
                }
            }
        }
    }
}

$inputState = $errors ? 'error' : ($parsed ? 'ok' : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QRIS Dinamis — Static to Dynamic Converter</title>
    <meta name="description" content="Convert static QRIS to dynamic — parse, validate, and generate QR codes for the Indonesian payment system.">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        :root {
            --bg: #ffffff; --fg: #111827; --muted: #6b7280; --subtle: #9ca3af;
            --card: #ffffff; --card-head: #f9fafb; --border: #e5e7eb; --input-border: #d1d5db;
            --hover: #f9fafb; --primary: #2563eb; --primary-hover: #1d4ed8; --primary-soft: #dbeafe; --primary-text: #1d4ed8;
            --red-bg: #fef2f2; --red-border: #fecaca; --red: #dc2626; --red-strong: #ef4444;
            --green-bg: #f0fdf4; --green-border: #bbf7d0; --green: #15803d; --green-dash: #86efac;
            --yellow-bg: #fef9c3; --yellow: #a16207;
        }
        .dark {
            --bg: #030712; --fg: #f3f4f6; --muted: #9ca3af; --subtle: #6b7280;
            --card: #111827; --card-head: #0f1623; --border: #1f2937; --input-border: #374151;
            --hover: #111827; --primary: #3b82f6; --primary-hover: #2563eb; --primary-soft: #1e3a8a55; --primary-text: #93c5fd;
            --red-bg: #450a0a55; --red-border: #7f1d1d; --red: #f87171; --red-strong: #ef4444;
            --green-bg: #052e1655; --green-border: #14532d; --green: #4ade80; --green-dash: #166534;
            --yellow-bg: #713f1255; --yellow: #facc15;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            background: var(--bg); color: var(--fg);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh; display: flex; flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        .wrap { width: 100%; max-width: 42rem; margin: 0 auto; padding: 0 1rem; }
        header { position: sticky; top: 0; z-index: 50; border-bottom: 1px solid var(--border); background: color-mix(in srgb, var(--bg) 85%, transparent); backdrop-filter: blur(6px); }
        header .wrap { height: 3.5rem; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: .5rem; text-decoration: none; }
        .brand b { font-size: 1.25rem; background: linear-gradient(90deg, #2563eb, #60a5fa); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .pill { font-size: .75rem; padding: .125rem .5rem; border-radius: 999px; background: var(--primary-soft); color: var(--primary-text); font-weight: 500; }
        .icon-btn { background: none; border: 0; color: var(--muted); padding: .5rem; border-radius: .5rem; cursor: pointer; display: inline-flex; }
        .icon-btn:hover { background: var(--hover); color: var(--fg); }
        svg.i { width: 1.25rem; height: 1.25rem; }
        svg.s { width: 1rem; height: 1rem; flex-shrink: 0; }
        main { flex: 1; padding-top: 2rem; padding-bottom: 2rem; display: flex; flex-direction: column; gap: 1.5rem; }
        label.lbl { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .5rem; }
        .drop { position: relative; border: 2px dashed var(--input-border); border-radius: .75rem; transition: border-color .15s, background .15s; }
        .drop.ok { border-color: var(--green-dash); }
        .drop.error { border-color: var(--red-border); }
        .drop.over { border-color: var(--primary); background: var(--primary-soft); }
        textarea { width: 100%; padding: .75rem 2.25rem .75rem 1rem; background: transparent; border: 0; border-radius: .75rem; color: var(--fg); font: .875rem/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; resize: none; outline: none; word-break: break-all; }
        textarea::placeholder { color: var(--subtle); }
        .clear { position: absolute; top: .5rem; right: .5rem; padding: .25rem; }
        .errors { margin-top: .75rem; border-radius: .5rem; background: var(--red-bg); border: 1px solid var(--red-border); padding: .75rem; }
        .errors ul { margin: 0; padding: 0; list-style: none; color: var(--red); font-size: .875rem; }
        .errors li { display: flex; gap: .5rem; }
        .errors li + li { margin-top: .25rem; }
        .row { display: flex; gap: .5rem; margin-top: .75rem; flex-wrap: wrap; }
        .btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .625rem 1rem; border-radius: .5rem; border: 1px solid var(--input-border); background: transparent; color: var(--fg); font-family: inherit; font-size: .875rem; font-weight: 500; line-height: 1.25; cursor: pointer; text-decoration: none; transition: background .15s; min-width: 8rem; }
        .btn:hover { background: var(--hover); }
        .btn.primary { background: var(--primary); border-color: var(--primary); color: #fff; }
        .btn.primary:hover { background: var(--primary-hover); }
        .btn.danger { background: var(--red-strong); border-color: var(--red-strong); color: #fff; }
        .camera { position: relative; margin-top: .75rem; border-radius: .75rem; overflow: hidden; border: 1px solid var(--input-border); background: #000; }
        .camera video { width: 100%; display: block; }
        .camera .frame { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
        .camera .frame div { width: 12rem; height: 12rem; border: 2px solid rgba(255,255,255,.7); border-radius: 1rem; }
        .camera .hint { position: absolute; bottom: .75rem; left: 0; right: 0; text-align: center; font-size: .875rem; color: rgba(255,255,255,.85); }
        .card { border: 1px solid var(--border); border-radius: .75rem; background: var(--card); overflow: hidden; }
        .card-head { padding: .75rem 1rem; border-bottom: 1px solid var(--border); background: var(--card-head); }
        .card-head h2 { margin: 0; font-size: .875rem; font-weight: 600; display: flex; align-items: center; gap: .5rem; }
        .card-head h2 svg { color: var(--primary); }
        .info-row { display: flex; justify-content: space-between; gap: 1rem; padding: .625rem 1rem; font-size: .875rem; }
        .info-row + .info-row { border-top: 1px solid var(--border); }
        .info-row span:first-child { color: var(--muted); }
        .info-row span:last-child { text-align: right; font-weight: 500; word-break: break-word; }
        .badge { display: inline-block; font-size: .75rem; padding: .125rem .5rem; border-radius: 999px; font-weight: 500; }
        .badge.static { background: var(--yellow-bg); color: var(--yellow); }
        .badge.dynamic { background: var(--green-bg); color: var(--green); }
        .body { padding: 1rem; display: flex; flex-direction: column; gap: 1rem; }
        .field label { display: block; font-size: .875rem; font-weight: 500; margin-bottom: .375rem; }
        .input { display: flex; align-items: center; border: 1px solid var(--input-border); border-radius: .5rem; overflow: hidden; }
        .input:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .input .affix { padding: 0 .75rem; font-size: .875rem; color: var(--muted); }
        .input input { flex: 1; min-width: 0; padding: .625rem .75rem; border: 0; background: transparent; color: var(--fg); font: inherit; font-size: .875rem; outline: none; }
        .input .affix + input { padding-left: 0; }
        .seg { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; }
        .seg input { position: absolute; opacity: 0; pointer-events: none; }
        .seg label { margin: 0; text-align: center; padding: .5rem; border: 1px solid var(--input-border); border-radius: .5rem; font-size: .875rem; cursor: pointer; }
        .seg input:checked + label { border-color: var(--primary); background: var(--primary-soft); color: var(--primary-text); }
        .seg input:focus-visible + label { outline: 2px solid var(--primary); outline-offset: 2px; }
        .notice { border-radius: .75rem; border: 1px solid var(--green-border); background: var(--green-bg); color: var(--green); padding: 1rem; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }
        .result-body { padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 1rem; }
        .qr-box { background: #fff; padding: .75rem; border-radius: .75rem; border: 1px solid var(--border); line-height: 0; }
        .qr-box canvas { width: 280px; max-width: 100%; height: auto; image-rendering: pixelated; }
        .amount-big { font-size: 1.5rem; font-weight: 700; }
        .muted { color: var(--muted); font-size: .875rem; text-align: center; }
        .code { width: 100%; font: .75rem/1.5 ui-monospace, SFMono-Regular, Menlo, monospace; background: var(--card-head); border: 1px solid var(--border); border-radius: .5rem; padding: .75rem; word-break: break-all; }
        .result-body .row { width: 100%; margin-top: 0; }
        footer { border-top: 1px solid var(--border); padding: 1.5rem 0; text-align: center; font-size: .875rem; color: var(--muted); }
        footer p { margin: .25rem 0; }
        footer a { color: var(--primary); text-decoration: none; }
        footer a:hover { text-decoration: underline; }
        footer small { font-size: .75rem; color: var(--subtle); }
        .hidden { display: none !important; }
        .sr { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); }
    </style>
</head>
<body>

<header>
    <div class="wrap">
        <a class="brand" href="./"><b>QRIS Dinamis</b><span class="pill">v2.0 · PHP</span></a>
        <div>
            <a class="icon-btn" href="https://github.com/verssache/qris-dinamis" target="_blank" rel="noopener noreferrer" aria-label="GitHub">
                <svg class="i" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .3a12 12 0 0 0-3.8 23.4c.6.1.8-.3.8-.6v-2c-3.3.7-4-1.6-4-1.6-.6-1.4-1.4-1.8-1.4-1.8-1-.7.1-.7.1-.7 1.2 0 1.9 1.2 1.9 1.2 1 1.8 2.8 1.3 3.5 1 0-.8.4-1.3.7-1.6-2.7-.3-5.5-1.3-5.5-6 0-1.2.5-2.3 1.3-3.1-.2-.4-.6-1.6 0-3.2 0 0 1-.3 3.4 1.2a11.5 11.5 0 0 1 6 0c2.3-1.5 3.3-1.2 3.3-1.2.6 1.6.2 2.8 0 3.2.9.8 1.3 1.9 1.3 3.2 0 4.6-2.8 5.6-5.5 5.9.5.4.9 1 .9 2.2v3.3c0 .3.1.7.8.6A12 12 0 0 0 12 .3"/></svg>
            </a>
            <button class="icon-btn" id="themeBtn" type="button" aria-label="Toggle theme">
                <svg class="i" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75 9.75 9.75 0 0 1 8.25 6c0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25 9.75 9.75 0 0 0 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
            </button>
        </div>
    </div>
</header>

<main class="wrap">

    <!-- Input QRIS -->
    <form method="post" id="parseForm" autocomplete="off">
        <input type="hidden" name="action" value="parse">
        <label class="lbl" for="qris">QRIS String</label>
        <div class="drop <?= $inputState ?>" id="drop">
            <textarea id="qris" name="qris" rows="3" spellcheck="false"
                      placeholder="Paste QRIS string here, or drag &amp; drop a QR image..."><?= e($qris) ?></textarea>
            <a href="./" class="icon-btn clear <?= $qris === '' ? 'hidden' : '' ?>" id="clearBtn" aria-label="Clear">
                <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </a>
        </div>

        <?php if ($errors): ?>
            <div class="errors" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><span>&#x2717;</span><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row">
            <button type="button" class="btn" id="uploadBtn">
                <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.41a2.25 2.25 0 0 1 3.182 0l2.909 2.91m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                Upload Image
            </button>
            <button type="button" class="btn" id="cameraBtn">
                <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z"/></svg>
                <span>Scan Camera</span>
            </button>
            <button type="submit" class="btn primary">Check QRIS</button>
            <input type="file" id="fileInput" accept="image/*" class="hidden">
        </div>

        <div class="camera hidden" id="camera">
            <video id="video" playsinline muted></video>
            <div class="frame"><div></div></div>
            <div class="hint">Point camera at a QRIS code</div>
        </div>
    </form>

    <?php if ($parsed): $issuer = $parsed->merchantAccountInfo[0]['globallyUniqueId'] ?? '-'; ?>
        <!-- Info QRIS -->
        <section class="card">
            <div class="card-head">
                <h2>
                    <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                    QRIS Information
                </h2>
            </div>
            <div class="info-row"><span>Merchant</span><span><?= e($parsed->merchantName) ?></span></div>
            <div class="info-row"><span>City</span><span><?= e($parsed->merchantCity) ?></span></div>
            <div class="info-row"><span>Postal Code</span><span><?= e($parsed->postalCode ?: '-') ?></span></div>
            <div class="info-row"><span>Issuer</span><span><?= e($issuer) ?></span></div>
            <div class="info-row"><span>Method</span><span><span class="badge <?= $parsed->method ?>"><?= $parsed->isDynamic() ? 'Dynamic' : 'Static' ?></span></span></div>
            <div class="info-row"><span>Category</span><span><?= e(MCC_MAP[$parsed->merchantCategoryCode] ?? $parsed->merchantCategoryCode) ?></span></div>
            <div class="info-row"><span>Currency</span><span><?= e(CURRENCY_MAP[$parsed->currency] ?? $parsed->currency) ?></span></div>
            <?php if ($parsed->amount): ?>
                <div class="info-row"><span>Amount</span><span><?= e(rupiah($parsed->amount)) ?></span></div>
            <?php endif; ?>
        </section>

        <?php if ($parsed->isDynamic()): ?>
            <div class="notice">
                <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                This QRIS is already dynamic with amount <?= e(rupiah($parsed->amount ?? 0)) ?>
            </div>
        <?php else: ?>
            <!-- Form konversi -->
            <form method="post" class="card" id="convertForm">
                <input type="hidden" name="action" value="convert">
                <input type="hidden" name="qris" value="<?= e($qris) ?>">
                <div class="card-head">
                    <h2>
                        <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                        Convert to Dynamic
                    </h2>
                </div>
                <div class="body">
                    <div class="field">
                        <label for="amount">Amount</label>
                        <div class="input">
                            <span class="affix">Rp</span>
                            <input id="amount" name="amount" type="number" inputmode="numeric" min="1" step="1" required placeholder="25000" value="<?= e($amount) ?>">
                        </div>
                    </div>

                    <div class="field">
                        <label>Service Fee</label>
                        <div class="seg">
                            <?php foreach (['none' => 'None', 'fixed' => 'Fixed', 'percentage' => 'Percentage'] as $k => $v): ?>
                                <input type="radio" name="fee_type" id="fee_<?= $k ?>" value="<?= $k ?>" <?= $feeType === $k ? 'checked' : '' ?>>
                                <label for="fee_<?= $k ?>"><?= $v ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field <?= $feeType === 'none' ? 'hidden' : '' ?>" id="feeField">
                        <label for="fee_value" id="feeLabel"><?= $feeType === 'percentage' ? 'Fee Percentage' : 'Fee Amount' ?></label>
                        <div class="input">
                            <span class="affix <?= $feeType === 'percentage' ? 'hidden' : '' ?>" id="feePrefix">Rp</span>
                            <input id="fee_value" name="fee_value" type="number" inputmode="decimal" min="0" step="any" placeholder="<?= $feeType === 'percentage' ? '2.5' : '1000' ?>" value="<?= e($feeValue) ?>">
                            <span class="affix <?= $feeType === 'percentage' ? '' : 'hidden' ?>" id="feeSuffix">%</span>
                        </div>
                    </div>

                    <button type="submit" class="btn primary">Generate Dynamic QRIS</button>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($result && $resultData): ?>
        <!-- Hasil -->
        <section class="card" id="result">
            <div class="card-head">
                <h2>
                    <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/></svg>
                    Dynamic QRIS
                </h2>
            </div>
            <div class="result-body">
                <div class="qr-box"><canvas id="qrCanvas" data-qris="<?= e($result) ?>" aria-label="QR code"></canvas></div>
                <div style="text-align:center">
                    <div class="muted"><?= e($resultData->merchantName) ?></div>
                    <div class="amount-big"><?= e(rupiah($resultData->amount)) ?></div>
                    <?php if ($resultData->tipIndicator === 'fixed'): ?>
                        <div class="muted">+ fee <?= e(rupiah($resultData->tipFixed)) ?></div>
                    <?php elseif ($resultData->tipIndicator === 'percentage'): ?>
                        <div class="muted">+ fee <?= e($resultData->tipPercentage) ?>%</div>
                    <?php endif; ?>
                </div>
                <div class="code" id="resultText"><?= e($result) ?></div>
                <div class="row">
                    <button type="button" class="btn" id="copyBtn">
                        <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                        <span>Copy String</span>
                    </button>
                    <button type="button" class="btn primary" id="downloadBtn"
                            data-name="qris-dynamic-<?= e(strtolower(preg_replace('/\s+/', '-', $resultData->merchantName))) ?>.png">
                        <svg class="s" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Download QR
                    </button>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer>
    <div class="wrap">
        <p>Made by <a href="https://github.com/verssache" target="_blank" rel="noopener noreferrer">Gidhan</a></p>
        <p><small>QRIS is a standardized QR Code payment system by Bank Indonesia</small></p>
    </div>
</footer>

<script src="assets/jsQR.js"></script>
<script src="assets/qrcode.js"></script>
<script>
(function () {
    'use strict';

    // ---------- Tema ----------
    document.getElementById('themeBtn').addEventListener('click', function () {
        var dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
    });

    // ---------- Input QRIS ----------
    var form = document.getElementById('parseForm');
    var textarea = document.getElementById('qris');
    var drop = document.getElementById('drop');
    var fileInput = document.getElementById('fileInput');

    function submitQris(value) {
        textarea.value = value;
        form.submit();
    }

    // Tempel string lalu otomatis cek
    textarea.addEventListener('paste', function (e) {
        var text = e.clipboardData && e.clipboardData.getData('text');
        if (text && text.trim().indexOf('000201') === 0) {
            e.preventDefault();
            submitQris(text.trim());
        }
    });
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.submit(); }
    });

    function decodeImageData(imageData) {
        return jsQR(imageData.data, imageData.width, imageData.height);
    }

    function decodeImageFile(file) {
        var reader = new FileReader();
        reader.onload = function () {
            var img = new Image();
            img.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                var code = decodeImageData(ctx.getImageData(0, 0, canvas.width, canvas.height));
                if (code) {
                    submitQris(code.data);
                } else {
                    alert('QR code not found in image. Please try another image.');
                }
            };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    }

    document.getElementById('uploadBtn').addEventListener('click', function () { fileInput.click(); });
    fileInput.addEventListener('change', function () {
        if (fileInput.files[0]) decodeImageFile(fileInput.files[0]);
        fileInput.value = '';
    });

    // Drag & drop
    drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('over'); });
    drop.addEventListener('dragleave', function () { drop.classList.remove('over'); });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        drop.classList.remove('over');
        var f = e.dataTransfer.files[0];
        if (f && f.type.indexOf('image/') === 0) decodeImageFile(f);
    });

    // Tempel screenshot (Ctrl+V / Cmd+V)
    document.addEventListener('paste', function (e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image/') === 0) {
                var f = items[i].getAsFile();
                if (f) { e.preventDefault(); decodeImageFile(f); return; }
            }
        }
    });

    // ---------- Kamera ----------
    var cameraBtn = document.getElementById('cameraBtn');
    var cameraBox = document.getElementById('camera');
    var video = document.getElementById('video');
    var scanCanvas = document.createElement('canvas');
    var stream = null, raf = 0;

    function stopCamera() {
        if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
        cancelAnimationFrame(raf);
        cameraBox.classList.add('hidden');
        cameraBtn.classList.remove('danger');
        cameraBtn.querySelector('span').textContent = 'Scan Camera';
    }

    function scan() {
        if (!stream) return;
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
            scanCanvas.width = video.videoWidth;
            scanCanvas.height = video.videoHeight;
            var ctx = scanCanvas.getContext('2d');
            ctx.drawImage(video, 0, 0);
            var code = decodeImageData(ctx.getImageData(0, 0, scanCanvas.width, scanCanvas.height));
            if (code) { stopCamera(); submitQris(code.data); return; }
        }
        raf = requestAnimationFrame(scan);
    }

    cameraBtn.addEventListener('click', function () {
        if (stream) { stopCamera(); return; }
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Camera access denied or unavailable.');
            return;
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (s) {
            stream = s;
            cameraBox.classList.remove('hidden');
            cameraBtn.classList.add('danger');
            cameraBtn.querySelector('span').textContent = 'Stop Camera';
            video.srcObject = s;
            return video.play();
        }).then(scan).catch(function () {
            stopCamera();
            alert('Camera access denied or unavailable.');
        });
    });

    // ---------- Form fee ----------
    var convertForm = document.getElementById('convertForm');
    if (convertForm) {
        var feeField = document.getElementById('feeField');
        var feeLabel = document.getElementById('feeLabel');
        var feeInput = document.getElementById('fee_value');
        var prefix = document.getElementById('feePrefix');
        var suffix = document.getElementById('feeSuffix');
        convertForm.querySelectorAll('input[name="fee_type"]').forEach(function (r) {
            r.addEventListener('change', function () {
                var t = r.value;
                feeField.classList.toggle('hidden', t === 'none');
                var pct = t === 'percentage';
                feeLabel.textContent = pct ? 'Fee Percentage' : 'Fee Amount';
                feeInput.placeholder = pct ? '2.5' : '1000';
                prefix.classList.toggle('hidden', pct);
                suffix.classList.toggle('hidden', !pct);
            });
        });
    }

    // ---------- Render hasil QR ----------
    var qrCanvas = document.getElementById('qrCanvas');
    if (qrCanvas) {
        var qr = qrcode(0, 'M');
        qr.addData(qrCanvas.getAttribute('data-qris'));
        qr.make();
        var count = qr.getModuleCount();
        var margin = 2, scale = Math.max(4, Math.floor(560 / (count + margin * 2)));
        var size = (count + margin * 2) * scale;
        qrCanvas.width = size;
        qrCanvas.height = size;
        var c = qrCanvas.getContext('2d');
        c.fillStyle = '#ffffff';
        c.fillRect(0, 0, size, size);
        c.fillStyle = '#000000';
        for (var r = 0; r < count; r++) {
            for (var col = 0; col < count; col++) {
                if (qr.isDark(r, col)) c.fillRect((col + margin) * scale, (r + margin) * scale, scale, scale);
            }
        }

        document.getElementById('downloadBtn').addEventListener('click', function () {
            var a = document.createElement('a');
            a.download = this.getAttribute('data-name');
            a.href = qrCanvas.toDataURL('image/png');
            a.click();
        });

        var copyBtn = document.getElementById('copyBtn');
        copyBtn.addEventListener('click', function () {
            var text = document.getElementById('resultText').textContent;
            var label = copyBtn.querySelector('span');
            var done = function () {
                label.textContent = 'Copied!';
                setTimeout(function () { label.textContent = 'Copy String'; }, 2000);
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(done);
            } else {
                var ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); ta.remove(); done();
            }
        });

        document.getElementById('result').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
</body>
</html>
