# Meeting Room Booking REST API

RESTful API untuk pemesanan ruang rapat berbasis **Laravel**, **PostgreSQL**, **JWT**, dan **Docker** dengan jaminan pencegahan *double booking* pada permintaan paralel.

## Arsitektur

- **Controller** (`app/Http/Controllers/Api/V1/`): Validasi request dan formatting response JSON.
- **Service** (`app/Services/`): Aturan bisnis, validasi waktu UTC, serta transaksi database (`DB::transaction`) dengan *pessimistic row locking* (`SELECT ... FOR UPDATE`).
- **Repository** (`app/Repositories/`): Abstraksi seluruh query PostgreSQL.
- **Database** (`schema.sql` & `queries.sql`): Inisialisasi otomatis tabel + 3 akun awal saat container PostgreSQL menyala, serta query SQL analitik.

## Akun Awal (Seeder)

| Nama | Email | Password | Role |
| :--- | :--- | :--- | :--- |
| Administrator | `admin@test.com` | `password123` | `ADMIN` |
| User Satu | `user1@test.com` | `password123` | `USER` |
| User Dua | `user2@test.com` | `password123` | `USER` |

## Menjalankan Aplikasi via Docker

```bash
docker-compose up -d --build