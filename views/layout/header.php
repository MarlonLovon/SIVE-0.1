<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>SIVE 2.0 - Heladería & Cremoladas</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Hoja de estilos principal -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    
    <!-- Configuración Global JS -->
    <script>
        const BASE_URL = '<?= rtrim(BASE_URL, '/') ?>/';
        // Aplicar clase modo oscuro inmediatamente para evitar parpadeo blanco
        if (localStorage.getItem('sive_theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
</head>
<body class="<?= (isset($_COOKIE['sive_theme']) && $_COOKIE['sive_theme'] === 'dark') ? 'dark-mode' : '' ?>">
<div class="app-container">
