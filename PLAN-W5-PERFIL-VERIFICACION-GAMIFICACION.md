# Plan de Implementación — W5: Perfil, verificación y gamificación del estudiante (inquilino)

> **Oleada:** W5 · **Ref. doc:** DF-NidoUniversitario §3.4.2 (verificación estudiantil), §3.4.3 (dashboard), §3.4.4 (gamificación), §4.5.2/§4.5.3 (registro/auth) · **Depende de:** W1/W2 (✅), W6 chat (✅, sección Mensajes) · **Desbloquea:** W8.2 (referidos consume `PuntosNido`), dashboard real del inquilino
> **Estado:** ⬜ Pendiente · **Creado:** 2026-07-09
> Registro de seguimiento.

---

## 1. Objetivo

Cerrar el ciclo del estudiante inquilino dentro del portal: **completar el registro** (campos faltantes + T&C + fortaleza de password), **verificar la cuenta por email** (token en `usuario.token`), **verificar la identidad estudiantil** (dominio .edu + subida de carnet/constancia + validación admin + badge "Estudiante verificado"), entregar un **dashboard real** que orqueste los módulos del portal, permitir la **edición de perfil** y habilitar la **gamificación** (puntos Nido, racha, niveles, canje) **sin** eventos de notificación push.

**Criterio de aceptación W5:**
> Registro completo con T&C y password fuerte → email de verificación con token → dashboard real con secciones que consumen W5.4/W5.8/W6 (y stubs para W3/W4/W7/W8) → perfil editable → verificación de identidad subida y pendiente de admin → gamificación con saldo, nivel, racha y movimientos funcionando. OAuth (5.2) queda defer v1; preferencias de notificación (5.7) descartadas.

---

## 2. Decisiones de alcance (tomadas con el usuario)

| # | Decisión | Opción elegida |
|---|---|---|
| D1 | Campos faltantes 5.1 (carrera, año de ingreso, país de origen) | **Proponer `ALTER TABLE usuario ADD COLUMN`** (verificadas FALTAS en §5.1). Confirmar con el usuario antes de ejecutar. |
| D2 | Confirmar contraseña + checkbox T&C + fortaleza de password | **Sí, en el paso 1 del registro existente** (JS de medidor de fortaleza + match de contraseñas + checkbox `terminos` obligatorio que persiste en `usuario.terminos`). |
| D3 | OAuth Google/Facebook (5.2) | **Defer v1** — se documenta como pendiente; no se implementa. |
| D4 | Email de bienvenida + verificación de cuenta (5.3) | **Token en `usuario.token`/`fecha_expiracion_token` + link `/verificar?token=...`**. **Envío de email real: stub v1** (se genera el token y se loguea/muestra el link en flash en dev; no hay transport SMTP configurado). Documentado en §10. |
| D5 | Verificación de identidad estudiantil (5.4) | **Detección de dominio .edu/institucional** (marca `verificado=true` automático si el correo cumple) **+ subida de carnet/constancia** vía tabla `multimedia` (`tipo_codigo='DOCUMENTO'`, `usuario_id`=estudiante) con estado "pendiente de validación admin". Badge `pd-verified` "Estudiante verificado" cuando `usuario.verificado=true`. |
| D6 | Subida de carnet — upload de archivos | **v1: stub de upload** — no hay storage/S3 configurado. El formulario acepta el archivo y guarda un registro `multimedia` con una URL simulada (o el nombre del archivo); el pipeline real de storage se deja para post-v1. Documentado en §10. |
| D7 | Dashboard real (5.5) | **Reemplazar el mockup** `app/views/inquilino/dashboard.php` (hoy tarjetas hardcodeadas) por secciones que consumen módulos reales (W5.4, W5.8, W6) y stubs "Próximamente" para W3/W4/W7/W8. |
| D8 | Layout del dashboard y secciones privadas | **Layout `main`** (app-shell inquilino con nav MenuMaestro + avatar). **Cargar `app-design.css` en `main.php`** y restylear `dashboard.php` con tokens `pd-*` para dar el look del design system. **Trade-off:** el app-shell nav (clases `app-nav`/`nav-link` propias) se mantiene intacto — solo se añade la hoja pd para el contenido del dashboard; puede haber mezcla de estilos Bootstrap+pd que se irá migrando incrementalmente. Documentado. |
| D9 | Edición de perfil (5.6) | **`PerfilController` + vista**, reutiliza `Usuario::actualizarPerfil` (ya existe). |
| D10 | Preferencias de notificación (5.7) | **Descartado** — sin notificaciones (decisión de proyecto). |
| D11 | Gamificación (5.8) | **Modelo `PuntosNido`** (puntos, racha, niveles, canje) sobre `punto_movimiento` + `usuario.puntos_acumulados`. **Sin** eventos de notificación. |
| D12 | Contrato W5↔W8 (`PuntosNido`) | **Congelado antes de empezar** (§6) para que W8.2 pueda codear contra él en paralelo. |

