<?php
// Script para verificar y regenerar el hash de contraseña del admin

$password = 'admin123';
$currentHash = '$2y$10$2NLDxm.85K/2AOmXWGyS9OZSGSruxcOeEJcq.LAF9Q4VPduUEsqhC';

echo "Contraseña a verificar: $password\n";
echo "Hash actual: $currentHash\n";
echo "¿Hash actual es válido?: " . (password_verify($password, $currentHash) ? "SÍ" : "NO") . "\n";

// Generar un nuevo hash
$newHash = password_hash($password, PASSWORD_DEFAULT);
echo "Nuevo hash generado: $newHash\n";
echo "¿Nuevo hash es válido?: " . (password_verify($password, $newHash) ? "SÍ" : "NO") . "\n";

// Probar con contraseña simple "admin"
$password2 = 'admin';
echo "\nProbando con contraseña 'admin':\n";
echo "¿Hash actual funciona con 'admin'?: " . (password_verify($password2, $currentHash) ? "SÍ" : "NO") . "\n";

// Nuevo hash para 'admin'
$adminHash = password_hash($password2, PASSWORD_DEFAULT);
echo "Hash para 'admin': $adminHash\n";
?>
