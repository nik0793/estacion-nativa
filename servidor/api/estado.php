<?php
// POST JSON: {estacion, clave, pila_mv, pendientes, tanque, causa, version, arranques}
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['ok' => false]);
$d = json_decode(file_get_contents('php://input'), true);
if (!is_array($d)) responder(400, ['ok' => false, 'error' => 'json']);
exigirClave($d['clave'] ?? null);
$estacion = idEstacionValido($d['estacion'] ?? null);

$pila = isset($d['pila_mv']) ? (int)$d['pila_mv'] : null;
$pendientes = (int)($d['pendientes'] ?? 0);
$tanque = in_array($d['tanque'] ?? 'na', ['ok', 'bajo', 'na'], true) ? $d['tanque'] : 'na';
$causa = substr((string)($d['causa'] ?? ''), 0, 16);
$version = substr((string)($d['version'] ?? ''), 0, 16);
$arranques = isset($d['arranques']) ? (int)$d['arranques'] : null;

$pdo = db();
$pdo->prepare('INSERT OR IGNORE INTO estaciones (id, nombre, tipo) VALUES (?, ?, ?)')->execute([$estacion, $estacion, 'base']);
$pdo->prepare('UPDATE estaciones SET ultimo_contacto = ?, pila_mv = ?, pendientes = ?, tanque = ?, causa = ?, version = ?, arranques = ? WHERE id = ?')
    ->execute([ahora(), $pila, $pendientes, $tanque, $causa, $version, $arranques, $estacion]);

$avisos = [];
if ($pila !== null && $pila < 3600) $avisos[] = "Pila baja en $estacion: " . round($pila / 1000, 2) . ' V';
if ($tanque === 'bajo') $avisos[] = "Tanque de agua bajo en $estacion";
foreach ($avisos as $texto) {
    $existe = $pdo->prepare('SELECT COUNT(*) FROM avisos WHERE estacion = ? AND texto = ? AND visto = 0');
    $existe->execute([$estacion, $texto]);
    if ((int)$existe->fetchColumn() === 0) {
        $pdo->prepare('INSERT INTO avisos (estacion, texto, creado) VALUES (?, ?, ?)')->execute([$estacion, $texto, ahora()]);
    }
}

responder(200, ['ok' => true, 'hora' => ahora()]);
