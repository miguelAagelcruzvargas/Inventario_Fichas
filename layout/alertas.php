<?php
if (!isset($tipo_mensaje)) $tipo_mensaje = $_GET['tipo'] ?? 'info'; // info, success, warning, error
$mensaje_alerta = $_GET['mensaje'] ?? '';

if ($mensaje_alerta !== ''):
    $clases = match ($tipo_mensaje) {
        'success' => 'bg-green-100 text-green-800 border-green-300',
        'error' => 'bg-red-100 text-red-800 border-red-300',
        'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
        default => 'bg-blue-100 text-blue-800 border-blue-300'
    };
?>
<div id="alerta-mensaje" class="border rounded p-3 mb-6 text-sm <?php echo $clases; ?>">
    <?php echo htmlspecialchars($mensaje_alerta); ?>
</div>

<script>
// Desaparecer el mensaje después de 5 segundos
setTimeout(() => {
    const alerta = document.getElementById('alerta-mensaje');
    if (alerta) alerta.style.display = 'none';
}, 5000);
</script>
<?php endif; ?>
