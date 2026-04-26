<?php
/**
 * models/User.php
 * Kullanıcı modeli: kayıt, giriş, profil güncelleme işlemleri.
 * Tüm SQL sorguları prepared statements ile yazılmıştır (SQL injection koruması).
 */

require_once __DIR__ . '/../config/Database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ----------------------------------------------------------------
    //  Kullanıcı oluşturma
    // ----------------------------------------------------------------

    /**
     * Yeni kullanıcı kaydeder.
     *
     * @param array $data [name, email, password, age, gender]
     * @return int Yeni kullanıcının ID'si
     * @throws RuntimeException E-posta zaten kayıtlıysa
     */
    public function create(array $data): int
    {
        // Şifreyi bcrypt ile hashle (cost=12 güvenli varsayılan)
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $sql = "INSERT INTO users (name, email, password, age, gender)
                VALUES (:name, :email, :password, :age, :gender)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'     => trim($data['name']),
            ':email'    => strtolower(trim($data['email'])),
            ':password' => $hashedPassword,
            ':age'      => (int) $data['age'],
            ':gender'   => $data['gender'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    // ----------------------------------------------------------------
    //  Kimlik doğrulama
    // ----------------------------------------------------------------

    /**
     * E-posta + şifre ile kullanıcıyı doğrular.
     *
     * @return array|null Kullanıcı satırı veya null (başarısız giriş)
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']); // şifreyi session'a taşıma
            return $user;
        }

        return null;
    }

    // ----------------------------------------------------------------
    //  Sorgular
    // ----------------------------------------------------------------

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => strtolower(trim($email))]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    // ----------------------------------------------------------------
    //  Profil güncelleme
    // ----------------------------------------------------------------

    /**
     * Kullanıcının profil bilgilerini günceller.
     * Şifre boş bırakılırsa değiştirilmez.
     */
    public function update(int $id, array $data): bool
    {
        $fields = [
            'name'               => trim($data['name']),
            'age'                => (int) $data['age'],
            'gender'             => $data['gender'],
            'daily_calorie_goal' => (int) $data['daily_calorie_goal'],
            'daily_water_goal'   => (int) $data['daily_water_goal'],
        ];

        // Şifre değiştirilmek isteniyorsa hash'le
        if (!empty($data['password'])) {
            $fields['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $setClauses = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
        $sql = "UPDATE users SET $setClauses WHERE id = :id";

        $fields['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($fields);
    }
}
