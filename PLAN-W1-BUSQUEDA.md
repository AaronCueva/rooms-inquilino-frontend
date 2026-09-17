# Plan de Implementación — W1: Búsqueda y filtros de alojamientos

> **Oleada:** W1 · **Ref. doc:** DF-NidoUniversitario §3.2 · **Depende de:** W0 (✅) · **Desbloquea:** W2 (ficha)
> **Estado:** ✅ Implementado (2026-07-09) · **Creado:** 2026-07-09
> Registro de seguimiento. Cada tarea tiene ref, archivos y dependencias.

---

## 1. Objetivo

Permitir a un usuario anónimo o logueado **buscar alojamientos** desde el hero de la home o desde `/buscar`, aplicar **filtros**, ver resultados en **grilla** y **mapa**, ordenar y paginar con "Ver más".

> **Nota de alcance:** el sistema **no** incluirá notificaciones, alertas ni recomendaciones (decisión del proyecto). Por tanto **W1.7 "Guardar búsqueda" queda eliminado** — no hay feed de alertas que la consuma. W1 se enfoca solo en buscar + filtrar + ver resultados.

**Criterio de aceptación W1:**
> Desde home o `/buscar`, filtrar y ver resultados en grilla/mapa.

---

## 2. Decisiones de alcance (tomadas con el usuario)

| # | Decisión | Opción elegida |
|---|---|---|
| D1 | Filtros v1 | **Subset práctico**: `q` (universidad/ciudad), `tipo[]`, `presupuesto` (precio máx), `amoblado`, `mascotas`, `solo_verificados`, `fecha` (disponible desde). |
| D2 | Vistas de resultados | **Grilla (default) + Mapa (Leaflet/OSM)**. No lista. |
| D3 | Paginación | **"Ver más" (botón)** vía AJAX append. 12/pg. |
| D4 | Guardar búsqueda (W1.7) | **Eliminado**. No hay sistema de notificaciones/alertas que la consuma. |

**Fuera de W1 v1 (quedan para W1.2 o posterior):** radio km, precio mín, servicios checkbox, género, duración mínima, calificación mínima. El modelo `getAll` se diseña extensible para añadirlos sin refactor.

---

## 3. Arquitectura — archivos a crear / modificar

### Crear
| Archivo | Rol |
|---|---|
| `app/controllers/AlojamientoController.php` | Controller de búsqueda (`buscar`). |
| `app/views/buscar/index.php` | Página de resultados (layout `public`): header de resultados, panel de filtros, grilla, contenedor mapa, "Ver más", estado vacío. |
| `app/views/buscar/_cards.php` | Partial reutilizable de la grilla de cards (lo usa `index.php` y la respuesta AJAX). |

### Modificar
| Archivo | Cambio |
|---|---|
| `app/models/Alojamiento.php` | Implementar `getAll(filtros, pagina, porPagina)` y `contar(filtros)` reales (hoy son stubs que devuelven `[]`/`0`). Añadir `getCoordenadas(filtros)` para el mapa. |
| `index.php` | Registrar ruta `GET /buscar`. |
| `app/views/home/index.php` | Mover `.pd-type-row` (chips de tipo) **dentro** del `<form class="pd-search">` para que `tipo[]` se envíe. Hoy están fuera del form y no se envían. |

### BD
Sin cambios. No se crea ninguna tabla nueva en W1.

---

## 4. Ruteo

```
GET  /buscar  →  AlojamientoController::buscar   (página completa o fragmento AJAX)
```

En `index.php`:
```php
$router->get('/buscar', 'AlojamientoController', 'buscar');
```

---

## 5. Modelo `Alojamiento` — `getAll` / `contar` / `getCoordenadas`

