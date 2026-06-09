# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Secure Digital Document Management System dengan Enkripsi dan Audit Log Berbasis Web

---

# 1. Informasi Umum

## Nama Sistem

Secure Digital Document Management System (SDDMS)

## Platform

Web Application

## Teknologi

### Backend

- Laravel 12
- PHP 8.3

### Frontend

- Laravel Blade
- Bootstrap 5
- AdminLTE

### Database

- MySQL

### Security

- Laravel Authentication
- AES-256 Encryption
- Role Based Access Control (RBAC)
- Audit Logging

---

# 2. Latar Belakang

Banyak organisasi masih menyimpan dokumen digital tanpa mekanisme keamanan yang memadai. Dokumen penting seperti laporan, kontrak, data pegawai, dan dokumen akademik rentan terhadap pencurian, modifikasi, maupun akses tidak sah.

Untuk mengatasi masalah tersebut diperlukan sistem manajemen dokumen digital yang menerapkan mekanisme enkripsi file, pengaturan hak akses pengguna, serta pencatatan aktivitas pengguna melalui audit log sehingga keamanan dan integritas dokumen dapat terjamin.

---

# 3. Tujuan Sistem

1. Menyimpan dokumen secara aman.
2. Melindungi dokumen menggunakan enkripsi.
3. Mengatur hak akses pengguna.
4. Mendokumentasikan seluruh aktivitas pengguna.
5. Memfasilitasi pertukaran dokumen antar pengguna secara aman.

---

# 4. Aktor Sistem

## Admin

Hak akses:

- Mengelola pengguna
- Mengelola role
- Melihat seluruh dokumen
- Melihat audit log
- Menonaktifkan akun pengguna
- Mencetak laporan aktivitas
- Monitoring aktivitas pengguna

## User

Hak akses:

- Upload dokumen
- Download dokumen
- Mengirim dokumen
- Menerima dokumen
- Mengelola dokumen milik sendiri

---

# 5. Fitur Utama

## 5.1 Login

Fitur:

- Login email
- Logout
- Remember me
- Validasi akun

Keamanan:

- Password Hashing
- Session Authentication
- CSRF Protection

---

## 5.2 Dashboard

Menampilkan:

- Total dokumen
- Total user
- Total dokumen terenkripsi
- Aktivitas terbaru
- Statistik penggunaan sistem

---

## 5.3 Upload Dokumen

Format file:

- PDF
- DOCX
- XLSX
- JPG
- PNG

Proses:

1. User memilih file.
2. Sistem memvalidasi file.
3. Sistem mengenkripsi file.
4. File terenkripsi disimpan.
5. Audit log dibuat.

---

## 5.4 Manajemen Dokumen

Fitur:

- Lihat dokumen
- Detail dokumen
- Cari dokumen
- Download dokumen
- Hapus dokumen

Informasi dokumen:

- Nama file
- Pemilik
- Ukuran file
- Tanggal upload
- Status enkripsi

---

## 5.5 Enkripsi Dokumen

Metode:

AES-256 Encryption

Alur:

Upload File
↓
Encrypt
↓
Storage
↓
Decrypt Saat Download

File yang tersimpan pada server berupa file terenkripsi.

---

## 5.6 Pengiriman Dokumen Antar User

Fitur:

- Pilih dokumen
- Pilih penerima
- Tambahkan pesan
- Kirim dokumen

Hak akses:

- View Only
- Download

Status pengiriman:

- Terkirim
- Dibaca
- Diunduh

---

## 5.7 File Masuk

Fitur:

- Daftar dokumen diterima
- Download dokumen
- Riwayat penerimaan dokumen

---

## 5.8 File Terkirim

Fitur:

- Riwayat pengiriman
- Status penerima
- Riwayat download penerima

---

## 5.9 Audit Log

Mencatat:

- Login
- Logout
- Upload
- Download
- Delete
- Share File
- Failed Login
- User Management

Data log:

- Waktu
- User
- Aktivitas
- IP Address
- Status

---

## 5.10 Manajemen User

Admin dapat:

- Tambah user
- Edit user
- Hapus user
- Nonaktifkan user
- Mengatur role

---

# 6. Flow Sistem

## Flow Login

Login
↓
Validasi User
↓
Dashboard Sesuai Role

---

## Flow Upload Dokumen

User Upload
↓
Validasi File
↓
Encrypt File
↓
Simpan Storage
↓
Catat Audit Log

---

## Flow Kirim Dokumen

Pilih Dokumen
↓
Pilih Penerima
↓
Kirim
↓
Simpan Data Sharing
↓
Audit Log

---

## Flow Download Dokumen

Klik Download
↓
Cek Hak Akses
↓
Decrypt Sementara
↓
Download File
↓
Audit Log

---

# 7. Struktur Database

## users

- id
- name
- email
- password
- role_id
- status
- created_at

## roles

- id
- name

## documents

- id
- owner_id
- file_name
- file_path
- file_size
- encrypted
- created_at

## document_shares

- id
- document_id
- sender_id
- receiver_id
- permission
- status
- created_at

## audit_logs

- id
- user_id
- activity
- description
- ip_address
- created_at

---

# 8. Struktur Menu

## Admin

- Dashboard
- User Management
- Documents
- Audit Logs
- Activity Reports
- Profile

## User

- Dashboard
- My Documents
- Upload Document
- Incoming Files
- Sent Files
- Profile

---

# 9. Kebutuhan Non Fungsional

## Security

- Password Hashing
- AES-256 Encryption
- Role Based Access
- Session Security
- CSRF Protection

## Performance

- Maksimal upload 10 MB
- Waktu akses < 3 detik

## Availability

- Sistem dapat diakses 24 jam

## Usability

- Responsive Desktop
- Responsive Mobile

---

# 10. Halaman Sistem

1. Login Page
2. Dashboard
3. Upload Document
4. My Documents
5. Document Detail
6. Incoming Files
7. Sent Files
8. User Management
9. Audit Log
10. Activity Report
11. Profile
12. Change Password

---

# 11. Target Implementasi

## Sprint 1

- Authentication
- Role Management
- Dashboard

## Sprint 2

- Upload Dokumen
- Enkripsi Dokumen
- Download Dokumen

## Sprint 3

- Share Dokumen
- File Masuk
- File Terkirim

## Sprint 4

- Audit Log
- Laporan Aktivitas Admin
- Testing dan Deployment
