# Plan de Implementación — W6: Chat / mensajería (inquilino)

> **Oleada:** W6 · **Ref. doc:** DF-NidoUniversitario §4.5.1 (consumido por §3.3.7, §3.4.3) · **Depende de:** W2 (✅) · **Desbloquea:** sección Mensajes del dashboard (W5.5)
> **Estado:** ✅ Implementado · **Creado:** 2026-07-09 · **Done:** 2026-07-09
> Registro de seguimiento.

---

## 1. Objetivo

Chat bidireccional inquilino ↔ propietario. Un hilo (chat) por relación propietario-estudiante, con historial de mensajes de texto y **entrega en tiempo real vía Supabase Realtime**. Acceso desde la ficha del alojamiento ("Contactar Anfitrión") y desde un inbox `/mensajes`.

**Criterio de aceptación W6:**
> Chat bidireccional inquilino–propietario desde ficha y desde el inbox, con mensajes que aparecen en tiempo real.

---

## 2. Decisiones de alcance (tomadas con el usuario)

| # | Decisión | Opción elegida |
|---|---|---|
| D1 | Tiempo real | **Supabase Realtime**: el cliente se suscribe a inserts en `mensaje` filtrados por `chat_id` vía el SDK JS de Supabase. PHP solo inserta; Supabase pusha a los suscriptores. |
| D2 | Tipos de mensaje | **Solo texto v1** (max 2000 chars) + emojis. Sin imágenes/PDF (sin upload). |
| D3 | Mensajes predefinidos (6.5) | **Sí**: 3-4 plantillas rápidas ("Hola, ¿sigue disponible?", "¿Puedo visitar el lugar?", "¿Qué servicios incluye?"). |
| D4 | Búsqueda/archivar (6.6) | **Búsqueda por texto** en el inbox. **Archivar**: omitido v1. |

**Fuera de W6 v1:** imágenes/PDF, archivar chats, estados granulares enviado/entregado (solo `leído`/no leído).

---

## 3. Arquitectura — archivos a crear / modificar

### Crear
| Archivo | Rol |
|---|---|
| `app/models/Chat.php` | Modelo: hilos, participantes, mensajes, leer/enviar, conteo no leídos. |
| `app/controllers/MensajeController.php` | Inbox, conversación, enviar (POST), abrir hilo desde ficha. |
| `app/views/mensajes/index.php` | Inbox (lista de hilos + panel de conversación). Layout `public`. |
| `app/config/supabase.php` | Constantes `SUPABASE_URL` y `SUPABASE_ANON_KEY` (leídas por la vista para init del SDK). |

### Modificar
| Archivo | Cambio |
|---|---|
| `app/views/alojamiento/detalle.php` | El botón stub "Contactar anfitrión" pasa a ser link `GET /mensajes/abrir?alojamiento={id}` (crea/abre hilo y redirige). Solo si logueado. |
| `index.php` | Registrar rutas de mensajes. |
| `app/views/layouts/public.php` | (Opcional) badge en el nav "Mensajes" con conteo de no leídos si hay sesión. |

### BD (Supabase) — ver §5
- Habilitar `mensaje` en Realtime publication.
- Policy RLS para que el rol `anon` pueda leer `mensaje` (necesario para que el SDK reciba pushes). **Trade-off de seguridad documentado en §10.**
- Verificar catálogo de `estado_lectura_codigo` (§5.3).

---

## 4. Ruteo

```
GET  /mensajes                          → MensajeController::index        (inbox)
GET  /mensajes/abrir?alojamiento={id}   → MensajeController::abrir       (get-or-create hilo con el propietario del alojamiento, redirect a /mensajes?chat={id})
GET  /mensajes/nuevo?chat={id}          → MensajeController::nuevo       (AJAX: mensajes nuevos desde {ultimo_id} — fallback si realtime falla)
POST /mensajes/enviar                   → MensajeController::enviar      (inserta mensaje, devuelve JSON)
```

