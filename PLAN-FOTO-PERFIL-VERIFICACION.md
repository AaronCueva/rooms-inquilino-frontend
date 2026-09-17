# PLAN — Foto de perfil + Verificación estudiantil (rooms-inquilino-frontend)

> Objetivo: llevar a `rooms-inquilino-frontend` la subida de **foto de perfil a Azure Blob** (igual que `rooms-propietario-frontend`), garantizar que el **botón "Verificación" de Mi Perfil** redirija a la interfaz de verificación, y que esa interfaz **permita ver el documento** y guarde su URL en la columna `usuario.url_verificacion_estudiante`, reflejando el **cambio visual de estado verificado / pendiente / en revisión**.

Fecha: 2026-07-12 · Proyecto: `rooms-inquilino-frontend`

---

## 0. Hallazgos previos (estado actual)

| Aspecto | Estado | Archivo |
|---|---|---|
| `app/core/AzureStorage.php` | **NO existe** en este proyecto | `app/core/` sólo tiene `Controller.php`, `Database.php`, `Router.php` |
| `.env` con credenciales Azure | ✅ Existe (ya agregado por el usuario) | `rooms-inquilino-frontend/.env` |
| Foto de perfil en Mi Perfil | Campo de **texto** `url_foto` (no file upload) | `app/views/inquilino/perfil/index.php:118-121` |
| Botón "Verificación" en Mi Perfil | Existe pero apunta a `/perfil/verificacion` (ruta inválida) | `app/views/inquilino/perfil/index.php:128` |
| Ruta registrada de verificación | `/perfil/verificar` (no `/perfil/verificacion`) | `index.php:68-69` |
| Action del form de subida | `/perfil/verificacion/subir` (ruta inválida) | `app/views/inquilino/perfil/verificacion.php:86` |
| Subida de documento | **Local** (`move_uploaded_file` a `public/uploads/verificacion/`) | `app/controllers/PerfilController.php:122-137` |
| Persistencia del documento | Tabla `multimedia` (tipo `DOCUMENTO`) | `app/models/VerificacionEstudiantil.php:108-121` |
| Columna `url_verificacion_estudiante` | Existe en BD pero **no se usa** | confirmado vía `information_schema` |
| Dropdown del header | Link "Verificación" → `/perfil/verificar` ✅ (correcto) | `app/views/layouts/main.php:92` |

**Patrón de referencia (a replicar):** `rooms-propietario-frontend/app/controllers/PerfilController.php:42-81` (subida Azure con validación de tamaño/extensión, fallback local) y `rooms-propietario-frontend/app/core/AzureStorage.php`.

---

## 1. Tareas

### T1 — Crear `app/core/AzureStorage.php`
Copiar desde `rooms-propietario-frontend/app/core/AzureStorage.php` (es idéntico en namespace `App\Core` y lógica). Ya lee `.env` local y el `.env` compartido de `WS-ROOMS/`.

- Origen: `rooms-propietario-frontend/app/core/AzureStorage.php`
- Destino: `rooms-inquilino-frontend/app/core/AzureStorage.php`
- Métodos: `uploadFile($localFilePath, $fileName, $contentType)` y `uploadFileLocal($localFilePath, $fileName)`.
- Sin cambios de código (el `dirname(__DIR__, 2)` resuelve bien a la raíz del proyecto inquilino).

### T2 — Modificar `app/controllers/PerfilController.php`

**T2.1 `guardar()` — agregar subida de foto de perfil a Azure.**
Antes de `$modelo = new VerificacionEstudiantil();`, procesar `$_FILES['foto_perfil']` (mismo bloque que el propietario, líneas 42-81):

```php
// Subida de foto de perfil — Azure Blob Storage
if (!empty($_FILES['foto_perfil']['name'])) {
    $maxSize = 10 * 1024 * 1024; // 10MB
    $ext = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif'];

    if ($_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
        $this->setFlash('error', 'Error en la subida de la imagen. Código PHP: ' . $_FILES['foto_perfil']['error']);
        $this->redirect('/perfil');
    } elseif ($_FILES['foto_perfil']['size'] > $maxSize) {
        $this->setFlash('error', 'La foto supera el tamaño máximo de 10 MB.');
        $this->redirect('/perfil');
    } elseif (!in_array($ext, $allowed)) {
        $this->setFlash('error', 'Formato no válido. Solo JPG, PNG, WEBP o GIF.');
        $this->redirect('/perfil');
    } else {
        $newName = 'usuarios/perfil_' . $uid . '_' . time() . '.' . $ext;
        $mimeType = function_exists('mime_content_type')
            ? mime_content_type($_FILES['foto_perfil']['tmp_name']) : 'image/jpeg';
        if (!$mimeType) $mimeType = 'image/jpeg';

        $azureUrl = \App\Core\AzureStorage::uploadFile($_FILES['foto_perfil']['tmp_name'], $newName, $mimeType);
        if (!$azureUrl) {
            $azureUrl = \App\Core\AzureStorage::uploadFileLocal($_FILES['foto_perfil']['tmp_name'], $newName);
        }
        if ($azureUrl) {
            $campos['url_foto'] = $azureUrl;   // inyecta en el whitelist de guardarPerfil
            $_SESSION['url_foto'] = $azureUrl; // refresca avatar del header
        } else {
            $this->setFlash('error', 'No se pudo guardar la imagen (ni en Azure ni local).');
            $this->redirect('/perfil');
        }
    }
}
```

