# PLAN — Fix pagos (rooms-inquilino-frontend)

> Problemas: (1) contratos finalizados siguen mostrando pagos pendientes; (2) si desactivas pagos en BD, al abrir `/pagos` se vuelven a crear; (3) el flujo de pago no termina de funcionar end-to-end.

Fecha: 2026-07-12

## Bugs encontrados

1. **Regeneración de cuotas** — `Pago::generarCuotasContrato()` verifica `SELECT COUNT(*) FROM pago WHERE contrato_id=:c AND habilitado=true`. Si bajas `habilitado=false`, el count=0 → regenera todas. (`app/models/Pago.php:85`)
2. **Contratos finalizados muestran pendientes** — `obtenerPagosPorInquilino()` filtra `p.habilitado=true AND c.habilitado=true` pero NO filtra por estado del contrato. Finalizados (ESCO002) con cuotas habilitadas siguen apareciendo. (`app/models/Pago.php:44-46`)
3. **Genera cuotas para contratos finalizados** — `PagoController::index()` recorre TODOS los contratos y llama `generarCuotasContrato` incluso para finalizados. (`app/controllers/PagoController.php:39-48`)

## Fixes

### F1 — `app/models/Pago.php::generarCuotasContrato`
Cambiar el check de existencia para contar ANY pago (habilitado o no):
```php
$chk = $this->db->prepare("SELECT COUNT(*) FROM pago WHERE contrato_id = :c");
```
Así, una vez creadas las cuotas (activas o anuladas), no se regeneran.

### F2 — `app/models/Pago.php::obtenerPagosPorInquilino`
Agregar filtro para mostrar: todas las cuotas de contratos ACTIVOS + sólo historial pagado de finalizados/cancelados:
```sql
AND (c.estado_codigo = 'ESCO001' OR p.estado_codigo = 'ESPG003')
```

### F3 — `app/controllers/PagoController.php::index`
Saltar generación de cuotas para contratos no activos:
```php
foreach ($contratos as $c) {
    if (($c['estado_codigo'] ?? '') !== Contrato::EST_ACTIVO) continue;
    ...
}
```

> El flujo de pago (`simular`) ya funciona a nivel backend (probado: `simularPago` setea `ESPG003` correctamente). El problema end-to-end era del lado propietario (ver `PLAN-PAGOS-FIX.md` del propietario).
