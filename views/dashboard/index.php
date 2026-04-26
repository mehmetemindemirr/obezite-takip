<?php
/**
 * views/dashboard/index.php
 * Ana dashboard: VKİ kartı, hedef barı, kalori, su, grafikler.
 */
$bmiCategory = null;
$bmiColor    = 'badge-info';

if ($latestRecord) {
    $bmiCategory = \HealthRecord::getCategory($latestRecord['bmi'], $user['age'], $user['gender']);
    $bmiColor    = \HealthRecord::getColor($latestRecord['bmi'], $user['age'], $user['gender']);
}
?>

<div class="page-header">
    <h2>Merhaba, <?= htmlspecialchars($user['name'] ?? 'Kullanıcı') ?> 👋</h2>
  <?php
// Gün ve Ay isimlerini Türkçeleştirelim
$gunler = [
    'Monday'    => 'Pazartesi',
    'Tuesday'   => 'Salı',
    'Wednesday' => 'Çarşamba',
    'Thursday'  => 'Perşembe',
    'Friday'    => 'Cuma',
    'Saturday'  => 'Cumartesi',
    'Sunday'    => 'Pazar'
];

$aylar = [
    'January'   => 'Ocak',
    'February'  => 'Şubat',
    'March'     => 'Mart',
    'April'     => 'Nisan',
    'May'       => 'Mayıs',
    'June'      => 'Haziran',
    'July'      => 'Temmuz',
    'August'    => 'Ağustos',
    'September' => 'Eylül',
    'October'   => 'Ekim',
    'November'  => 'Kasım',
    'December'  => 'Aralık'
];

// Mevcut tarihi alalım
$en_tarih = date('d F Y, l'); 

// İngilizce kelimeleri Türkçe karşılıklarıyla değiştirelim
$tr_tarih = strtr($en_tarih, array_merge($gunler, $aylar));

echo $tr_tarih;
?>
</div>

<div class="grid-4">

    <div class="card stat-card <?= $latestRecord ? ($bmiColor === 'badge-success' ? 'green' : ($bmiColor === 'badge-warning' ? 'yellow' : 'red')) : '' ?>">
        <div class="card-title">VKİ (BMI)</div>
        <?php if ($latestRecord): ?>
            <div class="card-value"><?= $latestRecord['bmi'] ?></div>
            <span class="badge <?= $bmiColor ?>"><?= $bmiCategory ?></span>
        <?php else: ?>
            <div class="card-value">—</div>
            <div class="card-sub"><a href="<?= APP_URL ?>/health">Ölçüm ekle</a></div>
        <?php endif; ?>
    </div>

    <div class="card stat-card blue">
        <div class="card-title">Mevcut Kilo</div>
        <div class="card-value"><?= $latestRecord ? $latestRecord['weight'] . ' kg' : '—' ?></div>
        <div class="card-sub"><?= $latestRecord ? 'Boy: ' . $latestRecord['height'] . ' cm' : '' ?></div>
    </div>

    <div class="card stat-card <?= $todayCalorie > ($user['daily_calorie_goal'] ?? 2000) ? 'red' : 'green' ?>">
        <div class="card-title">Bugünkü Kalori</div>
        <div class="card-value"><?= number_format($todayCalorie) ?></div>
        <div class="card-sub">Hedef: <?= number_format($user['daily_calorie_goal'] ?? 2000) ?> kcal
            <?php if ($todayCalorie > ($user['daily_calorie_goal'] ?? 2000)): ?>
                <span class="badge badge-danger">Aşıldı!</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card stat-card blue">
        <div class="card-title">Bugünkü Su</div>
        <div class="card-value"><?= $todayWater ?> <small style="font-size:16px">ml</small></div>
        <div class="card-sub">Hedef: <?= $user['daily_water_goal'] ?? 2000 ?> ml</div>
    </div>
</div>

<div class="grid-2">

    <div class="card">
        <div class="card-title">🎯 Hedef Kilo İlerlemesi</div>
        <?php if ($goal): ?>
            <div style="display:flex; justify-content:space-between; font-size:13px; color:var(--text-muted); margin-bottom:6px;">
                <span>Başlangıç: <strong><?= $goal['start_weight'] ?> kg</strong></span>
                <span>Mevcut: <strong><?= $goal['current_weight'] ?> kg</strong></span>
                <span>Hedef: <strong><?= $goal['target_weight'] ?> kg</strong></span>
            </div>
            <div class="progress-wrap">
                <div class="progress-fill <?= $goalProgress >= 100 ? 'success' : ($goalProgress > 50 ? '' : 'warning') ?>"
                     style="width:<?= min(100, $goalProgress) ?>%"></div>
            </div>
            <div style="text-align:right; font-size:13px; color:var(--text-muted); margin-top:4px;">
                %<?= $goalProgress ?> tamamlandı
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:14px;">Henüz hedef belirlenmedi.</p>
            <a href="<?= APP_URL ?>/goals" class="btn btn-outline btn-sm" style="margin-top:12px;">Hedef Belirle</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">🤖 Diyet Önerisi</div>
        <?php if ($recommendation): ?>
            <div class="recommendation-box">
                <p><?= htmlspecialchars($recommendation['recommendation_text']) ?></p>
            </div>
            <div style="font-size:11px; color:var(--text-muted); margin-top:8px;">
                <?= date('d.m.Y', strtotime($recommendation['created_at'])) ?> tarihli analiz
            </div>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:14px;">Öneri için sağlık kaydı ekleyin.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">📈 Kilo Değişimi (son 30 gün)</div>
        <div class="chart-container">
            <canvas id="weightChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-title">🔥 Haftalık Kalori</div>
        <div class="chart-container">
            <canvas id="calorieChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  renderWeightChart('weightChart', <?= $chartData ?>);
  renderCalorieChart('calorieChart', <?= $weeklyCalories ?>);
});
</script>