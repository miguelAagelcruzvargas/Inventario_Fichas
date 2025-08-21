<?php
// clientes/subir_csv.php
session_start();
require_once '../config/security_check.php';

// IMPORTANTE: Incluir configuración de rutas ANTES del header
if (!defined('ACCESS_ALLOWED')) {
    define('ACCESS_ALLOWED', true);
}
require_once '../config/path_config.php';

// --- Configuración de Variables para Header/Menú ---
$page_title = "Carga Masiva de Clientes desde CSV"; 
$current_page = basename($_SERVER['PHP_SELF']); 
// Las variables de URL base ($baseUrl, etc.) ya están configuradas por path_config.php
// --- Fin Configuración de Variables ---

include_once "../layout/header.php";
include_once "cliente.php";
require_once "../config/conexion.php";

$mensaje = '';
$tipo_mensaje = '';
$errores_detallados = [];

// Token CSRF para mayor seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Estilos adicionales para asegurar compatibilidad
echo '<style>
.upload-area {
    border: 2px dashed #cbd5e0;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    background-color: #f8fafc;
}
.upload-area.drag-over {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
.upload-area:hover {
    border-color: #6b7280;
    background-color: #f1f5f9;
}
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}
.card {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
}
.card-header {
    background: #2563eb;
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
}
.card-body {
    padding: 1.5rem;
}
.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.375rem;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.btn-primary {
    background: #2563eb;
    color: white;
}
.btn-primary:hover {
    background: #1d4ed8;
}
.btn-secondary {
    background: #6b7280;
    color: white;
}
.btn-secondary:hover {
    background: #4b5563;
}
.alert {
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
}
.alert-success {
    background: #f0fdf4;
    border-color: #22c55e;
    color: #15803d;
}
.alert-warning {
    background: #fefce8;
    border-color: #eab308;
    color: #a16207;
}
.alert-error {
    background: #fef2f2;
    border-color: #ef4444;
    color: #dc2626;
}
.flex {
    display: flex;
}
.gap-2 {
    gap: 2rem;
}
.flex-wrap {
    flex-wrap: wrap;
}
</style>';

