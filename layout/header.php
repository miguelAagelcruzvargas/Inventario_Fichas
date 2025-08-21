<?php
// layout/header.php (CON MENÚ INTEGRADO, PARA USAR CON TAILWIND CSS)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración centralizada de rutas si no está ya incluida
if (!function_exists('getBaseUrl')) {
    if (!defined('ACCESS_ALLOWED')) {
        define('ACCESS_ALLOWED', true);
    }
    require_once __DIR__ . '/../config/path_config.php';
}

// Inicializar variables de rutas si no están definidas
if (!isset($baseUrl)) {
    initializePathVariables();
}

if (!isset($page_title)) {
    $page_title = "Gestión Liconsa";
}
// $baseUrl y $current_page deben definirse en la página que incluye este header
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Gestión Liconsa'; ?> - Liconsa</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        // Configuración base de Tailwind (puedes expandirla o moverla a un archivo JS global)
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        'liconsa-blue': '#1D4ED8', 
                        'liconsa-green': '#059669',
                        // Puedes añadir más colores personalizados aquí
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', 'sans-serif';
            background-color: #f3f4f6; /* bg-gray-100 de Tailwind */
            color: #374151; /* text-gray-700 de Tailwind */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-content-wrapper {
            flex: 1 0 auto;
        }
        /* Estilos básicos para el menú si no usas clases Tailwind directamente en el nav */
        /* O puedes quitar esto y usar solo clases Tailwind en el <nav> de abajo */
        .custom-navbar-tailwind {
            /* Ejemplo: bg-gray-800 text-white shadow-md */
        }
    </style>
</head>
<body class="antialiased">

    <nav class="bg-gray-800 shadow-md">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/index.php" class="text-white flex items-center">
                        <i class="fas fa-address-book mr-2 text-xl text-sky-400"></i>
                        <div>
                            <span class="font-semibold text-lg hover:text-sky-300">Gestión Leche</span>
                            <span class="block text-xs text-gray-400">LICONSA</span>
                        </div>
                    </a>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-1">
                        <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/index.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium flex items-center <?php echo (isset($current_page) && $current_page == 'index.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                           <i class="fas fa-home mr-2"></i>Inicio
                        </a>
                        <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/listar_clientes.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium flex items-center <?php echo (isset($current_page) && $current_page == 'listar_clientes.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                           <i class="fas fa-users mr-2"></i>Activos
                        </a>
                        <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/listar_clientes_inactivos.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium flex items-center <?php echo (isset($current_page) && $current_page == 'listar_clientes_inactivos.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                           <i class="fas fa-user-slash mr-2"></i>Inactivos
                        </a>
                        <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/agregar_cliente.php" 
                           class="px-3 py-2 rounded-md text-sm font-medium flex items-center <?php echo (isset($current_page) && $current_page == 'agregar_cliente.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                           <i class="fas fa-user-plus mr-2"></i>Agregar
                        </a>
                         <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/resumen/dashboard.php"
                           class="px-3 py-2 rounded-md text-sm font-medium flex items-center <?php echo (isset($current_page) && $current_page == 'dashboard.php' && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/resumen/') !== false) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?>">
                            <i class="fas fa-tachometer-alt mr-2"></i>Inventarios
                        </a>
                        <span class="text-gray-400 px-3 py-2 rounded-md text-sm font-medium flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i><?php echo date("d/m/Y"); ?>
                        </span>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium flex items-center">
                                <i class="fas fa-cog mr-1"></i> Herramientas <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 z-20" style="display:none;">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="document.getElementById('calculatorModal').style.display='block'; return false;">
                                    <i class="fas fa-calculator mr-2"></i>Calculadora
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="-mr-2 flex md:hidden">
                    <button type="button" class="bg-gray-800 inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button-tailwind">
                        <span class="sr-only">Abrir menú principal</span>
                        <i class="fas fa-bars" id="mobile-menu-icon-open"></i>
                        <i class="fas fa-times hidden" id="mobile-menu-icon-close"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden hidden" id="mobile-menu-tailwind">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/index.php" class="<?php echo (isset($current_page) && $current_page == 'index.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-home mr-2"></i>Inicio</a>
                <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/listar_clientes.php" class="<?php echo (isset($current_page) && $current_page == 'listar_clientes.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-users mr-2"></i>Activos</a>
                <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/listar_clientes_inactivos.php" class="<?php echo (isset($current_page) && $current_page == 'listar_clientes_inactivos.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-user-slash mr-2"></i>Inactivos</a>
                <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/clientes/agregar_cliente.php" class="<?php echo (isset($current_page) && $current_page == 'agregar_cliente.php') ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-user-plus mr-2"></i>Agregar</a>
                <a href="<?php echo isset($baseUrl) ? htmlspecialchars($baseUrl) : ''; ?>/resumen/dashboard.php" class="<?php echo (isset($current_page) && $current_page == 'dashboard.php' && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/resumen/') !== false) ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'; ?> block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</a>
                <a href="#" class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium" onclick="document.getElementById('calculatorModal').style.display='block'; return false;"><i class="fas fa-calculator mr-2"></i>Calculadora</a>
                <span class="text-gray-400 block px-3 py-2 rounded-md text-base font-medium"><i class="fas fa-calendar-alt mr-2"></i><?php echo date("d/m/Y"); ?></span>
            </div>
        </div>
        <script src="//unpkg.com/alpinejs" defer></script>
        <script>
            const btnMobile = document.getElementById('mobile-menu-button-tailwind');
            const menuMobile = document.getElementById('mobile-menu-tailwind');
            const iconOpen = document.getElementById('mobile-menu-icon-open');
            const iconClose = document.getElementById('mobile-menu-icon-close');
            if (btnMobile && menuMobile && iconOpen && iconClose) {
                btnMobile.addEventListener('click', () => {
                    menuMobile.classList.toggle('hidden');
                    iconOpen.classList.toggle('hidden');
                    iconClose.classList.toggle('hidden');
                });
            }
        </script>
    </nav>
    <div class="main-content-wrapper">
        <?php // El contenido de la página (ej. listar_clientes.php) se renderizará aquí.
              // Este div se cerrará en footer.php.
?>
