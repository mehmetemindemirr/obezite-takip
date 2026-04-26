<?php
/**
 * controllers/AuthController.php
 * Kayıt, giriş ve çıkış işlemleri.
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ----------------------------------------------------------------
    //  Giriş
    // ----------------------------------------------------------------

    public function loginForm(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/login', ['title' => 'Giriş Yap', 'flash' => $this->getFlash()]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/login');
        }

        $email    = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? ''; // hash karşılaştırılacak, sanitize etme

        // Basit doğrulama
        if (empty($email) || empty($password)) {
            $this->setFlash('danger', 'E-posta ve şifre zorunludur.');
            $this->redirect('/auth/login');
        }

        $user = $this->userModel->authenticate($email, $password);

        if (!$user) {
            $this->setFlash('danger', 'E-posta veya şifre hatalı.');
            $this->redirect('/auth/login');
        }

        // Oturumu başlat
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = $user;
        session_regenerate_id(true); // oturum sabitleme saldırısını önle

        $this->redirect('/dashboard');
    }

    // ----------------------------------------------------------------
    //  Kayıt
    // ----------------------------------------------------------------

    public function registerForm(): void
    {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->render('auth/register', ['title' => 'Kayıt Ol', 'flash' => $this->getFlash()]);
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/register');
        }

        $data = [
            'name'     => $this->sanitize($_POST['name'] ?? ''),
            'email'    => $this->sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'age'      => (int) ($_POST['age'] ?? 0),
            'gender'   => $_POST['gender'] ?? '',
        ];

        // Doğrulama
        $errors = $this->validateRegister($data);
        if (!empty($errors)) {
            $this->setFlash('danger', implode('<br>', $errors));
            $this->redirect('/auth/register');
        }

        // E-posta çakışma kontrolü
        if ($this->userModel->emailExists($data['email'])) {
            $this->setFlash('danger', 'Bu e-posta adresi zaten kayıtlı.');
            $this->redirect('/auth/register');
        }

        $userId = $this->userModel->create($data);
        $this->setFlash('success', 'Kayıt başarılı! Giriş yapabilirsiniz.');
        $this->redirect('/auth/login');
    }

    // ----------------------------------------------------------------
    //  Çıkış
    // ----------------------------------------------------------------

    public function logout(): void
    {
        session_unset();
        session_destroy();
        $this->redirect('/auth/login');
    }

    // ----------------------------------------------------------------
    //  Yardımcı: Kayıt doğrulama
    // ----------------------------------------------------------------

    private function validateRegister(array $data): array
    {
        $errors = [];

        if (strlen($data['name']) < 2) {
            $errors[] = 'Ad en az 2 karakter olmalıdır.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Geçerli bir e-posta adresi giriniz.';
        }
        if (strlen($data['password']) < 6) {
            $errors[] = 'Şifre en az 6 karakter olmalıdır.';
        }
        if ($data['age'] < 5 || $data['age'] > 120) {
            $errors[] = 'Geçerli bir yaş giriniz (5-120).';
        }
        if (!in_array($data['gender'], ['erkek', 'kadın', 'diğer'])) {
            $errors[] = 'Cinsiyet seçiniz.';
        }

        return $errors;
    }
}
