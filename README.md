# ObeziTakip — Akıllı Obezite Takip ve Beslenme Asistanı

PHP MVC + FastAPI AI mikro-servis mimarisiyle geliştirilmiş portfolyo seviyesi web uygulaması.

---

## Proje Yapısı

```
obesity-tracker/
├── config/
│   ├── config.php        # Tüm sabitler (DB, AI URL, BMI kategorileri)
│   └── Database.php      # PDO Singleton bağlantı sınıfı
├── models/
│   ├── User.php          # Kullanıcı modeli (bcrypt, kimlik doğrulama)
│   ├── HealthRecord.php  # VKİ hesaplama, grafik verisi
│   └── Models.php        # Meal, Goal, WaterTracking, DietRecommendation
├── controllers/
│   ├── BaseController.php   # Render, redirect, flash, json yardımcıları
│   ├── AuthController.php   # Login / register / logout
│   └── Controllers.php      # Dashboard, Health, Meal, Goal, Water, Profile
├── views/
│   ├── layouts/          # header.php + footer.php
│   ├── auth/             # login.php, register.php
│   ├── dashboard/        # Ana dashboard
│   ├── health/           # Sağlık takibi
│   ├── meals/            # Kalori takibi
│   ├── water/            # Su takibi
│   ├── goals/            # Hedef kilo
│   └── profile/          # Profil düzenleme
├── assets/
│   ├── css/style.css     # Tüm stiller
│   └── js/app.js         # Chart.js + UI etkileşimleri
├── ai_service/
│   └── main.py           # FastAPI kalori tahmin servisi
├── uploads/              # Yemek fotoğrafları (gitignore'a ekle)
├── database.sql          # Veritabanı şeması
├── index.php             # Front Controller / Router
└── .htaccess             # URL yeniden yönlendirme
```

---

## Kurulum

### 1. Gereksinimler

- PHP 8.1+
- MySQL 8.0+ (veya MariaDB 10.6+)
- Apache + mod_rewrite (XAMPP / WAMP / Laragon)
- Python 3.9+ (FastAPI servisi için)

### 2. Projeyi Kopyala

```bash
# htdocs veya www dizinine koy
cp -r obesity-tracker/ /var/www/html/
# ya da XAMPP için: C:\xampp\htdocs\obesity-tracker\
```

### 3. Veritabanını Oluştur

```bash
mysql -u root -p < database.sql
```

### 4. Ayarları Düzenle

`config/config.php` dosyasını aç ve şunları güncelle:

```php
define('DB_USER', 'senin_kullanıcı_adın');
define('DB_PASS', 'senin_şifren');
define('APP_URL', 'http://localhost/obesity-tracker');
```

### 5. Apache'de `mod_rewrite` Aktif Et

`httpd.conf` veya `httpd-vhosts.conf` içinde:
```
AllowOverride All
```

---

## FastAPI Servisini Başlat (Opsiyonel)

Yemek fotoğrafı AI analizi için (YOLO ile eğitilmiş model):

```bash
cd obesity-tracker/ai_service

# Bağımlılıkları yükle
pip install fastapi uvicorn python-multipart pillow

# Servisi başlat
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

Tarayıcıda `http://localhost:8000/docs` adresinden Swagger UI'ye erişebilirsin.

### FAZ 2: YOLO Entegrasyonu

```bash
pip install ultralytics

# main.py içindeki YOLO yorumlarını kaldır:
# from ultralytics import YOLO
# _model = YOLO("yolov8n-cls.pt")
# analyze_image_yolo() fonksiyonundaki yorumları kaldır
```

> **Not:** FastAPI çalışmıyorsa uygulama hata vermez, kullanıcı kaloriye el ile girer.

---

## Kullanım

1. `http://localhost/obesity-tracker` adresine git
2. "Kayıt Ol" ile hesap oluştur
3. "Sağlık" sayfasından ilk ölçümünü gir → VKİ otomatik hesaplanır
4. "Kalori" sayfasından günlük öğünleri ekle
5. "Su" sayfasından su tüketimini takip et
6. "Hedef" sayfasından kilo hedefi belirle
7. "Dashboard"dan tüm verileri tek ekranda gör

---

## Güvenlik

| Önlem | Uygulama |
|---|---|
| Şifre hashleme | `password_hash()` bcrypt cost=12 |
| SQL injection | PDO prepared statements |
| XSS | `htmlspecialchars()` tüm çıktılarda |
| Oturum sabitleme | `session_regenerate_id()` girişte |
| Dosya yükleme | MIME tipi + boyut kontrolü |
| Kimlik doğrulama | `requireAuth()` tüm korumalı sayfalarda |

---

## Mimari Kararlar

**Neden PHP + FastAPI?**  
PHP ortamında YOLO gibi derin öğrenme kütüphaneleri çalışmaz. FastAPI ayrı bir Python süreci olarak çalışır, PHP cURL ile JSON API üzerinden iletişim kurar. PHP çevrimdışı servis yerine kural tabanlı tahmini devreye alır — uygulama her koşulda çalışır.

**Neden Singleton veritabanı bağlantısı?**  
Tek istek içinde birden fazla model kullanıldığında her seferinde yeni bağlantı açılmasını önler. PDO bağlantısı tek kez kurulur, tüm modeller paylaşır.

---

## Geliştirme Fikirleri

- [ ] REST API + React Native mobil uygulama
- [ ] YOLO food101 model entegrasyonu (FAZ 2 hazır)
- [ ] E-posta hatırlatıcı (PHP cron + mail())
- [ ] PDF rapor çıktısı (FPDF/TCPDF)
- [ ] Besin değerleri API (Open Food Facts)
- [ ] Admin paneli

---

## Test Kullanıcısı

| Alan | Değer |
|---|---|
| E-posta | test@example.com |
| Şifre | test1234 |
