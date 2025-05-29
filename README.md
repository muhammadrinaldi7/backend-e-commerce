<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# 🛒 Sistem Backend API E-commerce

**Technical Test – ZedGroup**

Sistem Backend RESTful API untuk e-commerce yang mendukung manajemen produk, kategori, autentikasi pengguna, role-based access (user & admin), integrasi dengan Xendit untuk pembayaran, dan pengelolaan galeri gambar produk.

---

## 🚀 Fitur Utama

-   🔐 **Autentikasi & Role-Based Access**

    -   Register, Login, Logout (Sanctum)
    -   Promote user to admin
    -   Upload avatar pengguna

-   🛍️ **Manajemen Produk & Kategori (Admin)**

    -   Tambah, ubah, hapus produk & kategori
    -   Upload gambar produk utama & galeri produk

-   📦 **Katalog Produk (User)**

    -   List produk dengan filter berdasarkan nama & kategori

-   💸 **Integrasi Xendit**

    -   Buat invoice pembayaran
    -   Webhook untuk update status pembayaran & stok otomatis

-   ✅ **CI/CD dengan GitHub Actions**

---

## ⚙️ Requirements

-   PHP >= 8.2
-   Composer
-   MySQL / MariaDB
-   Laravel 12
-   Laravel Sanctum
-   Xendit PHP SDK

---

## 📂 Struktur Folder

    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │  └── Api/
    │   │   │   ├── AuthController.php
    │   │   │   ├── CategoriesController.php
    │   │   │   ├── OrdersController.php
    │   │   │   ├── PaymentsController.php
    │   │   │   └── ProductController.php
    │   │   │
    │   │   └── Middleware/
    │   │       └── Authenticate.php
    │   │
    │   ├── Models/
    │   │     ├── Category.php
    │   │     ├── Detail_order.php
    │   │     ├── Order.php
    │   │     ├── Payment.php
    │   │     ├── Product.php
    │   │     └── User.php
    │   │
    │   ├── Traits/
    │       └── CheckAdmin.php
    │       └── HasUuid.php
    │
    ├── routes/
    │   └── api.php
    ├── database/
    │   ├── migrations/
    │   ├── seeders/
    │
    └── ...

---

## 📫 Endpoint Dokumentasi API

### 🧑‍💼 Auth

| Method | Endpoint                   | Deskripsi                     | Auth     |
| ------ | -------------------------- | ----------------------------- | -------- |
| POST   | /api/register              | Register user + upload avatar | ❌       |
| POST   | /api/login                 | Login user                    | ❌       |
| POST   | /api/logout                | Logout user                   | ✅       |
| POST   | /api/promote-to-admin/{id} | Promote user menjadi admin    | ✅ Admin |

### 🛍️ Produk

| Method | Endpoint                             | Deskripsi                          | Auth      |
| ------ | ------------------------------------ | ---------------------------------- | --------- |
| GET    | /api/products                        | List semua produk                  | ❌ Public |
| POST   | /api/products                        | Tambah produk baru                 | ✅ Admin  |
| PUT    | /api/products/{id}                   | Update produk                      | ✅ Admin  |
| DELETE | /api/products/{id}                   | Hapus produk                       | ✅ Admin  |
| GET    | /api/products/category/{category_id} | Filter produk berdasarkan kategori | ❌ Public |

### 📂 Galeri Produk

| Method | Endpoint                         | Deskripsi                               | Auth     |
| ------ | -------------------------------- | --------------------------------------- | -------- |
| POST   | /api/add-gallery/{product_id}    | Upload beberapa gambar produk (gallery) | ✅ Admin |
| DELETE | /api/delete-gallery/{product_id} | Hapus semua gallery produk              | ✅ Admin |
| GET    | /api/get-gallery/{product_id}    | Lihat semua gambar galeri produk        | ✅ User  |

### 📚 Kategori

| Method | Endpoint             | Deskripsi           | Auth      |
| ------ | -------------------- | ------------------- | --------- |
| GET    | /api/categories      | List semua kategori | ❌ Public |
| POST   | /api/categories      | Tambah kategori     | ✅ Admin  |
| DELETE | /api/categories/{id} | Hapus kategori      | ✅ Admin  |

### 🧾 Order

| Method | Endpoint         | Deskripsi              | Auth     |
| ------ | ---------------- | ---------------------- | -------- |
| POST   | /api/orders      | Buat order             | ✅ User  |
| GET    | /api/orders      | Lihat semua order user | ✅ User  |
| PUT    | /api/orders/{id} | Update status order    | ✅ Admin |
| DELETE | /api/orders/{id} | Hapus order            | ✅ Admin |

### 💸 Pembayaran

| Method | Endpoint            | Deskripsi                        | Auth      |
| ------ | ------------------- | -------------------------------- | --------- |
| POST   | /api/payments       | Buat invoice pembayaran (Xendit) | ✅        |
| POST   | /api/webhook/xendit | Webhook callback dari Xendit     | 🔐 Server |

---

## 🛠️ Instalasi & Setup

```bash
git clone https://github.com/muhammadrinaldi7/backend-e-commerce.git
cd backend-e-commerce

# Install dependensi
composer install

# Duplikat file .env dan generate app key
cp .env
php artisan key:generate

# Setup database di file .env

# Migrasi & seed data
php artisan migrate --seed

# Jalankan server
php artisan serve

## 📌 Catatan

* Menggunakan Laravel Sanctum untuk autentikasi token.
* Endpoint yang butuh autentikasi dilindungi oleh middleware `auth:sanctum`.
* Gambar avatar dan produk disimpan di `storage/app/public/images`.
* Galeri mendukung multiple upload menggunakan array.

---

## ✅ CI/CD

* Menggunakan **GitHub Actions** untuk:

  * PHPUnit testing (via php artisan test)
  * Deploy otomatis ke shared hosting

---

## 👨‍💻 Kontributor

* Muhammad Rinaldi

---

## 📄 Lisensi

MIT © 2025 Muhammad Rinaldi
```
