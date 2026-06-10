# SecureDocs Task List

Dokumen ini adalah roadmap implementasi Secure Digital Document Management System (SDDMS). Urutan dibuat dari fondasi teknis, authentication, RBAC, dokumen terenkripsi, sharing, audit log, UI, testing, sampai deployment.

Status:

- `[x]` Selesai
- `[~]` Sebagian selesai
- `[ ]` Belum dikerjakan

---

## 0. Fondasi Project

- [x] Install dependency PHP dengan `composer install`.
- [x] Install dependency frontend dengan `npm install`.
- [x] Buat `.env` dari `.env.example`.
- [x] Generate `APP_KEY`.
- [x] Jalankan migration awal.
- [x] Pastikan `php artisan test` berjalan.
- [x] Tambahkan `package-lock.json` agar dependency frontend reproducible.
- [x] Perkuat `.gitignore` untuk `.env`, `vendor`, `node_modules`, sqlite lokal, cache Laravel, compiled views, log, dan file sistem lokal.
- [x] Hapus file `.DS_Store` lokal.
- [ ] Sesuaikan metadata project di `composer.json` dari skeleton Laravel menjadi nama project SDDMS.
- [ ] Sesuaikan `APP_NAME` di `.env.example` menjadi nama aplikasi.
- [ ] Pastikan versi teknologi di PRD konsisten dengan `composer.json` dan `package.json`.
- [ ] Tambahkan dokumentasi setup lokal di `README.md`.

Acceptance criteria:

- Developer baru bisa menjalankan `composer install`, `npm install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`, dan `php artisan test` tanpa langkah tersembunyi.
- File generated/local tidak muncul sebagai perubahan git.

---

## 1. Database dan Model Domain

### 1.1 Role dan User

- [x] Buat model `Role`.
- [x] Buat migration `roles`.
- [x] Tambahkan `role_id` ke `users`.
- [x] Tambahkan `status` ke `users`.
- [x] Tambahkan relasi `User belongsTo Role`.
- [x] Tambahkan relasi `Role hasMany User`.
- [x] Tambahkan helper `User::hasRole()`.
- [x] Tambahkan helper `User::isActive()`.
- [x] Seeder role awal: `admin` dan `user`.
- [x] Hapus role `auditor` dari PRD dan seeder.
- [x] Tetapkan enum/constant untuk role agar tidak hardcode string berulang.
- [x] Tetapkan enum/constant untuk status user: `active`, `inactive`.

### 1.2 Document

- [x] Buat model `Document`.
- [x] Buat migration `documents`.
- [x] Tambahkan field `owner_id`, `file_name`, `original_name`, `file_path`, `file_size`, `mime_type`, `file_hash`, `encrypted`.
- [x] Tambahkan relasi `Document belongsTo User` sebagai owner.
- [x] Tambahkan relasi `Document hasMany DocumentShare`.
- [x] Tambahkan relasi `User hasMany Document`.
- [ ] Tambahkan soft delete jika dokumen perlu bisa dipulihkan.
- [ ] Tambahkan field opsional `deleted_by` atau `last_accessed_at` bila dibutuhkan untuk audit lanjutan.

### 1.3 Document Share

- [x] Buat model `DocumentShare`.
- [x] Buat migration `document_shares`.
- [x] Tambahkan field `document_id`, `sender_id`, `receiver_id`, `permission`, `status`, `message`, `read_at`, `downloaded_at`.
- [x] Tambahkan unique key `document_id + receiver_id`.
- [x] Tambahkan relasi ke `document`, `sender`, dan `receiver`.
- [x] Tambahkan relasi `User hasMany sentShares`.
- [x] Tambahkan relasi `User hasMany receivedShares`.
- [x] Tetapkan enum/constant permission: `view`, `download`.
- [x] Tetapkan enum/constant status share: `sent`, `read`, `downloaded`, `revoked`.

### 1.4 Audit Log

- [x] Buat model `AuditLog`.
- [x] Buat migration `audit_logs`.
- [x] Tambahkan field `user_id`, `activity`, `description`, `ip_address`, `user_agent`, `status`, `metadata`.
- [x] Tambahkan relasi `AuditLog belongsTo User`.
- [x] Tambahkan relasi `User hasMany AuditLog`.
- [ ] Tetapkan daftar activity resmi: `login`, `logout`, `failed_login`, `upload`, `download`, `delete`, `share_file`, `unshare_file`, `user_management`.
- [x] Buat service khusus untuk menulis audit log agar controller tidak duplikasi logic.

Acceptance criteria:

