# Plan de Implementación — W8: Blog / Guía del universitario + Programa de referidos

> **Oleada:** W8 · **Ref. doc:** DF-NidoUniversitario §3.7.2 (blog) + §3.7.3 (referidos) · **Depende de:** W2 (✅), W5.8 PuntosNido (contrato congelado — lo consumes, no lo redefines) · **Desbloquea:** secciones "Blog" y "Mis referidos" del dashboard (W5.5)
> **Estado:** ⬜ Plan redactado · **Creado:** 2026-07-09
> Registro de seguimiento.

---

## 1. Objetivo

Dos funcionalidades independientes que aterrizan en paralelo (Track B):

- **8.1 Blog / guía del universitario (§3.7.2):** listado público paginado + detalle de posts. Solo lectura para inquilinos (el contenido lo crea admin en otro portal). Layout `public` (mismo chrome que home/buscar).
- **8.2 Programa de referidos (§3.7.3):** el inquilino comparte su código de referido; cuando un referido se registra con ese código, se acredita al instante y el referidor gana puntos. Vista "Mis referidos" (app-shell, layout `main`). **Sin notificaciones** — el usuario ve el estado al abrir la sección.

**Criterio de aceptación W8:**
> Blog público navegable (listado + detalle) con posts de muestra, y programa de referidos donde el inquilino copia/comparte su código, un nuevo usuario se registra con ese código, y el referidor ve al referido acreditado con sus puntos ganados.

---

## 2. Decisiones de alcance (tomadas con el usuario)

| # | Decisión | Opción elegida |
|---|---|---|
| D1 | Categorías del blog | **Sin tabla de categorías v1.** Listado plano ordenado por `fecha_publicacion desc`. Categoría se deriva de tags en título o se omite. Hardening futuro: campo simple o tabla `blog_categoria`. |
| D2 | Ruta de detalle | **`GET /blog/ver?id={id}`** (query param, estilo foros) en lugar de path param `/blog/{id}`. Simplifica el Router y es consistente con `/foros/ver`. |
| D3 | Autoría / edición de posts | **Fuera de W8.** El contenido lo crea admin en otro portal. W8 solo lee (`habilitado=true` AND `estado_codigo='ESBL002'`). |
| D4 | Puntos por referido | **200 puntos** (constante `PUNTOS_REFERIDO = 200`, configurable). Tipo `TMPT001` (GANANCIA POR REFERIDO). |
| D5 | Momento de acreditación | **Al registrarse** el referido con el código (no espera verificación de email). Trade-off documentado en §10. |
| D6 | Notificaciones | **Sin notificaciones.** Los referidos acreditan silenciosamente; el usuario ve el estado al abrir "Mis referidos". |
| D7 | Código shareable | Derivar determinista: tomar `referido.codigo` de la fila más reciente del referidor, o generar `NIDO-{INICIALES}{AÑO}` si no tiene. El código vive en cada fila `referido`. |

**Fuera de W8 v1:** categorías filtrables, editor de blog, verificación de email antes de acreditar, canje de puntos (W5.8), leaderboard de referidos.

---

## 3. Arquitectura — archivos a crear / modificar

### Crear
| Archivo | Rol |
|---|---|
| `app/models/Blog.php` | Modelo lectura: posts publicados, paginación, detalle, recientes para widget. |
| `app/models/Referido.php` | Modelo: código shareable, mis referidos, conteo, registro, acreditación. Consume `PuntosNido` (W5.8). |
| `app/controllers/BlogController.php` | index (listado, layout public) + ver (detalle, layout public). Sin sesión requerida. |
| `app/controllers/ReferidoController.php` | index (mis referidos, layout main, sesión) + generarCodigo (POST JSON, regenera/share). |
| `app/views/blog/index.php` | Listado de cards `pd-card` con eyebrow "Guía del universitario" + paginación. Layout `public`. |
| `app/views/blog/ver.php` | Detalle: titulo, contenido (nl2br + htmlspecialchars), fecha, autor, link volver. Layout `public`. |
| `app/views/inquilino/referidos/index.php` | Código shareable + copiar/share WhatsApp + lista de referidos + total + puntos ganados. Layout `main`. |