**Fuera de W5 v1:** OAuth (5.2), preferencias de notificación (5.7), envío real de email, upload real de archivos a storage, validación admin de carnet con UI de admin (solo se persiste el estado pendiente).

---

## 3. Arquitectura — archivos a crear / modificar

### Crear
| Archivo | Rol |
|---|---|
| `app/models/PuntosNido.php` | Modelo gamificación: saldo, movimientos, acreditar, nivel, racha, canje. **Contrato congelado W5↔W8 (§6).** |
| `app/models/Verificacion.php` | Modelo verificación de cuenta (token) y de identidad (dominio .edu, multimedia carnet, estado). |
| `app/controllers/PerfilController.php` | Dashboard real, edición de perfil, verificación de identidad (subida carnet), mis puntos. |
| `app/views/perfil/dashboard.php` | Dashboard real (reemplaza al mockup). Layout `main`. |
| `app/views/perfil/editar.php` | Formulario de edición de perfil. Layout `main`. |
| `app/views/perfil/verificar.php` | Landing de verificación de cuenta (`/verificar?token=...`) + estado de verificación de identidad. Layout `public`. |
| `app/views/perfil/puntos.php` | Mis puntos Nido: saldo, nivel, racha, historial de movimientos, canje. Layout `main`. |

### Modificar
| Archivo | Cambio |
|---|---|
| `app/controllers/AuthController.php` | `register()`: capturar `carrera`, `anio_ingreso`, `pais_origen`, `terminos`; validar confirmación de password y fortaleza; generar `token` + `fecha_expiracion_token`; set `fecha_ingreso=now()`; acreditar bono de bienvenida (TMPT007) tras crear. `showRegister()`: pasar catálogos extra (género, ubicaciones país). |
| `app/models/Usuario.php` | `create()`: añadir columnas nuevas (`carrera`, `anio_ingreso`, `pais_origen`, `terminos`, `token`, `fecha_expiracion_token`, `fecha_ingreso`, `genero_codigo`). `actualizarPerfil()`: añadir campos nuevos. Nuevo `findByToken($token)`, `marcarVerificado($id)`, `setToken($id,$token,$exp)`. |
| `app/views/auth/register.php` | Paso 1: medidor de fortaleza de password + campo confirmar contraseña + checkbox T&C. Paso 3: añadir carrera, año de ingreso, país de origen, género. |
| `app/views/layouts/main.php` | Cargar `app-design.css` (después de `style.css`) para habilitar tokens `pd-*` en el contenido. El app-shell nav se mantiene. |
| `app/views/inquilino/dashboard.php` | **Eliminar/deprecar** el mockup (el dashboard real vive en `perfil/dashboard.php`; o bien reescribir este archivo y apuntar la ruta `/dashboard` a `PerfilController::dashboard`). |
| `index.php` | Registrar rutas de perfil/verificación/puntos (§4). Mover `/dashboard` a `PerfilController`. |

### BD (Supabase) — ver §5
- `ALTER TABLE usuario ADD COLUMN` para `carrera`, `anio_ingreso`, `pais_origen` (verificadas FALTAS, §5.1).
- No se crean tablas nuevas (`punto_movimiento`, `multimedia`, catálogos ya existen).

---

## 4. Ruteo

```
GET  /dashboard                       → PerfilController::dashboard        (dashboard real, layout main)
GET  /perfil                          → PerfilController::editar            (form edición perfil, layout main)
POST /perfil                          → PerfilController::guardar           (actualizarPerfil + redirect)
GET  /perfil/verificar                → PerfilController::verificarIdentidad (estado verificación identidad + form subida carnet, layout main)
POST /perfil/verificar/subir          → PerfilController::subirCarnet       (guarda multimedia DOCUMENTO, estado pendiente)
GET  /verificar                       → PerfilController::verificarCuenta   (?token=... — valida token, marca verificado, layout public)
GET  /puntos                          → PerfilController::puntos            (mis puntos Nido, layout main)
POST /puntos/canjear                  → PerfilController::canjear           (AJAX/POST: canje de puntos)
```

