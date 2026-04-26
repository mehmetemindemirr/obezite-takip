<?php /* views/health/index.php */ ?>
<div class="page-header">
    <h2>📈 Sağlık Takibi</h2>
    <p>Boy, kilo ve VKİ geçmişiniz</p>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Yeni Ölçüm Ekle</div>
        <form action="<?= APP_URL ?>/health/store" method="POST">
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                    <label>Kilo (kg)</label>
                    <input type="number" name="weight" step="0.1" min="20" max="300" class="form-control" required placeholder="75.5">
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                    <label>Boy (cm)</label>
                    <input type="number" name="height" step="0.1" min="100" max="250" class="form-control" required placeholder="175">
                </div>
            </div>
            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                    <label>Tarih</label>
                    <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                    <label>Not (opsiyonel)</label>
                    <input type="text" name="note" class="form-control" placeholder="Egzersiz yaptım...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Kaydet</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">VKİ Değişimi</div>
        <div class="chart-container">
            <canvas id="bmiChart"></canvas>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-title">Ölçüm Geçmişi</div>
    <?php if (empty($records)): ?>
        <p style="color:var(--text-muted);padding:12px 0;">Henüz kayıt yok.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table style="min-width: 500px;">
            <thead><tr><th>Tarih</th><th>Kilo</th><th>Boy</th><th>VKİ</th><th>Kategori</th><th>Not</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($records as $r): ?>
                <tr>
                    <td><?= date('d.m.Y', strtotime($r['date'])) ?></td>
                    <td><strong><?= $r['weight'] ?> kg</strong></td>
                    <td><?= $r['height'] ?> cm</td>
                    <td><?= $r['bmi'] ?></td>
                    <td><span class="badge <?= \HealthRecord::getColor($r['bmi'], $user['age'], $user['gender']) ?>"><?= \HealthRecord::getCategory($r['bmi'], $user['age'], $user['gender']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:13px"><?= htmlspecialchars($r['note'] ?? '') ?></td>
                    <td><a href="<?= APP_URL ?>/health/delete?id=<?= $r['id'] ?>" class="btn btn-outline btn-sm" onclick="return confirm('Silinsin mi?')">Sil</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const data = <?= json_encode(array_reverse($records)) ?>;
  renderBMIChart('bmiChart', data.slice(-30));
  renderWeightChart && renderWeightChart; 
});
</script>