### Modificar
| Archivo | Cambio |
|---|---|
| `index.php` | Registrar rutas de blog y referidos (§4). |
| `app/controllers/AuthController.php` | En `register()`: si llega `$_POST['codigo_referido']` (o query `?ref=CODIGO` persistido en sesión), llamar `Referido::aplicarCodigoEnRegistro($nuevo_uid, $codigo)` tras crear el usuario. **Punto de sync con W5.1** (campos de registro) — coordinar para no pisar cambios. |
| `app/views/auth/register.php` | (Opcional) campo oculto `codigo_referido` rellenado desde `$_GET['ref']` o `$_SESSION['ref_pending']`. |
| `app/views/layouts/public.php` | (Opcional) link "Blog" en el nav público. |

### BD (Supabase) — NO crear tablas (ya existen)
- `blog`: 6 posts de muestra ya existen. Catálogo `ESTADO_BLOG` (3 estados — inspeccionar; sample usa `ESBL002`=publicado).
- `referido`: datos de muestra existentes (códigos como "NIDO-JESUS2026"). Catálogo `ESTADO_REFERIDO`: ESREF01=PENDIENTE, ESREF02=ACREDITADO, ESREF03=CANCELADO.
- `punto_movimiento` + catálogo `TIPO_MOVIMIENTO_PUNTO` (TMPT001=GANANCIA POR REFERIDO).
- `usuario.puntos_acumulados` (int default 0).

---

## 4. Ruteo

```
GET  /blog                → BlogController::index            (listado, layout public, sin sesión)
GET  /blog/ver?id={id}    → BlogController::ver              (detalle, layout public, sin sesión)
GET  /referidos           → ReferidoController::index        (mis referidos, layout main, requiere sesión)
POST /referidos/codigo    → ReferidoController::generarCodigo (regenera/share, JSON, requiere sesión)
```

> Nota: `/blog/ver?id=` usa query param (estilo foros) para simplicidad del Router. `/blog/{id}` sería posible con el Router mejorado (W2) pero no se usa aquí.

En `index.php`:
```php
// Blog / Guía del universitario (W8.1)
$router->get('/blog', 'BlogController', 'index');
$router->get('/blog/ver', 'BlogController', 'ver');

// Programa de referidos (W8.2)
$router->get('/referidos', 'ReferidoController', 'index');
$router->post('/referidos/codigo', 'ReferidoController', 'generarCodigo');
```

- `/blog*`: sin sesión (público).
- `/referidos*`: requieren sesión (constructor redirect a `/login`).

---

## 5. BD — tablas existentes + catálogos

### 5.1 Tabla `blog` (ya existe, NO crear)
Columnas: `blog_id(uuid)`, `titulo`, `contenido(text)`, `fecha_publicacion`, `estado_codigo`, `usuario_id`, `habilitado`, `creado`, `creado_por`, `modificado`, `modificado_por`.

Catálogo `ESTADO_BLOG` (3 estados — inspeccionar al implementar con `Catalogo::obtenerPorReferencia('ESTADO_BLOG')`). El sample usa **`ESBL002` = publicado**. Definir constante `EST_PUBLICADO='ESBL002'` en el modelo; si la inspección revela otro código, ajustar.

### 5.2 Tabla `referido` (ya existe, NO crear)
Columnas: `referido_id(uuid)`, `codigo(varchar)`, `estado_codigo`, `fecha_creacion`, `usuario_referidor_id(uuid→usuario)`, `usuario_referido_id(uuid→usuario)`, `habilitado`, `creado`, `creado_por`, `modificado`, `modificado_por`.

Catálogo `ESTADO_REFERIDO`:
- `ESREF01` = PENDIENTE
- `ESREF02` = ACREDITADO
- `ESREF03` = CANCELADO

### 5.3 Tabla `punto_movimiento` (ya existe, NO crear)
Columnas: `punto_movimiento_id`, `tipo_movimiento_codigo`, `puntos(int)`, `descripcion`, `fecha_creacion`, `usuario_id`, `habilitado`, ...

Catálogo `TIPO_MOVIMIENTO_PUNTO`: **`TMPT001` = GANANCIA POR REFERIDO** (entre otros).

### 5.4 `usuario.puntos_acumulados` (int default 0)
Actualizado por `PuntosNido::acreditar` (W5.8).

