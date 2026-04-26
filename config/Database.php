<?php
/**
 * config/Database.php
 * PDO tabanlı veritabanı bağlantısı — Singleton deseni kullanır.
 * Böylece uygulama boyunca tek bir bağlantı nesnesi açık kalır.
 */

require_once __DIR__ . '/config.php';

class Database
{
    /** @var Database|null Singleton örneği */
    private static ?Database $instance = null;

    /** @var PDO PDO bağlantı nesnesi */
    private PDO $pdo;

    /**
     * Constructor private → dışarıdan new Database() çağrılamaz.
     * Bağlantı hatası yakalanıp anlamlı bir mesajla yeniden fırlatılır.
     */
    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // hataları exception olarak fırlat
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // sonuçları assoc array döndür
            PDO::ATTR_EMULATE_PREPARES   => false,                    // gerçek prepared statements kullan
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci", // Zorunlu Türkçe Kodlaması
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Üretim ortamında şifreler loglara yazılmamalı; sadece genel hata göster
            throw new RuntimeException('Veritabanına bağlanılamadı. Lütfen config.php ayarlarını kontrol edin.');
        }
    }

    /**
     * Singleton örneğini döndürür. İlk çağrıda yeni nesne oluşturur.
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * PDO nesnesini döndürür; model sınıflarında kullanılır.
     */
    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    /** Clone'u engelle */
    private function __clone() {}
}
