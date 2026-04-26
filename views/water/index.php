<?php /* views/water/index.php */ ?>
<div class="page-header"><h2>💧 Su Takibi</h2><p>Günlük su tüketimini takip et</p></div>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <input type="date" id="waterDate" value="<?= $selectedDate ?>" class="form-control" style="width:180px;"
           onchange="window.location.href='<?= APP_URL ?>/water?date='+this.value">
    <button class="btn btn-outline btn-sm" onclick="window.location.href='<?= APP_URL ?>/water'">Bugün</button>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-title">Su Ekle</div>
        <form action="<?= APP_URL ?>/water/store" method="POST">
            <input type="hidden" name="date" value="<?= $selectedDate ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Miktar</label>
                    <input type="number" name="amount" class="form-control" min="50" max="5000" required placeholder="250">
                </div>
                <div class="form-group">
                    <label>Birim</label>
                    <select name="unit" class="form-control">
                        <option value="ml">ml</option>
                        <option value="litre">Litre</option>
                    </select>
                </div>
            </div>
            <!-- Hızlı ekle butonları -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
                <?php foreach ([200,250,330,500] as $q): ?>
                    <button type="button" class="btn btn-outline btn-sm"
                            onclick="document.querySelector('[name=amount]').value=<?= $q ?>">
                        <?= $q ?>ml
                    </button>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-primary">Ekle 💧</button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Günlük Durum — <?= date('d.m.Y', strtotime($selectedDate)) ?></div>
        <?php $pct = $dailyGoal > 0 ? min(100, round($dailyTotal / $dailyGoal * 100)) : 0; ?>
        <div class="card-value" style="margin:10px 0"><?= $dailyTotal ?> <small style="font-size:16px">/ <?= $dailyGoal ?> ml</small></div>
        <div class="progress-wrap" style="height:14px">
            <div class="progress-fill <?= $pct >= 100 ? 'success' : '' ?>" style="width:<?= $pct ?>%"></div>
        </div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">%<?= $pct ?> tamamlandı</div>
        <div id="waterCups" style="margin-top:14px"></div>
    </div>
</div>

<div class="card">
    <div class="card-title">Kayıtlar</div>
    <?php if (empty($records)): ?>
        <p style="color:var(--text-muted);padding:12px 0">Bu gün için kayıt yok.</p>
    <?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Saat</th><th>Miktar</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($records as $r): ?>
            <tr>
                <td><?= date('H:i', strtotime($r['created_at'])) ?></td>
                <td><strong><?= $r['amount'] ?> ml</strong></td>
                <td><a href="<?= APP_URL ?>/water/delete?id=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                       onclick="return confirm('Silinsin mi?')">Sil</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>initWaterCups(<?= $dailyTotal ?>,<?= $dailyGoal ?>));</script>
