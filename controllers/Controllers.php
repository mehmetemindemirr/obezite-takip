<?php
/**
 * controllers/DashboardController.php
 * Ana dashboard sayfası: tüm özet verileri toplar.
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/HealthRecord.php';
require_once __DIR__ . '/../models/Models.php';

class DashboardController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        $today  = date('Y-m-d');

        $healthModel = new HealthRecord();
        $mealModel   = new Meal();
        $waterModel  = new WaterTracking();
        $goalModel   = new Goal();
        $dietModel   = new DietRecommendation();

        $latestRecord = $healthModel->getLatest($userId);
        $chartData    = $healthModel->getChartData($userId, 30);
        $todayCalorie = $mealModel->getDailyTotal($userId, $today);
        $todayWater   = $waterModel->getDailyTotal($userId, $today);
        $goal         = $goalModel->getByUser($userId);
        $recommendation = $dietModel->getLatest($userId);
        $weeklyCalories = $mealModel->getWeeklyCalories($userId);
        
        // Kullanıcı bilgisini çekiyoruz
        $user           = $this->getCurrentUser();

        // Hedef ilerleme yüzdesi
        $goalProgress = 0;
        if ($goal) {
            $goalProgress = Goal::calculateProgress(
                $goal['start_weight'],
                $goal['target_weight'],
                $goal['current_weight']
            );
        }

        $this->render('dashboard/index', [
            'title'          => 'Dashboard',
            'flash'          => $this->getFlash(),
            'latestRecord'   => $latestRecord,
            'chartData'      => json_encode($chartData),
            'weeklyCalories' => json_encode($weeklyCalories),
            'todayCalorie'   => $todayCalorie,
            'todayWater'     => $todayWater,
            'goal'           => $goal,
            'goalProgress'   => $goalProgress,
            'recommendation' => $recommendation,
            'user'           => $user, 
        ]);
    }
}

/**
 * controllers/HealthController.php
 * Sağlık kaydı ekleme, listeleme, silme.
 */
class HealthController extends BaseController
{
    private HealthRecord $model;

    public function __construct()
    {
        $this->model = new HealthRecord();
    }

    public function index(): void
    {
        $this->requireAuth();
        $userId  = $this->getCurrentUserId();
        $records = $this->model->getAllByUser($userId);
        
        $user    = $this->getCurrentUser(); 

        $this->render('health/index', [
            'title'   => 'Sağlık Takibi',
            'flash'   => $this->getFlash(),
            'records' => $records,
            'user'    => $user 
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/health');
        }

        $userId = $this->getCurrentUserId();
        $weight = (float) ($_POST['weight'] ?? 0);
        $height = (float) ($_POST['height'] ?? 0);
        $date   = $this->sanitize($_POST['date'] ?? date('Y-m-d'));
        $note   = $this->sanitize($_POST['note'] ?? '');

        if ($weight <= 0 || $height <= 0) {
            $this->setFlash('danger', 'Geçerli boy ve kilo giriniz.');
            $this->redirect('/health');
        }

        // Sağlık kaydını veritabanına yaz
        $this->model->create($userId, $weight, $height, $date, $note ?: null);

        // Hedef mevcut kilosunu güncelle
        $goalModel = new Goal();
        $goal      = $goalModel->getByUser($userId);
        if ($goal) {
            $goalModel->upsert($userId, $goal['start_weight'], $goal['target_weight'], $weight);
        }

        // Diyet önerisini güncelle
        $bmi       = HealthRecord::calculateBMI($weight, $height);
        $dietModel = new DietRecommendation();
        $dietModel->save($userId, $bmi);

        // Kullanıcı bilgisini çek ve yaşa/cinsiyete göre durumu hesapla
        $user = $this->getCurrentUser();
        $status = HealthRecord::getCategory($bmi, $user['age'], $user['gender']);

        // Persentil destekli flash mesajı bas ve doğru sayfaya (health) yönlendir
        $this->setFlash('success', 'Sağlık kaydı eklendi. Durum: ' . $status);
        $this->redirect('/health');
    }

    public function delete(): void
    {
        $this->requireAuth();
        $id     = (int) ($_GET['id'] ?? 0);
        $userId = $this->getCurrentUserId();

        if ($this->model->delete($id, $userId)) {
            $this->setFlash('success', 'Kayıt silindi.');
        } else {
            $this->setFlash('danger', 'Kayıt silinemedi.');
        }
        $this->redirect('/health');
    }
}

/**
 * controllers/MealController.php
 * Öğün ekleme, listeleme, silme ve fotoğraf yükleme.
 */
class MealController extends BaseController
{
    private Meal $model;