En `index.php`:
```php
// Dashboard + Perfil + Verificación + Gamificación (W5)
$router->get('/dashboard', 'PerfilController', 'dashboard');
$router->get('/perfil', 'PerfilController', 'editar');
$router->post('/perfil', 'PerfilController', 'guardar');
$router->get('/perfil/verificar', 'PerfilController', 'verificarIdentidad');
$router->post('/perfil/verificar/subir', 'PerfilController', 'subirCarnet');
$router->get('/verificar', 'PerfilController', 'verificarCuenta');
$router->get('/puntos', 'PerfilController', 'puntos');
$router->post('/puntos/canjear', 'PerfilController', 'canjear');
```

Todos los endpoints salvo `/verificar` (token público) requieren sesión (constructor redirect a `/login`). El dropdown del avatar en `main.php` ya apunta a `/perfil` — se mantiene.

> Nota: la ruta `/dashboard` hoy apunta a `DashboardController` (que renderiza el mockup). Se mueve a `PerfilController::dashboard`. Si `DashboardController` no existe como clase cargada, no hay conflicto; si existe, se elimina su ruta.

---

## 5. BD — columnas y catálogos

### 5.1 Columnas faltantes en `usuario` (verificadas vía `information_schema` el 2026-07-09)

**Query ejecutada** (PDO vía `App\Core\Database`):
```sql
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema='public' AND table_name='usuario'
ORDER BY ordinal_position;
```

**Resultado de la verificación de targets:**
| Columna | Estado |
|---|---|
| `carrera` | **FALTA** |
| `anio_ingreso` | **FALTA** |
| `pais_origen` | **FALTA** |
| `fecha_ingreso` | EXISTE (timestamp) |
| `token` | EXISTE (varchar) |
| `fecha_expiracion_token` | EXISTE (timestamp) |
| `verificado` | EXISTE (boolean) |
| `terminos` | EXISTE (boolean) |
| `puntos_acumulados` | EXISTE (integer) |
| `genero_codigo` | EXISTE (varchar → catálogo GENERO) |

**Propuesta de ALTER (ejecutar en Supabase SQL Editor, previa confirmación del usuario):**
```sql
alter table usuario add column carrera       character varying;
alter table usuario add column anio_ingreso  integer;
alter table usuario add column pais_origen   character varying;   -- nombre de país (v1 texto libre; futuro: pais_origen_id → ubicacion)
```
> `pais_origen` se modela como texto v1 (no hay FK a `ubicacion` de tipo país confirmado de forma simple; `ubicacion` es jerárquica por `referencia_id`). Si se prefiere normalizar, usar `pais_origen_id uuid → ubicacion(ubicacion_id)` filtrando `tipo_ubicacion_codigo='TPU001'`. **Decisión v1: texto libre** — documentado.

### 5.2 Tabla `punto_movimiento` (ya existe — confirmada)
Columnas: `punto_movimiento_id`(uuid), `tipo_movimiento_codigo`(varchar), `puntos`(int), `descripcion`(varchar), `fecha_creacion`(timestamp), `puntos_otorgados`(int), `usuario_id`(uuid), `habilitado`(bool), `creado`, `creado_por`, `modificado`, `modificado_por`.

### 5.3 Tabla `multimedia` (ya existe — confirmada)
Columnas: `multimedia_id`(uuid), `url`(varchar), `tipo_codigo`(varchar), `nombre`(varchar), `orden`(int), `usuario_id`(uuid), `alojamiento_id`(uuid), `foro_id`(uuid), `habilitado`(bool), `creado`, `creado_por`, `modificado`, `modificado_por`.
- Para carnet/constancia: `tipo_codigo='DOCUMENTO'`, `usuario_id`=estudiante, `alojamiento_id`/`foro_id` NULL.

### 5.4 Catálogos (confirmados)
- **TIPO_MOVIMIENTO_PUNTO:** TMPT001=GANANCIA POR REFERIDO, TMPT002=GANANCIA POR RESEÑA, TMPT003=GANANCIA POR PAGO PUNTUAL, TMPT004=GANANCIA POR PARTICIPACIÓN, TMPT005=CANJE DE PUNTOS, TMPT006=AJUSTE MANUAL (ADMIN), TMPT007=BONO DE BIENVENIDA.
- **ESTADO_USUARIO:** ESU001=ACTIVO, ESU002=INACTIVO.
- **GENERO:** GEN001=MASCULINO, GEN002=FEMENINO.
- **TIPO_DOCUMENTO:** (2 filas, ya usadas en registro).

---

## 6. Modelo `PuntosNido` — contrato congelado W5↔W8

> **Este contrato se congela antes de empezar la implementación** para que W8.2 (referidos) pueda codear contra él en paralelo. W8.2 usará `PuntosNido::acreditar($usuario_id, TMPT_REFERIDO, $puntos, $desc)` al concretarse un referido.

