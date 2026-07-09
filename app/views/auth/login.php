<div class="auth-panel">
    <div class="auth-logo">
        <div class="logo-box"><i class="fas fa-building text-white"></i></div>
        <div class="wm">APP-<span>ROOMS</span></div>
    </div>
    <div class="auth-role-pill">
        <i class="fas fa-user-graduate"></i> Portal de inquilinos
    </div>
    <h1>Inicia sesión</h1>
    <div class="sub">Encuentra opciones de alojamiento verificadas.</div>
    
    <form action="/login" method="POST">
        <div class="field">
            <label>Correo electrónico <span class="req">*</span></label>
            <input type="email" name="correo" placeholder="correo@email.com" required>
        </div>
        <div class="field field-pw">
            <label>Contraseña <span class="req">*</span></label>
            <input type="password" name="password" placeholder="••••••••" required>
            <i class="fas fa-eye-slash" id="toggleIcon" onclick="togglePassword()" style="position: absolute; right: 14px; top: 38px; cursor: pointer; color: var(--ink-faint);"></i>
        </div>
        <div class="terms">
            Al iniciar sesión aceptas los <a href="#">Términos de uso</a> y la <a href="#">Política de privacidad</a> de APP-ROOMS.
        </div>
        <button type="submit" class="btn btn-primary btn-block w-100">Ingresar a mi cuenta</button>
        <div class="mt-3">
            <a href="/buscar" class="btn btn-outline-secondary btn-block w-100" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; border-radius: 999px; padding: 11px 20px; text-decoration: none;">
                <i class="fas fa-search"></i> Explorar alojamientos sin iniciar sesión
            </a>
        </div>
    </form>
    
    <div class="auth-footer mt-4">
        <a href="/register">Crear una cuenta como inquilino</a> · <a href="#">Olvidé mi contraseña</a>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.querySelector('input[name="password"]');
        const toggleIcon = document.getElementById('toggleIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        }
    }
</script>
