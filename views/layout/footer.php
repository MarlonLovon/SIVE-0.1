</main>
</div><!-- /.app-container -->

<?php
// Recuperación y renderizado de mensajes flash de sesión
$flash = null;
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($flash && !empty($flash['message'])):
    $flashType = $flash['type'] ?? 'info';
    $alertType = match($flashType) {
        'error', 'danger' => 'danger',
        'success' => 'success',
        'warning' => 'warning',
        default => 'info'
    };
?>
<div class="toast-container" id="toastContainer">
    <div class="toast alert alert-<?= e($alertType) ?> alert-dismissible" role="alert">
        <div class="toast-content">
            <span class="toast-icon">
                <?php if ($alertType === 'success'): ?>✅
                <?php elseif ($alertType === 'danger'): ?>❌
                <?php elseif ($alertType === 'warning'): ?>⚠️
                <?php else: ?>ℹ️<?php endif; ?>
            </span>
            <span class="toast-message"><?= e($flash['message']) ?></span>
        </div>
        <button type="button" class="btn-close" onclick="this.closest('.toast').remove()" aria-label="Cerrar">&times;</button>
    </div>
</div>
<?php endif; ?>

<!-- JavaScript Global SIVE 2.0 -->
<script src="<?= asset('js/app.js') ?>"></script>

<!-- JavaScript específico de la vista activa si fue definido -->
<?php if (isset($pageScript) && !empty($pageScript)): ?>
    <script src="<?= asset('js/' . $pageScript) ?>"></script>
<?php endif; ?>

</body>
</html>
