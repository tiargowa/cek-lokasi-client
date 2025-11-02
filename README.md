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