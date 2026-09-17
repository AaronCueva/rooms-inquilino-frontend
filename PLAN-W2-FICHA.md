# Plan de Implementación — W2: Ficha del alojamiento

> **Oleada:** W2 · **Ref. doc:** DF-NidoUniversitario §3.3 · **Depende de:** W1 (✅) · **Desbloquea:** W3 (reserva), W6 (chat), W7 (favoritos/compartir)
> **Estado:** ✅ Implementado (2026-07-09) · **Creado:** 2026-07-09
> Registro de seguimiento. Cada tarea tiene ref, archivos y dependencias.

---

## 1. Objetivo

Página de detalle de un alojamiento (`GET /alojamiento/{id}`) con galería, info principal, servicios, mapa enmascarado, perfil del propietario, reseñas (lectura) y panel de reserva (sidebar). Es la página a la que ya enlazan las cards de W0/W1.

**Criterio de aceptación W2 (del PLAN-INQUILINO):**
> Clic en tarjeta → ficha completa con galería, servicios, mapa, perfil propietario, reseñas y sidebar de reserva.

---

## 2. Decisiones de alcance (tomadas con el usuario)

| # | Decisión | Opción elegida |
|---|---|---|
| D1 | Panel de reserva (sidebar 2.8) | **Adaptativo**: si no logueado → CTA "Inicia sesión para reservar"; si logueado → botones stub (Solicitar Reserva, Contactar Anfitrión, Favoritos, Compartir) disabled/"Próximamente". El resumen de costos (alquiler×meses + garantía + cargo 5%) es interactivo (JS) aunque reserva sea stub. |
| D2 | Galería | **Lightbox + miniaturas**: foto principal grande + grid de miniaturas + lightbox al clic (zoom, navegación con flechas). Solo FOTO/IMAGEN. |

**Fuera de W2 v1 (schema/dependencias):**
- **Video tour / plano**: no hay tipo `VIDEO` en `multimedia` ni columna de plano. Omitir.
- **Sub-calificaciones** (limpieza, veracidad, comunicación, etc.): `resenia_alojamiento` solo tiene `calificacion` (int única). Omitir; requiere migración de schema si se quieren.
- **Puntos de interés (Google Places)**: requiere API de pago. Omitir.
- **Tasa/tiempo de respuesta del propietario**: no hay columna en schema. Omitir.
- **Botones funcionales del sidebar**: dependen de W3/W6/W7. Quedan stub.

---

## 3. Arquitectura — archivos a crear / modificar

### Crear
| Archivo | Rol |
|---|---|
| `app/views/alojamiento/detalle.php` | Vista ficha completa (layout `public`). |
| `app/views/alojamiento/_resenas.php` | Partial de reseñas (listado paginado 5/pg) para recarga AJAX de página. |

### Modificar
| Archivo | Cambio |
|---|---|
| `app/controllers/AlojamientoController.php` | Añadir `detalle($id)` y `resenas($id)` (AJAX página de reseñas). |
| `app/models/Alojamiento.php` | Añadir `getResenas`, `contarResenas`, `getDistribucionResenas`, `getConteoAlojamientosPropietario`. (`findById` ya existe con universidades/servicios/fotos/politicas.) |
| `app/core/Router.php` | **Soporte para path params `{id}`** — hoy solo match exacto. Necesario para `/alojamiento/{id}`. Ver §5. |
| `index.php` | Registrar `GET /alojamiento/{id}` y `GET /alojamiento/{id}/resenas` (AJAX). |

### BD
Sin cambios. Solo lectura de tablas existentes (`alojamiento`, `multimedia`, `servicio`, `alojamiento_servicio`, `alojamiento_universidad`, `universidad`, `usuario`, `politica_casa`, `alojamiento_politica`, `resenia_alojamiento`).

---

## 4. Ruteo

```
GET  /alojamiento/{id}           → AlojamientoController::detalle
GET  /alojamiento/{id}/resenas   → AlojamientoController::resenas   (AJAX, paginación 5/pg)
```

En `index.php`:
```php
$router->get('/alojamiento/{id}', 'AlojamientoController', 'detalle');
$router->get('/alojamiento/{id}/resenas', 'AlojamientoController', 'resenas');
```

