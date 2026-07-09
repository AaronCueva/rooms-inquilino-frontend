# Plan Maestro — Portal Inquilino (Nido Universitario)

Documento de seguimiento generado a partir del **DF-NidoUniversitario-v1.0.docx** (Sección 3 — CU-WEB, rol **Estudiante registrado / Inquilino**).
Cada ítem tiene: **ref. doc**, **estado**, **sub-tareas**, **archivos implicados** y **dependencias**.
Usar este plan para generar planes de implementación por oleada y avanzar punto por punto.

> **Convención de estados**
> - ✅ Done — implementado y funcional
> - 🟡 Parcial — existe pero incompleto
> - 🟑 Mockup — solo HTML estático, sin lógica
> - ❌ Falta — no existe
> - 🔧 Refactor — requiere cambiar lo existente
> - 🚫 Descartado — fuera de alcance por decisión de proyecto

> **Decisión de alcance (2026-07-09, actualizada):** NO se implementa sistema de notificaciones, alertas ni recomendaciones. Esto **descarta**: W9 completo, W5.7 (preferencias de notificación), W1.7 (guardar búsqueda). **El programa de referidos (W8.2) SÍ está habilitado** (no es un sistema de notificaciones — acumula puntos silenciosamente, visibles en el dashboard). W8 = blog + referidos. W5.8 (gamificación) SÍ, sin eventos de notificación. Ver memoria `no-notifications-recommendations`.

---

## 1. Inventario — Lo que YA está (base sobre la que construir)

| Ref. doc | Ítem | Estado | Ubicación |
|---|---|---|---|
| — | Router MVC + autoload + layouts | ✅ | `index.php`, `app/core/` |
| — | PDO singleton (Supabase Postgres) | ✅ | `app/core/Database.php` |
| — | Sesiones + flash messages | ✅ | `app/core/Controller.php` |
| Auth | Login inquilino (bcrypt, rol `INQUILINO`) | ✅ | `AuthController::login` |
| Auth | Logout | ✅ | `AuthController::logout` |
| 3.4.1 | Registro multi-paso (3 pasos) | 🟡 | `AuthController::register`, `views/auth/register.php` |
| 3.4.3 | Dashboard | 🟑 | `views/inquilino/dashboard.php` |
| 3.7.1 | Foro por universidad | ✅ | `ForoController`, `models/Foro*`, `views/inquilino/foro/` |
| — | API ubicaciones por referencia | ✅ | `UbicacionController`, `models/Ubicacion` |
| — | Modelos de catálogo/rol/universidad | ✅ | `models/Catalogo`, `Rol`, `UniversidadModel`, `MenuMaestro` |
| 3.1 | Home / Landing pública | ✅ | `HomeController`, `views/home/index.php`, `views/layouts/public.php` |
| — | Modelo `Alojamiento` (lectura) + layout público | ✅ | `models/Alojamiento.php`, `views/layouts/public.php`, `public/css/app-design.css` |
| 3.2 | Búsqueda y filtros de alojamientos | ✅ | `AlojamientoController::buscar`, `views/buscar/` |
| 3.3 | Ficha/detalle del alojamiento | ✅ | `AlojamientoController::detalle`, `views/alojamiento/detalle.php` |
| 4.5.1 | Chat interno (cliente inquilino) | ✅ | `models/Chat.php`, `MensajeController`, `views/mensajes/index.php`, `config/supabase.php` |

### 1.1 Detalle del registro (3.4.1) — campos capturados vs. faltantes
**Capturados:** nombres, apellido_paterno, apellido_materno, correo, password, tipo_documento, numero_documento, celular, universidad.
**Faltantes (ver §4.5 más abajo).**

### 1.2 Detalle del foro (3.7.1) — completo
Categorías (`CATEGORIA_FORO`), crear publicación, comentarios anidados, reacciones toggle (👍❤️🔥 vía AJAX), filtros (universidad / categoría / búsqueda), paginación 10/pg, soft-delete de publicaciones y comentarios, validación de propiedad.

---

## 2. Inventario — Lo que FALTA (mapa general)