```php
namespace App\Models;
use App\Core\Database; use PDO;

class PuntosNido {
    // Catálogo TIPO_MOVIMIENTO_PUNTO
    public const TMPT_REFERIDO        = 'TMPT001';
    public const TMPT_RESENA          = 'TMPT002';
    public const TMPT_PAGO_PUNTUAL    = 'TMPT003';
    public const TMPT_PARTICIPACION   = 'TMPT004';
    public const TMPT_CANJE           = 'TMPT005';
    public const TMPT_AJUSTE          = 'TMPT006';
    public const TMPT_BONO_BIENVENIDA = 'TMPT007';

    // Niveles (threshold de puntos_acumulados)
    public const NIVEL_NOVATO            = ['codigo' => 'NV1', 'nombre' => 'Novato',             'min' => 0];
    public const NIVEL_INQUILINO_CONFIABLE = ['codigo' => 'NV2', 'nombre' => 'Inquilino Confiable', 'min' => 1000];
    public const NIVEL_NIDO_GOLD         = ['codigo' => 'NV3', 'nombre' => 'Nido Gold',          'min' => 3000];

    // Saldo actual = usuario.puntos_acumulados
    public function obtener(string $usuario_id): int;

    // Historial paginado de punto_movimiento order by fecha_creacion desc
    public function movimientos(string $usuario_id, int $pagina = 1, int $porPagina = 20): array;

    // INSERT punto_movimiento + UPDATE usuario.puntos_acumulados += puntos (transacción).
    // $puntos puede ser negativo (canje/ajuste). Devuelve la fila insertada o null.
    public function acreditar(string $usuario_id, string $tipo_codigo, int $puntos, string $descripcion): ?array;

    // ['codigo','nombre','proximo_nombre','falta'] según puntos_acumulados
    public function nivel(int $puntos): array;

    // Meses consecutivos con TMPT003 hasta hoy (v1 simple)
    public function racha(string $usuario_id): int;

    // Valida saldo >= canje; acreditar(TMPT_CANJE, -canje, descripcion). bool.
    public function canjear(string $usuario_id, int $puntos_a_canjear, string $descripcion): bool;
}
```

### SQL de referencia

**`obtener`:**
```sql
SELECT puntos_acumulados FROM usuario WHERE usuario_id = :u;
```

**`movimientos`:**
```sql
SELECT punto_movimiento_id, tipo_movimiento_codigo, puntos, descripcion, fecha_creacion, puntos_otorgados
FROM punto_movimiento
WHERE usuario_id = :u AND habilitado = true
ORDER BY fecha_creacion DESC
LIMIT :limit OFFSET :offset;
```

**`acreditar` (transacción):**
```sql
-- 1)
INSERT INTO punto_movimiento
  (tipo_movimiento_codigo, puntos, descripcion, fecha_creacion, puntos_otorgados, usuario_id, habilitado, creado, creado_por)
VALUES (:tipo, :puntos, :desc, now(), :puntos, :u, true, now(), :u)
RETURNING *;
-- 2)
UPDATE usuario SET puntos_acumulados = puntos_acumulados + :puntos, modificado = now(), modificado_por = :u
WHERE usuario_id = :u;
```

**`nivel`:** lógica PHP sobre las constantes de nivel (no toca BD):
```php
// Novato 0–999, Inquilino Confiable 1000–2999, Nido Gold ≥3000.
// 'falta' = puntos hasta el siguiente threshold (0 si ya está en el tope).
```

**`racha` (v1 simple):**
```sql
SELECT DISTINCT date_trunc('month', fecha_creacion) AS mes
FROM punto_movimiento
WHERE usuario_id = :u AND tipo_movimiento_codigo = 'TMPT003' AND habilitado = true
ORDER BY mes DESC;
```
PHP cuenta meses consecutivos hacia atrás desde el mes actual.

**`canjear`:** valida `obtener($u) >= $canje`; si ok, `acreditar($u, self::TMPT_CANJE, -$canje, $descripcion)`.

---

## 7. Modelo `Verificacion`