Nota: `$campos` ya se obtiene de `$this->leerCamposPerfil($_POST)`; `url_foto` está en `VerificacionEstudiantil::CAMPOS_PERFIL`, así que inyectarlo ahí es seguro.

**T2.2 `subirDocumento()` — migrar a Azure + guardar en `url_verificacion_estudiante`.**
Reemplazar el bloque local `move_uploaded_file` (líneas 122-137) por subida Azure (con fallback local):

```php
if (!empty($_FILES['documento']['name']) && ($_FILES['documento']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['documento']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif','pdf'];
    if (!in_array($ext, $allowed)) {
        $this->setFlash('error', 'Formato no válido. JPG, PNG, WEBP, GIF o PDF.');
        $this->redirect('/perfil/verificar');
    }
    $basename = pathinfo($_FILES['documento']['name'], PATHINFO_FILENAME);
    $nombre = $basename . '.' . $ext;
    $newName = 'verificacion/doc_' . $uid . '_' . time() . '.' . $ext;
    $mimeType = function_exists('mime_content_type')
        ? mime_content_type($_FILES['documento']['tmp_name']) : 'application/octet-stream';

    $url = \App\Core\AzureStorage::uploadFile($_FILES['documento']['tmp_name'], $newName, $mimeType);
    if (!$url) {
        $url = \App\Core\AzureStorage::uploadFileLocal($_FILES['documento']['tmp_name'], $newName);
    }
}
```

Tras `$id = $verifModel->subirDocumento(...)`, persistir también en `usuario.url_verificacion_estudiante`:

```php
if ($id) {
    $verifModel->guardarUrlVerificacion($uid, $url); // nuevo método (T3)
    $this->setFlash('success', 'Documento subido. Queda pendiente de revisión por el equipo Nido.');
}
```

> **Estado de `verificado`:** NO se setea `true` aquí. La aprobación la hace el admin desde `rooms-frontend`. El "cambio de estado" se refleja visualmente vía `url_verificacion_estudiante` (tiene doc → "En revisión") y `verificado` (true → "Verificado"). Si el correo es institucional, se puede mantener el comportamiento de auto-aprobar (opcional, dejar fuera del scope por ahora).

### T3 — Modificar `app/models/VerificacionEstudiantil.php`

Agregar método:

```php
/**
 * Guarda la URL del documento de verificación en usuario.url_verificacion_estudiante.
 */
public function guardarUrlVerificacion(string $usuario_id, string $url): bool
{
    $sql = "UPDATE usuario SET url_verificacion_estudiante = :url, modificado = now()
            WHERE usuario_id = :u";
    $stmt = $this->db->prepare($sql);
    $stmt->bindValue(':url', $url);
    $stmt->bindValue(':u', $usuario_id);
    return $stmt->execute();
}

/**
 * Estado de verificación para la vista: 'verificado' | 'en_revision' | 'pendiente'.
 */
public function estadoVerificacion(array $usuario): string
{
    if (!empty($usuario['verificado'])) return 'verificado';
    if (!empty($usuario['url_verificacion_estudiante'])) return 'en_revision';
    return 'pendiente';
}
```

### T4 — Modificar `app/views/inquilino/perfil/index.php`

**T4.1** Reemplazar el campo de texto "URL de foto" (líneas 118-121) por el componente avatar + input file con preview (estilo `pd-*`, adaptado del propietario). El `<form>` de la línea 55 debe agregar `enctype="multipart/form-data"`.