| Ref. doc | Módulo | Estado |
|---|---|---|
| 3.1 | Home / Landing pública | ✅ |
| 3.2 | Búsqueda y filtros de alojamientos | ✅ |
| 3.3 | Ficha/detalle del alojamiento | ✅ |
| 3.4.1 | Campos faltantes de registro + OAuth + email verify | ❌ |
| 3.4.2 | Verificación de identidad estudiantil | ❌ |
| 3.4.3 | Dashboard real (9 secciones) | 🟑→❌ |
| 3.4.4 | Gamificación (puntos NIDO, niveles, racha) | ❌ |
| 3.5 | Reserva y pago | ❌ |
| 3.6 | Contrato digital | ❌ |
| 3.7.2 | Blog / guía del universitario | ❌ |
| 3.7.3 | Programa de referidos | ❌ (habilitado, plan en PLAN-W8) |
| 3.8 | Notificaciones y alertas web | ❌ |
| 4.5.1 | Chat interno (cliente inquilino) | ✅ |
| 4.6.1 | Reporte de incidencias (formulario inquilino) | ❌ |
| 3.3.6 | Dejar reseña | ❌ |
| 3.3.7 | Favoritos + compartir alojamiento | ❌ |

---

## 3. Plan de implementación por oleadas (waves)

Cada oleada es entregable de forma independiente y deja usable lo anterior.
**Orden recomendado:** W0 → W1 → W2 → W3 → W4 → W5 → W6 → W7 → W8 → W9.

### W0 — Fundaciones de datos y home pública ✅
**Objetivo:** abrir la app sin login, mostrar landing, tener modelo `Alojamiento` y catálogos de servicios.

- [x] **0.1 Home pública** (ref 3.1) — nueva ruta `GET /` pública ( hoy redirige a login; mover login a `/login`).
  - Hero buscador (universidad/ciudad autocomplete, fecha, presupuesto, tipo) — sin registro.
  - Alojamientos destacados (carrusel 6-8), "¿Cómo funciona?", universidades aliadas, testimonios, banner propietarios, footer.
  - Chat flotante de soporte (placeholder WhatsApp).
  - Archivos: `controllers/HomeController`, `views/home/index.php`, layout `public`.
- [x] **0.2 Modelo `Alojamiento`** — CRUD lectura: `getAll(filtros, pagina)`, `findById($id)`, `contar(filtros)`, destacados, relacionados a universidad/servicios/fotos.
- [x] **0.3 Catálogos faltantes** — `TIPO_ALOJAMIENTO`, `SERVICIO` (por categoría), `GENERO`, `DURACION_MIN`, `MONEDA`. Sembrar datos en Supabase.
- [x] **0.4 Layout público** — `views/layouts/public.php` (header con buscador + nav, footer).

**Dependencias:** ninguna. **Desbloquea:** W1, W2.

---

### W1 — Búsqueda y filtros (ref 3.2) ✅
- [x] **1.1 Ruta `GET /buscar`** + `AlojamientoController::buscar`.
- [x] **1.2 Panel de filtros** — universidad, ciudad/distrito (select jerárquico vía `/api/ubicaciones`), radio km, tipo, precio (range), servicios (checkbox), amoblado, género, duración mínima, fecha disponible, solo verificados, calificación mínima, mascotas.
- [x] **1.3 Vistas** — grilla (default), lista, mapa (OpenStreetMap/Leaflet con pines de precio).
- [x] **1.4 Ordenamiento** — recientes, precio asc/desc, mejor calificados, más cercanos.
- [x] **1.5 Paginación** — 12/pg con "Ver más" / carga infinita.
- [x] **1.6 Contador de resultados** + estado vacío (sugerencias, zonas cercanas).
- [ ] **1.7 Guardar búsqueda** — 🚫 Descartado (consumía el feed de alertas de W9, que no se implementa).

**Dependencias:** W0. **Desbloquea:** W2.

---

### W2 — Ficha del alojamiento (ref 3.3) ✅
- [x] **2.1 Ruta `GET /alojamiento/{id}`** + `AlojamientoController::detalle`.
- [x] **2.2 Galería** — foto principal + lightbox, grid miniaturas, video tour (embed), plano (opcional).
- [x] **2.3 Info principal** — título, tipo, precio/mes, garantía, dirección (calle sin número hasta reserva), distancia a universidad, tamaño, capacidad, descripción, política de casa, género, amoblado, disponible desde, duración mínima, mascotas.
- [x] **2.4 Servicios e instalaciones** — checklist visual por categoría (íconos, activos/gris).
- [x] **2.5 Ubicación y mapa** — mapa con radio 100m enmascarado, distancias a universidades, puntos de interés (Google Places).
- [x] **2.6 Perfil del propietario** — foto, nombre (primer nombre + inicial), badge verificación, miembro desde, tasa/tiempo de respuesta, calificación, total alojamientos.
- [x] **2.7 Reseñas y calificaciones** (lectura) — calificación general + distribución, sub-calificaciones (limpieza, veracidad, comunicación, calidad-precio, ubicación, comodidad), listado paginado 5/pg, respuesta del propietario.
- [x] **2.8 Panel de reserva (sidebar)** — precio, fecha inicio (mín hoy+3), duración, resumen de costos (alquiler×meses + garantía + cargo 5%), botones **Solicitar Reserva** (stub), **Contactar Anfitrión** (→ W6 ✅), **Favoritos** (stub → W7), **Compartir** (stub → W7).

