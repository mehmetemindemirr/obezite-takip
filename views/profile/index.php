<?php /* views/profile/index.php */ ?>
<div class="page-header"><h2>👤 Profil</h2><p>Kişisel bilgilerini güncelle</p></div>

<div class="card" style="max-width:560px">
    <div class="card-title">Bilgileri Düzenle</div>
    <form action="<?= APP_URL ?>/profile/update" method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Ad Soyad</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled
                       style="background:#f8fafc;cursor:not-allowed;">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Yaş</label>
                <input type="number" name="age" class="form-control" min="5" max="120" value="<?= $user['age'] ?>">
            </div>
            <div class="form-group">
                <label>Cinsiyet</label>
                <select name="gender" class="form-control">
                    <?php foreach (['erkek','kadın','diğer'] as $g): ?>
                        <option value="<?= $g ?>" <?= $user['gender'] === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Günlük Kalori Hedefi (kcal)</label>
                <input type="number" name="daily_calorie_goal" class="form-control" min="800" max="6000"
                       value="<?= $user['daily_calorie_goal'] ?>">
            </div>
            <div class="form-group">
                <label>Günlük Su Hedefi (ml)</label>
                <input type="number" name="daily_water_goal" class="form-control" min="500" max="10000"
                       value="<?= $user['daily_water_goal'] ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Yeni Şifre <small style="color:var(--text-muted)">(boş bırakırsan değişmez)</small></label>
            <div class="input-with-toggle">
                <input type="password" name="password" id="password" class="form-control" placeholder="Şifreyi değiştir" minlength="6">
                <button type="button" class="toggle-pw" onclick="togglePassword('password')">👁</button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Kaydet</button>
    </form>
</div>
