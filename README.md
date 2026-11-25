# Our-Coffee Blade/Inertia [FORKED] — Sistem Operasional & Point of Sale Kafe Modern

> **Forked Repository** dari [nocturnnnnn/Our-Coffee](https://github.com/nocturnnnnn/Our-Coffee) (versi original React 19) — dikonversi 1:1 ke **Laravel Blade (`app.blade.php`) + Inertia.js v3 + Vue 3 + Tailwind CSS v4**.

**Our Coffee** adalah platform operasional kafe terpadu (_all-in-one cafe operations platform_) yang dirancang untuk menjembatani seluruh alur bisnis kedai kopi modern—mulai dari pemesanan mandiri oleh pelanggan melalui **Self-Ordering Kiosk**, pencatatan transaksi dan kasir melalui **POS Kasir**, visualisasi pesanan dapur secara real-time via **Kitchen Display System (KDS)**, monitor antrean publik (**Public Queue Display**), hingga manajemen inventaris, resep Bill of Materials (BOM), dan analisis bisnis di **Backoffice Admin**.

Aplikasi ini dibangun menggunakan arsitektur modern berbasis **Laravel 13**, **Laravel Blade**, **Inertia.js v3**, **Vue 3**, **TypeScript**, **Tailwind CSS v4**, dan basis data relasional **MariaDB**.

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

| Halaman Login Staf & Admin | Dashboard Operasional Admin Real-Time |
| :---: | :---: |
| ![Halaman Login](docs/screenshots/01-login.png) | ![Dashboard Admin](docs/screenshots/02-dashboard-admin.png) |
| *Autentikasi aman staf dengan logo ikonik Our Coffee* | *KPI Omzet, tren penjualan 7 hari, heatmap jam sibuk & margin laba kotor* |

### 2. Self-Ordering Kiosk (Layar Pelanggan)

| Layar Awal (Idle / Welcome Screen) | Pemilihan Tipe Pesanan & Nama Pelanggan |
| :---: | :---: |
| ![Layar Awal Kiosk](docs/screenshots/03-kiosk-welcome.png) | ![Pemilihan Tipe Pesanan](docs/screenshots/04-kiosk-order-type.png) |
| *Layar sambutan mandiri ramah sentuhan pelanggan* | *Pemilihan Dine-In / Takeaway dan identitas panggilan antrean* |

| Katalog Menu Kiosk & Navigasi Kategori |
| :---: |
| ![Katalog Menu Kiosk](docs/screenshots/05-kiosk-menu.png) |
| *Tampilan menu visual dengan kategori rail, harga, deskripsi, dan badge produk* |

### 3. POS Kasir (Point of Sale)

| Antarmuka Kasir POS 3 Kolom & Antrean Kiosk |
| :---: |
| ![Antarmuka POS Kasir](docs/screenshots/06-pos-kasir.png) |
| *Kolom kiri: antrean Kiosk menunggu bayar | Kolom tengah: katalog cepat | Kolom kanan: faktur & NumPad tunai* |

---

## Fitur Utama & Modul Sistem

### 1. Self-Ordering Kiosk (`/kiosk`)

- **Pairing Perangkat Kiosk**: Otorisasi tablet/perangkat pemesanan di lobi melalui tautan unik `/kiosk/pair/{token}` tanpa mengharuskan staf login manual pada perangkat pelanggan.
- **Alur Pemesanan 6 Tahap**: Layar pembuka (_Idle/Welcome_) $\to$ Pemilihan tipe pesanan (_Dine-In_ / _Takeaway_) & nama pelanggan $\to$ Eksplorasi kategori dan menu $\to$ Kustomisasi modifier (suhu, level gula, jenis susu, ekstra shot/drizzle) $\to$ Keranjang belanja $\to$ Konfirmasi tiket antrean cetak (`A-001`).
- **Keamanan & Ketahanan Kiosk**:
    - Target sentuh ramah jari tangan (minimal `64x64px`).
    - Pencegahan aksi tidak disengaja melalui penguncian klik kanan (_context menu_) dan gestur zoom browser.
    - _Idle Timeout Watchdog_: Otomatis mengulang alur ke layar awal jika ditinggalkan selama 60 detik (disertai peringatan hitung mundur 10 detik terakhir).
    - _Hidden Admin Exit_: Gestur rahasia dengan mengetuk logo kafe berulang kali diikuti verifikasi 6-digit PIN Admin berkecepatan aman (`throttle:15,1`) untuk keluar dari mode kiosk.
- **Sinkronisasi Stok Real-time**: Pengecekan ketersediaan menu secara berkala (polling 5 detik) dan proteksi _atomic 409 Conflict_ saat checkout jika bahan baku mendadak habis.

### 2. POS Kasir & Manajemen Shift (`/pos`, `/pos/shift`)

- **Kontrol Shift Kasir Berintegritas Tinggi**:
    - Kasir diwajibkan membuka shift dengan memasukkan saldo modal kas awal (_opening cash_) sebelum dapat membuka menu register.
    - Penutupan shift mengharuskan rekonsiliasi uang fisik aktual di laci kasir dengan pencatatan selisih otomatis (_cash variance tracking_).
- **Tata Letak Register 3 Kolom Responsif**:
    - **Kolom Kiri**: Daftar antrean pesanan belum bayar dari Kiosk (_live polling_ 3 detik) serta riwayat transaksi shift berjalan.
    - **Kolom Tengah**: Katalog menu cepat dengan filter kategori instan, pencarian berbasis teks, dan navigasi varian.
    - **Kolom Kanan**: Faktur aktif, kustomisasi diskon manual (memerlukan otorisasi 6-digit PIN Admin/Supervisor), modul pembayaran tunai (_Cash Only_), dan papan tombol sentuh NumPad `60x60px` untuk kalkulasi uang diterima dan kembalian.
- **Pencetakan Struk Termal**: Tata letak cetak nota profesional yang mendukung printer termal ukuran standar **58mm** dan **80mm** via browser print preview (`@media print`).
- **Void Pesanan Terproteksi**: Pembatalan transaksi wajib menggunakan otorisasi PIN Admin/Supervisor dengan pengembalian stok bahan baku (_stock rollback_) otomatis dan pencatatan riwayat pada audit log.

### 3. Kitchen Display System / KDS (`/kds`)

- **Papan Kanban FIFO 3 Kolom**: Tiket pesanan terorganisir rapi berdasarkan urutan pembayaran (`Paid` $\to$ `Preparing` $\to$ `Ready` $\to$ `Completed`) dengan pembaruan otomatis setiap 3 detik.
- **Ergonomi Barista**:
    - _SLA Timer_: Indikator batas waktu berbasis warna (Normal `<5 menit`, Peringatan Kuning/Amber `5–10 menit`, Kritis Merah `>10 menit`).
    - Sorotan kustomisasi bahan khusus (_modifier pill_) agar barista dapat segera melihat preferensi khusus (misal: _Oat Milk_, _No Sugar_).
    - Transisi status optimistik instan dengan tombol _Undo_ darurat (5 detik) via notifikasi Sonner.
    - Sinyal audio Web Audio API yang membunyikan lonceng lembut saat pesanan baru masuk ke antrean dapur.

### 4. Layar Antrean Publik (`/display/queue`)

- Layar monitor ruang tunggu beresolusi tinggi dengan tema kontras elegan.
- Menampilkan daftar nomor antrean yang **Sedang Disiapkan** dan **Siap Diambil** secara otomatis (pembaruan data setiap 4 detik).

### 5. Backoffice Admin (`/admin/*`)

- **Dashboard Eksekutif (`/admin/dashboard`)**: Pemantauan 4 metrik KPI harian, grafik pendapatan 7 & 30 hari terakhir, komposisi channel (Kiosk vs POS), peta panas (_heatmap_) jam-jam sibuk 24 jam, 5 menu terlaris (_Top 5 Bestsellers_), serta widget peringatan stok kritis.
- **Katalog & Resep BOM (`/admin/categories`, `/admin/menus`, `/admin/modifiers`)**: Pengaturan master data kategori, menu, varian modifier, dan komposisi bahan baku (_Bill of Materials_ / BOM) per menu yang secara otomatis mengkalkulasi Harga Pokok Penjualan (HPP / COGS) serta margin keuntungan kotor.
- **Inventaris & Manajemen Pemasok (`/admin/inventory`, `/admin/distributors`)**:
    - Pencatatan stok bahan baku dengan satuan multi-unit (gram, mililiter, pieces).
    - Restock barang masuk dengan kalkulasi biaya rata-rata tertimbang (_weighted average cost_).
    - Pencatatan pembuangan bahan rusak/basi (_waste tracking_).
    - Rekonsiliasi stok fisik (_stock opname_) berkala.
    - Buku besar mutasi stok (_stock_movements_) yang bersifat permanen (_immutable_).
- **Laporan Finansial (`/admin/reports`)**: Filter data penjualan berdasarkan rentang tanggal dengan ekspor laporan detail ke format CSV/Excel.
- **Karyawan & Otorisasi PIN (`/admin/staff`)**: Pengelolaan akun staf (Admin, Kasir, Barista) serta penetapan PIN otorisasi supervisor 6-digit.
- **Audit Trail (`/admin/audit-logs`)**: Rekam jejak seluruh aktivitas penting kafe (pembukaan/penutupan shift, pembatalan pesanan/void, restock, penyesuaian resep) untuk akuntabilitas operasional.
- **Pengaturan Kafe & Token Kiosk (`/admin/settings`)**: Profil kafe, teks footer struk, timer peringatan kiosk, dan pembuatan/rotasi token otorisasi perangkat kiosk.

---

## Panduan Operasional Pengguna

### 1. Alur Self-Ordering Kiosk (Pelanggan)

![Alur Pemesanan Kiosk](docs/screenshots/05-kiosk-menu.png)

1. **Mulai Pemesanan**: Pelanggan menyentuh layar awal bertuliskan _"Mulai Pesan"_.
2. **Pilih Tipe Pesanan**: Pilih opsi **Dine-In** (makan di tempat) atau **Takeaway** (bawa pulang), lalu masukkan nama panggilan (opsional).
3. **Pilih Menu & Kustomisasi**:
    - Ketuk menu yang diinginkan.
    - Jika menu memiliki opsi kustomisasi, lembar modifier akan terbuka (atur tingkat kemanisan, es, atau jenis susu tambahan).
    - Ketuk **Tambah ke Pesanan**.
4. **Periksa Keranjang & Checkout**:
    - Ketuk tombol **Keranjang** di pojok kanan atas.
    - Periksa daftar pesanan dan total harga, lalu ketuk **Bayar Tunai di Kasir**.
5. **Ambil Nomor Antrean**:
    - Layar konfirmasi akan menampilkan nomor antrean (misal: `#A-001`).
    - Pelanggan menuju kasir untuk melakukan pembayaran tunai.

---

### 2. Alur POS Kasir & Manajemen Shift (Kasir)

![Alur POS Kasir](docs/screenshots/06-pos-kasir.png)

1. **Membuka Shift Kasir**:
    - Masuk ke menu **Shift Kasir** (`/pos/shift`).
    - Masukkan nominal uang modal awal kas di laci (_Opening Cash_, contoh: `Rp 500.000`), lalu klik **Buka Shift Baru**.
2. **Melayani Pesanan Kiosk (Pay at Cashier)**:
    - Masuk ke menu **POS Kasir** (`/pos`).
    - Pada kolom kiri (**Pending Kiosk**), klik tiket antrean pelanggan yang baru datang ke meja kasir.
    - Masukkan nominal uang tunai yang diserahkan pelanggan menggunakan NumPad sentuh di kolom kanan.
    - Klik **Lunasi** $\to$ Struk pembelian akan otomatis dicetak dan pesanan langsung diteruskan ke KDS Dapur.
3. **Melayani Pesanan Langsung (Walk-In)**:
    - Pilih menu di kolom tengah untuk memasukkannya ke keranjang POS.
    - Atur diskon jika diperlukan (klik tombol diskon dan masukkan 6-digit PIN Admin).
    - Masukkan nominal uang tunai pada NumPad, lalu selesaikan pembayaran.
4. **Membatalkan Transaksi (Void)**:
    - Pilih pesanan pada riwayat transaksi, klik tombol **Void**.
    - Masukkan PIN Supervisor 6-digit dan alasan pembatalan. Stok bahan baku akan otomatis dikembalikan ke gudang.
5. **Menutup Shift Kasir**:
    - Pada akhir jam kerja, kembali ke menu **Shift Kasir**.
    - Hitung total uang tunai fisik di laci kasir, masukkan nominal pada form penutupan, lalu klik **Tutup Shift**. Sistem akan menghitung selisih kas (_variance_) secara otomatis.

---

### 3. Alur Kitchen Display System (Barista)

1. Buka layar **Kitchen (KDS)** di tablet atau monitor dapur (`/kds`).
2. Pesanan yang telah lunas otomatis muncul di kolom **Paid** disertai bunyi notifikasi (_chime_).
3. Saat mulai meracik minuman, barista mengetuk tombol **Mulai Siapkan** pada tiket $\to$ tiket berpindah ke kolom **Preparing**.
4. Setelah minuman selesai diracik, ketuk tombol **Selesai / Panggil** $\to$ tiket berpindah ke kolom **Ready** dan nomor antrean otomatis muncul di monitor lobi.
5. Saat pelanggan mengambil minumannya di meja pick-up, ketuk **Selesai Diambil** $\to$ tiket diarsipkan ke status **Completed**.

---

### 4. Alur Layar Antrean Publik (Pelanggan / Ruang Tunggu)

1. Buka URL `/display/queue` pada TV atau monitor display pelanggan di area lobi kafe.
2. Monitor akan otomatis membagi tampilan menjadi dua kolom:
    - **Sedang Disiapkan**: Nomor antrean yang sedang diracik oleh barista.
    - **Siap Diambil**: Nomor antrean yang sudah siap diambil di konter penyerahan.
3. Halaman ini bekerja secara otomatis (_hands-free_) tanpa perlu interaksi keyboard maupun mouse.

---

### 5. Alur Backoffice & Manajemen Kafe (Owner / Admin)

![Dashboard Admin](docs/screenshots/02-dashboard-admin.png)

1. **Analisis Bisnis**: Masuk ke `/admin/dashboard` untuk memantau tren pendapatan, menu paling laku, dan ketersediaan stok bahan baku.
2. **Pembaruan Resep & Menu**:
    - Kelola menu di `/admin/menus`.
    - Atur bahan baku di `/admin/inventory`. Setiap menu dapat dikaitkan dengan resep bahan baku sehingga stok terpotong otomatis setiap kali menu tersebut terjual.
3. **Laporan & Pembukuan**:
    - Akses `/admin/reports` untuk melihat rekap omzet kotor, total diskon, HPP bahan terpakai, dan laba bersih.
    - Klik **Ekspor CSV** untuk mengunduh laporan pembukuan ke spreadsheet.

---

## Hak Akses Pengguna (RBAC)

Sistem menggunakan kontrol akses berbasis peran (_Role-Based Access Control_):

| Peran (_Role_)      | Akses URL Utama                | Cakupan Wewenang & Tanggung Jawab                                                                                                            |
| :------------------ | :----------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------- |
| **Admin / Owner**   | `/admin/*`, `/pos/*`, `/kds/*` | Akses menyeluruh ke seluruh sistem: konfigurasi kafe, resep BOM, stok gudang, laporan keuangan, audit trail, serta otorisasi PIN supervisor. |
| **Kasir**           | `/pos/*`                       | Membuka dan menutup shift kasir, melayani pelunasan antrean Kiosk, menerima pesanan langsung (_walk-in_), dan mencetak struk.                |
| **Barista**         | `/kds/*`                       | Memantau tiket pesanan masuk di dapur, memproses antrean kopi, dan memperbarui status pesanan.                                               |
| **Perangkat Kiosk** | `/kiosk/*`                     | Mode khusus pemesanan mandiri pelanggan via token perangkat terpasang. Terisolasi dari menu backoffice maupun kasir.                         |
| **Display Monitor** | `/display/queue`               | Layar publik tanpa autentikasi khusus untuk menampilkan status pemanggilan nomor antrean.                                                    |

---

## Teknologi & Standar Desain

- **Backend**: PHP 8.3+, Laravel 13, Laravel Fortify (Autentikasi Aman), Laravel Wayfinder (Type-safe routing).
- **Frontend**: Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, komponen antarmuka shadcn/ui berbasis Radix UI, Zustand (State Management Kiosk), Lucide Icons, Sonner.
- **Basis Data**: MariaDB / MySQL dengan engine InnoDB (`oc_db` untuk operasional dan `oc_db_testing` untuk otomatisasi pengujian).
- **Tooling & Linter**: Laravel Pint (PSR-12), Larastan / PHPStan (Level 7 Strict), Pest PHP v5, Vite Plus (`vp check`).
- **Palet Warna & Identitas Desain**:
    - **Espresso** (`#1c120d` / `#150e0a`): Warna dasar kafe yang hangat dan elegan.
    - **Crema & Amber** (`#b45309` / `#f59e0b`): Warna aksen interaktif berdaya kontras tinggi.
    - **Oat & Latte** (`#faf7f2` / `#eadfd3`): Warna pendukung bernuansa susu dan gandum alami.
    - **Tipografi**: _Plus Jakarta Sans_ untuk keterbacaan teks antarmuka dan _JetBrains Mono_ untuk visualisasi angka nominal harga rupiah.
- **Konvensi Finansial & Waktu**:
    - Seluruh nominal mata uang disimpan dalam bilangan bulat **Rupiah (IDR)** tanpa desimal (`id-ID`).
    - Penomoran antrean direset setiap pergantian hari mengacu pada zona waktu **Asia/Jakarta (WIB)**.

---

## Arsitektur Sistem & Database

Logika bisnis inti kafe diisolasi ke dalam lapisan servis independen di dalam direktori `app/Services/`:

- `OrderService`: Manajemen siklus hidup pesanan, kalkulasi subtotal, pelunasan di kasir, dan pembatalan (_void_) berantai.
- `InventoryService`: Pengurangan bahan baku otomatis menggunakan transaksi basis data atomik (`DB::transaction` + `lockForUpdate`) untuk mencegah kondisi balapan (_race condition_).
- `BomCostService`: Kalkulasi otomatis Harga Pokok Penjualan (HPP) berdasarkan komposisi bahan baku terkini.
- `MenuAvailabilityService`: Validasi status ketersediaan menu secara berkala berdasarkan sisa kuantitas bahan baku di gudang.
- `QueueNumberGenerator`: Pembuat nomor antrean berurutan harian (`A-001` hingga `A-999`) berbasis zona waktu WIB.
- `PinAuthorizationService`: Enkripsi dan validasi 6-digit PIN otorisasi supervisor/admin.
- `AuditLogService`: Pencatat rekam jejak aktivitas kritis untuk kebutuhan audit internal.

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
   Salin file konfigurasi environment dan generate application key:

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

    Pastikan pengaturan `DB_DATABASE=oc_db`, `DB_USERNAME`, dan `DB_PASSWORD` pada file `.env` telah sesuai.

4. **Migrasi & Seeding Data Awal**:
   Jalankan migrasi tabel domain dan data benih (staf, kategori, menu kopi, modifier, dan bahan baku awal):

    ```bash
    php artisan migrate:fresh --seed
    ```

5. **Build Aset Frontend**:

    ```bash
    npm run build
    ```

6. **Menjalankan Server Lokal**:
   Jalankan seluruh ekosistem pengembangan (Laravel, Vite, antrean worker) dalam satu perintah:
    ```bash
    composer dev
    ```
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

Untuk memastikan integritas sistem dan kebersihan kode, jalankan perintah uji kualitas berikut:

```bash
# 1. Menjalankan seluruh pengujian backend (Pint PSR-12, PHPStan Level 7, dan Pest Tests)
composer test

# 2. Pengecekan tipe data TypeScript dan validasi linter frontend
npm run types:check
npm run check

# 3. Pengecekan otomatisasi CI menyeluruh (Frontend + Backend)
composer ci:check
```

---

_Dikembangkan dengan dedikasi untuk menghadirkan alur operasional kafe yang cepat, transparan, dan dapat diandalkan._