```php
namespace App\Models;
use App\Core\Database; use PDO;

class Verificacion {
    public const TOKEN_TTL_HORAS = 24;
    public const DOMINIOS_INSTITUCIONALES = ['.edu', '.edu.pe', '.edu.mx', '.edu.co', '.gob.pe']; // ampliable

    // ¿El correo termina en un dominio institucional? → verificado automático
    public function esDominioInstitucional(string $correo): bool;

    // Genera token aleatorio (bin2hex(random_bytes(32))) + fecha_expiracion_token = now()+TTL
    public function generarToken(string $usuario_id): ?array;   // ['token','expiracion']

    // Valida token no expirado; marca usuario.verificado=true, limpia token. Devuelve bool.
    public function validarToken(string $token): bool;

    // Registra carnet/constancia en multimedia (tipo DOCUMENTO, usuario_id). v1: url simulada.
    public function subirDocumento(string $usuario_id, string $nombre, string $url_simulada): ?array;

    // Lista documentos del usuario (multimedia tipo DOCUMENTO)
    public function documentos(string $usuario_id): array;
}
```

### SQL de referencia

**`generarToken`:**
```sql
UPDATE usuario SET token = :token, fecha_expiracion_token = now() + interval '24 hours', modificado = now()
WHERE usuario_id = :u RETURNING token, fecha_expiracion_token;
```

**`validarToken`:**
```sql
UPDATE usuario
SET verificado = true, token = NULL, fecha_expiracion_token = NULL, modificado = now()
WHERE token = :token AND fecha_expiracion_token > now() AND habilitado = true
RETURNING usuario_id;
```

**`subirDocumento`:**
```sql
INSERT INTO multimedia (url, tipo_codigo, nombre, orden, usuario_id, habilitado, creado, creado_por)
VALUES (:url, 'DOCUMENTO', :nombre, 1, :u, true, now(), :u)
RETURNING multimedia_id, url, nombre, creado;
```
> El estado "pendiente de validación admin" es implícito: `usuario.verificado` sigue `false` hasta que un admin lo apruebe. No hay columna de estado del documento en v1 (el documento existe y `verificado` es el flag global). Documentado en §10.

---

## 8. Controller `PerfilController`

```php
class PerfilController extends Controller {
    public function __construct() {
        // Excepto verificarCuenta (token público), todos requieren sesión.
        $action = $_GET['action'] ?? null; // no disponible vía Router; se valida por método
        if (!isset($_SESSION['usuario_id'])) $this->redirect('/login');
    }

    public function dashboard() {
        $uid = $_SESSION['usuario_id'];
        $usuario = (new Usuario())->findById($uid);
        $puntosModel = new PuntosNido();
        $saldo = $puntosModel->obtener($uid);
        $nivel = $puntosModel->nivel($saldo);
        $racha = $puntosModel->racha($uid);
        $ultMov = $puntosModel->movimientos($uid, 1, 5);
        $noLeidos = (new Chat())->contarNoLeidos($uid);   // W6 ✅
        // Secciones stub: referidos (W8.2), blog (W8.1), reservas (W3), alojamiento actual (W3),
        //   favoritos (W7), reseñas (W7), documentos/contrato (W4), descuentos/beneficios (lectura).
        $descuentos = $this->listarDescuentos(); // SELECT * FROM descuento (solo lectura)
        $beneficios = $this->listarBeneficios(); // SELECT * FROM beneficio
        $this->render('perfil/dashboard', [...], 'main');
    }

    public function editar() {
        // GET: cargar usuario + catálogos (genero, ubicaciones, universidades)
        $this->render('perfil/editar', [...], 'main');
    }

    public function guardar() {
        // POST: validar + Usuario::actualizarPerfil($uid, $_POST). Redirect /perfil con flash.
    }

    public function verificarIdentidad() {
        // GET: estado de verificación (verificado bool, dominio institucional, documentos subidos)
        $this->render('perfil/verificar', [...], 'main');  // nota: vista perfil/verificar.php, layout main
    }

    public function subirCarnet() {
        // POST (multipart): v1 stub — tomar nombre del archivo, url simulada, Verificacion::subirDocumento.
        //   Si dominio institucional → marcar verificado=true automático.
    }

    public function verificarCuenta() {
        // GET /verificar?token=... — NO requiere sesión (override del constructor).
        //   Verificacion::validarToken → flash success/error → redirect /login.
    }

    public function puntos() {
        // GET: saldo, nivel, racha, movimientos paginados
        $this->render('perfil/puntos', [...], 'main');
    }

    public function canjear() {
        // POST puntos_a_canjear + descripcion. PuntosNido::canjear. Devuelve JSON o flash+redirect.
    }
}
```

> **Excepción de sesión para `verificarCuenta`:** el constructor redirige a `/login` si no hay sesión. Como el Router invoca el constructor antes que la acción, se añade una guarda: si `$_GET['token']` está presente Y la acción es `verificarCuenta`, se omite el redirect. Alternativa más limpia: mover `verificarCuenta` a `AuthController` (que ya maneja flujos sin sesión). **Decisión: poner `verificarCuenta` en `AuthController`** para evitar la excepción. Se actualiza el ruteo: `GET /verificar → AuthController::verificarCuenta`. El resto queda en `PerfilController`.