    public function __construct()
    {
        $this->model = new Meal();
    }

    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        $date   = $this->sanitize($_GET['date'] ?? date('Y-m-d'));
        $meals  = $this->model->getByDate($userId, $date);
        $total  = $this->model->getDailyTotal($userId, $date);
        $user   = $this->getCurrentUser();

        $this->render('meals/index', [
            'title'       => 'Kalori Takibi',
            'flash'       => $this->getFlash(),
            'meals'       => $meals,
            'dailyTotal'  => $total,
            'dailyGoal'   => $user['daily_calorie_goal'] ?? 2000,
            'selectedDate'=> $date,
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/meals');
        }

        $userId    = $this->getCurrentUserId();
        $imagePath = null;
        $aiAnalyzed = 0;

        // Fotoğraf yükleme işlemi
        if (!empty($_FILES['food_image']['name'])) {
            $result = $this->handleImageUpload($_FILES['food_image']);
            if ($result['success']) {
                $imagePath  = $result['path'];
                $aiAnalyzed = (int) ($_POST['ai_analyzed_flag'] ?? 0);
            } else {
                $this->setFlash('danger', $result['error']);
                $this->redirect('/meals');
            }
        }

        $data = [
            'user_id'     => $userId,
            'food_name'   => $this->sanitize($_POST['food_name'] ?? ''),
            'calories'    => (int) ($_POST['calories'] ?? 0),
            'meal_type'   => $_POST['meal_type'] ?? 'ara öğün',
            'image_path'  => $imagePath,
            'ai_analyzed' => $aiAnalyzed,
            'date'        => $this->sanitize($_POST['date'] ?? date('Y-m-d')),
        ];

        if (empty($data['food_name']) || $data['calories'] <= 0) {
            $this->setFlash('danger', 'Yemek adı ve kalori zorunludur.');
            $this->redirect('/meals');
        }

        $this->model->create($data);
        $msg = $aiAnalyzed ? 'Öğün eklendi (AI analiz edildi).' : 'Öğün eklendi.';
        $this->setFlash('success', $msg);
        $this->redirect('/meals?date=' . $data['date']);
    }

    public function delete(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $this->model->delete($id, $this->getCurrentUserId());
        $this->setFlash('success', 'Öğün silindi.');
        $this->redirect('/meals');
    }

    private function handleImageUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Dosya yükleme hatası.'];
        }
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'error' => 'Dosya 5 MB\'dan büyük olamaz.'];
        }
        if (!in_array(mime_content_type($file['tmp_name']), ALLOWED_TYPES)) {
            return ['success' => false, 'error' => 'Sadece JPEG, PNG ve WebP desteklenir.'];
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('meal_', true) . '.' . $ext;
        $destPath = UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['success' => false, 'error' => 'Dosya kaydedilemedi.'];
        }

        return ['success' => true, 'path' => 'uploads/' . $filename];
    }
}

/**
 * controllers/GoalController.php
 */
class GoalController extends BaseController
{
    private Goal $model;

    public function __construct()
    {
        $this->model = new Goal();
    }

