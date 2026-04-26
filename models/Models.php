<?php
/**
 * models/Meal.php
 * Günlük yemek / kalori kayıtlarını yönetir.
 */

require_once __DIR__ . '/../config/Database.php';

class Meal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO meals (user_id, food_name, calories, meal_type, image_path, ai_analyzed, date)
             VALUES (:user_id, :food_name, :calories, :meal_type, :image_path, :ai_analyzed, :date)"
        );
        $stmt->execute([
            ':user_id'     => $data['user_id'],
            ':food_name'   => trim($data['food_name']),
            ':calories'    => (int) $data['calories'],
            ':meal_type'   => $data['meal_type'] ?? 'ara öğün',
            ':image_path'  => $data['image_path'] ?? null,
            ':ai_analyzed' => $data['ai_analyzed'] ?? 0,
            ':date'        => $data['date'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Belirli bir güne ait öğünleri döndürür */
    public function getByDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM meals
             WHERE user_id = :user_id AND date = :date
             ORDER BY created_at ASC"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        return $stmt->fetchAll();
    }

    /** Günlük toplam kalori */
    public function getDailyTotal(int $userId, string $date): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(calories), 0) as total
             FROM meals WHERE user_id = :user_id AND date = :date"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        return (int) $stmt->fetchColumn();
    }

    /** Son 7 günün günlük kalori toplamları (grafik için) */
    public function getWeeklyCalories(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT date, SUM(calories) as total
             FROM meals
             WHERE user_id = :user_id AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY date ORDER BY date ASC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM meals WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}


/**
 * models/Goal.php
 * Hedef kilo takibi.
 */
class Goal
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Hedef oluştur veya güncelle (UPSERT) */
    public function upsert(int $userId, float $startWeight, float $targetWeight, float $currentWeight): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO goals (user_id, start_weight, target_weight, current_weight)
             VALUES (:user_id, :start_weight, :target_weight, :current_weight)
             ON DUPLICATE KEY UPDATE
                target_weight  = VALUES(target_weight),
                current_weight = VALUES(current_weight),
                updated_at     = CURRENT_TIMESTAMP"
        );
        return $stmt->execute([
            ':user_id'        => $userId,
            ':start_weight'   => $startWeight,
            ':target_weight'  => $targetWeight,
            ':current_weight' => $currentWeight,
        ]);
    }

    public function getByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM goals WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * İlerleme yüzdesini hesaplar.
     * 0 = başlangıç, 100 = hedefe ulaşıldı
     */
    public static function calculateProgress(float $start, float $target, float $current): float
    {
        $total   = abs($start - $target);
        $covered = abs($start - $current);

        if ($total == 0) return 100.0;
        return min(100.0, round(($covered / $total) * 100, 1));
    }
}


/**
 * models/WaterTracking.php
 * Günlük su takibi.
 */
class WaterTracking
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function add(int $userId, int $amountMl, string $date): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO water_tracking (user_id, amount, date)
             VALUES (:user_id, :amount, :date)"
        );
        $stmt->execute([':user_id' => $userId, ':amount' => $amountMl, ':date' => $date]);
        return (int) $this->db->lastInsertId();
    }

    /** Belirli bir günün toplam su miktarı (ml) */
    public function getDailyTotal(int $userId, string $date): int
    {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM water_tracking WHERE user_id = :user_id AND date = :date"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        return (int) $stmt->fetchColumn();
    }

    /** Günlük kayıtları listele */
    public function getByDate(int $userId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM water_tracking
             WHERE user_id = :user_id AND date = :date ORDER BY created_at ASC"
        );
        $stmt->execute([':user_id' => $userId, ':date' => $date]);
        return $stmt->fetchAll();
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM water_tracking WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $id, ':user_id' => $userId]);
    }
}


/**
 * models/DietRecommendation.php
 * VKİ ve Yaşa göre (Persentil destekli) kural tabanlı diyet önerisi üretir ve kaydeder.
 */