**Dependencias:** W1, W5 (verificación para reservar), W6 (contactar), W7 (favoritos/compartir). El detalle 2.1–2.7 puede avanzar solo; 2.8 integra con W3/W6/W7. **Estado:** 2.1–2.7 done; 2.8 tiene "Contactar Anfitrión" funcional (W6), los demás botones quedan como stub hasta W3/W7.

---

### W3 — Reserva y pago (ref 3.5)
- [ ] **3.1 Modelo `Reserva`** — estados: Pendiente, Aprobado, Activo, Rechazado, Cancelado, Finalizado, En disputa.
- [ ] **3.2 Flujo de solicitud** — seleccionar fecha+duración, resumen de costos, **mensaje de presentación (obligatorio, min 50 char)**, exigir verificación de identidad si falta, registrar método de pago (sin cobro aún), enviar solicitud (propietario tiene 48h).
- [ ] **3.3 Rutas** — `GET/POST /reserva/crear`, `GET /reservas` (mis reservas), `POST /reserva/cancelar`.
- [ ] **3.4 Métodos de pago** — tarjeta (pasarela), transferencia (subir comprobante), Yape/Plin (QR), MercadoPago. Integración de pasarela (Culqi/MercadoPago/Stripe) — **puede quedar como stub en v1**.
- [ ] **3.5 Política de cancelación** — 24h (reembolso completo), 24h–7d (50%), <7d (sin reembolso), no coincide anuncio (reembolso completo).
- [ ] **3.6 Cron/job 48h** — auto-cancelar solicitudes sin respuesta. (Si no hay cron, job on-request.)

**Dependencias:** W2, W5 (verificación). **Desbloquea:** W4.

---

### W4 — Contrato digital (ref 3.6)
- [ ] **4.1 Modelo `Contrato`** — datos arrendador/arrendatario/inmueble, renta, duración, fechas, garantía, condiciones, cargo plataforma.
- [ ] **4.2 Generación PDF** — librería PDF (ej. TCPDF/mpdf) con disclaimer legal de jurisdicción.
- [ ] **4.3 Firma digital** — click + OTP por SMS (o stub de confirmación en v1).
- [ ] **4.4 Entrega** — almacenar en panel inquilino y propietario, enviar copia por email.
- [ ] **4.5 Rutas** — `GET /contrato/{id}`, `GET /contrato/{id}/pdf`, `POST /contrato/{id}/firmar`.

**Dependencias:** W3.

---

### W5 — Perfil, verificación y gamificación del estudiante (ref 3.4)
- [ ] **5.1 Completar registro (3.4.1)** — agregar: carrera, año de ingreso, país de origen, confirmar contraseña, checkbox T&C, indicador de fortaleza de password. Migrar `usuario` con columnas faltantes.
- [ ] **5.2 OAuth Google/Facebook** — botones en login/registro. (Opcional v1 — marcar como pendiente.)
- [ ] **5.3 Email de bienvenida + verificación** — token de verificación de cuenta, enlace por email.
- [ ] **5.4 Verificación de identidad estudiantil (3.4.2)** — detección de dominio .edu/institucional, subida de carnet universitario, constancia de matrícula (PDF/imagen), estado validado por admin, badge "Estudiante verificado".
- [ ] **5.5 Dashboard real (3.4.3)** — reemplazar mockup por 9 secciones: Mis Reservas, Mi Alojamiento Actual, Recordatorio de Pagos, Favoritos, Mensajes, Alertas de Búsqueda, Mis Reseñas, Perfil y Verificación, Descuentos/Beneficios, Documentos. Cada sección consume su módulo.
- [ ] **5.6 Edición de perfil** — `actualizarPerfil` ya existe en `Usuario`; crear vista + controlador `PerfilController`.
- [ ] **5.7 Preferencias de notificación** — 🚫 Descartado (sin notificaciones).
- [ ] **5.8 Gamificación (3.4.4)** — modelo `PuntosNido`: puntos por pago puntual, racha (3/6/12 meses), canje por descuentos, nivel de perfil (Novato → Inquilino Confiable → Nido Gold). **Sin** eventos de notificación ni referidos.

