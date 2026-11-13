# Hasil pemeriksaan

Pemeriksaan dilakukan 13 November 2026 pada salinan pengujian terpisah; paket rilis tidak memuat admin, pesanan, atau stok uji.

- Sintaks JavaScript: lulus node --check.
- Sintaks PHP: lulus parser PHP 8.3.
- Integrasi API pada runtime PHP 8.3 WebAssembly: lulus setup admin, login, logout, ubah password, penolakan akses tanpa autentikasi, dan token CSRF salah.
- Lulus penyimpanan stok, penyembunyian stok rahasia dari katalog publik, kunci cek pesanan salah, pesanan ulang dengan idempotency key yang sama, dan penolakan pembelian saat stok habis.
- Lulus Paid → pengurangan tepat satu stok → pengiriman otomatis. Pengulangan Paid ditolak.
- Lulus pengiriman manual: unit teralokasi tidak muncul ke pelanggan sampai admin memilih Kirim produk.
- Lulus penolakan penyimpanan dengan nomor versi data usang.
- Browser: katalog desktop, filter kategori, pencarian, mode terang/gelap diperiksa. Layar 390px diperiksa melalui iframe; dua kolom produk dan tidak ada horizontal overflow (375px area konten).

Batas pemeriksaan: belum di-deploy ke akun cPanel, Vercel, atau GitHub milik pengguna. Pengujian PHP memakai runtime WebAssembly, bukan server Apache cPanel nyata. Izin folder, aturan .htaccess, HTTPS, dan pengiriman chat WhatsApp perlu diuji setelah instalasi. Tampilan panel admin diperiksa melalui kode; alur API admin diuji otomatis. Tidak ada transaksi pembayaran atau pesan WhatsApp yang dikirim selama pengujian.