```php
<div class="pf-field">
    <label>Foto de perfil</label>
    <div style="display:flex; align-items:center; gap:16px;">
        <?php if (!empty($pf_u['url_foto'])): ?>
            <img id="preview-foto" src="<?= htmlspecialchars($pf_u['url_foto']); ?>"
                 alt="Foto de perfil"
                 style="width:72px;height:72px;border-radius:999px;object-fit:cover;border:1px solid var(--pd-line);">
        <?php else: ?>
            <div id="preview-foto-placeholder"
                 style="width:72px;height:72px;border-radius:999px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--pd-primary),var(--pd-ink));color:#fff;font-weight:700;">
                <?= htmlspecialchars($pf_iniciales); ?>
            </div>
            <img id="preview-foto" src="" class="d-none"
                 style="width:72px;height:72px;border-radius:999px;object-fit:cover;border:1px solid var(--pd-line);">
        <?php endif; ?>
        <label class="pd-btn pd-btn-ghost" for="foto_perfil" style="cursor:pointer;">
            <i class="fas fa-camera"></i> Cambiar foto
        </label>
        <input type="file" id="foto_perfil" name="foto_perfil" class="d-none"
               accept="image/png,image/jpeg,image/webp">
    </div>
</div>
<script>
document.getElementById('foto_perfil')?.addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
        var r = new FileReader();
        r.onload = function(ev) {
            var img = document.getElementById('preview-foto');
            var ph  = document.getElementById('preview-foto-placeholder');
            img.src = ev.target.result;
            img.classList.remove('d-none');
            if (ph) ph.classList.add('d-none');
        };
        r.readAsDataURL(e.target.files[0]);
    }
});
</script>
```

**T4.2** Corregir el botón "Verificación" (línea 128): cambiar `href="/perfil/verificacion"` → `href="/perfil/verificar"`. Asegurar que sea visible (ya está en `.pf-actions` junto a "Guardar cambios").

### T5 — Modificar `app/views/inquilino/perfil/verificacion.php`

**T5.1** Corregir action del form (línea 86): `/perfil/verificacion/subir` → `/perfil/verificar/subir`.

**T5.2** Cambio visual de estado (líneas 52-56). Reemplazar el binario verificado/pendiente por tres estados usando `VerificacionEstudiantil::estadoVerificacion()` (inyectado desde el controller en T2.2 o computado en la vista):

```php
<?php
$pf_estado = 'pendiente';
if (!empty($pf_u['verificado'])) $pf_estado = 'verificado';
elseif (!empty($pf_u['url_verificacion_estudiante'])) $pf_estado = 'en_revision';
?>
<?php if ($pf_estado === 'verificado'): ?>
    <span class="pf-status pf-status-ok"><i class="fas fa-check-circle"></i> Estudiante verificado</span>
<?php elseif ($pf_estado === 'en_revision'): ?>
    <span class="pf-status pf-status-wait"><i class="fas fa-hourglass-half"></i> Documento en revisión</span>
<?php else: ?>
    <span class="pf-status pf-status-wait"><i class="fas fa-clock"></i> Verificación pendiente</span>
<?php endif; ?>
```

(Agregar `.pf-status-wait` ya existe; opcionalmente un tono `--pd-accent` para "en revisión".)

**T5.3** El botón "Ver" de cada documento (línea 121) ya abre el archivo en una pestaña nueva. Confirmar que la URL devuelta por Azure es pública (lo es: el contenedor tiene SAS de lectura). Sin cambios funcionales; sólo verificar que tras T2.2 la URL guardada en `multimedia.url` sea la de Azure.

### T6 — (Opcional) Refrescar avatar del header tras guardar foto
T2.1 ya setea `$_SESSION['url_foto']`, y `app/views/layouts/main.php:29` lee de sesión. No se requiere cambio extra.

---

## 2. Orden de ejecución

1. T1 (crear `AzureStorage.php`)
2. T3 (métodos del modelo)
3. T2 (controller)
4. T4 + T5 (vistas)
5. Verificación end-to-end (sección 4)

## 3. Archivos tocados (resumen)

- `app/core/AzureStorage.php` **(nuevo)**
- `app/controllers/PerfilController.php` (modificar `guardar`, `subirDocumento`)
- `app/models/VerificacionEstudiantil.php` (agregar `guardarUrlVerificacion`, `estadoVerificacion`)
- `app/views/inquilino/perfil/index.php` (foto file upload + fix link verificación + enctype)
- `app/views/inquilino/perfil/verificacion.php` (fix action form + 3 estados visuales)

## 4. Verificación

- [ ] Login como inquilino → Mi Perfil → "Cambiar foto" → sube JPG/PNG → guardar → avatar del header y de la vista se actualiza; en BD `usuario.url_foto` apunta a `https://veladerostorage.blob.core.windows.net/...`.
- [ ] Mi Perfil → botón "Verificación" → llega a `/perfil/verificar` (200, no 404).
- [ ] Subir un carnet (PDF/PNG) → mensaje "pendiente de revisión"; en BD `multimedia` con URL Azure **y** `usuario.url_verificacion_estudiante` con la misma URL.
- [ ] Estado visual: sin doc → "Verificación pendiente"; con doc → "Documento en revisión"; si admin marca `verificado=true` → "Estudiante verificado".
- [ ] Validaciones: archivo >10MB → error; extensión no permitida → error; Azure cae → fallback local funciona.