    public function index(): void
    {
        $this->requireAuth();
        $userId = $this->getCurrentUserId();
        $goal   = $this->model->getByUser($userId);
        $progress = $goal ? Goal::calculateProgress(
            $goal['start_weight'], $goal['target_weight'], $goal['current_weight']
        ) : 0;

        $this->render('goals/index', [
            'title'    => 'Hedef Kilo',
            'flash'    => $this->getFlash(),
            'goal'     => $goal,
            'progress' => $progress,
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('/goals');

        $userId = $this->getCurrentUserId();
        $start  = (float) ($_POST['start_weight'] ?? 0);
        $target = (float) ($_POST['target_weight'] ?? 0);
        $current= (float) ($_POST['current_weight'] ?? $start);

        if ($start <= 0 || $target <= 0) {
            $this->setFlash('danger', 'Geçerli değerler giriniz.');
            $this->redirect('/goals');
        }

        $this->model->upsert($userId, $start, $target, $current);
        $this->setFlash('success', 'Hedef güncellendi.');
        $this->redirect('/goals');
    }
}

/**
 * controllers/WaterController.php
 */
class WaterController extends BaseController
{
    private WaterTracking $model;

    public function __construct()
    {
        $this->model = new WaterTracking();
    }

    public function index(): void
    {
        $this->requireAuth();
        $userId  = $this->getCurrentUserId();
        $date    = $this->sanitize($_GET['date'] ?? date('Y-m-d'));
        $records = $this->model->getByDate($userId, $date);
        $total   = $this->model->getDailyTotal($userId, $date);
        $user    = $this->getCurrentUser();

        $this->render('water/index', [
            'title'        => 'Su Takibi',
            'flash'        => $this->getFlash(),
            'records'      => $records,
            'dailyTotal'   => $total,
            'dailyGoal'    => $user['daily_water_goal'] ?? 2000,
            'selectedDate' => $date,
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('/water');

        $userId = $this->getCurrentUserId();
        $amount = (int) ($_POST['amount'] ?? 0);
        $unit   = $_POST['unit'] ?? 'ml';
        $date   = $this->sanitize($_POST['date'] ?? date('Y-m-d'));

        // Litre → ml dönüşümü
        if ($unit === 'litre') $amount = (int) ($amount * 1000);

        if ($amount <= 0 || $amount > 5000) {
            $this->setFlash('danger', 'Geçerli bir miktar giriniz (maks. 5000 ml).');
            $this->redirect('/water');
        }

        $this->model->add($userId, $amount, $date);
        $this->setFlash('success', "{$amount} ml su eklendi.");
        $this->redirect('/water?date=' . $date);
    }

    public function delete(): void
    {
        $this->requireAuth();
        $id = (int) ($_GET['id'] ?? 0);
        $this->model->delete($id, $this->getCurrentUserId());
        $this->setFlash('success', 'Kayıt silindi.');
        $this->redirect('/water');
    }
}

/**
 * controllers/ProfileController.php
 */
class ProfileController extends BaseController
{
    private User $model;

    public function __construct()
    {
        $this->model = new User();
    }

    public function index(): void
    {
        $this->requireAuth();
        $user = $this->model->findById($this->getCurrentUserId());
        $this->render('profile/index', ['title' => 'Profil', 'flash' => $this->getFlash(), 'user' => $user]);
    }

    public function update(): void
    {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect('/profile');

        $userId = $this->getCurrentUserId();
        $data   = [
            'name'               => $this->sanitize($_POST['name'] ?? ''),
            'age'                => (int) ($_POST['age'] ?? 0),
            'gender'             => $_POST['gender'] ?? '',
            'daily_calorie_goal' => (int) ($_POST['daily_calorie_goal'] ?? 2000),
            'daily_water_goal'   => (int) ($_POST['daily_water_goal'] ?? 2000),
            'password'           => $_POST['password'] ?? '',
        ];

        $this->model->update($userId, $data);

        // Session'ı güncelle
        $updated = $this->model->findById($userId);
        unset($updated['password']);
        $_SESSION['user'] = $updated;

        $this->setFlash('success', 'Profil güncellendi.');
        $this->redirect('/profile');
    }
}