<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <img src="<?= APP_URL ?>/assets/icon.png" alt="NexaFit Logo" 
                     style="width: 80px; height: 80px; border-radius: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); object-fit: cover;">
            </div>
            <h1 style="margin-top: 15px;">NexaFit</h1>
            <p>Sağlıklı yaşamın akıllı asistanı</p>
        </div>

        <form action="<?= APP_URL ?>/auth/login" method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">E-posta Adresi</label>
                <input type="email" id="email" name="email" 
                       class="form-control" placeholder="ornek@email.com" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Şifre</label>
                <div class="input-with-toggle">
                    <input type="password" id="password" name="password" 
                           class="form-control" placeholder="Şifreniz" required>
                    <button type="button" class="toggle-pw" onclick="togglePassword('password')">👁</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Giriş Yap</button>
        </form>

        <p class="auth-switch">
            Hesabın yok mu? 
            <a href="<?= APP_URL ?>/auth/register">Kayıt Ol</a>
        </p>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = (input.type === 'password') ? 'text' : 'password';
}
</script>