class DietRecommendation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Yaş ve VKİ'ye göre öneri metnini döndürür.
     */
    public static function generateText(float $bmi, int $age, string $gender): string
    {
        // 18 YAŞ ALTI İÇİN PERSENTİL BAZLI ÖNERİLER (İstatistiksel)
        if ($age >= 2 && $age < 18) {
            $kategori = \HealthRecord::getCategory($bmi, $age, $gender);

            if (strpos($kategori, '< 5') !== false) {
                return "İstatistiklere göre, {$age} yaşındaki 100 çocuktan en zayıf 5'lik dilimdesiniz (5. Persentil altı). "
                     . "Büyüme ve gelişimin sağlıklı devam etmesi için enerji ve protein içeriği yüksek, besleyici gıdalar tüketmeniz çok önemlidir. "
                     . "Bir çocuk sağlığı uzmanına danışmanız tavsiye edilir.";
            } 
            elseif (strpos($kategori, '5-85') !== false) {
                return "Harika! {$age} yaşındaki yaşıtlarınızdaki 100 çocukla kıyaslandığında tam da olmanız gereken ideal sağlıklı aralıktasınız (5-85. Persentil). "
                     . "Mevcut hareketli yaşamınızı ve dengeli beslenme alışkanlıklarınızı aynen korumaya devam edin!";
            } 
            elseif (strpos($kategori, '85-95') !== false) {
                return "Büyüme eğrilerine göre, {$age} yaşındaki 100 yaşıtınızdan yaklaşık 85'inden daha yüksek kilodasınız (Fazla Kilolu). "
                     . "İleride obezite riskini önlemek için paketli gıdaları azaltmak, fiziksel aktiviteyi (oyun, spor) artırmak ve porsiyon kontrolü yapmak harika bir başlangıç olur.";
            } 
            else {
                return "İstatistiklere göre, {$age} yaşındaki 100 çocuğun 95'inden daha yüksek bir kiloya sahipsiniz (95. Persentil üzeri / Obezite). "
                     . "Sağlıklı gelişimin desteklenmesi ve olası metabolik risklerin önlenmesi için mutlaka bir uzman diyetisyen ile adım adım sağlıklı yaşam planı oluşturulmalıdır.";
            }
        }

        // 18 YAŞ VE ÜSTÜ İÇİN STANDART YETİŞKİN ÖNERİLERİ
        return match(true) {
            $bmi < 18.5 => "VKİ'niz düşük kategorisinde ({$bmi}). Günlük kalori alımınızı artırmanız önerilir. "
                         . "Protein açısından zengin besinler (et, yumurta, baklagiller), sağlıklı yağlar (avokado, fındık) "
                         . "ve tam tahıllı karbonhidratlar tüketmeye özen gösterin.",

            $bmi < 25.0 => "VKİ'niz normal aralıkta ({$bmi}). Tebrikler! "
                         . "Mevcut beslenme düzeninizi koruyun. Haftada en az 150 dakika orta yoğunluklu egzersiz, "
                         . "bol sebze-meyve ve yeterli su tüketimi sağlıklı yaşamınızı sürdürmenize yardımcı olur.",

            $bmi < 30.0 => "VKİ'niz fazla kilolu kategorisinde ({$bmi}). "
                         . "Günlük kalori alımınızı 300-500 kcal azaltmayı hedefleyin. "
                         . "İşlenmiş gıdalar, şekerli içecekler ve fast food'u kısıtlayın. Düzenli yürüyüş yapın.",

            default     => "VKİ'niz obez kategorisinde ({$bmi}). Bir diyetisyen ve sağlık uzmanına danışmanız önemlidir. "
                         . "Günlük kalori açığı oluşturmak için düşük kalorili, yüksek lifli besinler tercih edin. "
                         . "Şekerli, yağlı ve işlenmiş gıdalardan kaçının."
        };
    }

    /** Öneriyi veritabanına kaydet */
    public function save(int $userId, float $bmi): int
    {
        // 1. Önce kullanıcının yaşını ve cinsiyetini veritabanından çek (Controller'a dokunmamak için)
        $stmt = $this->db->prepare("SELECT age, gender FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $age    = (int)($user['age'] ?? 25); // Yaş yoksa varsayılan 25
        $gender = $user['gender'] ?? 'erkek';

        // 2. Hem çocuk hem yetişkin için doğru kategoriyi ve metni oluştur
        $category = \HealthRecord::getCategory($bmi, $age, $gender);
        $text     = self::generateText($bmi, $age, $gender);

        // 3. Veritabanına kaydet
        $stmt = $this->db->prepare(
            "INSERT INTO diet_recommendations (user_id, bmi_value, bmi_category, recommendation_text)
             VALUES (:user_id, :bmi_value, :bmi_category, :recommendation_text)"
        );
        $stmt->execute([
            ':user_id'             => $userId,
            ':bmi_value'           => $bmi,
            ':bmi_category'        => $category,
            ':recommendation_text' => $text,
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /** Kullanıcının son önerisini döndürür */
    public function getLatest(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM diet_recommendations
             WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}