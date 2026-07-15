# Laravel WhatsApp Web Gateway

Project Laravel 11 yang terintegrasi dengan `kstmostofa/laravel-whatsapp` menggunakan arsitektur **Dual-Backend (Web Sidecar)**. Project ini menyediakan dashboard interaktif modern untuk menghubungkan WhatsApp (via QR Code) dan mengirimkan pesan langsung melalui antarmuka web.

---

## 🛠 Technology Stack & Requirements

### 1. Stack Utama
* **Backend:** Laravel 11 (PHP 8.2 atau lebih tinggi)
* **Database:** MySQL
* **WhatsApp Automation:** Node.js (v18+) & Puppeteer (headless Chromium)
* **Frontend:** Tailwind CSS v4, Vite, Vanilla JS Polling

### 2. Kebutuhan Server & Memori (Penting)
* **RAM Minimum:** **2 GB RAM**. Proses Puppeteer menjalankan instance Chromium secara headless (tanpa GUI) di latar belakang untuk memuat WhatsApp Web, yang membutuhkan alokasi memori yang cukup.
* **Server Linux (Production):** Memerlukan instalasi dependensi lib-browser Chromium agar Puppeteer dapat berjalan dengan lancar.
* **Skema Server:**
  * **Single Server (Satu Server):** Laravel dan Node.js Sidecar berjalan di satu VPS yang sama. Ini adalah skema paling praktis dan direkomendasikan.
  * **Multi-Server (Dua Server):** Laravel ditaruh di server web utama, sedangkan Node.js Sidecar ditaruh di VPS kecil lain (misal jika server utama menggunakan serverless / shared hosting yang membatasi background process).

---

## 💻 Panduan Menjalankan di Lokal (Development)

### 1. Persiapan Awal
Pastikan Anda sudah menginstal **Node.js (v18+)**, **Composer**, dan **MySQL** di komputer Anda.

1. Duplikat file `.env.example` menjadi `.env` dan sesuaikan koneksi database MySQL Anda.
2. Jalankan migrasi database:
   ```bash
   php artisan migrate
   ```
3. Install dependensi Node.js untuk WhatsApp Sidecar (ini akan mengunduh Chromium secara otomatis):
   ```bash
   php artisan whatsapp:sidecar:install
   ```

### 2. Menjalankan Aplikasi
Untuk menjalankan gateway ini di komputer lokal, Anda perlu menjalankan Laravel server dan WhatsApp sidecar secara bersamaan:

1. **Jalankan Laravel Server:**
   Jalankan server di port `8080` (untuk menghindari bentrok port):
   ```bash
   php artisan serve --port=8080
   ```
2. **Jalankan WhatsApp Services:**
   * **Windows:** Cukup klik dua kali file **`start-whatsapp-sidecar.bat`** di folder root project. Ini akan otomatis membuka jendela terminal baru untuk menjalankan Node.js sidecar (port 3000) dan event listener.
   * **Linux/macOS:** Jalankan kedua perintah berikut di terminal terpisah:
     ```bash
     # Terminal 1: Jalankan Sidecar
     php artisan whatsapp:sidecar:start
     
     # Terminal 2: Jalankan Listener Event
     php artisan whatsapp:web:listen
     ```

3. **Buka Aplikasi:**
   Akses dashboard melalui browser di: **[http://localhost:8080/whatsapp](http://localhost:8080/whatsapp)**.

---

## 🚀 Panduan Deploy di Production (Server)

Di server production, Anda tidak perlu menggunakan terminal manual atau file `.bat`. Proses background dikelola secara otomatis oleh sistem operasi menggunakan **Supervisor** (di Linux) atau melalui dashboard **Laravel Forge**.

### Opsi A: Menggunakan Laravel Forge (Paling Mudah)
Jika Anda menggunakan Laravel Forge, masuk ke server Anda, lalu buka menu **Daemons** di sidebar kiri. Tambahkan 2 daemon berikut:

#### 1. Daemon WhatsApp Sidecar (Node.js)
* **Command:**
  ```bash
  env PORT=3000 HOST=127.0.0.1 SIDECAR_TOKEN=secure_token_anda SESSION_DIR=storage/app/whatsapp-sidecar/sessions SIDECAR_PID_FILE=storage/app/whatsapp-sidecar/sidecar.pid node vendor/kstmostofa/laravel-whatsapp/sidecar/index.js
  ```
* **Directory:** `/home/forge/nama-website-anda.com`
* **Processes:** `1`
* **User:** `forge`

#### 2. Daemon Event Listener (Laravel)
* **Command:**
  ```bash
  php artisan whatsapp:web:listen
  ```
* **Directory:** `/home/forge/nama-website-anda.com`
* **Processes:** `1`
* **User:** `forge`

---

### Opsi B: Menggunakan Raw Supervisor (Linux VPS Manual)
Jika Anda mengonfigurasi VPS Ubuntu secara manual, pasang Supervisor:
```bash
sudo apt update && sudo apt install supervisor -y
```

Buat file konfigurasi baru di `/etc/supervisor/conf.d/whatsapp-gateway.conf`:

```ini
[program:whatsapp-sidecar]
process_name=%(program_name)s
command=node /home/user/nama-website.com/vendor/kstmostofa/laravel-whatsapp/sidecar/index.js
directory=/home/user/nama-website.com
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/home/user/nama-website.com/storage/logs/whatsapp-sidecar-supervisor.log
environment=PORT="3000",HOST="127.0.0.1",SIDECAR_TOKEN="secure_token_anda",SESSION_DIR="/home/user/nama-website.com/storage/app/whatsapp-sidecar/sessions",SIDECAR_PID_FILE="/home/user/nama-website.com/storage/app/whatsapp-sidecar/sidecar.pid"

[program:whatsapp-listener]
process_name=%(program_name)s
command=php /home/user/nama-website.com/artisan whatsapp:web:listen
directory=/home/user/nama-website.com
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/home/user/nama-website.com/storage/logs/whatsapp-listener-supervisor.log
```

Setelah itu, update dan muat ulang Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## 🔒 Catatan Keamanan & Firewall
1. **Isolasi Port:** Pastikan port `3000` (port Node.js sidecar) **tidak dibuka untuk publik** di firewall VPS Anda (seperti AWS Security Group atau UFW). Komunikasi ke port `3000` hanya boleh dilakukan secara lokal (`127.0.0.1`) dari program PHP Laravel Anda.
2. **SIDECAR_TOKEN:** Selalu gunakan token unik dan aman pada variabel `WHATSAPP_WEB_TOKEN` di file `.env` production untuk melindungi endpoint API sidecar.