### 5.5 Verificación previa a implementar
- Inspeccionar `ESTADO_BLOG` para confirmar `ESBL002` = publicado.
- Confirmar que los 6 posts de muestra tienen `habilitado=true` AND `estado_codigo='ESBL002'`.
- Confirmar catálogo `ESTADO_REFERIDO` y `TIPO_MOVIMIENTO_PUNTO` con los códigos anteriores.

---

## 6. Modelos (con SQL)

### 6.1 `App\Models\Blog` (lo defines tú en W8.1)

Constantes:
```php
public const EST_PUBLICADO = 'ESBL002'; // confirmar al implementar
```

Métodos:

- **`getPublicados(int $pagina=1, int $porPagina=9): array`** — `blog` where `habilitado=true` AND `estado_codigo='ESBL002'` order by `fecha_publicacion desc` LIMIT/OFFSET. Incluye `usuario_id` join `usuario` para autor (`nombres`, `apellido_paterno`).
  ```sql
  SELECT b.blog_id, b.titulo, b.contenido, b.fecha_publicacion,
         u.nombres AS autor_nombres, u.apellido_paterno AS autor_apellido
  FROM blog b
  LEFT JOIN usuario u ON b.usuario_id = u.usuario_id
  WHERE b.habilitado = true AND b.estado_codigo = 'ESBL002'
  ORDER BY b.fecha_publicacion DESC
  LIMIT :limit OFFSET :offset
  ```

- **`contarPublicados(): int`** — count de filas con `habilitado=true` AND `estado_codigo='ESBL002'`.

- **`findById(string $id): ?array`** — un post; valida `habilitado=true` AND `estado_codigo='ESBL002'`. Devuelve null si no existe/no está publicado. Incluye datos del autor.
  ```sql
  SELECT b.*, u.nombres AS autor_nombres, u.apellido_paterno AS autor_apellido
  FROM blog b
  LEFT JOIN usuario u ON b.usuario_id = u.usuario_id
  WHERE b.blog_id = :id AND b.habilitado = true AND b.estado_codigo = 'ESBL002'
  LIMIT 1
  ```

- **`getRecientes(int $n=3): array`** — para widget dashboard (W5.5). Mismo WHERE que `getPublicados`, sin paginar, `LIMIT :n`. Devuelve `blog_id`, `titulo`, `fecha_publicacion`.

### 6.2 `App\Models\Referido` (lo defines tú en W8.2)

Constantes:
```php
public const EST_PENDIENTE   = 'ESREF01';
public const EST_ACREDITADO  = 'ESREF02';
public const EST_CANCELADO   = 'ESREF03';
public const PUNTOS_REFERIDO = 200; // valor v1, configurable
```

Métodos:

- **`obtenerMiCodigo(string $usuario_id): string`** — código shareable. Derivar determinista: tomar `referido.codigo` de la fila más reciente donde `usuario_referidor_id=:uid` (order by `fecha_creacion desc` limit 1). Si no tiene ninguna fila, generar `NIDO-{INICIALES}{AÑO}` (iniciales de `nombres` + `apellido_paterno`, año actual). El código vive en cada fila `referido` que se crea al compartir/registrar.
  ```sql
  SELECT codigo FROM referido
  WHERE usuario_referidor_id = :uid AND habilitado = true
  ORDER BY fecha_creacion DESC LIMIT 1
  ```
  Generación fallback: `NIDO-{strtoupper(substr(nombres,0,1) . substr(apellido_paterno,0,1))}{date('Y')}` (mb_* con guard vía `pd_initial`).

- **`misReferidos(string $usuario_id): array`** — `referido` where `usuario_referidor_id=:uid` JOIN `usuario` (datos del referido: `nombres`, `apellido_paterno`, `correo` opcional, `estado_codigo`, `fecha_creacion`). Order by `fecha_creacion desc`.
  ```sql
  SELECT r.referido_id, r.codigo, r.estado_codigo, r.fecha_creacion,
         u.nombres AS referido_nombres, u.apellido_paterno AS referido_apellido,
         u.correo AS referido_correo
  FROM referido r
  LEFT JOIN usuario u ON r.usuario_referido_id = u.usuario_id
  WHERE r.usuario_referidor_id = :uid AND r.habilitado = true
  ORDER BY r.fecha_creacion DESC
  ```

- **`contarActivos(string $usuario_id): int`** — count de referidos ACREDITADOS (para dashboard).
  ```sql
  SELECT COUNT(*) FROM referido
  WHERE usuario_referidor_id = :uid AND estado_codigo = 'ESREF02' AND habilitado = true
  ```

