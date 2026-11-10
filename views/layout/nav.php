<!-- Barra Superior Móvil con botón Hamburguesa -->
<header class="top-bar">
    <button type="button" class="btn-icon" id="btnSidebarToggle" aria-label="Abrir Menú de Navegación">
        <span class="hamburger-icon">☰</span>
    </button>
    <a href="<?= url('dashboard') ?>" class="top-bar-brand">
        <span class="brand-emoji">🍦</span>
        <span class="brand-name">SIVE 2.0</span>
    </a>
    <div class="top-bar-actions">
        <a href="<?= url('ventas/crear') ?>" class="btn btn-sm btn-primary" title="Nueva Venta">
            <span>+ Venta</span>
        </a>
    </div>
</header>

<!-- Overlay de oscurecimiento para pantallas móviles -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Barra lateral de navegación (Sidebar) -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?= url('dashboard') ?>" class="sidebar-brand">
            <span class="brand-emoji">🍦</span>
            <div class="brand-info">
                <span class="brand-title">SIVE 2.0</span>
                <span class="brand-subtitle">Heladería & Cremolada</span>
            </div>
        </a>
        <button type="button" class="btn-icon sidebar-close-btn" id="btnSidebarClose" aria-label="Cerrar Menú">
            &times;
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= url('dashboard') ?>" class="nav-link <?= isActive('dashboard') ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('ventas/crear') ?>" class="nav-link <?= isActive('ventas/crear') ? 'active' : '' ?>">
                    <span class="nav-icon">🛒</span>
                    <span class="nav-label">Nueva Venta</span>
                    <span class="badge badge-pill badge-primary">POS</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('ventas') ?>" class="nav-link <?= (isActive('ventas') && !isActive('ventas/crear')) ? 'active' : '' ?>">
                    <span class="nav-icon">📋</span>
                    <span class="nav-label">Ventas del Día</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('ventas-atrasadas') ?>" class="nav-link <?= (isActive('ventas-atrasadas') || isActive('ventas_atrasadas')) ? 'active' : '' ?>">
                    <span class="nav-icon">📝</span>
                    <span class="nav-label">Ventas Atrasadas</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('caja') ?>" class="nav-link <?= isActive('caja') ? 'active' : '' ?>">
                    <span class="nav-icon">💰</span>
                    <span class="nav-label">Caja</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('inventario') ?>" class="nav-link <?= isActive('inventario') ? 'active' : '' ?>">
                    <span class="nav-icon">📦</span>
                    <span class="nav-label">Inventario</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('reposiciones') ?>" class="nav-link <?= isActive('reposiciones') ? 'active' : '' ?>">
                    <span class="nav-icon">🔄</span>
                    <span class="nav-label">Reposiciones</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= url('config') ?>" class="nav-link <?= isActive('config') ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span class="nav-label">Configuración</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <button type="button" class="btn btn-outline btn-block btn-dark-toggle" id="btnDarkModeToggle">
            <span class="toggle-icon">🌙</span>
            <span class="toggle-text">Modo Oscuro</span>
        </button>
        <div class="sidebar-meta">
            <span class="version-badge">v2.0</span>
            <span class="app-status">Online</span>
        </div>
    </div>
</aside>

<!-- Contenedor Principal de Contenido Dinámico -->
<main class="main-content" id="mainContent">
