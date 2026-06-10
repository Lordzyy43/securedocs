# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Secure Digital Document Management System (SDDMS) dengan Enkripsi AES-256 & Audit Log Berbasis Web

---

# 1. Informasi Umum

## Nama Sistem
Secure Digital Document Management System (SDDMS)

## Platform
Decoupled Web Application (Frontend SPA & Backend API)

## Spesifikasi Teknologi & Library

### Backend API
*   **Framework:** Laravel 13
*   **Runtime:** PHP 8.3
*   **Database:** MySQL 8.x
*   **Autentikasi:** Laravel Session Guard (Stateful Cookie-based) dengan middleware `web`

### Frontend SPA
*   **Library Utama:** React 19 (JSX)
*   **Build Tool:** Vite 8.x
*   **CSS Framework:** Tailwind CSS v4
*   **Routing:** React Router v7
*   **Library Pendukung:** Lucide React (Icons), React Hook Form, Radix UI (Headless components / shadcn style)

---

# 2. Latar Belakang & Aspek Keamanan
Banyak organisasi menyimpan dokumen penting tanpa mekanisme proteksi data diam (*data at rest*) maupun data berpindah (*data in transit*). Dokumen sensitif rentan terhadap pencurian oleh pihak luar maupun ancaman internal (*insider threat*) dari administrator server yang memiliki akses langsung ke filesystem.

SDDMS dirancang untuk mengatasi masalah ini dengan memisahkan hak akses sistem (RBAC), mengenkripsi file secara transparan menggunakan **AES-256-CBC** sebelum ditulis ke penyimpanan fisik, memverifikasi integritas file dengan hash **SHA-256**, serta merekam setiap aktivitas sensitif ke dalam **Audit Log** yang tidak dapat diubah oleh pengguna biasa.

---

# 3. Tujuan Sistem
1.  **Confidentiality (Kerahasiaan):** Dokumen disimpan dalam keadaan terenkripsi di server. Tanpa otorisasi yang valid, konten dokumen tidak dapat dibaca.
2.  **Integrity (Integritas):** Mendeteksi modifikasi atau kerusakan file di server menggunakan pencocokan hash SHA-256.
3.  **Authenticity & Authorization:** Mengatur hak akses secara ketat berdasarkan peran (*Role-Based Access Control*).
4.  **Non-Repudiation (Nir-penyangkalan):** Merekam seluruh aktivitas pengguna (login, unggah, unduh, hapus, sharing) ke dalam audit log forensik.
5.  **Secure Document Exchange:** Memungkinkan pengguna saling bertukar dokumen secara aman dengan kontrol hak akses pratinjau (*view only*) atau unduh (*download*).

---

# 4. Aktor & Matriks Kontrol Akses (Access Control Matrix)

Sistem ini memiliki dua aktor utama dengan pembagian hak akses sebagai berikut:

| Fitur / Aksi | Admin | User (Owner) | User (Recipient / Shared) | Guest |
| :--- | :---: | :---: | :---: | :---: |
| **Login / Logout** | Ya | Ya | Ya | Ya |
| **Melihat Dashboard Ringkasan** | Ya (Global) | Ya (Personal) | Ya (Personal) | Tidak |
| **Mengunggah Dokumen baru** | Tidak | Ya | Tidak | Tidak |
| **Melihat Daftar Dokumen** | Ya (Semua) | Ya (Milik Sendiri) | Ya (Hanya yang di-share) | Tidak |
| **Mengubah Nama Dokumen** | Tidak | Ya | Tidak | Tidak |
| **Menghapus Dokumen dari Server** | Tidak | Ya | Tidak | Tidak |
| **Mendekripsi & Mengunduh Dokumen**| Tidak | Ya | Ya (Jika permission = `download`) | Tidak |
| **Pratinjau / View Dokumen** | Tidak | Ya | Ya (Jika permission = `view`) | Tidak |
| **Membagikan Dokumen (Share)** | Tidak | Ya | Tidak | Tidak |
| **Mencabut Hak Akses Sharing** | Tidak | Ya | Tidak | Tidak |
| **Melihat Log Aktivitas (Audit Log)** | Ya (Semua) | Tidak | Tidak | Tidak |
| **Mencetak Laporan Lintas Pengguna**| Ya | Tidak | Tidak | Tidak |
| **Membuat / Edit / Nonaktifkan User**| Ya | Tidak | Tidak | Tidak |

