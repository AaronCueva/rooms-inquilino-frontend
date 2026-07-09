<?php /** @var array $resenas */ ?>
<?php foreach ($resenas as $r): ?>
    <div class="pd-resena">
        <div class="pd-resena-head">
            <span class="pd-av"><?php echo htmlspecialchars(strtoupper(pd_initial($r['estudiante_nombres'] ?? 'A'))); ?></span>
            <div class="pd-resena-who">
                <b><?php echo htmlspecialchars(trim(($r['estudiante_nombres'] ?? '') . ' ' . ($r['estudiante_apellido'] ?? ''))); ?></b>
                <span class="pd-stars"><i class="fas fa-star"></i> <?php echo number_format((float)$r['calificacion'], 1); ?></span>
            </div>
            <span class="pd-resena-fecha"><?php echo htmlspecialchars(date('d/m/Y', strtotime($r['creado']))); ?></span>
        </div>
        <p><?php echo nl2br(htmlspecialchars($r['comentario'] ?? '')); ?></p>
        <?php if (!empty($r['respuesta_propietario'])): ?>
            <div class="pd-resena-resp"><i class="fas fa-reply"></i> <span>Respuesta del propietario:</span> <?php echo nl2br(htmlspecialchars($r['respuesta_propietario'])); ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