- **`registrar(string $referidor_id, string $referido_id, string $codigo): ?string`** — crea `referido` PENDIENTE. Valida no duplicar el par referidor↔referido (si ya existe una fila con ambos, devuelve null). Inserta `estado_codigo='ESREF01'`, `fecha_creacion=now()`, `habilitado=true`. Devuelve `referido_id`.

- **`acreditar(string $referido_id): bool`** — si estado=PENDIENTE → `ESREF02` + `PuntosNido::acreditar(referidor_id, TMPT001, PUNTOS_REFERIDO, 'Bono por referir a {referido}')`. **Transacción** (begin/commit/rollback). Idempotente: si ya ACREDITADO, no hace nada (devuelve true). Si CANCELADO, no acredita (devuelve false).
  ```php
  // Pseudocódigo
  $this->db->beginTransaction();
  $row = load($referido_id);
  if (!$row || $row['estado_codigo'] !== self::EST_PENDIENTE) { $this->db->rollBack(); return $row && $row['estado_codigo']===self::EST_ACREDITADO; }
  update($referido_id, ['estado_codigo' => self::EST_ACREDITADO]);
  \App\Models\PuntosNido::acreditar($row['usuario_referidor_id'], \App\Models\PuntosNido::TMPT_REFERIDO, self::PUNTOS_REFERIDO, 'Bono por referir a ' . $referido_id);
  $this->db->commit();
  return true;
  ```

- **`aplicarCodigoEnRegistro(string $referido_id_nuevo, string $codigo): void`** — llamado al registrarse con un código de referido: busca el referidor por `referido.codigo = :codigo` (fila más reciente con ese código → `usuario_referidor_id`), valida que el referidor != referido_nuevo, crea `referido` PENDIENTE vía `registrar()`, y **acredita al instante** vía `acreditar()` (decisión D5: acreditar al registrarse, sin esperar verificación de email — trade-off en §10). Si el código no existe o es auto-referido, no hace nada (silencioso, no bloquea el registro).

### 6.3 `App\Models\PuntosNido` (definido por W5.8 — lo asumes existente, NO lo redefines)

Contrato congelado:
- `acreditar(string $usuario_id, string $tipo_codigo, int $puntos, string $descripcion): ?array` — suma puntos + inserta movimiento.
- `obtener(string $usuario_id): int`, `movimientos(...)`, `nivel(int): array`, `racha(string): int`, `canjear(...)`.
- Constante `TMPT_REFERIDO='TMPT001'`.

> **Si W5.8 no ha aterrizado todavía:** W8.2 puede avanzar (Blog model + Referido model + vistas) y deja la llamada `PuntosNido::acreditar(...)` como punto de integración explícito en §13. El método `Referido::acreditar()` se escribe con el `use App\Models\PuntosNido;` y la llamada, pero se marca como bloqueado hasta que W5.8 aterrice. Mientras tanto, `aplicarCodigoEnRegistro` puede insertar el `referido` PENDIENTE sin acreditar (o acreditar solo el estado, sin puntos) para smoke-test.

---

## 7. Controller

### 7.1 `BlogController` (sin sesión)

```php
class BlogController extends Controller {
    public function index() {
        $blogModel = new Blog();
        $pagina = max(1, (int)($_GET['p'] ?? 1));
        $porPagina = 9;
        $posts = $blogModel->getPublicados($pagina, $porPagina);
        $total = $blogModel->contarPublicados();
        $totalPaginas = (int)ceil($total / $porPagina);
        $this->render('blog/index', [
            'posts' => $posts,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
        ], 'public');
    }

    public function ver() {
        $id = $_GET['id'] ?? '';
        $blogModel = new Blog();
        $post = $blogModel->findById($id);
        if (!$post) {
            http_response_code(404);
            $this->setFlash('error', 'Artículo no encontrado.');
            $this->redirect('/blog');
        }
        $this->render('blog/ver', ['post' => $post], 'public');
    }
}
```

### 7.2 `ReferidoController` (requiere sesión)

