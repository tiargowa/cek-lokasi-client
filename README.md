# 🧭 Cek Lokasi Client Sederhana

Proyek sederhana berbasis web yang bertujuan untuk mendeteksi lokasi geografis (Latitude dan Longitude) dari perangkat yang mengaksesnya menggunakan HTML5 Geolocation API.

Aplikasi ini sangat ringan, hanya terdiri dari satu file `index.html`, dan dihosting menggunakan GitHub Pages.

## 🚀 Cara Akses

Aplikasi ini dapat diakses secara publik melalui GitHub Pages di URL berikut:

**[Akses Aplikasi Cek Lokasi](https://tiargowa.github.io/cek-lokasi-client/)**

### Cara Kerja

1.  Ketika halaman dimuat, browser akan otomatis meminta izin akses lokasi.
2.  Pengguna **harus mengizinkan (Allow)** permintaan lokasi di pop-up browser.
3.  Setelah diizinkan, aplikasi akan menampilkan koordinat **Latitude** dan **Longitude** dari perangkat.
4.  Jika pengguna menolak, aplikasi akan menampilkan pesan kesalahan.

## 💻 Teknologi yang Digunakan

* **HTML5:** Struktur dasar halaman web.
* **JavaScript (Geolocation API):** Fungsi utama untuk mengambil koordinat lokasi dari perangkat klien.
* **CSS (Inline/Basic):** Untuk tampilan sederhana.

## ⚙️ Instalasi Lokal

Jika Anda ingin menjalankan proyek ini secara lokal:

1.  **Clone Repositori:**
    ```bash
    git clone [https://github.com/tiargowa/cek-lokasi-client.git](https://github.com/tiargowa/cek-lokasi-client.git)
    ```
2.  **Buka File:** Buka folder proyek dan jalankan file `index.html` di browser Anda.

**(Catatan: Browser modern mungkin memerlukan HTTPS atau `localhost` untuk mengaktifkan Geolocation API. Menjalankan langsung dari file lokal mungkin gagal meminta izin lokasi.)**

## 🔒 Mengamankan API Key (Google Maps Geocoding)

Proyek ini sekarang menggunakan endpoint server-side `api_proxy.php` untuk melakukan reverse geocoding sehingga kunci Google Maps tidak perlu langsung ditaruh di JavaScript.

Langkah singkat untuk mengamankan kunci:

1. Buat file `secret_config.php` dari `secret_config.example.php` dan isi `$GOOGLE_MAPS_API_KEY` dengan kunci Anda.
    - Sangat disarankan menempatkan `secret_config.php` di luar webroot (mis. `c:/wamp64/secrets/secret_config.php`) dan sesuaikan `api_proxy.php` untuk meng-include file tersebut.
    - Alternatif terbaik: set environment variable `GOOGLE_MAPS_API_KEY` di konfigurasi Apache/PHP.

2. Jangan commit `secret_config.php` ke git. Tambahkan nama file tersebut ke `.gitignore` jika perlu.

3. Di Google Cloud Console, batasi penggunaan API key Anda (mis. HTTP referrers atau IP address untuk server) dan aktifkan hanya API yang diperlukan (Geocoding API).

4. Jalankan aplikasi via localhost atau server (WAMP). `index.html` sudah diperbarui untuk memanggil `api_proxy.php` yang akan menambahkan API key di server dan meneruskan permintaan ke Google.

Contoh file yang ditambahkan:


Jika Anda butuh, saya bisa membantu menempatkan `secret_config.php` di luar webroot dan menyesuaikan `api_proxy.php` untuk meng-include path tersebut.

## 📤 Publish ke Server Git (best practices)

Jika Anda mem-push repo ini ke Server Git (self-hosted Git, GitLab, Bitbucket Server, dsb.), lakukan langkah-langkah berikut agar API key tidak bocor:

1. Pastikan `secret_config.php` ada di `.gitignore` (sudah ditambahkan di repo ini).
2. Jangan pernah menambahkan nilai kunci nyata di commit. Jika terlanjur, segera lakukan `git filter-branch` atau `git filter-repo` untuk menghapusnya dan rotasi (ubah) kunci di Google Cloud.
3. Buat file `secret_config.php` di server tujuan setelah Anda melakukan deploy/checkout di server, bukan di repo. Contoh (Linux server via SSH):

    ```bash
    # cd ke folder webroot aplikasi
    cd /var/www/cek-lokasi-client
    # buat file konfigurasi rahasia (letakkan di webroot sementara atau di luar webroot)
    cat > secret_config.php <<'PHP'
    <?php
    $GOOGLE_MAPS_API_KEY = 'PASTE_YOUR_REAL_KEY_HERE';
    PHP
    ```

    - Lebih aman: letakkan file di luar webroot, mis. `/var/secrets/cek-lokasi/secret_config.php` dan ubah `api_proxy.php` untuk meng-include path tersebut.

4. Alternatif yang lebih baik: set environment variable `GOOGLE_MAPS_API_KEY` di konfigurasi server (Apache/Nginx/systemd) dan jangan menggunakan file. `api_proxy.php` sudah mencoba membaca env var terlebih dahulu.

    - Contoh (Apache virtual host):

      ```apache
      # di dalam konfigurasi vhost
      SetEnv GOOGLE_MAPS_API_KEY "your_real_key_here"
      ```

    - Contoh (systemd service file untuk PHP-FPM): tambahkan Environment=GOOGLE_MAPS_API_KEY=your_real_key

5. Setelah deploy, uji endpoint `api_proxy.php` secara lokal di server (mis. dengan curl) untuk memastikan respons dari Google berfungsi.

6. Batasi penggunaan API key di Google Cloud Console (referrer, IP) dan rotasi kunci jika ada indikasi kebocoran.

Jika Anda mau, saya bisa bantu membuat skrip deploy kecil yang membuat file `secret_config.php` di server melalui SSH atau menambahkan instruksi setup untuk panel hosting Anda.

Jika Anda butuh, saya bisa membantu menempatkan `secret_config.php` di luar webroot dan menyesuaikan `api_proxy.php` untuk meng-include path tersebut.

## � Token Singkat (short-lived) — cara kerja dan penerbitan

Saya menambahkan endpoint `token.php` untuk menerbitkan token singkat yang ditandatangani HMAC. Alur dasarnya:

1. Admin (atau proses deploy) memanggil `token.php` dengan POST JSON { issue_secret: 'TOKEN_ISSUE_SECRET', ttl: 300 }.
2. `token.php` memverifikasi `issue_secret` (harus sama dengan `TOKEN_ISSUE_SECRET` di server) dan mengembalikan token berbentuk `base64url(payload).base64url(sig)`.
3. Client mengirim token tersebut pada header `X-Client-Token` saat memanggil `api_proxy.php`.
4. `api_proxy.php` memverifikasi tanda tangan token menggunakan `TOKEN_SIGNING_SECRET` dan memeriksa `exp` (expiry). Jika valid, request dilayani.

Contoh permintaan untuk mendapatkan token (di server / trusted environment):

```bash
curl -X POST -H "Content-Type: application/json" -d '{"issue_secret":"PASTE_ISSUE_SECRET","ttl":300}' https://yourserver/cek-lokasi-client/token.php
```

Contoh respons:

```json
{ "token":"<token>", "expires_at": 163... }
```

Contoh penggunaan token di client (fetch):

```js
fetch('api_proxy.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Client-Token': '<token-from-server>'
    },
    body: JSON.stringify({ action: 'geocode', lat, lng })
})
```

Catatan: penerbitan token harus dilakukan dari lingkungan ter-trusted (server atau proses deploy). Jangan menerbitkan token issuance secara langsung ke browser tanpa autentikasi admin.

## �🔐 Memasang Pemeriksaan Origin dan Token (opsional)

Tambahan keamanan yang saya terapkan pada `api_proxy.php`:

- Pemeriksaan Origin/Referer: server akan membaca header `Origin` (atau mengambil domain dari `Referer`) dan memvalidasinya terhadap daftar `ALLOWED_ORIGINS` yang dikonfigurasi.
- Token klien (opsional): bila Anda mengatur `CLIENT_TOKEN` di environment atau `secret_config.php`, server akan mengharuskan setiap permintaan menyertakan header `X-Client-Token` (atau field `client_token` di body JSON). Token ini harus cocok dengan nilai di server.

Konfigurasi yang tersedia (letakkan di environment atau `secret_config.php`):

- `GOOGLE_MAPS_API_KEY` — kunci Google (wajib)
- `ALLOWED_ORIGINS` — daftar origin yang diizinkan, dipisah koma. Contoh: `https://example.com, https://sub.domain.com`
- `CLIENT_TOKEN` — token rahasia yang harus dikirim client pada header `X-Client-Token` (opsional)

Contoh `secret_config.php` (jangan commit file ini):

```php
<?php
$GOOGLE_MAPS_API_KEY = 'PASTE_REAL_KEY';
$ALLOWED_ORIGINS = 'https://yourdomain.com';
$CLIENT_TOKEN = 'a-very-secret-token';
```

Contoh cara mengirim token dari client (jika Anda memilih memakai token):

```js
// --- jika Anda memutuskan menyuntikkan token ke halaman dari server ---
const CLIENT_TOKEN = 'PASTE_TOKEN_HERE'; // atau lebih baik: inject via server-side template

fetch('api_proxy.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Client-Token': CLIENT_TOKEN
    },
    body: JSON.stringify({ action: 'geocode', lat, lng })
})
```

Peringatan: Token yang dimasukkan ke JavaScript masih bisa dilihat pengguna tech-savvy. Untuk solusi produksi lebih aman, gunakan:

- mekanisme server-side untuk menyuntik token ke halaman hanya untuk session terautentikasi, atau
- endpoint perolehan token singkat (short-lived) yang mengeluarkan token setelah autentikasi.

Jika Anda mau, saya bisa tambahkan: (A) bantuan memasang env vars pada Apache, (B) skrip deploy yang menaruh `secret_config.php` di server, atau (C) endpoint kecil untuk menghasilkan token singkat bagi client terautentikasi.