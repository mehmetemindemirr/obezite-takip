<?php
/**
 * models/HealthRecord.php
 * Boy, kilo, VKİ ve 18 yaş altı için Persentil verilerini yönetir.
 */

require_once __DIR__ . '/../config/Database.php';

class HealthRecord
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ----------------------------------------------------------------
    //  VKİ ve Persentil Hesaplama (statik yardımcılar)
    // ----------------------------------------------------------------

    /**
     * BMI = kg / (m^2)
     */
    public static function calculateBMI(float $weight, float $height): float
    {
        $heightM = $height / 100; // cm → metre
        return round($weight / ($heightM ** 2), 2);
    }

    /**
     * DSÖ Basitleştirilmiş Persentil Tabloları (10-17 yaş)
     * Yaş => [5. Persentil, 85. Persentil, 95. Persentil]
     */
    private static $percentileMale = [
        10 => [14.2, 19.3, 21.4], 11 => [14.5, 20.1, 22.5],
        12 => [14.9, 21.0, 23.6], 13 => [15.4, 21.9, 24.8],
        14 => [15.9, 22.7, 25.9], 15 => [16.5, 23.6, 27.0],
        16 => [17.1, 24.5, 27.9], 17 => [17.7, 25.2, 28.6]
    ];

    private static $percentileFemale = [
        10 => [14.0, 19.9, 22.6], 11 => [14.4, 20.8, 23.7],
        12 => [14.8, 21.7, 24.9], 13 => [15.3, 22.6, 26.0],
        14 => [15.8, 23.3, 26.8], 15 => [16.3, 23.9, 27.5],
        16 => [16.8, 24.4, 27.9], 17 => [17.2, 24.7, 28.2]
    ];

    /**
     * Yaşa göre VKİ Kategori Adı Döndürür (Persentil destekli)
     */
    public static function getCategory(float $bmi, int $age, string $gender = 'erkek'): string
    {
        // 18 Yaş Altı (Çocuk/Ergen) - Persentil Hesabı
        if ($age >= 10 && $age < 18) {
            $table = ($gender === 'erkek') ? self::$percentileMale : self::$percentileFemale;
            // Tabloda yaş varsa kontrol et (hata vermesin diye)
            if (isset($table[$age])) {
                $limits = $table[$age];
                if ($bmi < $limits[0]) return "< 5. Persentil (Zayıf)";
                if ($bmi >= $limits[0] && $bmi < $limits[1]) return "5-85. Persentil (Normal)";
                if ($bmi >= $limits[1] && $bmi < $limits[2]) return "85-95. Persentil (Fazla Kilolu)";
                if ($bmi >= $limits[2]) return "> 95. Persentil (Obezite)";
            }
        }
        
        // 18 Yaş ve Üstü - Standart Yetişkin Hesabı
        return match(true) {
            $bmi < 18.5 => 'Zayıf',
            $bmi < 25.0 => 'Normal',
            $bmi < 30.0 => 'Fazla Kilolu',
            default     => 'Obez',
        };
    }

    /**
     * Yaşa göre CSS renk sınıfı döndürür (badge için)
     */
    public static function getColor(float $bmi, int $age, string $gender = 'erkek'): string
    {
        // 18 Yaş Altı
        if ($age >= 10 && $age < 18) {
            $table = ($gender === 'erkek') ? self::$percentileMale : self::$percentileFemale;
            if (isset($table[$age])) {
                $limits = $table[$age];
                if ($bmi < $limits[0]) return 'badge-info';
                if ($bmi >= $limits[0] && $bmi < $limits[1]) return 'badge-success';
                if ($bmi >= $limits[1] && $bmi < $limits[2]) return 'badge-warning';
                if ($bmi >= $limits[2]) return 'badge-danger';
            }
        }

        // 18 Yaş ve Üstü
        return match(true) {
            $bmi < 18.5 => 'badge-info',
            $bmi < 25.0 => 'badge-success',
            $bmi < 30.0 => 'badge-warning',
            default     => 'badge-danger',
        };
    }

    // ----------------------------------------------------------------
    //  CRUD (Mevcut Veritabanı İşlemleri)
    // ----------------------------------------------------------------

    public function create(int $userId, float $weight, float $height, string $date, ?string $note = null): int
    {
        $bmi = self::calculateBMI($weight, $height);

        $stmt = $this->db->prepare(
            "INSERT INTO health_records (user_id, weight, height, bmi, date, note)
             VALUES (:user_id, :weight, :height, :bmi, :date, :note)"
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':weight'  => $weight,
            ':height'  => $height,
            ':bmi'     => $bmi,
            ':date'    => $date,
            ':note'    => $note,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getAllByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM health_records
             WHERE user_id = :user_id
             ORDER BY date DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getChartData(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            "SELECT date, weight, bmi
             FROM health_records
             WHERE user_id = :user_id
             ORDER BY date ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getLatest(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM health_records
             WHERE user_id = :user_id
             ORDER BY date DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM health_records WHERE id = :id AND user_id = :user_id"
        );
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }

    // ----------------------------------------------------------------
    //  FALLBACK (Eski Diyet Robotu ve Grafikler İçin Yedek Fonksiyonlar)
    // ----------------------------------------------------------------

    /**
     * Diyet robotu ve arka plan işlemleri için eski standart VKİ fonksiyonu (Fallback)
     */
    public static function getBMICategory(float $bmi): string
    {
        return match(true) {
            $bmi < 18.5 => 'Zayıf',
            $bmi < 25.0 => 'Normal',
            $bmi < 30.0 => 'Fazla Kilolu',
            default     => 'Obez',
        };
    }

    /**
     * Eski sayfalarda patlama olmasın diye eski renk fonksiyonu (Fallback)
     */
    public static function getBMIColor(float $bmi): string
    {
        return match(true) {
            $bmi < 18.5 => 'badge-info',
            $bmi < 25.0 => 'badge-success',
            $bmi < 30.0 => 'badge-warning',
            default     => 'badge-danger',
        };
    }
}