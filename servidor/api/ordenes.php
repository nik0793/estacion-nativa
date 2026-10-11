<?php
// GET ?estacion=ID&clave=...  -> [{"id":12,"tipo":"comida"},{"id":14,"tipo":"horario","valor":"8,17"}]
// Marca las órdenes como entregadas al devolverlas.
require __DIR__ . '/../config.php';

exigirClave($_GET['clave'] ?? null);
$estacion = idEstacionValido($_GET['estacion'] ?? null);

$pdo = db();
$st = $pdo->prepare('SELECT id, tipo, valor FROM ordenes WHERE estacion = ? AND entregada IS NULL ORDER BY id');
$st->execute([$estacion]);
$ordenes = $st->fetchAll();

if ($ordenes) {
    $ids = array_column($ordenes, 'id');
    $marcas = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("UPDATE ordenes SET entregada = ? WHERE id IN ($marcas)")->execute(array_merge([ahora()], $ids));
}

$salida = array_map(function ($o) {
    $r = ['id' => (int)$o['id'], 'tipo' => $o['tipo']];
    if ($o['valor'] !== null) $r['valor'] = $o['valor'];
    return $r;
}, $ordenes);

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($salida, JSON_UNESCAPED_UNICODE);