- Semua migration bisa jalan dari database kosong.
- Semua relasi Eloquent punya test minimal.
- Semua status/permission/role memakai constant atau enum agar konsisten.

---

## 2. Authentication

- [x] Pilih pendekatan auth: custom controller JSON berbasis Laravel session guard.
- [x] Buat halaman login.
- [x] Implement login email dan password.
- [x] Implement logout.
- [x] Implement remember me.
- [x] Blokir login untuk user dengan `status = inactive`.
- [x] Catat audit log `login` saat login berhasil.
- [x] Catat audit log `logout` saat logout.
- [x] Catat audit log `failed_login` saat login gagal.
- [x] Tambahkan throttle pada proses login.
- [x] Tambahkan validasi request login.
- [ ] Tambahkan redirect dashboard sesuai role.
- [x] Tambahkan test login berhasil.
- [x] Tambahkan test login gagal.
- [x] Tambahkan test user inactive tidak bisa login.
- [x] Tambahkan test audit log login/logout/failed login.

Acceptance criteria:

- User aktif bisa login dan logout.
- User inactive tidak bisa login.
- Percobaan login dicatat ke audit log.
- Endpoint auth terlindungi CSRF dan throttle.

---

## 3. Authorization dan RBAC

- [x] Buat middleware role.
- [x] Register middleware role di bootstrap/app config Laravel.
- [x] Buat policy untuk `Document`.
- [x] Buat policy untuk `DocumentShare`.
- [x] Buat policy untuk `AuditLog`.
- [x] Admin bisa melihat seluruh dokumen.
- [x] User hanya bisa melihat dokumen milik sendiri dan dokumen yang dibagikan kepadanya.
- [x] Admin bisa melihat audit log.
- [x] User tidak bisa melihat audit log global.
- [x] Admin bisa mengelola user.
- [x] User tidak bisa mengelola user.
- [x] Terapkan policy di controller, bukan hanya helper private.
- [x] Tambahkan test akses Admin.
- [x] Tambahkan test akses User.
- [x] Tambahkan test forbidden untuk akses lintas user.

Acceptance criteria:

- Semua akses sensitif melewati policy/middleware.
- Tidak ada user biasa yang bisa membaca atau mengubah data user lain tanpa share permission.

---

## 4. Dashboard

### 4.1 Admin Dashboard

- [ ] Buat route dashboard admin.
- [ ] Tampilkan total user.
- [ ] Tampilkan total dokumen.
- [ ] Tampilkan total dokumen terenkripsi.
- [ ] Tampilkan total share.
- [ ] Tampilkan aktivitas terbaru.
- [ ] Tampilkan statistik upload/download per periode.
- [ ] Tambahkan filter periode statistik.
- [ ] Tambahkan link cepat ke User Management, Documents, Audit Logs, dan Activity Reports.

### 4.2 User Dashboard

- [ ] Buat route dashboard user.
- [ ] Tampilkan total dokumen milik user.
- [ ] Tampilkan total file masuk.
- [ ] Tampilkan total file terkirim.
- [ ] Tampilkan aktivitas terbaru milik user.
- [ ] Tampilkan tombol upload dokumen.

Acceptance criteria:

- Setelah login, Admin dan User diarahkan ke dashboard masing-masing.
- Dashboard tidak melakukan query N+1.

---

## 5. Upload Dokumen dan Enkripsi

- [x] Buat endpoint store awal di `DocumentController`.
- [x] Validasi tipe file: PDF, DOCX, XLSX, JPG, JPEG, PNG.
- [x] Validasi ukuran maksimal 10 MB.
- [x] Simpan file ke storage local private.
- [x] Enkripsi konten file sebelum disimpan.
- [x] Simpan metadata dokumen.
- [x] Simpan hash file.
- [x] Catat audit log upload.
- [ ] Pindahkan proses enkripsi ke service khusus, misalnya `DocumentStorageService`.
- [ ] Pastikan file terenkripsi tidak bisa dibuka langsung dari storage.
- [ ] Pertimbangkan streaming encryption untuk file besar agar tidak seluruh file masuk memory.
- [x] Tambahkan validasi ekstensi dan MIME dengan strategi yang konsisten.
- [x] Tambahkan proteksi nama file agar tidak menggunakan input user sebagai path.
- [x] Tambahkan halaman upload dokumen.
- [x] Tambahkan progress/loading state di UI.
- [x] Tambahkan test upload berhasil.
- [x] Tambahkan test upload tipe file ditolak.
- [ ] Tambahkan test upload file terlalu besar.
- [ ] Tambahkan test file yang tersimpan benar-benar terenkripsi.

