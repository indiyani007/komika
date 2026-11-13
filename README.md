# Warung Techno — Petunjuk instalasi

Website toko aplikasi premium, katalog responsif, tema terang/gelap, pemesanan WhatsApp, dan panel admin PHP. Produk, harga, durasi, dan deskripsi awal adalah CONTOH; stok awal kosong dan nomor WhatsApp belum diisi. Tinjau dan sesuaikan sebelum berjualan.

## Pilih hosting

| Hosting | Katalog + WhatsApp | Panel admin + stok + pesanan |
|---|---|---|
| cPanel dengan PHP 8.1+ | Ya | Ya |
| Vercel, paket ini | Ya, versi statis di docs/ | Tidak |
| GitHub Pages | Ya, versi statis di docs/ | Tidak |

Paket tidak mengklaim PHP dapat berjalan langsung di GitHub Pages. Vercel memakai ekspor statis, bukan runtime PHP atau database serverless. Tidak ada sinkronisasi otomatis antara versi cPanel dan statis. Untuk seluruh fitur gunakan cPanel. Full backend Vercel memerlukan implementasi serverless + database persisten tersendiri, di luar paket ini.

## 1. Instalasi cPanel (fitur lengkap)

1. Buat cadangan website lama sebelum mengunggah ke folder yang sudah dipakai.
2. Aktifkan PHP 8.1 atau lebih baru (disarankan PHP 8.3+) dan HTTPS/SSL di hosting.
3. Upload file ROOT paket ke public_html atau subfolder: index.html, style.css, script.js, api.php, data.json, config.php, favicon.svg, dan .htaccess. Folder docs/, vercel.json, README.md, dan laporan pengujian tidak diperlukan di cPanel. Pastikan .htaccess ikut diunggah (tampilkan hidden files di File Manager).
4. Folder harus dapat ditulis oleh proses PHP. Umumnya direktori 755 dan file 644 cukup pada cPanel; jangan gunakan 777. Hubungi hosting jika muncul gagal menyimpan.
5. Buka https://domain-anda/index.html#admin (atau alamat subfolder).
6. Lihat nilai setup_key pada config.php lewat File Manager cPanel, salin ke kolom kunci instalasi. Jangan membagikan kunci atau config.php. Buat password admin minimal 12 karakter. Setup hanya bisa sekali.
7. Pengaturan → isi nomor WhatsApp format internasional tanpa tanda +, contoh 628123456789. Isi logo/favicon, judul, deskripsi, banner, footer, dan link Telegram bila digunakan.
8. Produk & stok → ubah contoh produk, harga/durasi, tambah foto, aktifkan/nonaktifkan produk, dan masukkan stok nyata. Satu baris = satu unit. Untuk informasi lebih dari satu bagian, pisahkan dengan ` | ` pada baris yang sama.
9. Uji satu pemesanan sendiri, pastikan chat WhatsApp benar, salin tautan cek pesanan, lalu konfirmasi Paid di panel dan cek pengiriman. Jangan gunakan data pelanggan saat pengujian.

Tidak ada password bawaan publik. Jika lupa password, minta administrator hosting memperbarui hash password pada salinan cadangan storage.php dengan password_hash PHP. Jangan menghapus storage.php untuk reset password karena file ini memuat seluruh stok dan pesanan.

## 2. Alur pemesanan dan pengiriman

- Pelanggan memilih produk tanpa mengisi nomor WhatsApp.
- Buat pesanan → sistem memberi kode dan kunci akses acak → pelanggan menekan Lanjut chat WhatsApp.
- Pelanggan menyimpan tautan cek pesanan. Kunci tidak dimasukkan dalam pesan WhatsApp otomatis.
- Pembayaran ditangani secara manual melalui percakapan. Tidak ada payment gateway/webhook; status Paid hanya boleh ditetapkan setelah admin memverifikasi pembayaran.
- Stok belum dicadangkan pada pesanan pending. Satu unit stok dialokasikan ketika admin menandai Paid. Bila stok habis, Paid ditolak. Jangan menerima pembayaran sebelum memastikan stok tersedia.
- **Otomatis aktif:** data stok langsung muncul di cek pesanan setelah Paid.
- **Otomatis nonaktif:** stok dialokasikan saat Paid, tetapi baru terlihat oleh pelanggan setelah admin menekan Kirim produk.
- Ini pengiriman melalui halaman cek pesanan, bukan bot pengirim pesan WhatsApp atau Telegram. Otomatisasi chat memerlukan API layanan tersebut dan konfigurasi tambahan.
- Pending dapat dibatalkan. Paid tidak dapat dibatalkan agar unit yang sudah diterima tidak dijual ulang. Refund/restok harus ditangani terpisah dengan unit yang dipastikan valid.
- Pesanan paid/cancel dapat dihapus permanen setelah konfirmasi; penghapusan menghentikan tautan cek pesanan, tidak mengembalikan stok dan mengurangi metrik dashboard yang dihitung dari pesanan tersimpan.
- Pelanggan tanpa kunci akses dapat menghubungi admin. Admin bisa memeriksa detail produk teralokasi di Daftar pesanan → Detail, lalu melakukan bantuan secara manual.

