<?php
/** @var array $tipos_documento */
/** @var array $universidades */
/** @var string $ref_pending */
$ref_value = htmlspecialchars($ref_pending ?? '', ENT_QUOTES, 'UTF-8');
?>
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
                        <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres, mayús, minús y número" required>
                        <i class="fas fa-eye-slash" id="toggleIcon" onclick="togglePassword()" style="position: absolute; right: 14px; top: 38px; cursor: pointer; color: var(--ink-faint);"></i>
                    </div>
                    <!-- 5.1 — Indicador de fortaleza de password -->
                    <div id="pw-strength" style="font-size:12px; margin-top:6px; font-weight:600;"></div>
                </div>
                <div class="col-md-12">
                    <div class="field field-pw">
                        <label>Confirmar Contraseña <span class="req">*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" placeholder="Repita su contraseña" required>
                    </div>
                    <div id="pw-match" style="font-size:12px; margin-top:6px; font-weight:600;"></div>
                </div>
                <div class="col-md-12">
                    <!-- 5.1 — Términos y Condiciones obligatorio -->
                    <div class="field" style="display:flex; align-items:flex-start; gap:10px;">
                        <input type="checkbox" id="terminos" name="terminos" value="1" style="margin-top:4px; width:auto;">
                        <label for="terminos" style="margin:0; font-weight:500;">
                            Acepto los <a href="#" onclick="return false;">Términos y Condiciones</a> y la <a href="#" onclick="return false;">Política de Privacidad</a> <span class="req">*</span>
                        </label>
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

        <!-- PASO 3: Ubicación, Universidad y Datos Académicos -->
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
                <!-- 5.1 — Campos académicos nuevos -->
                <div class="col-md-12">
                    <div class="field">
                        <label>Carrera</label>
                        <input type="text" name="carrera" placeholder="Ej. Ingeniería de Sistemas">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <label>Año de Ingreso</label>
                        <input type="number" name="anio_ingreso" min="2000" max="<?php echo (int)date('Y'); ?>" placeholder="Ej. 2024">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="field">
                        <label>País de Origen</label>
                        <input type="text" name="pais_origen" placeholder="Ej. Perú">
                    </div>
                </div>
                <!-- 8.2 — Código de referido opcional -->
                <div class="col-md-12">
                    <div class="field">
                        <label>Código de referido (opcional)</label>
                        <input type="text" name="codigo_referido" id="codigo_referido" placeholder="Ej. NIDO-CR2026" value="<?php echo $ref_value; ?>">
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

    // 5.1 — Evaluación de fortaleza de password (débil / media / fuerte)
    function evalPwStrength(pw) {
        var score = 0;
        if (pw.length >= 8) score++;
        if (/[a-z]/.test(pw)) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (score >= 4) return 'fuerte';
        if (score === 3) return 'media';
        return 'debil';
    }

    function updatePwStrength() {
        var pw = document.getElementById('password').value;
        var box = document.getElementById('pw-strength');
        if (!pw) { box.textContent = ''; box.style.color = ''; return; }
        var level = evalPwStrength(pw);
        var labels = { debil: 'Débil', media: 'Media', fuerte: 'Fuerte' };
        var colors = { debil: '#dc3545', media: '#f0ad4e', fuerte: '#28a745' };
        box.textContent = 'Fortaleza: ' + labels[level];
        box.style.color = colors[level];
    }

    function updatePwMatch() {
        var pw = document.getElementById('password').value;
        var pw2 = document.getElementById('password_confirm').value;
        var box = document.getElementById('pw-match');
        if (!pw2) { box.textContent = ''; box.style.color = ''; return; }
        if (pw === pw2) {
            box.textContent = 'Las contraseñas coinciden.';
            box.style.color = '#28a745';
        } else {
            box.textContent = 'Las contraseñas no coinciden.';
            box.style.color = '#dc3545';
        }
    }

    document.getElementById('password').addEventListener('input', function () {
        updatePwStrength();
        updatePwMatch();
    });
    document.getElementById('password_confirm').addEventListener('input', updatePwMatch);

    function regStep(n) {
        // 5.1 — Validaciones custom del paso 1 antes de avanzar
        if (n === 2) {
            var pw = document.getElementById('password').value;
            var pw2 = document.getElementById('password_confirm').value;
            var terms = document.getElementById('terminos');

            if (evalPwStrength(pw) === 'debil') {
                Swal.fire({
                    icon: 'warning', toast: true, position: 'top-end',
                    title: 'La contraseña es demasiado débil.',
                    showConfirmButton: false, timer: 2500
                });
                return;
            }
            if (pw !== pw2) {
                Swal.fire({
                    icon: 'warning', toast: true, position: 'top-end',
                    title: 'Las contraseñas no coinciden.',
                    showConfirmButton: false, timer: 2500
                });
                return;
            }
            if (!terms.checked) {
                Swal.fire({
                    icon: 'warning', toast: true, position: 'top-end',
                    title: 'Debe aceptar los Términos y Condiciones.',
                    showConfirmButton: false, timer: 2500
                });
                return;
            }
        }

        // Validar campos requeridos antes de avanzar
        if (n > 1) {
            let prevStep = document.getElementById('reg-s' + (n - 1));
            let inputs = prevStep.querySelectorAll('input[required], select[required]');
            let valid = true;
            inputs.forEach(input => {
                // Saltar checkbox: se valida arriba para el paso 1
                if (input.type === 'checkbox') return;
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