### 5.1 Filtros soportados (array `$filtros`)
| Key | Origen GET | Columna / condición |
|---|---|---|
| `q` | `q` | `ILIKE` contra `universidad.nombre` (vía `alojamiento_universidad`), `ubicacion.nombre` (distrito), `alojamiento.titulo`, `alojamiento.direccion` |
| `tipos` | `tipo[]` | `a.tipo_codigo IN (...)` |
| `presupuesto` | `presupuesto` | `a.precio_mensual <= :presupuesto` |
| `amoblado` | `amoblado` (1/0) | `a.amoblado = true` |
| `mascotas` | `mascotas` (1/0) | `a.mascotas_permitidas = true` |
| `solo_verificados` | `solo_verificados` (1/0) | `u.verificado = true` (propietario) |
| `fecha` | `fecha` (YYYY-MM-DD) | `a.fecha_disponible IS NULL OR a.fecha_disponible <= :fecha` |
| `orden` | `orden` | ver §7 |

Siempre: `a.habilitado = true AND a.estado_codigo IN ('EPA001','EPA003')` (ACTIVO/APROBADO), reusando `ESTADOS_VISIBLES`.

### 5.2 SQL base (SELECT compartido entre `getAll` y `contar`)
```sql
SELECT a.alojamiento_id, a.titulo, a.tipo_codigo, a.precio_mensual, a.moneda_codigo,
       a.calificacion, a.amoblado, a.mascotas_permitidas, a.fecha_disponible,
       a.latitud, a.longitud,
       ub.nombre AS distrito,
       u.nombres AS propietario_nombres, u.apellido_paterno AS propietario_apellido,
       u.verificado AS propietario_verificado,
       f.url AS foto_principal,
       au.distancia_km  -- solo si q matchea una universidad concreta (para "más cercanos")
FROM alojamiento a
LEFT JOIN ubicacion ub ON a.ubicacion_id = ub.ubicacion_id
LEFT JOIN usuario u ON a.usuario_id = u.usuario_id
LEFT JOIN alojamiento_universidad au ON au.alojamiento_id = a.alojamiento_id
    AND au.universidad_id = :uni_ref   -- NULL si no hay uni de referencia
LEFT JOIN multimedia f ON f.alojamiento_id = a.alojamiento_id
    AND f.tipo_codigo IN ('FOTO','IMAGEN')
    AND f.orden = (SELECT MIN(m.orden) FROM multimedia m
                   WHERE m.alojamiento_id = a.alojamiento_id
                     AND m.tipo_codigo IN ('FOTO','IMAGEN'))
WHERE a.habilitado = true
  AND a.estado_codigo IN ('EPA001','EPA003')
  AND ($filtros dinámicos)
ORDER BY $orden
LIMIT :limit OFFSET :offset
```

- `contar()` usa el mismo WHERE sin `LIMIT/OFFSET` ni `ORDER BY`, `SELECT COUNT(DISTINCT a.alojamiento_id)`.
- Los filtros dinámicos se construyen con binds parametrizados (never string concat de valores; `inList()` ya existe para listas constantes).
- `q`: si coincide exactamente (o ILIKE) con un `universidad.nombre`, se setea `:uni_ref` para traer `distancia_km` y habilitar orden "más cercanos". Si no, `:uni_ref = NULL` y el LEFT JOIN no aporta.

### 5.3 `getCoordenadas(filtros)`
Devuelve `alojamiento_id, titulo, precio_mensual, moneda_codigo, latitud, longitud` para los pines del mapa (mismo WHERE, sin paginar, cap 200 para no saturar). Reusa `getAll` con un flag o un método aparte.

### 5.4 Extensibilidad
Los filtros futuros (radio km, servicios, género, duración mínima, calificación mínima) se añaden como nuevas keys del array `$filtros` + su cláusula en un `switch`/map, sin tocar la firma.

---

## 6. Ordenamiento (`orden`)

| Valor | ORDER BY |
|---|---|
| `recientes` (default) | `a.creado DESC` |
| `precio_asc` | `a.precio_mensual ASC NULLS LAST` |
| `precio_desc` | `a.precio_mensual DESC NULLS LAST` |
| `calificados` | `a.calificacion DESC NULLS LAST` |
| `cercanos` | `au.distancia_km ASC NULLS LAST` (solo si hay `uni_ref`; si no, fallback a `recientes`) |

Whitelist estricta en el controller (no inyectar el string directo en SQL).

---

## 7. Controller `AlojamientoController`