**Ruteo ajustado:**
```php
$router->get('/verificar', 'AuthController', 'verificarCuenta');
```

---

## 9. Vista

### 9.1 `perfil/dashboard.php` (layout `main`) — dashboard real
Reemplaza al mockup. Estructura con tokens `pd-*` (eyebrow, cards, chips, verified badge):

- **Header:** `pd-eyebrow` "Dashboard", h1 "Bienvenida, {nombres}", sub. Badge `pd-verified` si `verificado`.
- **Grid de secciones (pd-card cada una):**
  1. **Perfil y verificación** → link `/perfil/verificar` + estado (verificado / pendiente).
  2. **Mis puntos Nido** → saldo, nivel (chip), racha, últimos 5 movimientos. Link `/puntos`.
  3. **Mis referidos** → W8.2 stub "Próximamente" (si W8.2 no aterrizó).
  4. **Mensajes** → W6 ✅: link `/mensajes` + badge `contarNoLeidos`.
  5. **Blog / Guía** → W8.1 stub (o últimos posts si W8.1 aterrizó).
  6. **Descuentos y beneficios** → lectura `descuento`/`beneficio` (listado simple).
  7. **Mis reservas** → W3 stub.
  8. **Mi alojamiento actual** → W3 stub.
  9. **Favoritos** → W7 stub.
  10. **Mis reseñas** → W7 stub.
  11. **Documentos / Contrato** → W4 stub.
- Cada stub: `pd-card` con icono, título, texto "Próximamente" y un botón `pd-btn-ghost` deshabilitado.

### 9.2 `perfil/editar.php` (layout `main`)
Formulario (POST `/perfil`) con los campos de `Usuario::actualizarPerfil` + los nuevos (carrera, año de ingreso, país de origen, género, descripción, url_foto, celular, teléfono, universidad, ubicación). Botones `pd-btn-primary`. Usa `htmlspecialchars` para pintar valores.

### 9.3 `perfil/verificar.php` (layout `main`)
- Sección "Verificación de cuenta": estado (verificado / pendiente), si pendiente mostrar link/instrucción (en dev, flash con el link `/verificar?token=...`).
- Sección "Verificación de identidad estudiantil": si dominio institucional → mensaje "verificado automáticamente". Si no → form subida de carnet/constancia (multipart, stub). Lista de documentos subidos (`multimedia` DOCUMENTO) con estado "pendiente de validación admin".

### 9.4 `perfil/puntos.php` (layout `main`)
- Header con saldo grande, chip de nivel, racha (meses).
- Barra de progreso al siguiente nivel (pd tokens).
- Historial de movimientos (tabla: fecha, tipo, descripción, puntos +/-).
- Form de canje (POST `/puntos/canjear`): input puntos a canjear + descripción → valida saldo.

### 9.5 Modificación `register.php`
- **Paso 1:** añadir campo `password_confirm` + medidor de fortaleza JS (reglas: ≥8 chars, mayús, minús, número) + checkbox `terminos` (obligatorio).
- **Paso 3:** añadir `carrera` (texto), `anio_ingreso` (number, año entre 2000 y actual), `pais_origen` (texto/select), `genero_codigo` (select catálogo GENERO).

---

## 10. Riesgos / notas técnicas