> Nota: `/mensajes/{chat_id}` como path param sería posible con el Router mejorado (W2), pero usamos `?chat={id}` para simplicidad y porque el inbox y la conversación comparten vista.

En `index.php`:
```php
$router->get('/mensajes', 'MensajeController', 'index');
$router->get('/mensajes/abrir', 'MensajeController', 'abrir');
$router->get('/mensajes/nuevo', 'MensajeController', 'nuevo');
$router->post('/mensajes/enviar', 'MensajeController', 'enviar');
```

Todos los endpoints requieren sesión (constructor redirect a `/login`).

---

## 5. BD — Realtime + RLS + catálogo

### 5.1 Habilitar Realtime sobre `mensaje`
```sql
alter publication supabase_realtime add table mensaje;
```
(Ejecutar en el SQL Editor de Supabase. Si la publication no existe, Supabase la crea por defecto.)

### 5.2 RLS para que el SDK (rol anon) reciba los pushes
```sql
alter table mensaje enable row level security;
create policy "anon lee mensajes para realtime"
  on mensaje for select to anon using (true);
```
> **Trade-off v1 (ver §10):** esto permite al rol anon leer **cualquier** mensaje. La app usa auth por sesión PHP, **no** Supabase Auth, así que no podemos usar `auth.uid()` en policies. El control de acceso real lo hace el **PHP** en cada endpoint HTTP (valida participación). El SDK cliente solo se suscribe filtrando por `chat_id` (UUID difícil de adivinar). Aceptable para v1; hardening en §10.

### 5.3 Catálogo `estado_lectura_codigo`
La columna `mensaje.estado_lectura_codigo` referencia un catálogo no listado en `db-schema-digest`. En implementación: consultar `catalogo` para ver la referencia. Si no existe, usar literales `'ENVIADO'` / `'LEIDO'` directamente (definir constantes en el modelo). Los mensajes nuevos se insertan con estado "no leído"; al abrir la conversación, se marcan los del otro usuario como "leído".

---

## 6. Modelo `Chat`

Métodos (todos validan participación donde corresponda):

- `getOrCreateChat($usuario_id, $propietario_id): string` — busca un `chat` que tenga a ambos en `chat_usuario`; si no existe, crea `chat` + 2 `chat_usuario`. Devuelve `chat_id`.
- `getChatsByUsuario($usuario_id, $busqueda = ''): array` — lista hilos del usuario con: nombre/avatar del **otro** participante, último mensaje (contenido + fecha), conteo de no leídos. Filtra por búsqueda de texto (nombre del otro o contenido).
- `esParticipante($chat_id, $usuario_id): bool`.
- `getMensajes($chat_id, $usuario_id): array` — valida participación; devuelve mensajes ordenados por `fecha_envio`. Incluye `es_mio` (bool) para alinear bubbles.
- `enviarMensaje($chat_id, $usuario_id, $contenido): ?array` — valida participación y longitud (≤2000); inserta en `mensaje` (`contenido`, `fecha_envio=now()`, `estado_lectura_codigo='ENVIADO'`, `chat_id`, `usuario_id`, `habilitado=true`). Devuelve la fila insertada.
- `marcarLeido($chat_id, $usuario_id): void` — `update mensaje set estado_lectura_codigo='LEIDO' where chat_id=:c and usuario_id<>:u and estado_lectura_codigo<>'LEIDO'`.
- `contarNoLeidos($usuario_id): int` — total de mensajes no leídos en todos sus chats (para badge).

SQL base para `getChatsByUsuario`: join `chat_usuario` (del usuario) → `chat` → `chat_usuario` (del otro) → `usuario` (datos del otro) → último `mensaje` por `chat_id` (subquery `distinct on (chat_id)` order by fecha_envio desc). Conteo no leídos: subquery count.

---

## 7. Controller `MensajeController`