**Dependencias:** W0 (modelos). 5.5 integra con W3/W6/W7/W9.

---

### W6 — Chat / mensajería (inquilino) (ref 4.5.1 consumido por 3.3.7, 3.4.3, 3.8) ✅
> Implementado 2026-07-09. Ver `PLAN-W6-CHAT.md` para el detalle y decisiones de alcance.
> **Scope v1:** solo texto (≤2000 chars) + emojis; tiempo real vía **Supabase Realtime** (SDK JS v2 por CDN) con fallback polling; estados leído/no leído (literales `ENVIADO`/`LEIDO`, sin catálogo); búsqueda por texto en el inbox; **sin** imágenes/PDF, **sin** archivar.

- [x] **6.1 Modelo `Chat`** — un hilo (`chat` + `chat_usuario`) por relación propietario-estudiante, historial (`mensaje`). Métodos: getOrCreateChat, getChatsByUsuario, esParticipante, getMensajes, enviarMensaje, marcarLeido, contarNoLeidos, getOtroParticipante.
- [x] **6.2 Rutas** — `GET /mensajes`, `GET /mensajes/abrir?alojamiento={id}`, `GET /mensajes/nuevo?chat=&ultimo=` (fallback AJAX), `POST /mensajes/enviar` (JSON).
- [x] **6.3 Tipos de mensaje** — solo texto (max 2000) + emojis. (Imágenes/PDF fuera de v1.)
- [x] **6.4 Estados de lectura** — leído / no leído (`ENVIADO`/`LEIDO`). Sin estados granulares enviado/entregado.
- [x] **6.5 Mensajes predefinidos** — 3 plantillas rápidas ("Hola, ¿sigue disponible?", "¿Puedo visitar el lugar?", "¿Qué servicios incluye?").
- [x] **6.6 Búsqueda** — por texto (nombre del otro o contenido). Archivar: omitido v1.
- [x] **6.7 Tiempo real** — Supabase Realtime (suscripción a `INSERT` en `mensaje` filtrado por `chat_id`) + fallback polling cada 5s si el canal no conecta.
- [x] **6.8 "Contactar Anfitrión"** desde ficha (W2) → `GET /mensajes/abrir?alojamiento={id}` crea/abre hilo y redirige a `/mensajes?chat={id}`.
- [x] **6.9 BD** — `mensaje` en `supabase_realtime` + RLS policy `anon select` (trade-off documentado: auth PHP, no Supabase Auth → control de acceso real en cada endpoint vía `esParticipante`).
- [x] **6.10 Badge no leídos** en nav del layout público.

**Dependencias:** W2. **Desbloquea:** sección Mensajes del dashboard (W5.5).

---

### W7 — Favoritos, reseñas, incidencias, compartir (refs 3.3.6, 3.3.7, 4.6.1)
- [ ] **7.1 Favoritos** — modelo `Favorito`, toggle desde ficha, grilla en dashboard, ruta `POST /favorito/toggle`, `GET /favoritos`.
- [ ] **7.2 Compartir alojamiento** — botones WhatsApp/Instagram (link simple). 🚫 Sin tracking de visitas ni puntos por compartir (era parte de referidos, descartado).
- [ ] **7.3 Dejar reseña (3.3.6)** — solo si completó ≥1 mes de alquiler en el inmueble. Calificación 1-5 + sub-calificaciones + comentario. Rutas `GET/POST /resena/crear`.
- [ ] **7.4 Reporte de incidencias (4.6.1)** — formulario inquilino: tipo (infraestructura/electrodoméstico/seguridad/convivencia/otro), título, descripción, fotos, prioridad auto-asignada. Rutas `GET/POST /incidencia/crear`, `GET /incidencias`.

**Dependencias:** W2, W3 (para validar 1 mes de alquiler en reseña), W5.8 (puntos por compartir).

---