El controller recibe el `{id}` como parámetro de método (inyectado por el Router) o vía `$_GET['id']` (ver §5).

---

## 5. Enhancement del Router (path params)

Hoy `Router::dispatch()` hace match exacto por `$this->routes[$method][$uri]`. No soporta `/alojamiento/{id}`.

**Cambio mínimo:**
1. En `addRoute`, si `$route` contiene `{`, guardarlo en un array aparte `$this->paramRoutes[$method][]` con el controller/action y el patrón.
2. En `dispatch`, tras fallar el match exacto, iterar `paramRoutes`: partir patrón y URI por `/`, comparar segmento a segmento; los segmentos `{x}` capturan el valor en `$params['x']`.
3. Inyectar `$params` en `$_GET` (merge) para que el controller los lea con `$_GET['id']`, **y** pasarlos como argumento al método si la firma lo admite (`$controller->$action($id)`).

**Ejemplo:** patrón `/alojamiento/{id}` + URI `/alojamiento/b7925f3a-...` → `$_GET['id'] = 'b7925f3a-...'`, llama `detalle('b7925f3a-...')`.

Compatibilidad: las rutas existentes (sin `{`) siguen por match exacto, sin regresión.

---

## 6. Modelo `Alojamiento` — métodos nuevos

### 6.1 `getResenas($alojamiento_id, $pagina = 1, $porPagina = 5): array`
```sql
SELECT r.resenia_alojamiento_id, r.calificacion, r.comentario, r.respuesta_propietario,
       r.creado, u.nombres AS estudiante_nombres, u.apellido_paterno AS estudiante_apellido,
       u.url_foto AS estudiante_foto, u.universidad_id
FROM resenia_alojamiento r
JOIN usuario u ON r.estudiante_id = u.usuario_id
WHERE r.alojamiento_id = :id
  AND r.estado_codigo IN (<whitelist visible>)   -- definir constante; verificar códigos ESTADO_RESENIA_ALOJAMIENTO
ORDER BY r.creado DESC
LIMIT :limit OFFSET :offset
```
> **Nota:** los códigos exactos de `ESTADO_RESENIA_ALOJAMIENTO` (4 valores) no están listados en `db-schema-digest`. En implementación: consultar `catalogo` para `referencia_codigo='ESTADO_RESENIA_ALOJAMIENTO'` y usar el de "PUBLICADA/APROBADA" (o un whitelist). Si solo hay un estado visible, filtrar por ese.

### 6.2 `contarResenas($alojamiento_id): int`
Mismo WHERE, `COUNT(*)`.

### 6.3 `getDistribucionResenas($alojamiento_id): array`
```sql
SELECT calificacion, COUNT(*) AS total
FROM resenia_alojamiento
WHERE alojamiento_id = :id AND estado_codigo IN (<whitelist>)
GROUP BY calificacion
```
Devuelve `[1=>n, 2=>n, ... 5=>n]` para las barras de distribución.

### 6.4 `getConteoAlojamientosPropietario($propietario_id): int`
```sql
SELECT COUNT(*) FROM alojamiento
WHERE usuario_id = :id AND habilitado = true AND estado_codigo IN ('EPA001','EPA003')
```

`findById` ya trae: `a.*` (título, tipo_codigo, precio_mensual, moneda_codigo, garantia, direccion, tamano_m2, numero_habitaciones, numero_banos, genero_exclusivo_codigo, amoblado, mascotas_permitidas, fumadores_permitidas, descripcion, latitud, longitud, duracion_minima_meses, fecha_disponible, calificacion), `distrito`, `propietario_id/nombres/apellido_paterno/foto/verificado/calificacion/miembro_desde`, `universidades` (con distancia_km), `servicios`, `fotos`, `politicas`.

---

## 7. Controller `AlojamientoController`