// Función para validar y limpiar datos del CSV
function validarDatosCSV($datos, $fila) {
    $errores = [];
    
    // Limpiar y asegurar que hay al menos 4 elementos
    $datos = array_map(function($item) {
        return trim(strip_tags($item ?? ''));
    }, $datos);
    
    // Rellenar con valores vacíos si faltan columnas
    while (count($datos) < 4) {
        $datos[] = '';
    }
    
    // Validar nombre completo (columna B - índice 1)
    $nombre = $datos[1];
    if (empty($nombre)) {
        $errores[] = "Nombre vacío o inválido";
    } elseif (strlen($nombre) < 3) {
        $errores[] = "Nombre demasiado corto (mínimo 3 caracteres)";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "Nombre demasiado largo (máximo 100 caracteres)";
    }
    
    // Validar número de tarjeta (columna C - índice 2)
    $numero_tarjeta = preg_replace('/\D/', '', $datos[2]);
    if (empty($numero_tarjeta)) {
        $errores[] = "Número de tarjeta vacío";
    } elseif (strlen($numero_tarjeta) < 1 || strlen($numero_tarjeta) > 5) {
        $errores[] = "Número de tarjeta inválido (debe tener entre 1-5 dígitos)";
    }
    
    // Validar dotación máxima (columna D - índice 3)
    $dotacion = filter_var($datos[3], FILTER_VALIDATE_INT);
    if ($dotacion === false || $dotacion < 1 || $dotacion > 999) {
        $errores[] = "Dotación inválida (debe ser entre 1-999 sobres). Valor recibido: '" . $datos[3] . "'";
    }
    
    return [
        'errores' => $errores,
        'datos' => [
            'nombre' => $nombre,
            'numero_tarjeta' => $numero_tarjeta,
            'dotacion' => $dotacion
        ]
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensaje = "Token de seguridad inválido. Recarga la página e inténtalo de nuevo.";
        $tipo_mensaje = "error";
    } elseif (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = "Error al subir el archivo. Por favor, inténtalo de nuevo.";
        $tipo_mensaje = "error";
    } else {
        $archivo = $_FILES['archivo_csv'];
        
        // Validaciones de archivo
        $extensiones_validas = ['csv'];
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $tamaño_maximo = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($extension, $extensiones_validas)) {
            $mensaje = "Solo se permiten archivos CSV (.csv).";
            $tipo_mensaje = "error";
        } elseif ($archivo['size'] > $tamaño_maximo) {
            $mensaje = "El archivo es demasiado grande. Máximo 5MB permitido.";
            $tipo_mensaje = "error";
        } else {
            $cliente = new Cliente();
            $archivo_tmp = $archivo['tmp_name'];
            
            // Verificar que el archivo sea realmente un CSV
            $handle = fopen($archivo_tmp, 'r');
            if ($handle === false) {
                $mensaje = "No se pudo leer el archivo CSV.";
                $tipo_mensaje = "error";
            } else {
                // Detectar y manejar BOM (Byte Order Mark) si existe
                $first_line = fgets($handle);
                rewind($handle);
                
                // Si hay BOM, usar UTF-8, si no, asumir que es correcto
                if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
                    error_log("DEBUG CSV - BOM detectado, archivo probablemente UTF-8 con BOM");
                } else {
                    error_log("DEBUG CSV - No se detectó BOM");
                }
                
                error_log("DEBUG CSV - Primera línea del archivo: " . bin2hex(substr($first_line, 0, 50)));
                
                $fila = 0;
                $clientes_nuevos = 0;
                $clientes_actualizados = 0;
                $errores_detallados = [];
                $filas_procesadas = 0;
                $filas_omitidas = 0;

                while (($linea = fgets($handle)) !== false) {
                    $fila++;
                    $linea_original = $linea; // Guardar copia para debug
                    
                    // Limpiar la línea de caracteres invisibles y BOM
                    $linea = trim($linea);
                    if (substr($linea, 0, 3) === "\xEF\xBB\xBF") {
                        $linea = substr($linea, 3);
                    }
                    
                    // Saltar líneas vacías
                    if (empty($linea)) {
                        continue;
                    }
                    
                    // DEBUG: Para las primeras líneas, mostrar información detallada
                    if ($fila <= 3) {
                        error_log("DEBUG CSV - Línea {$fila} original: " . bin2hex($linea_original));
                        error_log("DEBUG CSV - Línea {$fila} limpia: " . bin2hex($linea));
                    }
                    
                    // Manejar líneas que están completamente entre comillas
                    if (strlen($linea) > 1 && $linea[0] === '"' && $linea[-1] === '"') {
                        $linea = substr($linea, 1, -1);
                        error_log("DEBUG CSV - Línea {$fila} tenía comillas externas, removidas");
                    }
                    
                    // Usar str_getcsv para parsear la línea con mejor manejo de comillas
                    $datos = str_getcsv($linea, ',', '"');
                    
                    // Limpiar datos de espacios y caracteres invisibles
                    $datos = array_map(function($item) {
                        return trim($item);
                    }, $datos);
                    
                    // DEBUG: Para las primeras líneas, mostrar los datos parseados
                    if ($fila <= 3) {
                        error_log("DEBUG CSV - Línea {$fila} datos parseados: " . print_r($datos, true));
                        error_log("DEBUG CSV - Línea {$fila} número de columnas: " . count($datos));
                    }
                    
                    // Saltar encabezado
                    if ($fila === 1) {
                        // DEBUG: Mostrar información del encabezado
                        error_log("DEBUG CSV - Encabezado detectado: " . print_r($datos, true));
                        error_log("DEBUG CSV - Número de columnas en encabezado: " . count($datos));
                        
                        // Validar que el encabezado tenga al menos 4 columnas
                        if (count($datos) < 4) {
                            $errores_detallados[] = "El archivo CSV debe tener al menos 4 columnas (A, B, C, D). Detectadas: " . count($datos) . " columnas";
                            $errores_detallados[] = "SUGERENCIA: Si su archivo fue exportado desde Excel, intente guardarlo como 'CSV UTF-8' o 'CSV (separado por comas)'";
                            $errores_detallados[] = "DATOS DEL ENCABEZADO: " . implode(' | ', $datos);
                            break;
                        }
                        continue;
                    }

                    // Limitar a 1000 filas para evitar timeout
                    if ($fila > 1001) {
                        $errores_detallados[] = "Archivo demasiado grande. Máximo 1000 registros permitidos.";
                        break;
                    }

                    // Verificar que la fila tenga suficientes columnas
                    if (count($datos) < 4) {
                        // Diagnóstico adicional para problemas de formato
                        $diagnostico = "Detectadas: " . count($datos) . " columnas";
                        if (count($datos) === 1 && strpos($datos[0], ',') !== false) {
                            $diagnostico .= " (Posible problema: toda la línea está en una sola celda - verifique el formato CSV)";
                        }
                        $errores_detallados[] = "Fila {$fila}: Faltan columnas (se requieren al menos 4, {$diagnostico})";
                        $filas_omitidas++;
                        continue;
                    }

                    // DEBUG: Para las primeras 3 filas, mostrar los datos detectados
                    if ($fila <= 4) {
                        error_log("DEBUG CSV - Fila {$fila}: " . print_r($datos, true));
                    }

                    // Validar datos de la fila
                    $validacion = validarDatosCSV($datos, $fila);
                    
                    if (!empty($validacion['errores'])) {
                        $errores_detallados[] = "Fila {$fila}: " . implode(', ', $validacion['errores']);
                        $filas_omitidas++;
                        continue;
                    }

                    $datos_limpios = $validacion['datos'];
                    
                    try {
                        // Verificar si el cliente ya existe
                        if ($cliente->existeTarjeta($datos_limpios['numero_tarjeta'])) {
                            // Si ya existe, crear una nueva instancia para buscar el ID
                            $cliente_buscar = new Cliente();
                            
                            // Buscar cliente por número de tarjeta para obtener el ID
                            $conexion = new Conexion();
                            $conn = $conexion->conectar();
                            
                            if ($conn) {
                                $stmt = $conn->prepare("SELECT id FROM clientes WHERE numero_tarjeta = ?");
                                $stmt->execute([$datos_limpios['numero_tarjeta']]);
                                $cliente_existente = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($cliente_existente && $cliente->actualizarDotacionPorId($cliente_existente['id'], $datos_limpios['dotacion'])) {
                                    $clientes_actualizados++;
                                } else {
                                    $errores_detallados[] = "Fila {$fila}: Error al actualizar cliente con tarjeta {$datos_limpios['numero_tarjeta']}";
                                }
                            } else {
                                $errores_detallados[] = "Fila {$fila}: Error de conexión a la base de datos";
                            }
                        } else {
                            // Crear nuevo cliente usando el método crearCliente
                            if ($cliente->crearCliente($datos_limpios['numero_tarjeta'], $datos_limpios['nombre'], $datos_limpios['dotacion'])) {
                                $clientes_nuevos++;
                            } else {
                                $errores_detallados[] = "Fila {$fila}: Error al crear cliente {$datos_limpios['nombre']}";
                            }
                        }
                        $filas_procesadas++;
                        
                    } catch (Exception $e) {
                        $errores_detallados[] = "Fila {$fila}: Error de base de datos - " . $e->getMessage();
                        error_log("Error CSV upload: " . $e->getMessage());
                    }
                }

                fclose($handle);
                
                // Generar mensaje de resultado
                if ($filas_procesadas > 0) {
                    $mensaje = "Carga completada. ";
                    $mensaje .= "Nuevos clientes: <strong>{$clientes_nuevos}</strong>. ";
                    $mensaje .= "Clientes actualizados: <strong>{$clientes_actualizados}</strong>. ";
                    
                    if ($filas_omitidas > 0) {
                        $mensaje .= "Filas omitidas: <strong>{$filas_omitidas}</strong>.";
                    }
                    
                    $tipo_mensaje = ($clientes_nuevos > 0 || $clientes_actualizados > 0) ? "success" : "warning";
                } else {
                    $mensaje = "No se procesó ningún registro válido.";
                    $tipo_mensaje = "error";
                }
            }
        }
    }
}
?>