```php
class ReferidoController extends Controller {
    public function __construct() {
        if (!isset($_SESSION['usuario_id'])) $this->redirect('/login');
    }

    public function index() {
        $referidoModel = new Referido();
        $uid = $_SESSION['usuario_id'];
        $codigo = $referidoModel->obtenerMiCodigo($uid);
        $referidos = $referidoModel->misReferidos($uid);
        $totalAcreditados = $referidoModel->contarActivos($uid);
        $puntosGanados = $totalAcreditados * Referido::PUNTOS_REFERIDO;
        $this->render('inquilino/referidos/index', [
            'codigo' => $codigo,
            'referidos' => $referidos,
            'totalAcreditados' => $totalAcreditados,
            'puntosGanados' => $puntosGanados,
        ], 'main');
    }

    public function generarCodigo() {
        // POST JSON — regenera/share el código (devuelve el código actual o uno nuevo)
        header('Content-Type: application/json');
        $uid = $_SESSION['usuario_id'];
        $referidoModel = new Referido();
        $codigo = $referidoModel->obtenerMiCodigo($uid);
        echo json_encode(['ok' => true, 'codigo' => $codigo]);
        exit;
    }
}
```

---

## 8. Vista

### 8.1 `app/views/blog/index.php` (layout `public`, pd-*)

- Eyebrow `pd-eyebrow` "Guía del universitario".
- Título de sección + intro breve.
- Grid de cards `pd-card` (3 columnas en desktop, 1 en móvil): cada card con titulo (link a `/blog/ver?id={blog_id}`), extracto (primeros ~160 chars de `contenido` con `htmlspecialchars` + truncado mb_* con guard), fecha formateada, autor.
- Paginación: links `?p={n}` con clases `pd-btn` / `pd-btn-ghost`, deshabilitado en extremos.
- Estado vacío: "Aún no hay artículos publicados."

### 8.2 `app/views/blog/ver.php` (layout `public`, pd-*)

- Eyebrow "Guía del universitario" + link volver a `/blog`.
- Título (`h1`), meta (fecha + autor).
- Contenido: `nl2br(htmlspecialchars($post['contenido']))` (preserva saltos de línea, escapa HTML).
- Link volver (`pd-btn pd-btn-ghost`).

### 8.3 `app/views/inquilino/referidos/index.php` (layout `main`, pd-* tokens)

- Eyebrow "Programa de referidos".
- **Tarjeta de código shareable** (`pd-card`):
  - Muestra el código del usuario en monoespaciado.
  - Botón "Copiar" (JS `navigator.clipboard.writeText`).
  - Link de invitación completo: `{BASE_URL}/register?ref={codigo}` (con botón copiar).
  - Botón "Compartir por WhatsApp": `https://wa.me/?text={urlencode("Únete a Nido Universitario: {BASE_URL}/register?ref={codigo}")}`.
- **Resumen** (`pd-card` o chips `pd-chip`): total referidos acreditados, puntos ganados por referidos (`totalAcreditados * 200`).
- **Lista de mis referidos** (tabla o lista de cards): nombre (`nombres apellido_paterno`), estado badge (PENDIENTE/ACREDITADO/CANCELADO con color `pd-chip`), fecha. Si no hay, estado vacío "Aún no tienes referidos. Comparte tu código para invitar amigos."
- JS mínimo: copiar al portapapeles + toast Swal2.

---

## 9. Integración

### 9.1 Integración registro (`AuthController::register`) — W8.2 ↔ W5.1
En `AuthController::register()`, tras crear el usuario exitosamente:
```php
// Capturar código de referido (POST o query ?ref= persistido en sesión)
$codigoRef = $_POST['codigo_referido'] ?? $_SESSION['ref_pending'] ?? null;
if ($codigoRef) {
    $nuevoUid = $this->usuarioModel->findByEmail($datos['correo'])['usuario_id'];
    (new \App\Models\Referido())->aplicarCodigoEnRegistro($nuevoUid, $codigoRef);
    unset($_SESSION['ref_pending']);
}
```
**Punto de sync con W5.1:** si W5.1 añade campos al formulario de registro, coordinar para no pisar el array `$datos` ni el flujo post-create. El campo oculto `codigo_referido` y la captura de `?ref=` en `showRegister` (guardar en `$_SESSION['ref_pending']`) son aditivos y no conflictivos.

### 9.2 Integración dashboard (W5.5) — cuando aterrice
- Sección "Mis referidos": consume `Referido::misReferidos` + `contarActivos`.
- Sección "Blog" (widget): consume `Blog::getRecientes(3)`.
- Documentado aquí como punto de conexión; W8 entrega los modelos listos para que W5.5 los consuma.

