# Changelog

Semua perubahan penting dicatat di file ini.
Format mengikuti [Keep a Changelog](https://keepachangelog.com/id-ID/1.1.0/) dan proyek ini memakai [Semantic Versioning](https://semver.org/lang/id/).

## [1.0.0] - 2026-09-29

### Ditambahkan
- Rilis pertama: port PHP dari [verssache/qris-dinamis](https://github.com/verssache/qris-dinamis).
- `Qris::convert()` — konversi QRIS statis ke dinamis, dengan biaya layanan tetap atau persen.
- `Qris::parse()` — parse QRIS menjadi `QrisData` (termasuk `issuer()`, `nmid()`, `toArray()`).
- `Qris::validate()` / `Qris::isValid()` / `Qris::assertValid()` — validasi struktur dan CRC16.
- `InvalidQrisException` dengan `getErrors()`.
- CLI `vendor/bin/qris` (interaktif dan mode langsung).
- Web demo dan JSON API di `examples/web`.
- Contoh pemakaian dan integrasi Laravel di `examples/`.

[1.0.0]: https://github.com/dhank77/qris-dinamis/releases/tag/v1.0.0
