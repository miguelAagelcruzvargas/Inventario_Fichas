<?php
if (!isset($_SESSION)) session_start();
if (!isset($pdo)) require_once __DIR__ . '/../config/conexion.php';

if (!isset($_SESSION['inventario_id'])) return;

$inventario_id = (int) $_SESSION['inventario_id'];

// Obtener inventario activo
$stmt = $pdo->prepare("SELECT * FROM inventarios WHERE id = ?");
$stmt->execute([$inventario_id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inv) return;

// Obtener totales desde retiros
$stmt = $pdo->prepare("
    SELECT 
      SUM(sobres_retirados) AS total_sobres,
      SUM(monto_total) AS total_monto
    FROM retiros
    WHERE inventario_id = ? AND retiro_hecho = 1
");
$stmt->execute([$inventario_id]);
$totales = $stmt->fetch(PDO::FETCH_ASSOC);

// Cálculos
$sobres_total = (int) $inv['cajas_ingresadas'] * (int) $inv['sobres_por_caja'];
$sobres_retirados = (int) ($totales['total_sobres'] ?? 0);
$monto_total = (float) ($totales['total_monto'] ?? 0.00);
$sobres_restantes = $sobres_total - $sobres_retirados;
$cajas_restantes = intdiv($sobres_restantes, (int) $inv['sobres_por_caja']);
$sobres_sueltos = $sobres_restantes % (int) $inv['sobres_por_caja'];
?>

<div class="bg-gray-100 border border-blue-200 rounded-md p-4 mb-6 text-sm text-gray-700">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
    <div><strong>Cajas Ingresadas:</strong> <?php echo (int) $inv['cajas_ingresadas']; ?></div>
    <div><strong>Sobres Totales:</strong> <?php echo $sobres_total; ?></div>
    <div><strong>Sobres Retirados:</strong> <?php echo $sobres_retirados; ?></div>
    <div><strong>Sobres Restantes:</strong> <?php echo $sobres_restantes; ?></div>
    <div><strong>Cajas Restantes:</strong> <?php echo $cajas_restantes . " cajas y " . $sobres_sueltos . " sobres"; ?></div>
    <div><strong>Monto Total:</strong> $<?php echo number_format($monto_total, 2); ?></div>
  </div>
</div>
