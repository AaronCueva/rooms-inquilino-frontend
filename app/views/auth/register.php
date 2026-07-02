<div class="auth-panel" style="max-width:550px; flex:0 0 550px; overflow-y:auto; padding: 40px;">
    <div class="auth-logo">
        <div class="logo-box"><i class="fas fa-building text-white"></i></div>
        <div class="wm">APP-<span>ROOMS</span></div>
    </div>
    <div class="auth-role-pill">
        <i class="fas fa-user-graduate"></i> Registro de Inquilino
    </div>
    
    <h1 id="reg-title">Datos de la cuenta</h1>
    <div class="sub" id="reg-sub">Empieza a buscar en menos de 2 minutos.</div>
    
    <!-- Indicadores de paso -->
    <div class="steps-row mb-4">
        <div class="step-dot is-active" id="dot-1">1</div>
        <div class="step-line" id="line-12"></div>
        <div class="step-dot" id="dot-2">2</div>
        <div class="step-line" id="line-23"></div>
        <div class="step-dot" id="dot-3">3</div>
    </div>

    <form id="registerForm" action="/register" method="POST">
        
        <!-- PASO 1: Datos Personales -->
        <div id="reg-s1">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="field">
                        <label>Nombres Completos <span class="req">*</span></label>
                        <input type="text" name="nombres" placeholder="Ej. Camila" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <label>Apellido Paterno <span class="req">*</span></label>
                        <input type="text" name="apellido_paterno" placeholder="Ej. Ríos" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <label>Apellido Materno <span class="req">*</span></label>
                        <input type="text" name="apellido_materno" placeholder="Ej. López" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field">
                        <label>Correo Electrónico <span class="req">*</span></label>
                        <input type="email" name="correo" placeholder="correo@email.com" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field field-pw">
                        <label>Contraseña <span class="req">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                        <i class="fas fa-eye-slash" id="toggleIcon" onclick="togglePassword()" style="position: absolute; right: 14px; top: 38px; cursor: pointer; color: var(--ink-faint);"></i>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-primary btn-block w-100 mt-4" onclick="regStep(2)">Continuar</button>
        </div>

        <!-- PASO 2: Documento y Contacto -->
        <div id="reg-s2" style="display:none;">
            <div class="verify-note mb-3">
                <i class="fas fa-shield-alt text-success me-2 mt-1"></i>
                <span>Verificamos tu identidad para proteger la comunidad de APP-ROOMS.</span>
            </div>
            
            <div class="row g-3">
                <div class="col-md-5">
                    <div class="field">
                        <label>Tipo Documento <span class="req">*</span></label>
                        <select name="tipo_documento_codigo" required>
                            <option value="" selected disabled>Seleccione...</option>
                            <?php if (isset($tipos_documento)): ?>
                                <?php foreach ($tipos_documento as $tipo): ?>
                                    <option value="<?php echo htmlspecialchars($tipo['codigo']); ?>">
                                        <?php echo htmlspecialchars($tipo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="field">
                        <label>Número Documento <span class="req">*</span></label>
                        <input type="text" name="numero_documento" placeholder="Número" required>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="field">
                        <label>Celular <span class="req">*</span></label>
                        <input type="text" name="celular" placeholder="Número celular" required>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-primary btn-block w-100 mt-4" onclick="regStep(3)">Verificar y continuar</button>
            <button type="button" class="btn btn-ghost btn-block w-100 mt-2" onclick="regStep(1)">Atrás</button>
        </div>

        <!-- PASO 3: Ubicación y Universidad -->
        <div id="reg-s3" style="display:none;">
            <div class="verify-note mb-3">
                <i class="fas fa-map-marker-alt text-primary me-2 mt-1"></i>
                <span>Cuéntanos dónde buscas vivir y dónde estudias para recomendarte las mejores opciones.</span>
            </div>
            
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="field">
                        <label>Universidad <span class="req">*</span></label>
                        <select name="universidad_id" required>
                            <option value="" selected disabled>Seleccione su universidad...</option>
                            <?php if (isset($universidades)): ?>
                                <?php foreach ($universidades as $uni): ?>
                                    <option value="<?php echo htmlspecialchars($uni['universidad_id']); ?>">
                                        <?php echo htmlspecialchars($uni['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block w-100 mt-4">Crear mi cuenta</button>
            <button type="button" class="btn btn-ghost btn-block w-100 mt-2" onclick="regStep(2)">Atrás</button>
        </div>

    </form>
    
    <div class="auth-footer mt-4">
        <a href="/login">Ya tengo cuenta — Iniciar sesión</a>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById('password');
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

    function regStep(n) {
        // Validar campos requeridos antes de avanzar
        if (n > 1) {
            let prevStep = document.getElementById('reg-s' + (n - 1));
            let inputs = prevStep.querySelectorAll('input[required], select[required]');
            let valid = true;
            inputs.forEach(input => {
                if (!input.value) {
                    input.style.borderColor = 'var(--red)';
                    valid = false;
                } else {
                    input.style.borderColor = 'var(--line)';
                }
            });
            if (!valid) return;
        }

        [1, 2, 3].forEach(i => {
            var s = document.getElementById('reg-s' + i);
            if (s) s.style.display = (i === n) ? 'block' : 'none';
            
            var d = document.getElementById('dot-' + i);
            if (d) {
                d.classList.remove('is-active', 'is-done');
                if (i < n) d.classList.add('is-done');
                else if (i === n) d.classList.add('is-active');
            }
        });
        
        var l12 = document.getElementById('line-12'), l23 = document.getElementById('line-23');
        if (l12) l12.classList.toggle('is-done', n > 1);
        if (l23) l23.classList.toggle('is-done', n > 2);
        
        const titles = {
            1: 'Datos de la cuenta',
            2: 'Verifica tu identidad',
            3: 'Cuéntanos sobre ti'
        };
        const subs = {
            1: 'Empieza a buscar en menos de 2 minutos.',
            2: 'Último paso antes de empezar a buscar.',
            3: 'Solo te tomará un par de minutos.'
        };
        
        document.getElementById('reg-title').textContent = titles[n];
        document.getElementById('reg-sub').textContent = subs[n];
    }


</script>