```php
class AlojamientoController extends Controller {

    public function buscar() {
        // 1. Leer + sanitizar GET (q, tipo[], presupuesto, amoblado, mascotas,
        //    solo_verificados, fecha, orden, pagina, vista)
        // 2. Construir $filtros
        // 3. $alojamientos = (new Alojamiento())->getAll($filtros, $pagina, 12);
        //    $total = ...->contar($filtros);
        // 4. Si es AJAX (?ajax=1 o X-Requested-With): renderizar solo 'buscar/_cards'
        //    y devolver HTML fragment (para "Ver más").
        // 5. Si no: cargar catálogos (tipos), $coords para mapa, y render
        //    'buscar/index' con layout 'public'.
    }
}
```

- Sanitización: `trim`, `ctype_digit` para numéricos, whitelist para `orden` y `tipo_codigo` (validar contra catálogo).
- `pagina` ≥ 1; offset = `(pagina-1)*12`.

---

## 8. Vista `buscar/index.php` (layout `public`)

Estructura (reusa design system `pd-*` del home):

1. **Header de resultados**: eyebrow "Resultados", título dinámico ("Alojamientos cerca de *Pacífico*" o "Todos los alojamientos"), contador "N resultados", toggle **Grilla / Mapa**.
2. **Panel de filtros** (sidebar izquierda, colapsable en móvil):
   - Input `q` (universidad/ciudad) con autocomplete (reusa `/api/universidades`).
   - Select `tipo[]` (multi-chips, reusa `pd-chip`).
   - Number `presupuesto` (precio máx).
   - Toggles: `amoblado`, `mascotas`, `solo_verificados` (chips `is-on`).
   - Date `fecha` (disponible desde).
   - Select `orden` (5 opciones).
   - Botón "Aplicar" (submit GET) + "Limpiar" (link a `/buscar`).
3. **Contenedor de resultados**:
   - **Grilla** (default): reusa `.pd-cards` + `.pd-card` del home. Partial `_cards.php`.
   - **Mapa**: `<div id="pd-buscar-map">` + Leaflet (OSM tiles), pines con popup de precio+título. Cap 200 pines.
4. **"Ver más resultados"**: botón que fetch `/buscar?<filtros>&pagina=N&ajax=1`, appenda el HTML devuelto al contenedor de cards. Se oculta al llegar a `total`. Spinner durante la carga.
5. **Estado vacío**: ícono + "No encontramos alojamientos con esos filtros" + sugerencias (quitar filtros, zonas cercanas).

### Leaflet (CDN, sin npm)
```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```
Mapa centrado en el primer resultado o en la universidad de referencia (si tiene lat/lng). Zoom 12.

### `_cards.php` (partial)
Recibe `$alojamientos` y renderiza `.pd-cards > .pd-card` idéntico al home. Sin layout. Lo imprime `index.php` y lo devuelve el endpoint AJAX.

---

## 9. Tareas finas (checklist de implementación)

- [ ] **9.1** Mover `.pd-type-row` dentro del `<form>` del hero en `home/index.php` (que `tipo[]` se envíe).
- [ ] **9.2** Implementar `Alojamiento::getAll()` con builds dinámicos + binds (§5.2).
- [ ] **9.3** Implementar `Alojamiento::contar()` (mismo WHERE, COUNT DISTINCT).
- [ ] **9.4** Implementar `Alojamiento::getCoordenadas()` (§5.3).
- [ ] **9.5** Crear `AlojamientoController::buscar()` con sanitización + AJAX fragment (§7).
- [ ] **9.6** Crear vista `buscar/index.php` (filtros + grilla + mapa + Ver más + vacío) (§8).
- [ ] **9.7** Crear partial `buscar/_cards.php` (reusa `.pd-card`).
- [ ] **9.8** Toggle Grilla/Mapa (JS simple, sin recargar).
- [ ] **9.9** "Ver más" AJAX append + ocultar al final + spinner.
- [ ] **9.10** Leaflet: render pines desde `$coords`, popups de precio.
- [ ] **9.11** Estado vacío con sugerencias.
- [ ] **9.12** Registrar ruta `GET /buscar` en `index.php` (§4).
- [ ] **9.13** Ordenamiento whitelist (§6).
- [ ] **9.14** Pruebas manuales (ver §11).