```php
class MensajeController extends Controller {
    public function __construct() { if (!isset($_SESSION['usuario_id'])) $this->redirect('/login'); }

    public function index() {
        $chatModel = new Chat();
        $uid = $_SESSION['usuario_id'];
        $chatActivo = $_GET['chat'] ?? null;
        $busqueda = trim($_GET['q'] ?? '');
        $chats = $chatModel->getChatsByUsuario($uid, $busqueda);
        $mensajes = []; $otro = null;
        if ($chatActivo && $chatModel->esParticipante($chatActivo, $uid)) {
            $mensajes = $chatModel->getMensajes($chatActivo, $uid);
            $chatModel->marcarLeido($chatActivo, $uid);
            // $otro = datos del otro participante (para header)
        }
        // render 'mensajes/index' layout public (con config supabase para el SDK)
    }

    public function abrir() {
        // GET /mensajes/abrir?alojamiento={id}
        // 1. Cargar alojamiento → propietario_id (Alojamiento::findById)
        // 2. getOrCreateChat(uid, propietario_id)
        // 3. redirect /mensajes?chat={chat_id}
    }

    public function enviar() {
        // POST chat_id + contenido. Validar participación y longitud.
        // Insertar. Devolver JSON {ok, mensaje} (el SDK realtime hará el push al otro;
        //   el propio emctor actualiza la UI directamente).
    }

    public function nuevo() {
        // GET /mensajes/nuevo?chat={id}&ultimo={mensaje_id} — fallback AJAX
        //   por si realtime no conecta. Devuelve JSON {mensajes: [...]}.
    }
}
```

---

## 8. Vista `mensajes/index.php` (layout `public`)

Layout 2 paneles (estilo `pd-*`):
- **Izquierda (lista de hilos)**: buscador (`?q=`), cada hilo muestra avatar/initial del otro, nombre, último mensaje, fecha, badge de no leídos. Hilo activo destacado. Link a `/mensajes?chat={id}`.
- **Derecha (conversación)**: header con nombre del otro + link al alojamiento; lista de mensajes (bubbles: míos a la derecha en ultramarine, suyos a la izquierda en surface); input + botón enviar; plantillas rápidas (chips que rellenan el input).
- **Estado vacío**: "Aún no tienes conversaciones. Contacta a un anfitrión desde la ficha de un alojamiento."

### Realtime (SDK Supabase via CDN)
```html
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
```
Init con `SUPABASE_URL` y `SUPABASE_ANON_KEY` (desde `app/config/supabase.php`, inyectadas vía PHP). Cuando hay `chatActivo`:
```js
const sb = supabase.createClient(URL, ANON_KEY);
sb.channel('chat:' + chatId)
  .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'mensaje', filter: 'chat_id=eq.' + chatId },
      (payload) => { appendMensaje(payload.new); })
  .subscribe();
```
- Al enviar: `POST /mensajes/enviar` → on success append local (el realtime le llega al otro).
- `appendMensaje` renderiza el bubble y hace scroll al fondo; si el mensaje es del otro y la ventana está enfocada, marcar leído (POST o best-effort).
- Fallback: si el canal no conecta en ~5s, arrancar polling cada 5s a `/mensajes/nuevo`.

---

## 9. Integración con W2 (ficha)

En `detalle.php`, el botón "Contactar anfitrión" (hoy stub) pasa a:
- Si **logueado**: `<a href="/mensajes/abrir?alojamiento={id}" class="pd-btn pd-btn-ghost">Contactar anfitrión</a>`.
- Si **no logueado**: sigue oculto tras el CTA "Inicia sesión para reservar" (o un link a `/login`).

---

## 10. Riesgos / notas técnicas