<div class="container">
    <div class="flex flex-wrap gap-2">
        <!-- Guía de formato CSV -->
        <div style="flex: 1; min-width: 300px;">
            <div class="card">
                <div class="card-header">
                    <h6 style="margin: 0; font-size: 1.125rem; font-weight: 600;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i>Formato Requerido del CSV
                    </h6>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse; border: 1px solid #d1d5db;">
                            <thead>
                                <tr style="background-color: #f3f4f6;">
                                    <th style="border: 1px solid #d1d5db; padding: 0.5rem; text-align: left;">Col</th>
                                    <th style="border: 1px solid #d1d5db; padding: 0.5rem; text-align: left;">Campo</th>
                                    <th style="border: 1px solid #d1d5db; padding: 0.5rem; text-align: left;">Descripción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;">
                                        <span style="background: #6b7280; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">A</span>
                                    </td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><small>Folio</small></td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><small style="color: #6b7280;">Se ignora</small></td>
                                </tr>
                                <tr style="background-color: #f0fdf4;">
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;">
                                        <span style="background: #22c55e; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">B</span>
                                    </td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><strong>Nombre</strong></td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><small>Nombre completo del cliente</small></td>
                                </tr>
                                <tr style="background-color: #fefce8;">
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;">
                                        <span style="background: #eab308; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">C</span>
                                    </td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><strong>No. Tarjeta</strong></td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><small>Número de tarjeta único</small></td>
                                </tr>
                                <tr style="background-color: #eff6ff;">
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;">
                                        <span style="background: #3b82f6; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">D</span>
                                    </td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><strong>Sobres</strong></td>
                                    <td style="border: 1px solid #d1d5db; padding: 0.5rem;"><small>Dotación máxima (1-999)</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <h6 style="color: #2563eb; font-weight: 600; margin-bottom: 0.5rem;">Ejemplo de CSV:</h6>
                        <div style="background: #f3f4f6; padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; font-family: monospace;">
                            Folio,Nombre,No. Tarjeta,Sobres<br>
                            1,Juan Pérez López,12345,10<br>
                            2,María González,87654,15<br>
                            3,Pedro Martínez,11223,8
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem;">
                        <h6 style="color: #dc2626; font-weight: 600; margin-bottom: 0.5rem;">Restricciones:</h6>
                        <ul style="font-size: 0.875rem; color: #374151; margin: 0; padding-left: 1rem;">
                            <li>• Máximo 1000 registros</li>
                            <li>• Archivo máximo 5MB</li>
                            <li>• Solo formato .csv</li>
                            <li>• Codificación UTF-8</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Botón para descargar plantilla -->
            <div class="card" style="margin-top: 1.5rem;">
                <div style="padding: 1.5rem; text-align: center;">
                    <h6 style="color: #059669; font-weight: 600; margin-bottom: 0.5rem;">
                        <i class="fas fa-download" style="margin-right: 0.5rem;"></i>Plantilla CSV
                    </h6>
                    <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">Descarga una plantilla con el formato correcto</p>
                    <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/generar_plantilla_csv.php" class="btn" style="background: #059669; color: white;">
                        <i class="fas fa-file-csv" style="margin-right: 0.5rem;"></i> Descargar Plantilla
                    </a>
                </div>
            </div>
        </div>

        <!-- Formulario de carga -->
        <div style="flex: 2; min-width: 400px;">
            <div class="card">
                <div class="card-header">
                    <h5 style="margin: 0; font-size: 1.25rem; font-weight: 600;">
                        <i class="fas fa-file-csv" style="margin-right: 0.75rem;"></i> Cargar Clientes desde CSV
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                            <div style="display: flex; align-items: flex-start;">
                                <i class="fas fa-<?php 
                                    echo $tipo_mensaje === 'success' ? 'check-circle' : 
                                        ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); 
                                ?>" style="margin-right: 0.75rem; margin-top: 0.25rem;"></i>
                                <div style="flex: 1;">
                                    <?php echo $mensaje; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errores_detallados)): ?>
                        <div class="alert alert-warning">
                            <h6 style="font-weight: 600; margin-bottom: 0.75rem;">
                                <i class="fas fa-exclamation-triangle" style="margin-right: 0.5rem;"></i>Errores Encontrados:
                            </h6>
                            <div style="max-height: 200px; overflow-y: auto;">
                                <ul style="font-size: 0.875rem; margin: 0; padding-left: 1rem;">
                                    <?php foreach (array_slice($errores_detallados, 0, 20) as $error): ?>
                                        <li style="margin-bottom: 0.25rem;">• <?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                    <?php if (count($errores_detallados) > 20): ?>
                                        <li style="color: #6b7280;">... y <?php echo count($errores_detallados) - 20; ?> errores más</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div style="margin-top: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 0.375rem; font-size: 0.875rem;">
                                <strong>Sugerencias:</strong>
                                <ul style="margin: 0.5rem 0 0 1rem;">
                                    <li>• Asegúrese de que el archivo tenga exactamente 4 columnas: Folio, Nombre, No. Tarjeta, Sobres</li>
                                    <li>• Guarde el archivo como CSV UTF-8 desde Excel o Google Sheets</li>
                                    <li>• Verifique que no haya celdas vacías en las columnas B, C y D</li>
                                    <li>• Los números de tarjeta deben ser solo números</li>
                                    <li>• La dotación de sobres debe ser un número entre 1 y 999</li>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" id="csvForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label for="archivo_csv" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">
                                <i class="fas fa-file-upload" style="margin-right: 0.5rem;"></i>Seleccionar Archivo CSV
                            </label>
                            <div class="upload-area" id="dropZone">
                                <input type="file" id="archivo_csv" name="archivo_csv" accept=".csv,text/csv" required style="display: none;">
                                <div id="fileDisplay" style="color: #6b7280;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; margin-bottom: 1rem; color: #9ca3af;"></i>
                                    <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem;">Arrastre su archivo CSV aquí</p>
                                    <p style="font-size: 0.875rem; margin-bottom: 1rem;">o</p>
                                    <button type="button" onclick="document.getElementById('archivo_csv').click()" class="btn btn-primary">
                                        Seleccionar Archivo
                                    </button>
                                </div>
                            </div>
                            <div style="margin-top: 0.75rem; font-size: 0.875rem; color: #2563eb;">
                                <i class="fas fa-info-circle" style="margin-right: 0.25rem;"></i>
                                El archivo debe seguir el formato mostrado en la guía de la izquierda.
                            </div>
                            <div id="fileError" style="margin-top: 0.5rem; font-size: 0.875rem; color: #dc2626; display: none;">
                                <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i>
                                <span id="errorText"></span>
                            </div>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
                            <button type="submit" class="btn btn-primary" style="background: #059669;">
                                <i class="fas fa-upload" style="margin-right: 0.5rem;"></i>Procesar CSV
                            </button>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/listar_clientes.php" class="btn btn-secondary">
                                    <i class="fas fa-list" style="margin-right: 0.5rem;"></i>Ver Clientes
                                </a>
                                <a href="<?php echo htmlspecialchars($baseUrl); ?>/clientes/agregar_cliente.php" class="btn btn-secondary">
                                    <i class="fas fa-user-plus" style="margin-right: 0.5rem;"></i>Agregar Manual
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Funciones de utilidad
function showError(message) {
    const errorDiv = document.getElementById('fileError');
    const errorText = document.getElementById('errorText');
    errorText.textContent = message;
    errorDiv.style.display = 'block';
}

