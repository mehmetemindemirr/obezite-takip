<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="<?= APP_URL ?>/assets/icon.png" alt="NexaFit Logo" 
                     style="width: 80px; height: 80px; border-radius: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); object-fit: cover;">
            </div>
            <h1 style="margin-top: 15px;">NexaFit'e Katıl</h1>
            <p>Sağlıklı yaşam yolculuğuna bugün başlayın!</p>
        </div>

        <form action="<?= APP_URL ?>/auth/register" method="POST" class="auth-form">
            <div class="form-group">
                <label for="name">Ad Soyad</label>
                <input type="text" id="name" name="name" 
                       class="form-control" placeholder="Adınız Soyadınız" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" 
                       class="form-control" placeholder="ornek@email.com" required>
            </div>

            <div class="grid-2" style="gap: 15px; margin-bottom: 15px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="age">Yaş</label>
                    <input type="number" id="age" name="age" 
                           class="form-control" placeholder="Yaşınız" required min="2" max="120">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="gender">Cinsiyet</label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="erkek">Erkek</option>
                        <option value="kadın">Kadın</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <div class="input-with-toggle">
                    <input type="password" id="password" name="password" 
                           class="form-control" placeholder="En az 6 karakter" required minlength="6">
                    <button type="button" class="toggle-pw" onclick="togglePassword('password')">👁</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Kayıt Ol</button>
        </form>

        <p class="auth-switch">
            Zaten hesabın var mı? 
            <a href="<?= APP_URL ?>/auth/login">Giriş Yap</a>
        </p>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = (input.type === 'password') ? 'text' : 'password';
}
</script>