Acceptance criteria:

- File asli tidak pernah tersimpan plaintext di server.
- Upload valid menghasilkan metadata dokumen dan audit log.
- Upload invalid ditolak dengan pesan validasi jelas.

---

## 6. Manajemen Dokumen

- [x] Buat endpoint list dokumen milik user.
- [x] Buat endpoint detail dokumen.
- [x] Buat endpoint update metadata dokumen.
- [x] Buat endpoint delete dokumen.
- [x] Catat audit log delete.
- [x] Admin list bisa melihat semua dokumen.
- [x] User list hanya melihat dokumen milik sendiri.
- [ ] Tambahkan pencarian dokumen berdasarkan nama.
- [ ] Tambahkan filter berdasarkan tanggal upload.
- [ ] Tambahkan filter berdasarkan status enkripsi.
- [ ] Tambahkan pagination di UI.
- [x] Buat halaman My Documents.
- [ ] Buat halaman Document Detail.
- [x] Buat modal/halaman edit metadata dokumen.
- [x] Tambahkan konfirmasi sebelum delete.
- [ ] Tambahkan test list dokumen milik sendiri.
- [x] Tambahkan test user tidak bisa edit/delete dokumen user lain.
- [x] Tambahkan test admin bisa melihat semua dokumen.

Acceptance criteria:

- User tidak bisa mengakses dokumen user lain tanpa share.
- Delete dokumen menghapus metadata dan file terenkripsi dari storage.

---

## 7. Download Dokumen dan Dekripsi

- [x] Buat endpoint download awal.
- [x] Dekripsi file saat download.
- [x] Set response header `Content-Type`.
- [x] Set response header attachment filename.
- [x] Catat audit log download.
- [x] Update `document_shares.downloaded_at` saat receiver download dokumen shared.
- [x] Update status share menjadi `downloaded`.
- [x] Pastikan permission `view` tidak bisa download.
- [x] Pastikan owner selalu bisa download dokumennya sendiri.
- [ ] Tambahkan rate limit download bila diperlukan.
- [ ] Tambahkan test owner bisa download.
- [x] Tambahkan test receiver permission download bisa download.
- [x] Tambahkan test receiver permission view tidak bisa download.
- [x] Tambahkan test user tanpa akses tidak bisa download.
- [x] Tambahkan test audit log download.

Acceptance criteria:

- File hanya didekripsi saat response download dibuat.
- Hak akses download mengikuti owner/share permission.

---

## 8. Share Dokumen

- [x] Buat endpoint list share.
- [x] Buat endpoint create/update share.
- [x] Buat endpoint detail share.
- [x] Buat endpoint delete/revoke share.
- [x] Validasi receiver tidak boleh user yang sama.
- [x] Permission awal: `view` dan `download`.
- [x] Status awal: `sent`, `read`.
- [x] Catat audit log share dan unshare.
- [x] Buat halaman kirim dokumen.
- [x] Buat dropdown/list penerima.
- [x] Buat pilihan permission.
- [x] Buat input pesan.
- [x] Buat halaman File Masuk.
- [x] Buat halaman File Terkirim.
- [x] Update status menjadi `read` saat receiver membuka detail share.
- [x] Update status menjadi `downloaded` saat receiver download.
- [x] Cegah share duplikat dengan UI yang jelas.
- [x] Batasi receiver sharing hanya untuk role `user`, bukan `admin`.
- [ ] Tambahkan test owner bisa share dokumen.
- [ ] Tambahkan test non-owner tidak bisa share dokumen.
- [ ] Tambahkan test receiver bisa melihat file masuk.
- [ ] Tambahkan test sender bisa melihat file terkirim.
- [ ] Tambahkan test revoke share.

Acceptance criteria:

- Hanya owner dokumen yang bisa membagikan dokumen.
- Receiver hanya mendapat akses sesuai permission.
- Riwayat file masuk dan terkirim dapat dilihat.

---

## 9. Audit Log dan Activity Report

- [x] Buat endpoint audit log read-only.
- [x] Batasi audit log untuk Admin lewat pengecekan role awal.
- [x] Pindahkan authorization ke policy/middleware.
- [x] Buat service `AuditLogger`.
- [x] Catat semua aktivitas wajib: login, logout, upload, download, delete, share, failed login, user management.
- [ ] Tambahkan filter audit log berdasarkan user.
- [ ] Tambahkan filter audit log berdasarkan activity.
- [ ] Tambahkan filter audit log berdasarkan status.
- [ ] Tambahkan filter audit log berdasarkan tanggal.
- [x] Buat halaman Audit Logs.
- [ ] Buat halaman Activity Report.
- [ ] Tambahkan export/cetak laporan PDF atau print view.
- [ ] Tambahkan pagination dan sorting.
- [x] Tambahkan test Admin bisa melihat audit log.
- [x] Tambahkan test User tidak bisa melihat audit log.
- [ ] Tambahkan test filter audit log.