- **Email verify v1 = stub:** no hay transport SMTP configurado. Se genera el token y el link `/verificar?token=...`; en dev el link se muestra en el flash o se loguea. En prod se necesitará integrar un mailer (PHPMailer/Supabase Edge Function/SendGrid) — fuera de W5 v1. Documentado.
- **Upload de carnet = stub:** no hay storage (S3/Supabase Storage) configurado. v1 acepta el archivo en el form, guarda un registro `multimedia` con una URL simulada (ej. `storage://carnet/{usuario_id}/{filename}`) y deja el archivo sin persistir realmente. El pipeline real de storage + validación admin con UI es post-v1. Documentado.
- **Validación admin de identidad:** no hay UI de admin en este portal (es el portal inquilino). La subida queda en estado "pendiente" implícito (`verificado=false`); un admin la aprobaría vía el panel admin (otra app). El badge solo aparece cuando `verificado=true` (por dominio .edu o por aprobación admin externa).
- **`ALTER TABLE` de columnas nuevas:** ejecutar en Supabase SQL Editor previa confirmación del usuario. Si el usuario prefiere no alterar el schema, los campos carrera/año/pais quedan solo en captura de registro sin persistir (no recomendado). Documentado en §5.1.
- **`pais_origen` como texto v1:** no normalizado a `ubicacion`. Si se normaliza después, migrar el texto a `pais_origen_id`. Documentado.
- **Cargar `app-design.css` en `main.php`:** mezcla Bootstrap 5.3 + pd-* en el contenido del dashboard. El app-shell nav usa clases propias (`app-nav`, `nav-link`) que no colisionan con pd. Se migra incrementalmente. Trade-off aceptado (D8).
- **`puntos_acumulados` puede ser NULL:** la columna es `integer` nullable. `PuntosNido::obtener` debe coercionar a `(int)` (null → 0). El bono de bienvenida (TMPT007) tras registro inicializa el saldo.
- **`punto_movimiento.puntos` vs `puntos_otorgados`:** ambos existen; `puntos` es el monto aplicado al saldo (puede ser negativo en canje), `puntos_otorgados` el bruto. `acreditar` setea ambos al mismo valor absoluto salvo signo. Documentado.
- **Sin cron:** el bono de bienvenida se acredita on-request al finalizar el registro (no hay job programado). La racha se calcula on-request.
- **Sin motor de plantillas:** escapar todo con `htmlspecialchars`. `mbstring` puede no estar cargado → usar `function_exists('mb_*')` (igual que `pd_initial`).
- **mbstring:** para medir longitud de password/descripción, usar `function_exists('mb_strlen') ? mb_strlen($s) : strlen($s)`.
- **OAuth (5.2) defer:** no implementar; dejar placeholder en §14 como pendiente.
- **Sub-agentes:** solo glm 5.2.

---

## 11. Tareas finas (checklist)

- [ ] **11.1** BD: ejecutar `ALTER TABLE usuario ADD COLUMN carrera / anio_ingreso / pais_origen` (§5.1) — previa confirmación del usuario.
- [ ] **11.2** Modelo `PuntosNido` (constantes + obtener/movimientos/acreditar/nivel/racha/canjear) (§6). **Contrato congelado.**
- [ ] **11.3** Modelo `Verificacion` (esDominioInstitucional/generarToken/validarToken/subirDocumento/documentos) (§7).
- [ ] **11.4** Extender `Usuario`: `create()` con columnas nuevas + token + fecha_ingreso + terminos; `actualizarPerfil()` con campos nuevos; `findByToken`/`marcarVerificado`/`setToken` (§3).
- [ ] **11.5** `AuthController::register()`: capturar campos nuevos, validar confirmación + fortaleza + T&C, generar token, acreditar bono bienvenida (TMPT007). `AuthController::verificarCuenta()` (GET /verificar?token).
- [ ] **11.6** Vista `register.php`: paso 1 (confirmar pw + medidor fortaleza + checkbox T&C), paso 3 (carrera, año ingreso, país origen, género) (§9.5).
- [ ] **11.7** Controller `PerfilController` (dashboard, editar, guardar, verificarIdentidad, subirCarnet, puntos, canjear) (§8).
- [ ] **11.8** Rutas en `index.php` (mover `/dashboard` a PerfilController + nuevas) (§4).
- [ ] **11.9** Cargar `app-design.css` en `layouts/main.php` (§3).
- [ ] **11.10** Vista `perfil/dashboard.php` — dashboard real con 11 secciones (W5.4/W5.8/W6 reales, resto stubs) (§9.1).
- [ ] **11.11** Vista `perfil/editar.php` (§9.2).
- [ ] **11.12** Vista `perfil/verificar.php` + `subirCarnet` (§9.3).
- [ ] **11.13** Vista `perfil/puntos.php` + `canjear` (§9.4).
- [ ] **11.14** Deprecar/eliminar mockup `inquilino/dashboard.php` (§3).
- [ ] **11.15** Pruebas manuales (§12).

---

## 12. Criterios de aceptación / pruebas manuales

