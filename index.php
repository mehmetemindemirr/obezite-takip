<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * index.php — Front Controller / Router
 *
 * Tüm istekler .htaccess aracılığıyla buraya yönlendirilir.
 * URL'yi parse eder, ilgili controller ve metoda yönlendirir.
 *
 * Kullanılan MVC akışı:
 *   Tarayıcı → index.php (Router) → Controller → Model → View → Tarayıcı
 */

// -------------------------------------------------------
//  Başlangıç ayarları
// -------------------------------------------------------
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/HealthRecord.php';
require_once __DIR__ . '/models/Models.php';
require_once __DIR__ . '/controllers/BaseController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/Controllers.php';

// -------------------------------------------------------
//  URL Parsing
// -------------------------------------------------------

// APP_URL dışındaki kısmı al: /obesity-tracker/dashboard → /dashboard
// index.php - Line 33 ve civarı için düzeltme:
$requestUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$basePath    = parse_url(APP_URL, PHP_URL_PATH) ?? ''; // Eğer null dönerse boş metin say
$path        = substr($requestUri, strlen($basePath)) ?: '/';
$path = substr($requestUri, strlen($basePath ?? '')) ?: '/';

// Query string'i temizle
$pathClean   = strtok($path, '?');

// -------------------------------------------------------
//  Route Tablosu
// -------------------------------------------------------

$routes = [
    // Auth
    'GET /auth/login'    => [AuthController::class,     'loginForm'],
    'POST /auth/login'   => [AuthController::class,     'login'],
    'GET /auth/register' => [AuthController::class,     'registerForm'],
    'POST /auth/register'=> [AuthController::class,     'register'],
    'GET /auth/logout'   => [AuthController::class,     'logout'],

    // Dashboard
    'GET /'              => [DashboardController::class, 'index'],
    'GET /dashboard'     => [DashboardController::class, 'index'],

    // Sağlık Takibi
    'GET /health'        => [HealthController::class,   'index'],
    'POST /health/store' => [HealthController::class,   'store'],
    'GET /health/delete' => [HealthController::class,   'delete'],

    // Kalori / Öğün
    'GET /meals'         => [MealController::class,     'index'],
    'POST /meals/store'  => [MealController::class,     'store'],
    'GET /meals/delete'  => [MealController::class,     'delete'],

    // Hedef
    'GET /goals'         => [GoalController::class,     'index'],
    'POST /goals/store'  => [GoalController::class,     'store'],

    // Su Takibi
    'GET /water'         => [WaterController::class,    'index'],
    'POST /water/store'  => [WaterController::class,    'store'],
    'GET /water/delete'  => [WaterController::class,    'delete'],

    // Profil
    'GET /profile'       => [ProfileController::class,  'index'],
    'POST /profile/update'=> [ProfileController::class, 'update'],
];

// -------------------------------------------------------
//  Dispatch
// -------------------------------------------------------

$method    = $_SERVER['REQUEST_METHOD'];
$routeKey  = "$method $pathClean";

if (isset($routes[$routeKey])) {
    [$controllerClass, $action] = $routes[$routeKey];
    $controller = new $controllerClass();
    $controller->$action();
} elseif ($pathClean === '/' || $pathClean === '') {
    header("Location: " . APP_URL . "/auth/login");
    exit;
} else {
    http_response_code(404);
    echo "<h1>404 - Sayfa Bulunamadı</h1><p><a href='" . APP_URL . "'>Ana Sayfaya Dön</a></p>";
}