### 9.3 Integración nav público (opcional)
En `app/views/layouts/public.php`, añadir link "Blog" en el nav (`/blog`).

---

## 10. Riesgos / notas técnicas

- **Acreditación al registrarse (D5):** decisión v1 es acreditar al instante (sin verificación de email). Trade-off: un usuario podría registrar correos falsos con su propio código para farmear puntos. Mitigación v1: validar que el referidor != referido (auto-referido bloqueado en `aplicarCodigoEnRegistro`); el riesgo de farmeo multi-cuenta existe pero es bajo para v1. Hardening futuro: requerir verificación de email o primer login antes de acreditar (mover `acreditar()` a un hook post-verificación).
- **PuntosNido no aterrizado:** si W5.8 no está listo, `Referido::acreditar()` compila pero la llamada `PuntosNido::acreditar(...)` falla en runtime. Plan de contingencia en §6.3 y §13: avanzar Blog + Referido model + vistas sin la llamada a PuntosNido, marcar como punto de integración explícito.
- **Código de referido determinista:** `obtenerMiCodigo` toma el código de la fila más reciente; si el usuario no tiene filas, genera `NIDO-{INICIALES}{AÑO}`. Esto significa que el código "generado" no se persiste hasta que se crea una fila `referido` (al compartir o al ser referido). Para que el código sea estable, `generarCodigo` (POST) puede opcionalmente crear una fila `referido` placeholder con el código si no existe ninguna. Documentar decisión al implementar.
- **Catálogo `ESTADO_BLOG`:** inspeccionar al implementar; ajustar `EST_PUBLICADO` si `ESBL002` no es el código correcto.
- **Sin notificaciones (D6):** los referidos acreditan silenciosamente. El usuario descubre el estado al abrir "Mis referidos". No hay alertas, badges ni pushes (consistente con la decisión global no-notifications).
- **Escapado:** `htmlspecialchars` al renderizar `titulo`, `contenido`, `nombres`, `codigo`. `nl2br` para preservar saltos de línea del blog.
- **mb_* con guard:** truncado de extractos e iniciales usar `function_exists('mb_substr')` o el helper `pd_initial`.
- **Sin cierre `?>`** en archivos PHP.
- **Sub-agentes:** solo glm 5.2.
- **`php -S` dev server:** sin problema (sin realtime, sin workers).

---

## 11. Tareas finas (checklist)

- [ ] **11.1** Inspeccionar catálogos `ESTADO_BLOG`, `ESTADO_REFERIDO`, `TIPO_MOVIMIENTO_PUNTO`; confirmar códigos (`ESBL002`, `ESREF01-03`, `TMPT001`).
- [ ] **11.2** Modelo `Blog` (getPublicados, contarPublicados, findById, getRecientes) (§6.1).
- [ ] **11.3** Controller `BlogController` (index, ver) (§7.1).
- [ ] **11.4** Rutas blog en `index.php` (§4).
- [ ] **11.5** Vista `blog/index.php` (listado cards + paginación, layout public) (§8.1).
- [ ] **11.6** Vista `blog/ver.php` (detalle, layout public) (§8.2).
- [ ] **11.7** Modelo `Referido` (obtenerMiCodigo, misReferidos, contarActivos, registrar, acreditar, aplicarCodigoEnRegistro) (§6.2).
- [ ] **11.8** Controller `ReferidoController` (index, generarCodigo) (§7.2).
- [ ] **11.9** Rutas referidos en `index.php` (§4).
- [ ] **11.10** Vista `inquilino/referidos/index.php` (código shareable + copiar + WhatsApp + lista + resumen, layout main) (§8.3).
- [ ] **11.11** Integración `AuthController::register` (capturar `codigo_referido` / `?ref=`, llamar `aplicarCodigoEnRegistro`) (§9.1). **Sync con W5.1.**
- [ ] **11.12** Campo oculto `codigo_referido` en `register.php` + captura `?ref=` en `showRegister` (§9.1).
- [ ] **11.13** Link "Blog" en nav público (opcional) (§9.3).
- [ ] **11.14** Integración `PuntosNido::acreditar` en `Referido::acreditar` (§6.2, §6.3). **Sync con W5.8.**
- [ ] **11.15** Pruebas manuales (§12).

