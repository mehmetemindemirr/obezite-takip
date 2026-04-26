<?php /* views/goals/index.php */ ?>
<div class="page-header"><h2>🎯 Hedef Kilo</h2><p>Kilo hedefini belirle ve takip et</p></div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Hedef Belirle</div>
        <form action="<?= APP_URL ?>/goals/store" method="POST">
            <div class="form-group">
                <label>Başlangıç Kilosu (kg)</label>
                <input type="number" name="start_weight" step="0.1" class="form-control" required
                       value="<?= $goal['start_weight'] ?? '' ?>" placeholder="85">
            </div>
            <div class="form-group">
                <label>Hedef Kilo (kg)</label>
                <input type="number" name="target_weight" step="0.1" class="form-control" required
                       value="<?= $goal['target_weight'] ?? '' ?>" placeholder="75">
            </div>
            <div class="form-group">
                <label>Mevcut Kilo (kg)</label>
                <input type="number" name="current_weight" step="0.1" class="form-control" required
                       value="<?= $goal['current_weight'] ?? '' ?>" placeholder="82">
            </div>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </div>

    <?php if ($goal): ?>
    <div class="card">
        <div class="card-title">İlerleme</div>
        <div style="display:flex;justify-content:space-between;margin:16px 0;font-size:14px">
            <div style="text-align:center">
                <div style="font-size:22px;font-weight:700"><?= $goal['start_weight'] ?></div>
                <div style="color:var(--text-muted)">Başlangıç</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:28px;font-weight:700;color:var(--primary)"><?= $goal['current_weight'] ?></div>
                <div style="color:var(--text-muted)">Şu An</div>
            </div>
            <div style="text-align:center">
                <div style="font-size:22px;font-weight:700;color:var(--success)"><?= $goal['target_weight'] ?></div>
                <div style="color:var(--text-muted)">Hedef</div>
            </div>
        </div>
        <div class="progress-wrap" style="height:16px">
            <div class="progress-fill <?= $progress >= 100 ? 'success' : '' ?>" style="width:<?= $progress ?>%"></div>
        </div>
        <div style="text-align:center;margin-top:10px;font-size:20px;font-weight:700">%<?= $progress ?></div>
        <div style="text-align:center;color:var(--text-muted);font-size:13px">
            <?= abs($goal['current_weight'] - $goal['target_weight']) ?> kg kaldı
        </div>
    </div>
    <?php endif; ?>
</div>