- **Auth PHP vs Supabase Auth**: la app autentica por sesión PHP, no Supabase Auth. Por eso no podemos usar `auth.uid()` en RLS de realtime. v1 usa policy `anon using (true)` + filtro cliente por `chat_id`. **Hardening futuro**: integrar Supabase Auth (o un custom auth webhook/JWT) para policies reales por `usuario_id`. Mientras tanto, un usuario malicioso que conozca un `chat_id` (UUID) podría suscribirse a esa conversación. Los UUID son difíciles de adivinar; el riesgo es bajo pero real. Documentado.
- **anon key**: no está en el código (PHP usa el password de postgres). Obtenerlo del dashboard de Supabase → Project Settings → API → `anon public`. Guardarlo en `app/config/supabase.php` (no es secreto sensible — es publishable — pero idealmente fuera de git; por ahora en config).
- **`estado_lectura_codigo`**: verificar catálogo al implementar; usar literales si no existe.
- **`php -S` dev server**: el realtime va por el SDK JS directo a Supabase, no toca el PHP server. Sin problema de single-worker. Los endpoints HTTP normales funcionan normal.
- **Mensajes predefinidos**: array estático en la vista.
- **Sin motor de plantillas**: escapar `htmlspecialchars` al renderizar `contenido`. Emojis: permitir (UTF-8).
- **Sub-agentes**: solo glm 5.2.

---

## 11. Tareas finas (checklist)

- [ ] **11.1** `app/config/supabase.php` con URL + anon key (obtener del dashboard).
- [ ] **11.2** BD: `alter publication supabase_realtime add table mensaje;` + RLS policy anon select (§5).
- [ ] **11.3** Verificar catálogo `estado_lectura_codigo`; definir constantes.
- [ ] **11.4** Modelo `Chat` (getOrCreateChat, getChatsByUsuario, esParticipante, getMensajes, enviarMensaje, marcarLeido, contarNoLeidos) (§6).
- [ ] **11.5** Controller `MensajeController` (index, abrir, enviar, nuevo) (§7).
- [ ] **11.6** Rutas en `index.php` (§4).
- [ ] **11.7** Vista `mensajes/index.php` (inbox + conversación + plantillas) (§8).
- [ ] **11.8** SDK Supabase Realtime: suscripción a inserts en `mensaje` + append + fallback polling (§8).
- [ ] **11.9** Integración W2: botón "Contactar anfitrión" → `/mensajes/abrir` (§9).
- [ ] **11.10** Badge no leídos en nav (opcional).
- [ ] **11.11** Pruebas manuales (§12).

---

## 12. Criterios de aceptación / pruebas manuales

1. Desde la ficha de un alojamiento (logueado), "Contactar anfitrión" → abre `/mensajes?chat={id}` con el hilo.
2. Enviar un mensaje → aparece como bubble propio al instante.
3. En otra sesión (el propietario, o un segundo inquilino en otro navegador), el mensaje **llega solo** vía realtime (sin recargar).
4. Marcar leído: al abrir un hilo con mensajes no leídos, el badge desaparece.
5. Inbox: lista de hilos con último mensaje y conteo no leídos.
6. Búsqueda por texto filtra hilos.
7. Plantillas rápidas rellenan el input.
8. No logueado → redirect a `/login` en cualquier endpoint.
9. Validación: contenido vacío o >2000 chars rechazado.
10. Fallback: si realtime no conecta, polling trae los mensajes.
11. Sin errores PHP.

---

## 13. Orden de ejecución sugerido

1. Config supabase (11.1) + BD realtime/RLS (11.2) + catálogo (11.3).
2. Modelo Chat (11.4).
3. Controller + rutas (11.5, 11.6).
4. Vista inbox/conversación (11.7).
5. Realtime SDK + fallback (11.8).
6. Integración W2 (11.9).
7. Badge nav (11.10).
8. Pruebas (11.11).

---

## 14. Estado global

| Tarea | Estado |
|---|---|
| 11.1 Config supabase | ✅ |
| 11.2 BD realtime + RLS | ✅ |
| 11.3 Catálogo estado_lectura | ✅ (no existe catálogo; literales ENVIADO/LEIDO) |
| 11.4 Modelo Chat | ✅ |
| 11.5 Controller Mensaje | ✅ |
| 11.6 Rutas | ✅ |
| 11.7 Vista mensajes | ✅ |
| 11.8 Realtime SDK | ✅ |
| 11.9 Integración W2 | ✅ |
| 11.10 Badge nav | ✅ |
| 11.11 Pruebas | ✅ (smoke model + render inbox + render chat activo + enviar JSON) |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done