### Catatan Penting Mengenai Desain Keamanan (*Least Privilege*):
*   **Admin tidak dapat mengunduh atau mendekripsi file dokumen milik pengguna lain.** Admin hanya memiliki wewenang administratif (melihat metadata dokumen seperti nama, ukuran, tipe, dan pemilik) serta memantau log. Ini mencegah *insider threat*.
*   **Admin tidak diperbolehkan menghapus pengguna secara langsung (Hard Delete)** jika pengguna tersebut memiliki dokumen aktif. Admin hanya boleh mengubah status pengguna menjadi **`inactive` (Nonaktif)**. Hal ini dilakukan untuk menghindari hilangnya kepemilikan file secara mendadak yang dapat memicu *orphaned file* di filesystem server, sekaligus menjaga integritas data audit log.
*   **Admin tidak dapat menonaktifkan atau mengubah role akunnya sendiri** untuk mencegah penguncian sistem secara tidak sengaja (*self-lockout*).

---

# 5. Fitur Utama & Spesifikasi Teknis

## 5.1 Autentikasi Stateful & Keamanan Sesi
*   **Mekanisme:** Menggunakan Session Cookie bawaan Laravel yang dilindungi dengan atribut `HttpOnly` (mencegah pembacaan cookie oleh JavaScript untuk memitigasi serangan XSS) dan `Secure` (hanya dikirimkan melalui HTTPS).
*   **CSRF Protection:** Setiap request modifikasi data (POST, PUT, DELETE) wajib menyertakan token CSRF (`X-XSRF-TOKEN`) yang divalidasi oleh backend.
*   **Brute Force Mitigation:** Pembatasan percobaan login (Rate Limiting / Throttling) pada endpoint `/login` (maksimal 5 kali percobaan gagal per menit).
*   **User Status Guard:** Pengguna dengan status `inactive` otomatis ditolak saat mencoba login atau jika session miliknya masih aktif akan langsung dideautentikasi.

## 5.2 Dashboard Dinamis
Menampilkan ringkasan data real-time berbasis API:
*   **Admin Dashboard:**
    *   Total Pengguna Aktif.
    *   Total File Dokumen di Server (Terenkripsi).
    *   Total Dokumen yang Sedang Dibagikan.
    *   Grafik statistik aktivitas upload/download per minggu/bulan.
    *   Daftar 5 aktivitas sistem terbaru dari Audit Log.
*   **User Dashboard:**
    *   Total Dokumen Milik Sendiri.
    *   Total File Masuk (dibagikan oleh user lain).
    *   Total File Terkirim (dibagikan ke user lain).
    *   Daftar 5 aktivitas terakhir milik user bersangkutan.

## 5.3 Protokol Unggah & Enkripsi Dokumen
*   **Format Valid:** PDF, DOCX, XLSX, JPG, JPEG, PNG.
*   **Batas Ukuran:** Maksimal 10 MB.
*   **Alur Kriptografi Unggah:**
    1.  Pengguna memilih file di frontend React.
    2.  Frontend mengirimkan request POST multipart form ke `/documents`.
    3.  Backend memvalidasi ekstensi, MIME type, dan ukuran file.
    4.  Sistem menghitung nilai hash **SHA-256** dari konten file asli (plaintext) untuk disimpan sebagai checksum integritas (`file_hash`).
    5.  Konten file dienkripsi secara simetris menggunakan **AES-256-CBC** dengan memanfaatkan generator enkripsi bawaan Laravel (berbasis `APP_KEY` yang unik pada server).
    6.  File terenkripsi disimpan ke disk lokal private (`storage/app/documents/{user_id}/{uuid}.bin`). File tidak boleh ditaruh di folder publik (`public/`).
    7.  Metadata dokumen disimpan ke database.
    8.  Audit log mencatat aktivitas `upload` beserta metadata terkait.

## 5.4 Download & Dekripsi Transparan
*   **Alur Kriptografi Unduh:**
    1.  Pengguna menekan tombol unduh pada frontend.
    2.  Request dikirim ke `/documents/{id}/download`.
    3.  Backend memverifikasi otorisasi via `DocumentPolicy` (apakah pengguna adalah pemilik dokumen atau memiliki hak akses share dengan permission `download`).
    4.  Backend membaca file terenkripsi dari disk lokal.
    5.  Backend mendekripsi konten file kembali menjadi plaintext menggunakan kunci AES-256 terkait.
    6.  Backend menghitung hash SHA-256 dari konten plaintext hasil dekripsi dan mencocokkannya dengan `file_hash` di database. Jika tidak cocok, proses dibatalkan (mendeteksi korupsi data atau manipulasi server).
    7.  File dikirimkan sebagai stream response dengan header `Content-Disposition: attachment` agar browser mengunduh file dengan nama aslinya.
    8.  Audit log mencatat aktivitas `download`.