### W8 — Comunidad extra (refs 3.7.2, 3.7.3)
- [ ] **8.1 Blog / guía (3.7.2)** — modelo `Articulo`/`Blog`, listado + detalle, categorías (guías por ciudad, tips, comparativas, noticias). Rutas `GET /blog`, `GET /blog/{slug}`. (Contenido editorial: seed inicial — ya hay 6 posts en `blog`.)
- [ ] **8.2 Programa de referidos (3.7.3)** — **habilitado** (2026-07-09). El inquililo comparte su código de referido; cuando el referido se registra/verifica, se acredita (`referido.estado_codigo`→ACREDITADO) y se suman puntos (`punto_movimiento` TMPT001) al referidor. Vista "Mis referidos" + estado de cada invitación. **Sin notificaciones** — el usuario ve el estado al abrir la sección.

**Dependencias:** 8.2 depende de W5.8 (modelo de puntos) y del registro (W5.1) para capturar/validar el código de referido al registrarse. 8.1 independiente.

---

### W9 — Notificaciones y alertas (ref 3.8) — 🚫 DESCARTADO
No se implementa. Decisión de proyecto: sin notificaciones, alertas ni recomendaciones.

---

## 4. Detalle de sub-tareas por módulo (checklist fino)

### 4.1 Home (3.1) ✅
- [x] Mover ruteo: `/` → home pública; `/login` sigue siendo login.
- [x] `HomeController@index`.
- [x] Vista `home/index.php` con layout `public`.
- [x] Buscador hero → POST/GET a `/buscar` (W1).
- [x] Carrusel destacados → `Alojamiento::getDestacados(8)`.
- [x] Secciones estáticas: cómo funciona, universidades aliadas, testimonios, banner propietarios, footer.
- [x] Chat flotante (link WhatsApp placeholder).

### 4.2 Búsqueda (3.2) ✅
- [x] Filtros (12 campos del doc).
- [x] Select jerárquico país→ciudad→distrito (reusar `/api/ubicaciones`).
- [x] Toggles: amoblado, solo verificados, mascotas.
- [x] Vista grilla / lista / mapa (Leaflet).
- [x] Ordenamiento (5 opciones).
- [x] Paginación 12/pg + "Ver más".
- [x] Estado vacío con sugerencias.
- [ ] `BusquedaGuardada` (CRUD) + botón "Guardar búsqueda". — 🚫 Descartado (W1.7).

### 4.3 Ficha (3.3) ✅
- [x] 2.2 Galería + lightbox + video + plano.
- [x] 2.3 Info principal (todos los campos del doc).
- [x] 2.4 Servicios por categoría.
- [x] 2.5 Mapa enmascarado + puntos de interés.
- [x] 2.6 Perfil propietario (datos públicos).
- [x] 2.7 Reseñas (lectura) + distribución + sub-calificaciones.
- [x] 2.8 Sidebar reserva (precio, fechas, duración, resumen costos, 4 botones — Contactar Anfitrión funcional, resto stub).

### 4.4 Registro/perfil (3.4)
- [ ] 5.1 Campos faltantes + migración BD.
- [ ] 5.2 OAuth (pendiente v1).
- [ ] 5.3 Email verificación.
- [ ] 5.4 Verificación estudiantil (3 métodos + badge).
- [ ] 5.5 Dashboard 9 secciones.
- [ ] 5.6 Edición perfil.
- [ ] 5.7 Preferencias notificación.
- [ ] 5.8 Gamificación (puntos, racha, niveles, canje).

### 4.5 Reserva/pago (3.5) + contrato (3.6)
- [ ] 3.1 Modelo Reserva + estados.
- [ ] 3.2 Flujo solicitud + mensaje presentación.
- [ ] 3.3 Rutas.
- [ ] 3.4 Métodos pago (stub pasarela v1).
- [ ] 3.5 Política cancelación.
- [ ] 3.6 Job 48h.
- [ ] 4.1–4.5 Contrato (PDF, firma OTP, entrega).

### 4.6 Chat (4.5.1) ✅
- [x] 6.1–6.10 (ver W6). Realtime Supabase + fallback polling; solo texto v1; RLS anon (trade-off documentado).

### 4.7 Favoritos / reseñas / incidencias / compartir
- [ ] 7.1 Favoritos.
- [ ] 7.2 Compartir + tracking.
- [ ] 7.3 Reseña (con validación 1 mes).
- [ ] 7.4 Incidencias (formulario + seguimiento lectura).

