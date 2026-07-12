# Módulo de Reseñas de Alojamiento Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir que los estudiantes universitarios autenticados publiquen reseñas y calificaciones de 1 a 5 estrellas con distintivo de verificación de inquilino residente, actualizando la calificación promedio del alojamiento.

**Architecture:** Módulo MVC integrado al detalle de alojamiento. Modelo `Alojamiento.php` con métodos para crear/actualizar reseña, verificar estatus de estudiante residente y recalcular promedio. Controlador `AlojamientoController.php` con endpoint AJAX `POST /alojamiento/{id}/resena`. Vista `app/views/alojamiento/detalle.php` con modal interactivo `pd-modal-resena` y estrellas dinámicas.

**Tech Stack:** PHP 8+, PDO PostgreSQL (Supabase), Vanilla JavaScript, Vanilla CSS (`pd-*`).

## Global Constraints
- Compatibilidad absoluta con PDO emulated prepares (`PDO::ATTR_EMULATE_PREPARES => true`).
- Preservar la estética premium `pd-*` (Glassmorphism, tarjetas con bordes redondeados, animaciones suaves).
- Retornos JSON estructurados en endpoints AJAX.

---

### Task 1: Métodos de Modelo en `app/models/Alojamiento.php`

**Files:**
- Modify: `app/models/Alojamiento.php`

**Interfaces:**
- Produces: `crearOActualizarResenia(string $alojamiento_id, string $estudiante_id, int $calificacion, string $comentario): bool`
- Produces: `verificarEstudianteResidente(string $alojamiento_id, string $estudiante_id): bool`

- [ ] **Step 1: Añadir método `verificarEstudianteResidente` a `app/models/Alojamiento.php`**
Añadir consulta SQL para comprobar si el estudiante tiene reserva formalizada o contrato en el alojamiento.

- [ ] **Step 2: Añadir método `crearOActualizarResenia` a `app/models/Alojamiento.php`**
Añadir lógica para verificar existencia previa de reseña, realizar `INSERT` o `UPDATE` en `resenia_alojamiento`, y actualizar el promedio en `alojamiento`.

---

### Task 2: Endpoint del Controlador en `app/controllers/AlojamientoController.php`

**Files:**
- Modify: `app/controllers/AlojamientoController.php`
- Modify: `index.php` (para registrar ruta POST)

**Interfaces:**
- Consumes: `Alojamiento::crearOActualizarResenia(...)`
- Produces: `POST /alojamiento/{id}/resena` endpoint JSON

- [ ] **Step 1: Añadir método `guardarResena` en `AlojamientoController.php`**
Validar sesión, sanitizar entrada POST (`calificacion` 1..5, `comentario`), invocar al modelo y retornar JSON `{success: true, message: '...'}`.

- [ ] **Step 2: Registrar ruta en `index.php`**
Añadir `$router->post('/alojamiento/{id}/resena', 'AlojamientoController', 'guardarResena');`.

---

### Task 3: Interfaz Visual y Modal Interactivo en `app/views/alojamiento/detalle.php`

**Files:**
- Modify: `app/views/alojamiento/detalle.php`
- Modify: `app/views/alojamiento/_resenas.php`

- [ ] **Step 1: Añadir distintivo "✅ Estudiante Verificado" en `app/views/alojamiento/_resenas.php`**
- [ ] **Step 2: Añadir Botón y Modal Interactivo de Reseñas `pd-modal-resena` en `app/views/alojamiento/detalle.php`**
Incluir selector interactivo de estrellas de 1 a 5 y JavaScript de envío AJAX al endpoint `POST /alojamiento/{id}/resena`.