## 5.5 Secure Document Sharing (Kirim Dokumen)
*   **Prinsip Kerja:** Pemilik dokumen dapat membagikan akses ke pengguna lain tanpa menduplikasi file fisik di server. Akses diatur melalui tabel relasi `document_shares`.
*   **Tipe Hak Akses (Permission):**
    *   `view`: Penerima hanya diperbolehkan melakukan pratinjau (*preview*) dokumen di browser melalui Viewer khusus (PDF/Gambar). Tombol unduh disembunyikan dan endpoint unduh diblokir untuk user tersebut.
    *   `download`: Penerima diperbolehkan pratinjau sekaligus mengunduh file asli (plaintext).
*   **Pencatatan Status:** Status sharing diupdate secara otomatis:
    *   `sent`: Dokumen berhasil dibagikan.
    *   `read`: Diperbarui ketika penerima pertama kali membuka detail/pratinjau dokumen (`read_at` tercatat).
    *   `downloaded`: Diperbarui ketika penerima mengunduh dokumen (`downloaded_at` tercatat).
*   **Revokasi:** Pemilik dokumen dapat menghapus entri sharing kapan saja untuk langsung mencabut hak akses penerima.

## 5.6 Audit Logging Forensik
Setiap aktivitas sensitif wajib dicatat ke database melalui class `AuditLogger`. Log bersifat **Append-Only** (hanya bisa dibuat, tidak bisa diubah atau dihapus oleh siapapun melalui aplikasi).
Aktivitas yang wajib dicatat meliputi:
*   `login`: Login sukses.
*   `failed_login`: Login gagal (mencatat email dan IP).
*   `logout`: Pengguna keluar.
*   `upload`: Unggah file baru.
*   `download`: Unduh file.
*   `delete`: Penghapusan file.
*   `share_file`: Membagikan file ke pengguna lain.
*   `unshare_file`: Mencabut akses sharing.
*   `user_management`: Aktivitas admin (tambah user, edit user, nonaktifkan user).

---

# 6. Struktur Database (Terverifikasi)

Struktur tabel riil di database MySQL diselaraskan dengan migrasi sistem:

### 6.1 `roles`
Menyimpan daftar peran otorisasi sistem.
*   `id` (BigInt, PK, Auto Increment)
*   `name` (String: `admin`, `user`)
*   `created_at` / `updated_at`

### 6.2 `users`
*   `id` (BigInt, PK)
*   `name` (String)
*   `email` (String, Unique)
*   `password` (String, Hashed)
*   `role_id` (Foreign Key, nullable, references `roles.id` on delete set null)
*   `status` (String: `active`, `inactive`)
*   `created_at` / `updated_at`

### 6.3 `documents`
Menyimpan metadata file terenkripsi.
*   `id` (BigInt, PK)
*   `owner_id` (Foreign Key, references `users.id` on delete cascade)
*   `file_name` (String, nama file terenkripsi unik di filesystem)
*   `original_name` (String, nama file asli saat diunggah)
*   `file_path` (String, path relatif penyimpanan file `.bin`)
*   `file_size` (Unsigned BigInt, ukuran file asli dalam bytes)
*   `mime_type` (String, tipe media file)
*   `file_hash` (String, SHA-256 checksum dari file plaintext)
*   `encrypted` (Boolean, default: true)
*   `created_at` / `updated_at`

### 6.4 `document_shares`
Tabel transaksi sharing dokumen.
*   `id` (BigInt, PK)
*   `document_id` (Foreign Key, references `documents.id` on delete cascade)
*   `sender_id` (Foreign Key, references `users.id` on delete cascade)
*   `receiver_id` (Foreign Key, references `users.id` on delete cascade)
*   `permission` (String, default: `view`, pilihan: `view`, `download`)
*   `status` (String, default: `sent`, pilihan: `sent`, `read`, `downloaded`)
*   `message` (Text, nullable, pesan opsional pengirim)
*   `read_at` (Timestamp, nullable)
*   `downloaded_at` (Timestamp, nullable)
*   `created_at` / `updated_at`
*   *Index Unique:* `[document_id, receiver_id]` (satu dokumen hanya bisa dibagikan sekali ke penerima yang sama).