---

## 10. Dependencias / desbloquea

- **Depende de:** W0 (✅ — `Alojamiento`, catálogos, layout `public`, home).
- **Desbloquea:** W2 (ficha `/alojamiento/{id}` — las cards ya enlazan ahí; W2 hará que ese link funcione).

---

## 11. Criterios de aceptación / pruebas manuales

1. `GET /buscar` (sin filtros) → muestra hasta 12 alojamientos en grilla, contador total.
2. Desde el hero, escribir "Pacífico" + pulsar Buscar → `/buscar?q=Pacífico` muestra resultados que coinciden.
3. Aplicar filtro precio máx 800 → solo aparecen alojamientos ≤ 800.
4. Toggle "Solo verificados" → solo aparecen cards con propietario verificado.
5. Cambiar a vista **Mapa** → pines con popup de precio, centrado en resultados.
6. "Ver más resultados" → appenda 12 más sin recargar; desaparece al llegar al total.
7. Ordenar por "Precio asc" → orden correcto.
8. Filtros que no matchean → estado vacío con sugerencias.
9. `prefers-reduced-motion` → sin animaciones; resultados igualmente legibles.

---

## 12. Riesgos / notas técnicas

- **Solo 5 alojamientos seed** (ver `db-schema-digest`): varios filtros devolverán vacío. Considerar sembrar más alojamientos de prueba antes de validar UX.
- **`q` ambiguo** (universidad vs ciudad vs título): el ILIKE múltiple puede dar falsos positivos. Aceptable en v1; refinar con scoring en W2+.
- **"Más cercanos" sin universidad de referencia** → fallback a `recientes`. Documentar en UI.
- **Leaflet vía CDN** → dependencia externa. Si hay restricción de red, descargar local. OSM tiles gratis sin API key.
- **Sin motor de plantillas** → escapar todo con `htmlspecialchars()` en las vistas; el partial `_cards` reusa la misma lógica del home.
- **AJAX fragment** → el endpoint devuelve HTML (no JSON). El JS hace `innerHTML +=` sobre el contenedor. Mantener el partial sin layout.
- **Paginación + mapa** → el mapa muestra todos los coords (cap 200), no solo la página actual, para que el usuario vea la distribución completa. Las cards paginan 12.
- **Seguridad**: todos los valores de filtro van por binds PDO; `orden` y `tipo_codigo` por whitelist. No inyectar strings en SQL.
- **Sub-agentes**: solo modelo glm 5.2 disponible (ver memoria `subagent-model-constraint`).

---

## 13. Orden de ejecución sugerido

1. Modelo `Alojamiento`: `getAll` + `contar` + `getCoordenadas` (9.2–9.4).
2. Controller `AlojamientoController::buscar` (9.5, 9.13).
3. Ruta `GET /buscar` en `index.php` (9.12).
4. Vista `index.php` + partial `_cards.php` (9.6, 9.7).
5. Toggle grilla/mapa + Leaflet (9.8, 9.10).
6. "Ver más" AJAX (9.9).
7. Estado vacío (9.11).
8. Fix hero chips dentro del form (9.1).
9. Pruebas manuales (9.14 / §11).

---

## 14. Estado global (actualizar al avanzar)

| Tarea | Estado |
|---|---|
| 9.1 Hero chips en form | ⬜ |
| 9.2–9.4 Modelo Alojamiento (getAll/contar/coords) | ⬜ |
| 9.5 Controller buscar | ⬜ |
| 9.6–9.7 Vista + partial | ⬜ |
| 9.8 Toggle grilla/mapa | ⬜ |
| 9.9 Ver más AJAX | ⬜ |
| 9.10 Leaflet mapa | ⬜ |
| 9.11 Estado vacío | ⬜ |
| 9.12 Ruta /buscar | ⬜ |
| 9.13 Ordenamiento | ⬜ |
| 9.14 Pruebas manuales | ⬜ |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done