function hideError() {
    document.getElementById('fileError').style.display = 'none';
}

function clearFile() {
    document.getElementById('archivo_csv').value = '';
    resetFileDisplay();
}

function resetFileDisplay() {
    document.getElementById('fileDisplay').innerHTML = `
        <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; margin-bottom: 1rem; color: #9ca3af;"></i>
        <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem;">Arrastre su archivo CSV aquí</p>
        <p style="font-size: 0.875rem; margin-bottom: 1rem;">o</p>
        <button type="button" onclick="document.getElementById('archivo_csv').click()" class="btn btn-primary">
            Seleccionar Archivo
        </button>
    `;
    hideError();
}

// Validación del formulario
document.getElementById('csvForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('archivo_csv');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        e.preventDefault();
        showError('Debe seleccionar un archivo CSV válido.');
        return false;
    }
});

// Validación en tiempo real del archivo CSV
document.getElementById('archivo_csv').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const fileDisplay = document.getElementById('fileDisplay');
    
    if (file) {
        // Verificar extensión
        const extension = file.name.split('.').pop().toLowerCase();
        if (extension !== 'csv') {
            showError('Solo se permiten archivos CSV');
            this.value = '';
            return;
        }
        
        // Verificar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showError('El archivo es demasiado grande (máximo 5MB)');
            this.value = '';
            return;
        }
        
        // Todo correcto - mostrar información del archivo
        hideError();
        fileDisplay.innerHTML = `
            <div style="color: #059669;">
                <i class="fas fa-file-csv" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem;">Archivo seleccionado:</p>
                <p style="font-size: 0.875rem; font-weight: 600;">${file.name}</p>
                <p style="font-size: 0.75rem; color: #6b7280;">${(file.size / 1024).toFixed(1)} KB</p>
                <button type="button" onclick="clearFile()" style="margin-top: 0.75rem; color: #dc2626; font-size: 0.875rem; background: none; border: none; cursor: pointer;">
                    <i class="fas fa-trash" style="margin-right: 0.25rem;"></i>Quitar archivo
                </button>
            </div>
        `;
    } else {
        resetFileDisplay();
    }
});

// Arrastrar y soltar archivo
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('archivo_csv');

// Prevenir comportamiento por defecto
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, function(e) {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

// Resaltar zona de drop
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, function(e) {
        dropZone.classList.add('drag-over');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, function(e) {
        dropZone.classList.remove('drag-over');
    }, false);
});

// Manejar archivos soltados
dropZone.addEventListener('drop', function(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
}, false);
</script>

<?php include_once "../layout/footer.php"; ?>
