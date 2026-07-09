# Botón "Explorar sin iniciar sesión" en Login Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Agregar un botón secundario en el formulario de inicio de sesión (`/login`) para permitir a los inquilinos explorar el catálogo de habitaciones (`/buscar`) sin autenticarse.

**Architecture:** Se añadirá un enlace estilo botón (`btn btn-outline-secondary`) debajo del botón primario en `app/views/auth/login.php`.

**Tech Stack:** PHP, HTML, Vanilla CSS (`app-design.css`).

## Global Constraints
- Mantener la jerarquía visual del login: el botón principal debe seguir siendo `Ingresar a mi cuenta`.
- El botón secundario redirigirá a `/buscar`.

---

### Task 1: Agregar Botón de Exploración en Vista de Login

**Files:**
- Modify: `app/views/auth/login.php:24-28`

**Interfaces:**
- Consumes: Ruta `/buscar` en el enrutador público.
- Produces: UI interactiva en `/login`.

- [ ] **Step 1: Modificar `app/views/auth/login.php` para insertar el botón secundario**

Reemplazar debajo del botón de `submit` del formulario:
```html
        <button type="submit" class="btn btn-primary btn-block w-100">Ingresar a mi cuenta</button>
        <div class="mt-3">
            <a href="/buscar" class="btn btn-outline-secondary btn-block w-100" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; border-radius: 999px; padding: 11px 20px; text-decoration: none;">
                <i class="fas fa-search"></i> Explorar alojamientos sin iniciar sesión
            </a>
        </div>
```

- [ ] **Step 2: Verificar sintaxis PHP/HTML en el archivo modificado**

Run: `php -l app/views/auth/login.php`  
Expected: `No syntax errors detected in app/views/auth/login.php`

- [ ] **Step 3: Commit**

Run:
```bash
git add app/views/auth/login.php
git commit -m "feat: agregar boton explorar alojamientos sin iniciar sesion en login"
```