```php
public function detalle($id = null) {
    $id = $id ?? $_GET['id'] ?? null;
    if (!$id) { $this->redirect('/buscar'); }

    $modelo = new Alojamiento();
    $a = $modelo->findById($id);
    if (!$a) { http_response_code(404); $this->setFlash('error','Alojamiento no disponible.'); $this->redirect('/buscar'); }

    $resenas = $modelo->getResenas($id, 1, 5);
    $totalResenas = $modelo->contarResenas($id);
    $distribucion = $modelo->getDistribucionResenas($id);
    $totalAlojamientosProp = $modelo->getConteoAlojamientosPropietario($a['propietario_id']);
    $logueado = isset($_SESSION['usuario_id']);

    $this->render('alojamiento/detalle', [...], 'public');
}

public function resenas($id = null) {
    $id = $id ?? $_GET['id'] ?? null;
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    // AJAX: devolver JSON {html, hayMas} del partial _resenas
}
```

---

## 8. Vista `alojamiento/detalle.php` (layout `public`)

Estructura (reusa `pd-*`):

1. **Breadcrumb**: Inicio / Buscar / {titulo}.
2. **Galería** (2.2):
   - Foto principal grande (aspect 4/3).
   - Grid de miniaturas debajo; al clic, cambia la principal y abre lightbox.
   - **Lightbox**: overlay fullscreen, imagen, flechas ←/→, cerrar (Esc / clic fuera). JS vanilla.
3. **Layout 2 columnas**: izquierda (info + servicios + mapa + propietario + reseñas), derecha (sidebar reserva sticky).
4. **Info principal** (2.3): título, tipo (badge), precio/mes, garantía, distrito, tamaño m², habitaciones/baños, género, amoblado/mascotas/fumadores (chips), fecha disponible, duración mínima, descripción, política de casa (lista). Dirección: **solo calle sin número** + "Dirección exacta disponible tras reservar".
5. **Servicios** (2.4): checklist visual con íconos (activos en color, los de catálogo no presentes en gris). Por ahora sin categoría (schema `servicio` no tiene categoría); grid simple.
6. **Mapa** (2.5): Leaflet con **círculo de ~100m de radio** en lat/lng (ubicación aproximada, no pin exacto — "enmascarado"). Listado de distancias a universidades cercanas (from `universidades`). Sin POIs.
7. **Perfil propietario** (2.6): foto, primer nombre + inicial, badge verificado, miembro desde (creado), calificación, total alojamientos. Sin tasa de respuesta.
8. **Reseñas** (2.7): calificación general (a.calificacion) + distribución (barras 1-5) + total. Listado 5/pg con paginación AJAX (partial `_resenas.php`). Cada reseña: avatar/ini, nombre, estrellas, comentario, respuesta del propietario (si hay). **Sin sub-calificaciones**.
9. **Sidebar reserva** (2.8, sticky):
   - Precio/mes destacado.
   - Select fecha inicio (mín hoy+3), select duración (meses).
   - **Resumen de costos** (JS): `alquiler × meses + garantía + cargo 5%`. Se actualiza al cambiar selects.
   - Si **no logueado**: botón grande "Inicia sesión para reservar" → `/login`.
   - Si **logueado**: 4 botones stub disabled con tooltip "Próximamente": Solicitar Reserva, Contactar Anfitrión, Favoritos, Compartir. (Se activan cuando existan W3/W6/W7.)

### Leaflet (CDN)
Mismo CDN que W1 (`leaflet@1.9.4`). Solo el círculo, sin pines de otros alojamientos.

---

## 9. Tareas finas (checklist)

- [ ] **9.1** Enhancement Router path params `{id}` + inyección en `$_GET`/arg (§5).
- [ ] **9.2** Modelo: `getResenas`, `contarResenas`, `getDistribucionResenas`, `getConteoAlojamientosPropietario` (§6). Verificar whitelist `ESTADO_RESENIA_ALOJAMIENTO`.
- [ ] **9.3** Controller `detalle($id)` (§7).
- [ ] **9.4** Controller `resenas($id)` AJAX (JSON {html, hayMas}).
- [ ] **9.5** Vista `alojamiento/detalle.php` (galería + info + servicios + mapa + propietario + reseñas + sidebar) (§8).
- [ ] **9.6** Partial `_resenas.php` (item de reseña + paginación).
- [ ] **9.7** Lightbox JS (flechas, Esc, cerrar).
- [ ] **9.8** Mapa Leaflet con círculo 100m + distancias a universidades.
- [ ] **9.9** Sidebar adaptativo (login CTA / stub) + resumen de costos JS.
- [ ] **9.10** Registrar rutas en `index.php` (§4).
- [ ] **9.11** 404 si alojamiento no existe o no es visible.
- [ ] **9.12** Pruebas manuales (§11).