---

## 12. Criterios de aceptación / pruebas manuales

1. `GET /blog` muestra los 6 posts de muestra en cards (titulo, extracto, fecha, autor).
2. Paginación funciona (`?p=2` si >9 posts; con 6 no hay paginación visible).
3. Click en un post → `GET /blog/ver?id={id}` muestra detalle con titulo, contenido, fecha, autor.
4. Post inexistente o no publicado → redirect `/blog` con flash error.
5. `GET /blog` y `GET /blog/ver` accesibles sin sesión.
6. `GET /referidos` sin sesión → redirect `/login`.
7. `GET /referidos` logueado → muestra código shareable del usuario.
8. Botón "Copiar" copia el código al portapapeles; link `?ref=CODIGO` copiable.
9. Botón "Compartir por WhatsApp" abre `wa.me` con el mensaje correcto.
10. Lista de mis referidos muestra nombre, estado badge, fecha.
11. Resumen muestra total acreditados y puntos ganados (`total * 200`).
12. Registro con `?ref=CODIGO` → tras crear usuario, aparece un `referido` ACREDITADO para el referidor; el referidor gana 200 puntos.
13. Auto-referido (código propio) → no acredita, no bloquea registro.
14. Código inexistente → no acredita, no bloquea registro.
15. `Referido::acreditar` idempotente: llamar dos veces no duplica puntos.
16. Sin errores PHP; sin cierre `?>`; `mb_*` con guard.
17. (Si W5.8 no aterrizado) `aplicarCodigoEnRegistro` inserta `referido` PENDIENTE sin puntos; smoke-test OK.

---

## 13. Orden de ejecución sugerido

**Mapa de paralelización (Track B — W8):**
- **Track B.1 (8.1 Blog):** cero dependencias, arranca ya. 11.1 (catálogos) → 11.2 (modelo) → 11.3 (controller) → 11.4 (rutas) → 11.5/11.6 (vistas) → 11.13 (nav).
- **Track B.2 (8.2 Referidos):** Blog+Referido model + vistas pueden avanzar. 11.1 → 11.7 (modelo, sin PuntosNido aún) → 11.8 (controller) → 11.9 (rutas) → 11.10 (vista) → 11.12 (campo registro) → 11.11 (AuthController, sync W5.1) → 11.14 (PuntosNido, sync W5.8).

**Sync points:**
- (a) Contrato `PuntosNido` congelado — W8.2 lo consume, no lo redefine.
- (b) `AuthController::register` modificación coordinada con W5.1 (campos de registro) — cambio aditivo, no conflictivo.

**Secuencia detallada:**
1. 11.1 — Inspeccionar catálogos (común a B.1 y B.2).
2. **B.1:** 11.2 → 11.3 → 11.4 → 11.5 → 11.6 → 11.13 (Blog completo, paralelo a B.2).
3. **B.2:** 11.7 (sin PuntosNido) → 11.8 → 11.9 → 11.10 → 11.12 (vistas + campo registro).
4. **Sync W5.1:** 11.11 — `AuthController::register` + `aplicarCodigoEnRegistro` (cuando W5.1 no esté tocando el mismo flujo, o coordinar merge).
5. **Sync W5.8:** 11.14 — `PuntosNido::acreditar` en `Referido::acreditar` (cuando W5.8 aterrice).
6. 11.15 — Pruebas manuales (§12).

---

## 14. Estado global

| Tarea | Estado |
|---|---|
| 11.1 Inspeccionar catálogos | ⬜ |
| 11.2 Modelo Blog | ⬜ |
| 11.3 Controller Blog | ⬜ |
| 11.4 Rutas blog | ⬜ |
| 11.5 Vista blog/index | ⬜ |
| 11.6 Vista blog/ver | ⬜ |
| 11.7 Modelo Referido | ⬜ |
| 11.8 Controller Referido | ⬜ |
| 11.9 Rutas referidos | ⬜ |
| 11.10 Vista referidos/index | ⬜ |
| 11.11 Integración AuthController::register | ⬜ (sync W5.1) |
| 11.12 Campo oculto codigo_referido | ⬜ |
| 11.13 Link Blog en nav | ⬜ |
| 11.14 Integración PuntosNido::acreditar | ⬜ (sync W5.8) |
| 11.15 Pruebas manuales | ⬜ |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done
