<?php
/** @var array $alojamientos */
/** @var bool $reveal  — true en carga inicial (animación pd-reveal), false en append AJAX */
if (!function_exists('precio_fmt')) {
    function precio_fmt($monto, $monedaCodigo, $monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$']) {
        $simbolo = $monedaLabels[$monedaCodigo] ?? '';
        return $simbolo . ' ' . number_format((float)$monto, 0, ',', '.');
    }
}
$tipoLabels = ['TPA001' => 'Cuarto', 'TPA002' => 'Mini-depto', 'TPA003' => 'Depto completo'];
$monedaLabels = ['TPM001' => 'S/', 'TPM002' => 'US$'];
$reveal = $reveal ?? false;
?>
<?php foreach ($alojamientos as $i => $a): ?>
    <a href="/alojamiento/<?php echo htmlspecialchars($a['alojamiento_id']); ?>"
       class="pd-card<?php echo $reveal ? ' pd-reveal d' . (($i % 4) + 1) : ''; ?>">
        <div class="pd-thumb">
            <?php if (!empty($a['foto_principal'])): ?>
                <img src="<?php echo htmlspecialchars($a['foto_principal']); ?>" alt="<?php echo htmlspecialchars($a['titulo']); ?>">
            <?php else: ?>
                <div class="pd-ph"><i class="fas fa-bed"></i></div>
            <?php endif; ?>
            <span class="pd-price"><?php echo precio_fmt($a['precio_mensual'], $a['moneda_codigo'], $monedaLabels); ?>/mes</span>
        </div>
        <div class="pd-body">
            <span class="pd-kind"><?php echo htmlspecialchars($tipoLabels[$a['tipo_codigo']] ?? 'Alojamiento'); ?></span>
            <h3><?php echo htmlspecialchars($a['titulo']); ?></h3>
            <span class="pd-loc"><i class="fas fa-location-dot" style="color:var(--pd-accent)"></i> <?php echo htmlspecialchars($a['distrito'] ?? 'Distrito no disponible'); ?></span>
            <div class="pd-foot">
                <?php if (!empty($a['propietario_verificado'])): ?>
                    <span class="pd-verified"><i class="fas fa-check"></i> Verificado</span>
                <?php else: ?>
                    <span style="font-size:12.5px;color:var(--pd-muted)"><?php echo htmlspecialchars(trim(($a['propietario_nombres'] ?? '') . ' ' . ($a['propietario_apellido'] ?? ''))); ?></span>
                <?php endif; ?>
                <?php if ($a['calificacion'] !== null): ?>
                    <span class="pd-stars"><i class="fas fa-star"></i> <?php echo number_format((float)$a['calificacion'], 1); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </a>
<?php endforeach; ?>
