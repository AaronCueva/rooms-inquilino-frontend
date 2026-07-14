<?php /** @var array $resenas */ ?>
<?php foreach ($resenas as $r): ?>
    <?php
        $verificado = $r['verificado'] ?? false;
        $esVerificado = in_array((string)$verificado, ['1', 'true', 't', 'TRUE', 'T'], true);
    ?>
    <div class="pd-resena">
        <div class="pd-resena-head">
            <?php $estFoto = trim($r['estudiante_foto'] ?? ''); ?>
            <?php if ($estFoto !== ''): ?>
                <img class="pd-av" src="<?php echo htmlspecialchars($estFoto, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="object-fit:cover;" onerror="this.style.display='none';var s=document.createElement('span');s.className='pd-av';s.textContent='<?php echo htmlspecialchars(strtoupper(pd_initial($r['estudiante_nombres'] ?? 'A')), ENT_QUOTES, 'UTF-8'); ?>';this.parentNode.insertBefore(s,this);">
            <?php else: ?>
                <span class="pd-av"><?php echo htmlspecialchars(strtoupper(pd_initial($r['estudiante_nombres'] ?? 'A'))); ?></span>
            <?php endif; ?>
            <div class="pd-resena-who">
                <b><?php echo htmlspecialchars(trim(($r['estudiante_nombres'] ?? '') . ' ' . ($r['estudiante_apellido'] ?? ''))); ?></b>
                <?php if ($esVerificado): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;margin-left:6px;"><i class="fas fa-check-circle"></i> Estudiante Residente Verificado</span>
                <?php endif; ?>
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
