# Especificación de Diseño: Botón "Explorar sin iniciar sesión" en Login

**Fecha:** 2026-07-09  
**Módulo:** Autenticación (`app/views/auth/login.php`)  
**Enfoque Aprobado:** Enfoque A — Botón secundario en formulario de Login.

---

## 1. Objetivo
Permitir a los inquilinos que llegan a la pantalla de inicio de sesión (`/login`) —ya sea después de cerrar sesión o por navegación directa— continuar explorando el catálogo de alojamientos verificados sin obligación de iniciar sesión inmediatamente.

---

## 2. Ubicación y Componentes Visuales

### 2.1 Modificación en `app/views/auth/login.php`
Se insertará un botón secundario justo debajo del botón principal de envío del formulario de login (`Ingresar a mi cuenta`):

```html
<button type="submit" class="btn btn-primary btn-block w-100">Ingresar a mi cuenta</button>

<div class="mt-3">
    <a href="/buscar" class="btn btn-outline-secondary btn-block w-100" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; border-radius: 999px; padding: 11px 20px; text-decoration: none;">
        <i class="fas fa-search"></i> Explorar alojamientos sin iniciar sesión
    </a>
</div>
```

### 2.2 Estilo Visual y Jerarquía
- **Botón Primario:** `btn btn-primary` (fondo sólido oscuro/azul marca) para iniciar sesión.
- **Botón Secundario:** `btn btn-outline-secondary` (borde sutil, texto claro, ícono de búsqueda) para mantener la jerarquía sin competir, pero siendo sumamente legible y amigable al clic o touch.

---

## 3. Flujo de Navegación
1. El usuario hace clic en **"Cerrar sesión"** en cualquier parte de la app.
2. El sistema destruye la sesión y redirige a `/login`.
3. En `/login`, el usuario visualiza:
   - Formulario de login normal.
   - Botón **"Explorar alojamientos sin iniciar sesión"**.
4. Al hacer clic en el nuevo botón, es dirigido a `/buscar` donde puede consultar habitaciones disponibles, usar filtros y ver el header público adaptado para usuarios invitados.
