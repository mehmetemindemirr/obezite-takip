<?php
/**
 * config/config.php
 * Uygulama geneli sabit değerler ve ayarlar.
 * Hassas bilgileri (DB şifresi) .env dosyasına taşıyabilirsiniz.
 */

// ---------- Veritabanı ----------
define('DB_HOST', 'sql208.infinityfree.com');
define('DB_NAME', 'if0_41737898_obezite');
define('DB_USER', 'if0_41737898');        // kendi kullanıcı adınız
define('DB_PASS', 'CJ53UoenhnnyIj0');            // kendi şifreniz
define('DB_CHARSET', 'utf8mb4');
define('GEMINI_API_KEY', 'AIzaSyCAhMdEyCQoZXKhaidmZHL8dVw4VfVEIK8');
// ---------- Uygulama ----------
define('APP_NAME', 'ObeziTakip');
define('APP_URL', 'https://obezite.great-site.net'); // sunucu adresinize göre değiştirin
define('APP_VERSION', '1.0.0');

// ---------- Oturum ----------
define('SESSION_LIFETIME', 3600 * 24); // 24 saat

// ---------- Dosya Yükleme ----------
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ---------- FastAPI AI Servisi ----------
define('AI_SERVICE_URL', 'http://localhost:8000'); // FastAPI portu

// ---------- VKİ Kategorileri ----------
define('BMI_CATEGORIES', [
    'Zayıf'        => ['min' => 0,    'max' => 18.5],
    'Normal'       => ['min' => 18.5, 'max' => 25],
    'Fazla Kilolu' => ['min' => 25,   'max' => 30],
    'Obez'         => ['min' => 30,   'max' => 999],
]);

// ---------- Zaman Dilimi ----------
date_default_timezone_set('Europe/Istanbul');
