<div class="page-content">
    <div class="page-head">
        <div>
            <div class="eyebrow">Dashboard</div>
            <h1>Bienvenida, <?php echo htmlspecialchars($nombre_usuario); ?></h1>
            <div class="sub">Encuentra opciones de alojamiento verificadas.</div>
        </div>
    </div>

    <!-- Filtros de búsqueda (referencial) -->
    <div class="card mb-4" style="padding: 16px;">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control bg-light border-0" placeholder="Buscar por distrito o universidad...">
            </div>
            <div class="col-md-3">
                <select class="form-select bg-light border-0">
                    <option value="">Tipo de cuarto</option>
                    <option value="1">Privado</option>
                    <option value="2">Compartido</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select bg-light border-0">
                    <option value="">Presupuesto</option>
                    <option value="1">Hasta S/ 500</option>
                    <option value="2">S/ 500 - S/ 800</option>
                    <option value="3">Más de S/ 800</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="fas fa-search me-2"></i> Buscar</button>
            </div>
        </div>
    </div>

    <!-- Resultados (referencial) -->
    <h4 class="fw-bold mb-3">Recomendados para ti</h4>
    <div class="row g-4">
        <!-- Tarjeta 1 -->
        <div class="col-md-4">
            <div class="card h-100 overflow-hidden border-0 shadow-sm">
                <div style="height: 200px; background: linear-gradient(135deg, var(--blue), #7BA0F6); display: flex; align-items: center; justify-content: center; position: relative;">
                    <i class="fas fa-bed text-white" style="font-size: 3rem; opacity: 0.8;"></i>
                    <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 fw-bold">S/ 850 / mes</span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">Cuarto Privado · Pueblo Libre</h5>
                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger"></i> A 10 min de PUCP</p>
                    
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary d-flex justify-content-center align-items-center text-white" style="width: 30px; height: 30px; font-size: 0.8rem; font-weight: bold;">
                                MG
                            </div>
                            <span class="small fw-semibold">María G. (Verificada)</span>
                        </div>
                        <span class="text-warning small"><i class="fas fa-star"></i> 4.8</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 2 -->
        <div class="col-md-4">
            <div class="card h-100 overflow-hidden border-0 shadow-sm">
                <div style="height: 200px; background: linear-gradient(135deg, var(--teal), #4FCBDA); display: flex; align-items: center; justify-content: center; position: relative;">
                    <i class="fas fa-bed text-white" style="font-size: 3rem; opacity: 0.8;"></i>
                    <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 fw-bold">S/ 700 / mes</span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">Habitación Amplia · San Miguel</h5>
                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger"></i> A 15 min de UPC</p>
                    
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary d-flex justify-content-center align-items-center text-white" style="width: 30px; height: 30px; font-size: 0.8rem; font-weight: bold;">
                                JR
                            </div>
                            <span class="small fw-semibold">José R.</span>
                        </div>
                        <span class="text-warning small"><i class="fas fa-star"></i> 4.5</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjeta 3 -->
        <div class="col-md-4">
            <div class="card h-100 overflow-hidden border-0 shadow-sm">
                <div style="height: 200px; background: linear-gradient(135deg, var(--amber), #F0AE5C); display: flex; align-items: center; justify-content: center; position: relative;">
                    <i class="fas fa-bed text-white" style="font-size: 3rem; opacity: 0.8;"></i>
                    <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 fw-bold">S/ 650 / mes</span>
                </div>
                <div class="card-body">
                    <h5 class="fw-bold mb-1">Cuarto Compartido · Jesús María</h5>
                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger"></i> Cerca a Universidad del Pacífico</p>
                    
                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-secondary d-flex justify-content-center align-items-center text-white" style="width: 30px; height: 30px; font-size: 0.8rem; font-weight: bold;">
                                AL
                            </div>
                            <span class="small fw-semibold">Ana L. (Verificada)</span>
                        </div>
                        <span class="text-warning small"><i class="fas fa-star"></i> 4.9</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
