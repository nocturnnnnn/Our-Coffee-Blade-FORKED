# Our Coffee — Sistem Operasional & Point of Sale Kafe Modern (100% Native Laravel Blade)

> Dikonversi 1:1 menjadi **100% Native Laravel Blade** (`return view(...)` + `.blade.php`) dengan **Alpine.js 3** dan **Tailwind CSS v4** — sepenuhnya bebas dari React, Vue, Inertia.js, maupun TypeScript.

**Our Coffee** adalah platform operasional kafe terpadu (_all-in-one cafe operations platform_) yang dirancang untuk menjembatani seluruh alur bisnis kedai kopi modern—mulai dari pemesanan mandiri oleh pelanggan melalui **Self-Ordering Kiosk**, pencatatan transaksi dan kasir melalui **POS Kasir**, visualisasi pesanan dapur secara real-time via **Kitchen Display System (KDS)**, monitor antrean publik (**Public Queue Display**), hingga manajemen inventaris, resep Bill of Materials (BOM), dan analisis bisnis di **Backoffice Admin**.

Aplikasi ini dibangun menggunakan arsitektur modern berbasis **Laravel 13**, **Native Laravel Blade**, **Alpine.js 3**, **Tailwind CSS v4**, dan basis data relasional **MariaDB / MySQL**.

---

## Daftar Isi

