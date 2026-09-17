<?php
/**
 * Configuración Supabase para el SDK JS de Realtime (consumido en el navegador).
 *
 * SUPABASE_ANON_KEY es la clave "publishable" (anon public) del dashboard de Supabase
 * (Project Settings → API). NO es un secreto sensible como la service_role key, pero
 * idealmente debería vivir fuera de git. Por ahora se mantiene aquí para simplicidad.
 *
 * Si SUPABASE_ANON_KEY está vacío, el Realtime no funcionará (la vista arrancará el
 * fallback por polling). El resto (inbox, enviar, historial) sigue funcionando.
 */
if (!defined('SUPABASE_URL')) {
    define('SUPABASE_URL', 'https://lokjiueialuwrulybgut.supabase.co');
}
if (!defined('SUPABASE_ANON_KEY')) {
    define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imxva2ppdWVpYWx1d3J1bHliZ3V0Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODAyODE1MjcsImV4cCI6MjA5NTg1NzUyN30.z5tcJAOMvqe-d8jJs6zC0eRbUPw4wnU23EO9yTrSnd8');
}