1. **Registro completo:** llenar los 3 pasos (con confirmar contraseña, T&C, carrera, año, país, género) → crea usuario con `terminos=true`, `token` seteado, `fecha_ingreso=now()`, `puntos_acumulados` = bono de bienvenida (TMPT007).
2. **Fortaleza de password:** el medidor JS bloquea el avance si la password es débil; las contraseñas que no coincimen muestran error.
3. **Verificación de cuenta:** abrir `/verificar?token={token}` → marca `verificado=true`, limpia token, flash success, redirect a `/login`. Token expirado → error.
4. **Dominio institucional:** registrar con correo `@uni.edu.pe` → `verificado=true` automático.
5. **Subida de carnet:** desde `/perfil/verificar`, subir un documento → queda en `multimedia` (DOCUMENTO) con estado pendiente; `verificado` sigue false (si no era dominio .edu).
6. **Dashboard real:** `/dashboard` muestra las 11 secciones; "Mis puntos" con saldo/nivel/racha; "Mensajes" con badge de no leídos (W6); los stubs de W3/W4/W7/W8 muestran "Próximamente".
7. **Edición de perfil:** `/perfil` → cambiar nombres/carrera/descripción → guarda y refleja en el dashboard.
8. **Mis puntos:** `/puntos` muestra saldo, nivel, racha, historial de movimientos (incluye el bono de bienvenida).
9. **Canje:** canjear puntos ≤ saldo → descuenta saldo (movimiento TMPT005 negativo); canje > saldo → rechazado.
10. **Niveles:** con saldo < 1000 → "Novato"; 1000–2999 → "Inquilino Confiable"; ≥3000 → "Nido Gold".
11. **Badge verificado:** el dashboard y el avatar muestran `pd-verified` solo si `verificado=true`.
12. **Sin sesión:** cualquier endpoint privado → redirect a `/login`.
13. **Sin errores PHP** y sin warnings de columnas inexistentes (tras el ALTER).

---

## 13. Orden de ejecución sugerido

> **Sync point con W8:** el contrato `PuntosNido` (§6) se congela **antes** de empezar 11.2, para que W8.2 (referidos) pueda codear contra él en paralelo.

**Track A (W5):**
1. **11.1** BD ALTER (confirmar usuario) — desbloquea captura de campos nuevos.
2. **11.4** Extender `Usuario` (create/actualizarPerfil + token/verificado).
3. **11.5** `AuthController::register` + `verificarCuenta` (5.1 + 5.3).
4. **11.6** Vista `register.php` (5.1 UI).
5. **11.3** Modelo `Verificacion` (5.3 + 5.4 backend).
6. **11.2** Modelo `PuntosNido` (5.8 — contrato congelado).
7. **11.7** Controller `PerfilController` (5.5 + 5.6 + 5.4 + 5.8).
8. **11.8** Rutas + **11.9** `app-design.css` en `main.php`.
9. **11.10–11.13** Vistas dashboard/editar/verificar/puntos.
10. **11.14** Deprecar mockup.
11. **11.15** Pruebas.

**Paralelizables dentro de Track A (tras 11.2):** 5.3 (verificación email) ‖ 5.6 (edición perfil) ‖ 5.4 (verificación identidad) ‖ 5.8 (gamificación UI). 5.5 (dashboard) va al final porque orquesta 5.4 + 5.8 + W6.

**Defer / descartado:** 5.2 (OAuth) → pendiente v2. 5.7 (preferencias notificación) → descartado.

**Mapa de paralelización (referencia):**
```
Track A (W5): 5.1 → 5.4 → 5.8 → 5.5   (5.3 ‖ 5.6 ‖ ; 5.2 defer ; 5.7 descartado)
Sync W8: contrato PuntosNido (§6) se congela antes de 11.2 → W8.2 codea contra él en paralelo.
```

---

## 14. Estado global

| Tarea | Estado |
|---|---|
| 11.1 BD ALTER (carrera/anio_ingreso/pais_origen) | ⬜ (pendiente confirmación usuario) |
| 11.2 Modelo PuntosNido (contrato W5↔W8) | ⬜ |
| 11.3 Modelo Verificacion | ⬜ |
| 11.4 Extender Usuario (create/actualizarPerfil/token/verificado) | ⬜ |
| 11.5 AuthController register + verificarCuenta | ⬜ |
| 11.6 Vista register (confirmar pw + fortaleza + T&C + campos nuevos) | ⬜ |
| 11.7 PerfilController (dashboard/editar/guardar/verificarIdentidad/subirCarnet/puntos/canjear) | ⬜ |
| 11.8 Rutas + mover /dashboard | ⬜ |
| 11.9 app-design.css en main.php | ⬜ |
| 11.10 Vista perfil/dashboard (11 secciones) | ⬜ |
| 11.11 Vista perfil/editar | ⬜ |
| 11.12 Vista perfil/verificar + subirCarnet | ⬜ |
| 11.13 Vista perfil/puntos + canjear | ⬜ |
| 11.14 Deprecar mockup inquilino/dashboard | ⬜ |
| 11.15 Pruebas manuales | ⬜ |
| 5.2 OAuth Google/Facebook | ⬜ Defer v1 |
| 5.7 Preferencias de notificación | ❌ Descartado |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done · ❌ descartado