1. [Tangkapan Layar Antarmuka (Screenshots)](#tangkapan-layar-antarmuka-screenshots)
2. [Fitur Utama & Modul Sistem](#fitur-utama--modul-sistem)
3. [Panduan Operasional Pengguna](#panduan-operasional-pengguna)
    - [1. Alur Self-Ordering Kiosk (Pelanggan)](#1-alur-self-ordering-kiosk-pelanggan)
    - [2. Alur POS Kasir & Manajemen Shift (Kasir)](#2-alur-pos-kasir--manajemen-shift-kasir)
    - [3. Alur Kitchen Display System (Barista)](#3-alur-kitchen-display-system-barista)
    - [4. Alur Layar Antrean Publik (Pelanggan / Ruang Tunggu)](#4-alur-layar-antrean-publik-pelanggan--ruang-tunggu)
    - [5. Alur Backoffice & Manajemen Kafe (Owner / Admin)](#5-alur-backoffice--manajemen-kafe-owner--admin)
4. [Hak Akses Pengguna (RBAC)](#hak-akses-pengguna-rbac)
5. [Teknologi & Standar Desain](#teknologi--standar-desain)
6. [Arsitektur Sistem & Database](#arsitektur-sistem--database)
7. [Panduan Instalasi & Menjalankan Aplikasi](#panduan-instalasi--menjalankan-aplikasi)
8. [Akun Bawaan & Pengujian Kiosk](#akun-bawaan--pengujian-kiosk)
9. [Uji Kualitas & Otomatisasi Tes](#uji-kualitas--otomatisasi-tes)

---

## Tangkapan Layar Antarmuka (Screenshots)

Berikut adalah dokumentasi visual antarmuka pengguna (**User Interface**) sistem **Our Coffee**:

### 1. Backoffice & Manajemen Operasional

|              Halaman Login Staf & Admin               |                    Dashboard Operasional Admin Real-Time                     |
| :---------------------------------------------------: | :--------------------------------------------------------------------------: |
|    ![Halaman Login](docs/screenshots/01-login.png)    |         ![Dashboard Admin](docs/screenshots/02-dashboard-admin.png)          |
| _Autentikasi aman staf dengan logo ikonik Our Coffee_ | _KPI Omzet, tren penjualan 7/30 hari, heatmap jam sibuk & margin laba kotor_ |

### 2. Self-Ordering Kiosk (Layar Pelanggan)

|             Layar Awal (Idle / Welcome Screen)             |               Pemilihan Tipe Pesanan & Nama Pelanggan               |
| :--------------------------------------------------------: | :-----------------------------------------------------------------: |
| ![Layar Awal Kiosk](docs/screenshots/03-kiosk-welcome.png) | ![Pemilihan Tipe Pesanan](docs/screenshots/04-kiosk-order-type.png) |
|     _Layar sambutan mandiri ramah sentuhan pelanggan_      |   _Pemilihan Dine-In / Takeaway dan identitas panggilan antrean_    |

|                     Katalog Menu Kiosk & Navigasi Kategori                      |
| :-----------------------------------------------------------------------------: |
|            ![Katalog Menu Kiosk](docs/screenshots/05-kiosk-menu.png)            |
| _Tampilan menu visual dengan kategori rail, harga, deskripsi, dan badge produk_ |

### 3. POS Kasir (Point of Sale)

|                            Antarmuka Kasir POS & Antrean Kiosk                            |
| :---------------------------------------------------------------------------------------: |
|                 ![Antarmuka POS Kasir](docs/screenshots/06-pos-kasir.png)                 |
| _Kasir Walk-In, Antrean Bayar Kiosk, Riwayat Shift, faktur, diskon PIN, dan NumPad tunai_ |

---

## Fitur Utama & Modul Sistem

### 1. Self-Ordering Kiosk (`/kiosk`)

- **Pairing Perangkat Kiosk**: Otorisasi tablet/perangkat pemesanan di lobi melalui tautan unik `/kiosk/pair/{token}` yang menanamkan cookie perangkat HTTP-Only tanpa mengharuskan staf login manual pada perangkat pelanggan.
- **Alur Pemesanan 4 Layar Terpadu**:
    1. **Welcome Screen**: Layar pembuka ramah sentuhan.
    2. **Tipe Pesanan & Identitas**: Pemilihan _Dine-In_ atau _Takeaway_ + nama panggilan antrean (opsional).
    3. **Menu, Modifier Sheet & Keranjang**: Eksplorasi kategori, kustomisasi modifier (suhu, level gula, jenis susu, ekstra topping), catatan item, keranjang drawer, dan modal pemilihan pembayaran (**QRIS Instant** dengan SVG QR deterministik 100% offline + hitung mundur kedaluwarsa & simulasi bayar demo lokal, atau **Bayar di Kasir**).
    4. **Konfirmasi Antrean**: Menampilkan nomor antrean (`#A-001`) dengan auto-reset hitung mundur otomatis kembali ke layar pembuka.
- **Keamanan & Ketahanan Kiosk**:
    - Target sentuh ramah jari (`min-h-12` / `min-h-14`).
    - _Idle Timeout Watchdog_: Otomatis mengulang sesi ke layar awal jika ditinggalkan (`kiosk_idle_timeout_seconds`), disertai peringatan hitung mundur (`kiosk_idle_warning_seconds`).
    - _Hidden Admin Exit_: Ketuk 5 kali pada label nama perangkat di pojok kiri atas untuk membuka PIN Pad 6-digit Admin berkecepatan aman (`throttle:15,1`) guna keluar dari mode kiosk menuju halaman login staf.
- **Sinkronisasi Stok Real-Time**: Pengecekan ketersediaan menu otomatis setiap 15 detik (`/kiosk/availability`, pause saat tab tidak aktif) dan proteksi _atomic 409 Conflict_ saat checkout jika bahan baku mendadak habis.

### 2. POS Kasir & Manajemen Shift (`/pos`, `/pos/shift`)

- **Kontrol Shift Kasir Berintegritas Tinggi**:
    - Kasir diwajibkan membuka shift dengan memasukkan saldo modal kas awal (_opening cash_) sebelum dapat mengakses layar POS (`shift.open` middleware).
    - Penutupan shift dilengkapi kalkulator rekonsiliasi real-time antara kas sistem dengan uang fisik aktual di laci kasir beserta pencatatan alasan selisih (_cash variance tracking_).
- **Antarmuka 3 Tab Operasional**:
    - **Kasir Walk-In**: Katalog menu cepat dengan pencarian real-time, filter kategori, modal modifier, pemilihan _Dine-In_ / _Takeaway_, keranjang aktif, dan kustomisasi diskon manual (wajib 6-digit PIN Admin).
    - **Antrean Bayar Kiosk**: Daftar pesanan Kiosk berstatus belum bayar dengan polling otomatis setiap 5 detik (`/pos/queue`) dan notifikasi audio (_chime_) saat pesanan baru masuk.
    - **Riwayat Shift**: Daftar transaksi shift berjalan lengkap dengan tombol cetak ulang struk dan **Void Transaksi**.
- **Pembayaran Multi-Metode & NumPad Sentuh**: Mendukung pembayaran **Tunai** (dengan papan NumPad sentuh, tombol uang pas & nominal cepat 50rb/100rb/200rb, serta kalkulasi kembalian), **Kartu EDC**, dan **QRIS Statis**.
- **Pencetakan Struk Termal**: Tata letak cetak nota profesional `.thermal-receipt-print` yang mendukung pilihan lebar kertas **58mm** dan **80mm** via `@media print`.
- **Void Pesanan Terproteksi**: Pembatalan transaksi wajib mengisi alasan dan 6-digit PIN Supervisor/Admin (`throttle:15,1`), mengembalikan stok bahan baku secara atomik, dan mencatat ke audit log.

### 3. Kitchen Display System / KDS (`/kds`)

- **Papan Kanban FIFO 3 Kolom**: Tiket pesanan tersusun urut waktu bayar pada kolom **Antre / Pesanan Masuk** (`paid`), **Sedang Dibuat** (`preparing`), dan **Siap Diambil** (`ready`) dengan sinkronisasi otomatis setiap 4 detik (`/kds/tickets`).
- **Ergonomi Barista & Undo 1 Langkah**:
    - _Timer SLA Real-Time_: Stopwatch detik `MM:SS` per tiket dengan sorotan peringatan merah jika waktu tunggu melampaui batas.
    - Sorotan rincian modifier dan catatan pesanan per item minuman.
    - Tombol **Undo 1 Langkah** langsung pada kartu tiket (`preparing -> paid` dan `ready -> preparing`) untuk memperbaiki salah ketuk tanpa meninggalkan layar.
    - Toggle suara notifikasi Web Audio API yang membunyikan lonceng lembut setiap kali pesanan baru masuk ke kolom Antre.

### 4. Layar Antrean Publik (`/display/queue`)

- Layar monitor ruang tunggu beresolusi tinggi dengan tema kontras Espresso & Emerald tanpa autentikasi.
- Menampilkan daftar nomor antrean **Sedang Disiapkan** dan **Siap Diambil**, lengkap dengan jam digital WIB real-time, toggle suara panggilan, dan polling otomatis setiap 4 detik (`/display/queue/feed`).

### 5. Backoffice Admin (`/admin/*`)

- **Dashboard Eksekutif (`/admin/dashboard`)**: Auto-refresh setiap 30 detik (`/admin/dashboard/metrics`) dengan toggle rentang **7 Hari / 30 Hari**, 4 kartu KPI harian, grafik batang **Tren Omzet**, grafik donut SVG **Kontribusi Channel** (Kiosk vs POS), **Heatmap Jam Sibuk 24 Jam**, tabel **Top 5 Menu & Margin**, dan widget **Peringatan Stok Menipis**.
- **Katalog & Resep BOM (`/admin/categories`, `/admin/menus`, `/admin/modifiers`)**: Master data kategori, menu (termasuk upload foto, badge `Best Seller`/`New`, toggle ketersediaan manual), grup/opsi modifier terhubung bahan baku, serta penyusun resep _Bill of Materials_ (BOM) dengan pratinjau estimasi HPP (COGS) & margin otomatis.
- **Inventaris & Distributor (`/admin/inventory`, `/admin/distributors`)**: Stok bahan baku multi-satuan (`g`, `ml`, `pcs`) dengan indikator bar level stok, Stock In (kalkulasi _weighted average cost_), Waste, Stock Opname fisik, serta buku besar mutasi stok permanen (`stock_movements`) lengkap dengan filter bahan & tipe mutasi.
- **Laporan Finansial (`/admin/reports`)**: Filter rentang tanggal untuk melihat omzet bersih, diskon, HPP (COGS), laba kotor, margin per hari maupun per menu, serta **Ekspor CSV**.
- **Karyawan & Otorisasi PIN (`/admin/staff`)**: Pengelolaan akun staf (Admin, Kasir, Barista) serta pemasangan/pencabutan PIN otorisasi supervisor 6-digit.
- **Audit Log (`/admin/audit-logs`)**: Rekam jejak aktivitas sensitif (_immutable_) dengan filter aksi, staf, dan rentang tanggal. Snapshot `metadata` JSON diformat rapi menjadi badge berlabel Bahasa Indonesia dan format mata uang Rupiah.
- **Pengaturan Kafe & Pairing Kiosk (`/admin/settings`)**: Pengaturan identitas struk termal, durasi keamanan Kiosk (idle timeout, warning, auto-reset konfirmasi, masa berlaku QRIS), serta manajemen perangkat Kiosk (tambah perangkat, salin link pairing, buka kiosk, rotasi token, aktif/nonaktifkan, hapus).

---

## Panduan Operasional Pengguna

### 1. Alur Self-Ordering Kiosk (Pelanggan)

![Alur Pemesanan Kiosk](docs/screenshots/05-kiosk-menu.png)

1. **Mulai Pemesanan**: Pelanggan mengetuk tombol **"Mulai Pesan"** pada layar pembuka.
2. **Pilih Tipe Pesanan**: Pilih **Dine-In** atau **Takeaway**, lalu isi nama panggilan untuk antrean (opsional).
3. **Pilih Menu & Kustomisasi**:
    - Ketuk menu yang diinginkan untuk membuka lembar modifier (gula, es, susu, topping, catatan, jumlah).
    - Ketuk **Tambah** untuk memasukkan item ke keranjang.
4. **Checkout & Pembayaran**:
    - Ketuk tombol **Keranjang** di kanan atas, lalu pilih **Lanjut Pembayaran**.
    - Pilih **QRIS Instant** (scan QR / klik _Simulasi Bayar Lunas (Demo)_ di environment lokal) atau **Bayar di Kasir**.
5. **Ambil Nomor Antrean**: Layar konfirmasi menampilkan nomor antrean (`#A-001`) dan otomatis mereset ke layar awal setelah hitung mundur selesai.

---

### 2. Alur POS Kasir & Manajemen Shift (Kasir)

![Alur POS Kasir](docs/screenshots/06-pos-kasir.png)

1. **Membuka Shift Kasir**: Buka **Shift Kasir** (`/pos/shift`), masukkan nominal **Modal Awal Tunai**, lalu klik **Buka Shift Kasir**.
2. **Melayani Antrean Kiosk**: Buka tab **Antrean Bayar Kiosk** di `/pos`, klik **Terima Pembayaran** pada tiket pelanggan, pilih metode (**Tunai**, **Kartu EDC**, atau **QRIS Statis**), lalu selesaikan pembayaran hingga modal struk tampil.
3. **Melayani Pesanan Langsung (Walk-In)**: Pilih menu di tab **Kasir Walk-In**, atur diskon manual bila perlu (wajib 6-digit PIN Admin), klik **Bayar & Proses**, gunakan NumPad sentuh untuk pembayaran tunai, lalu cetak struk termal (**58mm / 80mm**).
4. **Membatalkan Transaksi (Void)**: Pada tab **Riwayat Shift**, klik **Void**, isi alasan dan masukkan PIN Supervisor 6-digit. Stok bahan baku otomatis dikembalikan ke inventaris.
5. **Menutup Shift Kasir**: Kembali ke `/pos/shift`, masukkan **Uang Tunai Aktual** sesuai fisik di laci kasir (sistem menghitung selisih secara real-time), isi catatan jika ada selisih, lalu klik **Tutup Shift Sekarang**.

---

### 3. Alur Kitchen Display System (Barista)

1. Buka `/kds` pada tablet atau monitor meja barista.
2. Tiket berstatus **Paid** otomatis masuk ke kolom **Antre / Pesanan Masuk** disertai lonceng audio.
3. Ketuk **Mulai Buat** $\to$ tiket pindah ke kolom **Sedang Dibuat**.
4. Ketuk **Tandai Siap Diambil** $\to$ tiket pindah ke kolom **Siap Diambil** dan muncul di Layar Antrean Publik (`/display/queue`).
5. Gunakan tombol panah kembali (**Undo**) bila perlu mengembalikan tiket 1 langkah, atau ketuk **Sudah Diserahkan** setelah minuman diambil pelanggan.

---

### 4. Alur Layar Antrean Publik (Pelanggan / Ruang Tunggu)

1. Buka URL `/display/queue` pada TV atau monitor area pengambilan pesanan.
2. Layar membagi antrean ke kolom **Sedang Disiapkan** dan **Siap Diambil** secara real-time tanpa perlu login ataupun interaksi manual.

---

### 5. Alur Backoffice & Manajemen Kafe (Owner / Admin)

![Dashboard Admin](docs/screenshots/02-dashboard-admin.png)

1. **Dashboard (`/admin/dashboard`)**: Pantau omzet harian, AOV, tren pendapatan 7/30 hari, rasio Kiosk vs POS, heatmap jam sibuk, 5 menu teratas, dan bahan stok menipis.
2. **Menu, BOM & Inventaris (`/admin/menus`, `/admin/inventory`)**: Atur komposisi bahan baku per porsi agar HPP (COGS) dan pemotongan stok berjalan otomatis saat transaksi lunas.
3. **Laporan & Audit (`/admin/reports`, `/admin/audit-logs`)**: Ekspor laporan CSV per rentang tanggal dan tinjau log aktivitas sensitif yang terformat rapi.

---

## Hak Akses Pengguna (RBAC)

| Peran (_Role_)      | Akses URL Utama                | Cakupan Wewenang & Tanggung Jawab                                                                                                       |
| :------------------ | :----------------------------- | :-------------------------------------------------------------------------------------------------------------------------------------- |
| **Admin / Owner**   | `/admin/*`, `/pos/*`, `/kds/*` | Akses menyeluruh ke seluruh modul: konfigurasi kafe, BOM, inventaris, laporan keuangan, audit log, dan otorisasi PIN supervisor.        |
| **Kasir**           | `/pos/*`                       | Membuka/menutup shift kasir, melunasi antrean Kiosk, melayani transaksi walk-in, mencetak struk, dan melakukan void (dengan PIN Admin). |
| **Barista**         | `/kds/*`                       | Memantau tiket dapur, memperbarui progres pengerjaan minuman, dan melakukan undo 1 langkah.                                             |
| **Perangkat Kiosk** | `/kiosk/*`                     | Mode khusus pemesanan mandiri pelanggan terisolasi via cookie token perangkat.                                                          |
| **Display Monitor** | `/display/queue`               | Layar publik tanpa autentikasi untuk menampilkan status pemanggilan nomor antrean.                                                      |

---

## Teknologi & Standar Desain

- **Backend**: PHP 8.3+, Laravel 13, Laravel Fortify (Autentikasi Aman), View Sharing Middleware (`ShareViewData`).
- **Frontend**: **100% Native Laravel Blade** (`.blade.php` + Anonymous Blade Components), **Alpine.js 3** (`resources/js/app.js`), **Tailwind CSS v4** (Oxide engine), 66 **Inline SVG Lucide Icons** ([icon.blade.php](file:///home/nocturn/Projects/Personal/Our-Coffee/resources/views/components/icon.blade.php)), serta generator QR SVG offline deterministik.
- **Basis Data**: MariaDB / MySQL dengan engine InnoDB (`oc_db` operasional dan `oc_db_testing` pengujian).
- **Tooling & Linter**: Laravel Pint (PSR-12), Larastan / PHPStan (Level 7 Strict), Pest PHP v5, Vite Plus (`vp build`, `vp check`).
- **Palet Warna & Identitas Desain**:
    - **Espresso** (`#1c120d` / `#150e0a`): Warna dasar gelap/hangat khas kedai kopi.
    - **Crema & Amber** (`#b45309` / `#f59e0b`): Aksen utama dengan kontras tinggi.
    - **Oat & Latte** (`#faf7f2` / `#eadfd3`): Permukaan mode terang (_Light Mode_) bernuansa gandum dan susu.
    - **Tipografi**: _Plus Jakarta Sans_ untuk keterbacaan antarmuka dan _JetBrains Mono_ (`.font-money`, `tabular-nums`) agar kolom nominal Rupiah selalu sejajar.
- **Konvensi Finansial & Waktu**:
    - Seluruh nominal mata uang disimpan sebagai integer **Rupiah (IDR)** tanpa desimal (`id-ID`).
    - Penomoran antrean direset harian mengacu pada zona waktu **Asia/Jakarta (WIB)**.

---

## Arsitektur Sistem & Database

Logika bisnis inti kafe diisolasi ke dalam lapisan servis independen di direktori `app/Services/`:

- `OrderService`: Manajemen siklus hidup pesanan, kalkulasi subtotal, pelunasan di kasir, dan pembatalan (_void_) berantai.
- `InventoryService`: Pengurangan bahan baku otomatis menggunakan transaksi basis data atomik (`DB::transaction` + `lockForUpdate` berurutan) untuk mencegah kondisi balapan (_race condition_) dan _deadlock_.
- `BomCostService`: Kalkulasi otomatis Harga Pokok Penjualan (HPP) berdasarkan komposisi resep bahan baku dan modifier.
- `MenuAvailabilityService`: Validasi ketersediaan menu real-time dan kapasitas saji maksimal berdasarkan sisa stok bahan.
- `QueueNumberGenerator`: Generator nomor antrean berurutan harian (`A-001` hingga `A-999`) berbasis zona waktu WIB.
- `PinAuthorizationService`: Hashing dan verifikasi 6-digit PIN otorisasi supervisor/admin.
- `AuditLogService`: Pencatat rekam jejak aktivitas kritis yang bersifat permanen (_immutable_).
- `SimulatedQrisGateway`: Implementasi simulasi `QrisGatewayInterface` untuk pengujian pembayaran QRIS lokal.

Struktur basis data terdiri atas **18 tabel domain** yang mencakup: `users`, `kiosk_devices`, `categories`, `distributors`, `ingredients`, `menu_items`, `modifier_groups`, `modifier_options`, `menu_item_ingredients`, `stock_movements`, `cashier_shifts`, `orders`, `order_items`, `order_item_modifiers`, `payments`, `audit_logs`, dan `app_settings`.

---

## Panduan Instalasi & Menjalankan Aplikasi

### Persyaratan Lingkungan (_Prerequisites_)

- PHP `^8.3` dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`.
- Composer 2.x
- Node.js versi `22+` dan npm
- Database server MariaDB atau MySQL berjalan pada `127.0.0.1:3306`

### Langkah Pemasangan

1. **Clone Repositori & Pasang Dependensi**:

    ```bash
    git clone https://github.com/nocturnnnnn/Our-Coffee.git
    cd Our-Coffee
    composer install
    npm install
    ```

2. **Konfigurasi Environment**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Inisialisasi Database**:
   Buat database pada server MariaDB/MySQL Anda:

    ```sql
    CREATE DATABASE IF NOT EXISTS oc_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE IF NOT EXISTS oc_db_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    ```

    Sesuaikan `DB_DATABASE=oc_db`, `DB_USERNAME`, dan `DB_PASSWORD` pada file `.env`.

4. **Migrasi & Seeding Data Awal**:

    ```bash
    php artisan migrate:fresh --seed
    ```

5. **Build Aset Frontend (Tailwind CSS v4 + Alpine.js)**:

    ```bash
    npm run build
    ```

6. **Menjalankan Aplikasi**:
    - **Untuk langsung menjalankan / demo / preview** (cukup 1 perintah karena aset `public/build/` sudah terkompilasi):
        ```bash
        php artisan serve
        ```
    - **Saat sedang mengembangkan & butuh auto-reload (HMR CSS/JS)**:
        ```bash
        composer dev
        ```
        _(Menjalankan Laravel server + Vite secara bersamaan dalam 1 perintah.)_

    Aplikasi dapat diakses melalui peramban di `http://localhost:8000`.

---

## Akun Bawaan & Pengujian Kiosk

Setelah menjalankan database seeder, akun berikut siap digunakan untuk pengujian:

| Peran             | Alamat Email             | Kata Sandi (_Password_) | PIN Otorisasi Supervisor |
| :---------------- | :----------------------- | :---------------------- | :----------------------- |
| **Admin / Owner** | `admin@ourcoffee.test`   | `password`              | `123456`                 |
| **Kasir**         | `kasir@ourcoffee.test`   | `password`              | —                        |
| **Barista**       | `barista@ourcoffee.test` | `password`              | —                        |

- **Tautan Pairing Tablet Kiosk Default**:  
  `http://localhost:8000/kiosk/pair/our-coffee-kiosk-lobby-1-token`
- **Layar Antrean Publik**:  
  `http://localhost:8000/display/queue`

---

## Uji Kualitas & Otomatisasi Tes

Untuk memastikan integritas sistem, tipe statis PHP, format kode, dan pengujian fitur berjalan baik, gunakan perintah berikut:

```bash
# 1. Menjalankan seluruh pengujian backend (Pint PSR-12, PHPStan Level 7, dan Pest 72 Tests)
composer test

# 2. Memeriksa format frontend/kode (Vite Plus check) dan build aset
npm run check
npm run build

# 3. Menjalankan seluruh rangkaian CI sekaligus (Vite Plus check + composer test)
composer ci:check
```

---

_Dikembangkan dengan dedikasi untuk menghadirkan alur operasional kafe yang cepat, transparan, ringan, dan dapat diandalkan dengan Native Laravel Blade._