Acceptance criteria:

- Admin bisa melakukan monitoring aktivitas lengkap.
- User biasa tidak bisa membaca audit log global.
- Laporan aktivitas bisa dicetak atau diekspor.

---

## 10. User Management

- [x] Buat `UserController`.
- [x] Buat route resource user management untuk Admin.
- [x] Buat halaman daftar user.
- [x] Buat form tambah user.
- [x] Buat form edit user.
- [x] Buat action nonaktifkan user.
- [x] Buat action aktifkan user.
- [ ] Buat action reset password admin-side bila diperlukan.
- [x] Validasi email unik.
- [x] Validasi password kuat.
- [x] Validasi role hanya `admin` atau `user`.
- [x] Cegah Admin menonaktifkan dirinya sendiri tanpa guard khusus.
- [x] Catat audit log tambah user.
- [x] Catat audit log edit user.
- [x] Catat audit log nonaktifkan/aktifkan user.
- [x] Tambahkan test Admin bisa membuat user.
- [x] Tambahkan test User biasa tidak bisa membuat user.
- [x] Tambahkan test user inactive tidak bisa login.

Acceptance criteria:

- Admin bisa mengelola user dan role.
- Semua perubahan user tercatat di audit log.

---

## 11. Profile dan Change Password

- [x] Buat halaman profile.
- [x] User bisa update nama.
- [x] User bisa update email dengan validasi unik.
- [x] Buat halaman change password.
- [x] Validasi password lama.
- [x] Hash password baru.
- [ ] Logout session lain setelah password berubah bila diperlukan.
- [x] Catat audit log change password.
- [x] Tambahkan test update profile.
- [x] Tambahkan test change password berhasil.
- [x] Tambahkan test change password gagal jika password lama salah.

Acceptance criteria:

- User bisa mengelola profil sendiri.
- Password tidak pernah disimpan plaintext.

---

## 12. Frontend Layout dan UI

- [ ] Putuskan konsistensi stack frontend: Bootstrap 5/AdminLTE sesuai PRD atau Tailwind sesuai dependency saat ini.
- [ ] Jika tetap Bootstrap/AdminLTE, pasang dependency yang diperlukan.
- [ ] Buat layout utama authenticated.
- [ ] Buat sidebar menu Admin.
- [ ] Buat sidebar menu User.
- [ ] Buat topbar dengan info user dan logout.
- [ ] Buat responsive layout desktop dan mobile.
- [ ] Buat komponen alert/success/error.
- [ ] Buat komponen table dengan empty state.
- [ ] Buat komponen pagination.
- [ ] Buat komponen confirmation dialog.
- [x] Pastikan semua form memakai CSRF.
- [ ] Pastikan semua output Blade escaped dengan `{{ }}`.
- [ ] Smoke test halaman utama di browser.

Acceptance criteria:

- Admin dan User melihat menu sesuai role.
- UI responsive untuk desktop dan mobile.
- Tidak ada halaman utama yang masih memakai welcome page Laravel.

---

## 13. Security Hardening

- [ ] Set `APP_DEBUG=false` untuk contoh production.
- [ ] Set rekomendasi `SESSION_ENCRYPT=true`.
- [ ] Set rekomendasi `SESSION_SECURE_COOKIE=true` untuk HTTPS.
- [ ] Pastikan file storage dokumen tidak berada di public disk.
- [ ] Pastikan route download selalu melewati authorization.
- [ ] Tambahkan throttling untuk upload/download jika diperlukan.
- [ ] Tambahkan password rule yang kuat.
- [ ] Tambahkan proteksi mass assignment pada semua model.
- [ ] Review semua raw query. Hindari raw SQL dengan input user.
- [ ] Pastikan semua POST/PUT/PATCH/DELETE memakai CSRF.
- [x] Validasi MIME, extension, dan size upload.
- [x] Audit dependency rutin: `composer audit --locked` dan `npm audit`.
- [ ] Tambahkan dokumentasi rotasi `APP_KEY` atau strategi key management jika diperlukan.

Acceptance criteria:

- Tidak ada file sensitif yang bisa diakses publik.
- Semua endpoint sensitif terlindungi auth dan authorization.
- Dependency audit bersih.

---

## 14. Performance dan Reliability

- [ ] Pastikan query dashboard memakai aggregate efisien.
- [ ] Hindari N+1 dengan eager loading.
- [ ] Tambahkan index untuk filter audit log dan dokumen.
- [ ] Evaluasi streaming download untuk file besar.
- [ ] Evaluasi queue untuk proses berat jika file/enkripsi makin besar.
- [ ] Tambahkan cleanup untuk file orphan jika transaksi database gagal.
- [ ] Tambahkan handling error untuk file storage hilang/rusak.
- [ ] Tambahkan backup strategy database dan storage.

Acceptance criteria:

- Halaman list utama tetap cepat untuk data besar.
- Tidak ada file orphan yang mudah menumpuk.

---

## 15. Testing

- [x] Test baseline Laravel.
- [x] Test relasi role user.
- [x] Test relasi document, share, dan audit log.
- [x] Test auth login/logout.
- [x] Test RBAC Admin/User.
- [x] Test upload dokumen.
- [ ] Test enkripsi storage.
- [x] Test download dokumen.
- [x] Test share dokumen.
- [x] Test file masuk.
- [x] Test file terkirim.
- [x] Test audit log.
- [x] Test user management.
- [x] Test profile dan change password.
- [x] Test validasi upload.
- [x] Test forbidden access.
- [ ] Tambahkan smoke test halaman penting.
- [ ] Tambahkan test untuk route list agar tidak ada route rusak.

Acceptance criteria:

- `php artisan test --compact` hijau.
- Jalur security-critical punya test positif dan negatif.

---

## 16. Documentation

- [x] Buat PRD.
- [x] Revisi PRD: hapus Auditor dan gabungkan ke Admin.
- [x] Buat task list implementasi.
- [ ] Update `README.md` dengan cara setup.
- [ ] Dokumentasikan struktur role dan permission.
- [ ] Dokumentasikan flow upload/enkripsi/download.
- [ ] Dokumentasikan flow sharing.
- [ ] Dokumentasikan audit activity.
- [ ] Dokumentasikan environment variable penting.
- [ ] Dokumentasikan cara menjalankan test.
- [ ] Dokumentasikan cara deployment.

Acceptance criteria:

- Developer dan reviewer bisa memahami project tanpa membaca seluruh kode.
- Setup lokal dan deployment punya instruksi yang bisa diikuti.

---

## 17. Deployment

- [ ] Tentukan environment production.
- [ ] Siapkan database MySQL production.
- [ ] Siapkan storage private production.
- [ ] Set `.env` production.
- [ ] Jalankan `composer install --no-dev --optimize-autoloader`.
- [ ] Jalankan `npm ci` dan build asset.
- [ ] Jalankan `php artisan config:cache`.
- [ ] Jalankan `php artisan route:cache`.
- [ ] Jalankan `php artisan view:cache`.
- [ ] Jalankan migration production dengan prosedur backup.
- [ ] Setup scheduler bila ada laporan/cleanup berkala.
- [ ] Setup queue worker bila proses async dipakai.
- [ ] Setup log monitoring.
- [ ] Setup backup database dan storage.
- [ ] Smoke test production setelah deploy.

Acceptance criteria:

- Aplikasi bisa berjalan di production dengan debug off.
- Storage dokumen aman dan tidak public.
- Ada prosedur backup dan rollback.

---

## 18. Milestone Rekomendasi

### Milestone 1: Auth dan RBAC

- [x] Authentication lengkap.
- [x] Middleware role.
- [x] Policy dasar.
- [~] Dashboard Admin/User.
- [x] User management dasar.

### Milestone 2: Dokumen Terenkripsi

- [x] Upload UI.
- [ ] Enkripsi service.
- [x] My Documents.
- [ ] Detail dokumen.
- [x] Download aman.
- [x] Test upload/download/enkripsi.

### Milestone 3: Sharing

- [x] Share dokumen UI.
- [x] File Masuk.
- [x] File Terkirim.
- [x] Permission view/download.
- [x] Status sent/read/downloaded.

### Milestone 4: Audit dan Laporan

- [ ] AuditLogger service.
- [ ] Audit Logs UI.
- [ ] Activity Reports.
- [ ] Print/export laporan.
- [ ] Security test dan hardening.

### Milestone 5: Polish dan Deploy

- [ ] UI responsive final.
- [ ] README lengkap.
- [ ] Full test suite.
- [x] Dependency audit.
- [ ] Deployment checklist.