---

## 10. Dependencias / desbloquea

- **Depende de:** W1 (✅ — cards enlazan a `/alojamiento/{id}`).
- **Desbloquea:** W3 (reserva — el botón "Solicitar Reserva" del sidebar), W6 (chat — "Contactar Anfitrión"), W7 (favoritos/compartir).
- Los botones del sidebar quedan stub hasta que existan W3/W6/W7.

---

## 11. Criterios de aceptación / pruebas manuales

1. Clic en una card de `/buscar` → abre `/alojamiento/{id}` con la ficha.
2. Galería: clic en miniatura cambia principal; clic abre lightbox con flechas.
3. Info principal muestra todos los campos (precio, garantía, tamaño, género, etc.).
4. Servicios checklist correcto.
5. Mapa muestra círculo ~100m (no pin exacto) + distancias a universidades.
6. Perfil propietario con badge verificación y total alojamientos.
7. Reseñas: calificación general + barras de distribución + listado 5/pg con paginación AJAX.
8. Sidebar: no logueado → "Inicia sesión para reservar"; logueado → botones stub.
9. Resumen de costos se actualiza al cambiar meses.
10. `/alojamiento/{id-inexistente}` → 404 / redirect a `/buscar` con flash.
11. Sin errores PHP; `prefers-reduced-motion` respeta.

---

## 12. Riesgos / notas técnicas

- **Router enhancement** es un cambio core: probar que las rutas existentes (sin `{`) siguen funcionando (sin regresión). Probar `/foros`, `/login`, `/buscar`, `/api/ubicaciones` tras el cambio.
- **`ESTADO_RESENIA_ALOJAMIENTO`**: códigos no listados en el digest. Consultar `catalogo` en implementación y definir whitelist. Si hay duda, mostrar todas las reseñas (sin filtrar por estado) y refinar.
- **Dirección enmascarada**: el campo `direccion` es free text. "Calle sin número" es heurístico (regex para quitar dígitos). Si es complejo, mostrar solo distrito + "dirección exacta tras reservar" y omitir la calle.
- **Mapa círculo 100m**: si lat/lng son NULL, omitir mapa y mostrar solo distrito.
- **Sub-calificaciones y video** quedan fuera por schema. Si después se migran columnas, W2 se extiende.
- **Seguridad**: `$id` es uuid; bind PDO. Validar formato uuid opcional. Escapar todo en vistas.
- **Sub-agentes**: solo glm 5.2 (ver memoria `subagent-model-constraint`).

---

## 13. Orden de ejecución sugerido

1. Enhancement Router path params (9.1) — probar rutas existentes.
2. Modelo: métodos de reseñas + conteo propietario (9.2).
3. Controller `detalle` + `resenas` (9.3, 9.4).
4. Rutas en `index.php` (9.10).
5. Vista `detalle.php` + partial `_resenas.php` (9.5, 9.6).
6. Lightbox (9.7).
7. Mapa círculo (9.8).
8. Sidebar adaptativo + resumen costos (9.9).
9. 404 (9.11).
10. Pruebas manuales (9.12 / §11).

---

## 14. Estado global (actualizar al avanzar)

| Tarea | Estado |
|---|---|
| 9.1 Router path params | ⬜ |
| 9.2 Modelo reseñas + conteo prop | ⬜ |
| 9.3 Controller detalle | ⬜ |
| 9.4 Controller resenas AJAX | ⬜ |
| 9.5 Vista detalle | ⬜ |
| 9.6 Partial _resenas | ⬜ |
| 9.7 Lightbox | ⬜ |
| 9.8 Mapa círculo 100m | ⬜ |
| 9.9 Sidebar adaptativo + costos | ⬜ |
| 9.10 Rutas | ⬜ |
| 9.11 404 | ⬜ |
| 9.12 Pruebas manuales | ⬜ |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done
