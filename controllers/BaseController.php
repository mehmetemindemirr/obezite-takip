<?php
/**
 * controllers/BaseController.php
 * Tüm controller'ların miras aldığı temel sınıf.
 * Yönlendirme, view render ve oturum yardımcıları buradadır.
 */

class BaseController
{
    // ----------------------------------------------------------------
    //  View Render
    // ----------------------------------------------------------------

    /**
     * Bir view dosyasını render eder.
     * $data dizisi, view içinde değişken olarak kullanılabilir hale gelir.
     *
     * @param string $view   'dashboard/index' formatında view yolu
     * @param array  $data   View'e aktarılacak değişkenler
     */
    protected function render(string $view, array $data = []): void
    {
        // Diziyi değişkenlere aç: ['title' => 'X'] → $title = 'X'
        extract($data);

        $viewPath = __DIR__ . "/../views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new RuntimeException("View bulunamadı: {$view}");
        }

        // Layout ile sarmala
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once $viewPath;
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    // ----------------------------------------------------------------
    //  Yönlendirme
    // ----------------------------------------------------------------

    protected function redirect(string $path): void
    {
        header("Location: " . APP_URL . $path);
        exit;
    }

    // ----------------------------------------------------------------
    //  Oturum Kontrol
    // ----------------------------------------------------------------

    /**
     * Giriş yapılmamışsa login sayfasına yönlendir.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }
    }

    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function getCurrentUserId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    // ----------------------------------------------------------------
    //  Flash Mesajları
    // ----------------------------------------------------------------

    protected function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    // ----------------------------------------------------------------
    //  JSON Yanıt (AJAX endpoint'leri için)
    // ----------------------------------------------------------------

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ----------------------------------------------------------------
    //  Girdi Temizleme
    // ----------------------------------------------------------------

    protected function sanitize(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
}
