<?php /* views/meals/index.php */ ?>
<div class="page-header">
    <h2>🍽 Kalori Takibi</h2>
    <p>Günlük öğün ve kalori kaydı</p>
</div>

<!-- Tarih Seçici -->
<div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
    <label style="font-weight:600; font-size:14px;">Tarih:</label>
    <input type="date" id="dateFilter" value="<?= $selectedDate ?>" class="form-control" style="width:180px;"
           onchange="window.location.href='<?= APP_URL ?>/meals?date=' + this.value">
    <button class="btn btn-outline btn-sm" onclick="setToday('dateFilter'); window.location.href='<?= APP_URL ?>/meals'">Bugün</button>
</div>

<div class="grid-2">
    <!-- Form -->
    <div class="card">
        <div class="card-title">Öğün Ekle</div>
        <form action="<?= APP_URL ?>/meals/store" method="POST" enctype="multipart/form-data" data-validate>
            <input type="hidden" name="ai_analyzed_flag" id="ai_flag" value="0">
            <input type="hidden" name="date" value="<?= $selectedDate ?>">
            <div class="form-group">
                <label>Yemek Adı</label>
                <input type="text" name="food_name" class="form-control" required placeholder="Izgara tavuk, pilav...">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Kalori (kcal)</label>
                    <input type="number" name="calories" class="form-control" min="1" max="5000" required placeholder="350">
                </div>
                <div class="form-group">
                    <label>Öğün</label>
                    <select name="meal_type" class="form-control">
                        <option value="kahvaltı">Kahvaltı</option>
                        <option value="öğle">Öğle</option>
                        <option value="akşam">Akşam</option>
                        <option value="ara öğün" selected>Ara Öğün</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Fotoğraf <small style="color:var(--text-muted)">(opsiyonel – AI kalori tahmini)</small></label>
                <input type="file" name="food_image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </form>
    </div>

    <!-- Günlük Özet -->
    <div class="card">
        <div class="card-title">Günlük Özet — <?= date('d.m.Y', strtotime($selectedDate)) ?></div>
        <div style="margin:12px 0;">
            <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:6px;">
                <span>Alınan Kalori</span>
                <strong><?= number_format($dailyTotal) ?> kcal</strong>
            </div>
            <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:10px;">
                <span>Hedef</span>
                <span><?= number_format($dailyGoal) ?> kcal</span>
            </div>
            <div class="progress-wrap">
                <?php $pct = $dailyGoal > 0 ? min(100, round($dailyTotal / $dailyGoal * 100)) : 0; ?>
                <div class="progress-fill <?= $pct >= 100 ? 'danger' : ($pct >= 80 ? 'warning' : '') ?>"
                     style="width:<?= $pct ?>%"></div>
            </div>
            <div style="text-align:right; font-size:12px; color:var(--text-muted); margin-top:4px;"><?= $pct ?>%</div>

            <?php if ($dailyTotal > $dailyGoal): ?>
                <div class="alert alert-danger" style="margin-top:12px;">
                    ⚠ Günlük hedefi <strong><?= number_format($dailyTotal - $dailyGoal) ?> kcal</strong> aştınız!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Öğün Listesi -->
<div class="card">
    <div class="card-title">Öğünler — <?= date('d F Y', strtotime($selectedDate)) ?></div>
    <?php if (empty($meals)): ?>
        <p style="color:var(--text-muted);padding:12px 0;">Bu gün için kayıt yok.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Yemek</th><th>Öğün</th><th>Kalori</th><th>AI</th><th>Fotoğraf</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($meals as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['food_name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['meal_type']) ?></td>
                    <td><?= $m['calories'] ?> kcal</td>
                    <td><?= $m['ai_analyzed'] ? '✅' : '—' ?></td>
                    <td>
                        <?php if ($m['image_path']): ?>
                            <img src="<?= APP_URL . '/' . htmlspecialchars($m['image_path']) ?>"
                                 style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td><a href="<?= APP_URL ?>/meals/delete?id=<?= $m['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Silinsin mi?')">Sil</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.querySelector('input[name="food_image"]');
    const yemekInput = document.querySelector('input[name="food_name"]'); 
    const kaloriInput = document.querySelector('input[name="calories"]');   
    const aiFlag = document.getElementById('ai_flag');

    if(fileInput) {
        fileInput.addEventListener('change', async function() {
            if (!this.files[0]) return;

            yemekInput.value = "Yapay zeka analiz ediyor...";
            kaloriInput.value = ""; 
            kaloriInput.placeholder = "Hesaplanıyor..."; 

            const formData = new FormData();
            formData.append('photo', this.files[0]);

            try {
                const response = await fetch('/ai_service/analyze.php', {
                    method: 'POST',
                    body: formData
                });

                const textResponse = await response.text();
                // Hata ayıklama için: Eğer garip bir hata verirse konsolda ne geldiğini görebilirsin.
                console.log("Sunucudan Gelen Ham Yanıt:", textResponse); 
                
                const data = JSON.parse(textResponse);
                
                // Kutuları Otomatik Doldur
                yemekInput.value = data.yemek;
                kaloriInput.value = data.kalori;
                kaloriInput.placeholder = "350"; 
                aiFlag.value = "1"; // AI analiz edildi flag'ini kaldır
            } catch (error) {
                console.error("JSON Parse Hatası:", error);
                yemekInput.value = "Analiz başarısız!";
                kaloriInput.value = "";
                kaloriInput.placeholder = "Elle giriniz";
                aiFlag.value = "0";
            }
        });
    }
});
</script>