### 4.8 Comunidad extra (3.7.2, 3.7.3)
- [ ] 8.1 Blog.
- [ ] 8.2 Referidos (habilitado: código share + acreditación + puntos TMPT001).

### 4.9 Notificaciones (3.8)
- [ ] 9.1–9.6 (ver W9).

---

## 5. Mapa de dependencias (qué bloquea qué)

```
W0 (fundaciones + home)
 ├─→ W1 (búsqueda)
 │    └─→ W2 (ficha)
 │         ├─→ W3 (reserva/pago) ─→ W4 (contrato)
 │         ├─→ W6 (chat)
 │         └─→ W7 (favoritos/reseña/incidencias/compartir)
 └─→ W5 (perfil/verificación/gamificación)
      └─→ W8 (blog)  [8.1 solo]

🚫 Descartado: W9 (notificaciones), W8.2 (referidos), W5.7 (prefs notif), W1.7 (guardar búsqueda)
```

- **W3 (reserva) depende de W5 (verificación)** — no se puede reservar sin cuenta verificada.
- **W7.3 (reseña) depende de W3** — requiere haber alquilado ≥1 mes.
- **W5.5 (dashboard) integra con W3, W6, W7** — es el hub; puede quedar con secciones "placeholder" hasta que cada módulo exista.

---

## 6. Criterios de aceptación por oleada

- **W0:** usuario anónimo entra a `/`, ve landing con buscador y destacados (aunque sean pocos alojamientos seed).
- **W1:** desde home o `/buscar`, filtrar y ver resultados en grilla/lista/mapa; guardar búsqueda si estoy logueado.
- **W2:** clic en tarjeta → ficha completa con galería, servicios, mapa, perfil propietario, reseñas y sidebar de reserva.
- **W3:** inquilino verificado puede solicitar reserva con mensaje de presentación; ve estado en "Mis Reservas".
- **W4:** al aprobarse reserva, contrato PDF generado y descargable; firma con OTP (o stub).
- **W5:** registro completo, verificación estudiantil funcional, dashboard con 9 secciones reales, puntos NIDO visibles.
- **W6:** chat bidireccional inquilino–propietario desde ficha y desde dashboard.
- **W7:** favoritos, compartir, dejar reseña (post-1-mes), reportar incidencia.
- **W8:** blog navegable; referido genera puntos.
- **W9:** campana de notificaciones muestra eventos in-app; emails de reserva/pago enviados.

---

## 7. Notas técnicas y riesgos

- **Pasarelas de pago / SMS / WhatsApp / OAuth** son integraciones externas (Sección 7 del doc). En v1 dejar **stubs** y marcar como pendiente; no bloquean el flujo funcional.
- **Credenciales BD hardcodeadas** en `app/core/Database.php` — considerar `.env` antes de producción.
- **Sin motor de plantillas** — escapar siempre con `htmlspecialchars()` en vistas.
- **Sin cron real** — los jobs (48h reserva, recordatorios 7/3/1d, alertas de búsqueda) pueden ejecutarse on-request o con un endpoint `GET /cron?key=...` temporal.
- **Mapas** — usar OpenStreetMap/Leaflet (gratis) antes que Google Maps para evitar costo.
- **PDF** — TCPDF o mpdf vía Composer (el proyecto no usa Composer aún; evaluar agregarlo o usar librería sin Composer).
- **Sub-agentes:** solo modelo glm 5.2 disponible (ver memoria `subagent-model-constraint`).

---

## 8. Estado global resumido (para seguimiento rápido)

| Oleada | Módulo | Estado |
|---|---|---|
| W0 | Fundaciones + home | ✅ Done |
| W1 | Búsqueda y filtros | ✅ Done |
| W2 | Ficha alojamiento | ✅ Done |
| W3 | Reserva y pago | ⬜ No iniciado |
| W4 | Contrato digital | ⬜ No iniciado |
| W5 | Perfil/verificación/gamificación | 🟡 Registro parcial; resto no iniciado |
| W6 | Chat | ✅ Done |
| W7 | Favoritos/reseñas/incidencias/compartir | ⬜ No iniciado |
| W8 | Blog/referidos | ⬜ No iniciado (referidos re-habilitado) |
| W9 | Notificaciones | 🚫 Descartado |
| — | Foro (3.7.1) | ✅ Done |
| — | Auth (login/logout) | ✅ Done |

Leyenda: ⬜ pendiente · 🟡 parcial · ✅ done