## 3. Vercel (katalog statis)

1. Edit docs/data.json: isi settings.whatsapp, harga, kategori, deskripsi, banner, logo/favicon, halaman, dan stockCount yang ditampilkan. Jangan taruh isi akun/lisensi, password, atau pesanan di JSON publik.
2. Upload proyek ke repository GitHub. config.php dan storage*.php dikecualikan oleh .gitignore; jangan paksa mengunggahnya.
3. Import repository ke Vercel. Pilih Other, tanpa build command, output directory `docs`. vercel.json sudah menetapkan output tersebut.
4. Katalog, pencarian, filter, tema, halaman, WhatsApp, dan link bot Telegram berfungsi. Admin dan cek pesanan memberi penjelasan bahwa backend diperlukan. Pada versi ini tombol WhatsApp langsung menghubungi admin tanpa mencatat order server.

## 4. GitHub Pages (katalog statis)

1. Gunakan folder docs/ sebagai source Pages: Settings → Pages → Deploy from a branch → main → /docs. File .nojekyll disertakan.
2. Alternatif: upload HANYA isi docs/ ke root repository baru lalu pilih /root. Jangan unggah config.php atau storage*.php ke repository publik.
3. Edit data.json di sumber Pages yang dipilih. Tunggu deployment selesai. Path aset relatif mendukung repository subpath.

## 5. Perubahan katalog dan pengaturan

- Pada cPanel, setelah setup, data aktif berada di storage.php. Mengedit data.json tidak mengganti data aktif.
- Panel → Pengaturan → Unduh data.json untuk mengekspor katalog publik terbaru. Ganti docs/data.json dengan file tersebut untuk memperbarui hosting statis. Stok rahasia/pesanan/password tidak diekspor.
- Foto/logo/favicon dapat memakai HTTPS URL atau upload JPG/PNG/WebP. Upload diperkecil maksimal 800px dan disimpan sebagai data gambar; ukuran file maksimum 5 MB sebelum kompresi. SVG upload tidak diterima.
- Link Telegram memakai https://t.me/nama_bot atau link deep-link bot. Ini hanya tautan; bot tidak dibuat oleh website.
- Halaman tambahan berisi teks biasa, bukan HTML, untuk mencegah script tak diinginkan. Cek pesanan merupakan halaman bawaan.
- Kode Google di Pengaturan mengubah meta tag lewat JavaScript. Untuk verifikasi Search Console yang konsisten gunakan DNS, atau masukkan kode pada atribut content meta google-site-verification di index.html dan docs/index.html. Judul/deskripsi SEO statis dapat disamakan secara manual pada bagian head.
- Font dimuat dari Google Fonts; jika koneksi font tidak tersedia, browser memakai sans-serif lokal.

## 6. Penyimpanan, keamanan, dan operasional

- data.json hanya katalog publik. storage.php berisi password ter-hash, stok rahasia, pesanan, dan penghitung rate limit. File ini diawali pelindung PHP dan diblokir melalui .htaccess. Jangan mengunggahnya ke hosting statis, Git publik, atau membagikannya.
- config.php berisi kunci setup unik. storage.lock.php/storage.tmp.php merupakan file internal.
- PHP sessions memakai cookie HttpOnly, SameSite Strict, Secure pada HTTPS; sesi admin 2 jam. Request mutasi memakai token CSRF, login dibatasi 10 percobaan/15 menit per alamat IP, pembuatan order 20/15 menit, cek order 120/15 menit. Di balik proxy, beberapa pelanggan bisa berbagi batas IP; konfigurasi server diperlukan untuk skala lebih besar.
- Penulisan menggunakan penguncian file dan penggantian atomik; perubahan katalog/stok menggunakan nomor versi untuk menolak penyimpanan data usang.
- Backup storage.php dan config.php melalui penyimpanan privat hosting secara rutin. Jangan menyimpan salinan .bak di public_html. Restore saat toko dihentikan sementara.
- Sistem berkas JSON cocok untuk toko kecil pada satu server. Untuk banyak pesanan, tim admin besar, transaksi kompleks, atau multi-server, migrasikan ke database transaksional dan pencadangan terkelola.
- Selalu gunakan lisensi/produk yang Anda berhak distribusikan dan jelaskan ketentuan lisensi kepada pembeli.

## Isi utama

index.html — halaman dan kerangka aplikasi
style.css — desain responsif dan tema
script.js — katalog, checkout WhatsApp, cek pesanan, panel admin
api.php — autentikasi, validasi, stok, pesanan, penyimpanan
 data.json — contoh katalog publik (tanpa data rahasia)
config.php — kunci instalasi unik
favicon.svg — ikon default
.htaccess — konfigurasi Apache dan proteksi file
vercel.json — output statis Vercel
docs/ — file publik untuk Vercel / GitHub Pages

Referensi platform: https://docs.github.com/en/pages/getting-started-with-github-pages/creating-a-github-pages-site dan https://vercel.com/docs/functions/runtimes