### 6.5 `audit_logs`
Mencatat jejak audit sistem.
*   `id` (BigInt, PK)
*   `user_id` (Foreign Key, nullable, references `users.id` on delete set null)
*   `activity` (String)
*   `description` (Text, nullable)
*   `ip_address` (String, 45, nullable)
*   `user_agent` (String, nullable)
*   `status` (String, default: `success`, pilihan: `success`, `failure`)
*   `metadata` (JSON, nullable)
*   `created_at` / `updated_at`

---

# 7. Struktur Menu Frontend SPA (React)

Navigasi menu diatur secara dinamis berdasarkan role user hasil autentikasi:

### Menu Aktor: Admin
1.  **Dashboard:** Ringkasan statistik sistem, total file, total user aktif, grafik, audit log terbaru.
2.  **User Management:** Daftar pengguna, tombol tambah user, tombol edit user, tombol aktifkan/nonaktifkan user.
3.  **All Documents:** Daftar seluruh metadata dokumen di sistem (tanpa hak pratinjau/download).
4.  **Audit Logs:** Tabel monitoring aktivitas global dengan filter pencarian (user, aktivitas, tanggal).
5.  **Activity Reports:** Halaman rekap laporan untuk dicetak/diekspor ke PDF.
6.  **Profile & Security:** Edit detail profil admin & ganti password.

### Menu Aktor: User
1.  **Dashboard:** Total file milik sendiri, file masuk, file terkirim, riwayat aktivitas pribadi.
2.  **My Documents:** Daftar dokumen pribadi, aksi rename, delete, share, dan download.
3.  **Upload Document:** Form dropzone file drag-and-drop dengan progress bar.
4.  **Incoming Files (File Masuk):** Dokumen yang dibagikan oleh user lain (dilengkapi penanda hak akses pratinjau / download).
5.  **Sent Files (File Terkirim):** Daftar dokumen yang telah dibagikan ke user lain beserta tracking status (`sent`, `read`, `downloaded`).
6.  **Profile & Security:** Edit nama, email, dan ubah password akun.

---

# 8. Rencana Implementasi (Sprint)

### Sprint 1: Autentikasi Stateful & Fondasi RBAC
*   Backend:
    *   Menerapkan middleware pengecekan role (`role:admin` atau `role:user`).
    *   Endpoint Auth (`/login`, `/logout`, `/me`, `/csrf-token`).
    *   Endpoint User List dasar (untuk dropdown recipient sharing).
*   Frontend:
    *   Setup React Router v7 & Protected Routes.
    *   Halaman Login & Halaman Dashboard Dasar.
    *   Layout Dashboard dengan Sidebar responsive (Tailwind v4).

### Sprint 2: Manajemen Dokumen & Pipeline Enkripsi (AES-256)
*   Backend:
    *   Penerapan file encryption service berbasis AES-256.
    *   Endpoint CRUD `/documents` & Endpoint `/documents/{id}/download`.
    *   Integrasi `DocumentPolicy` (Otorisasi).
*   Frontend:
    *   Halaman "My Documents" & Integrasi API upload (React Dropzone).
    *   Fitur unduh dokumen dengan penanganan blob hasil decrypt API.

### Sprint 3: Mekanisme Sharing Dokumen & Tracking
*   Backend:
    *   Endpoint `/document-shares` (Store, Index, Show, Destroy).
    *   Update timestamp `read_at` & `downloaded_at`.
*   Frontend:
    *   Halaman "Incoming Files" (File Masuk) beserta view pratinjau (PDF/Image Viewer) untuk hak akses `view`.
    *   Halaman "Sent Files" (File Terkirim) untuk memonitor status share.
    *   Modal interaktif "Share Document" pada halaman dokumen pribadi.

### Sprint 4: Audit Logging & User Management Admin
*   Backend:
    *   Penerapan global `AuditLogger` service.
    *   Endpoint `/users` CRUD lengkap untuk Admin.
    *   Endpoint `/audit-logs` dengan parameter filtering.
*   Frontend:
    *   Halaman "User Management" Admin (Tambah, Edit, Toggle Aktif/Nonaktif).
    *   Halaman "Audit Logs" Admin & Fitur Cetak Laporan (Activity Reports).
    *   Testing Keamanan Akhir (Penanganan SQL Injection, XSS, CSRF, & Uji Coba Dekripsi Ilegal).
