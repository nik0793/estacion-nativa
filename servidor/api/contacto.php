<?php
// Formulario de contacto de la web. POST (form o JSON): nombre, correo, mensaje, web (trampa: debe venir vacío).
// Guarda en la tabla contactos (se ven en /app/) y, si CORREO_AVISOS está configurado, avisa por mail.
require __DIR__ . '/../config.php';

$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origen !== '' && in_array($origen, preg_split('/\s+/', ORIGENES_WEB), true)) {
    header('Access-Control-Allow-Origin: ' . $origen);
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'HEAD') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['ok' => false]);

$d = $_POST;
if (!$d) { $json = json_decode(file_get_contents('php://input'), true); if (is_array($json)) $d = $json; }

$trampa = trim((string)($d['web'] ?? ''));
$nombre = trim((string)($d['nombre'] ?? ''));
$correo = trim((string)($d['correo'] ?? ''));
$mensaje = trim((string)($d['mensaje'] ?? ''));
if ($trampa !== '') responder(200, ['ok' => true]);   // bot: fingir éxito
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 80) responder(400, ['ok' => false, 'error' => 'nombre']);
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 120) responder(400, ['ok' => false, 'error' => 'correo']);
if (mb_strlen($mensaje) < 5 || mb_strlen($mensaje) > 2000) responder(400, ['ok' => false, 'error' => 'mensaje']);

$pdo = db();
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$hace1h = date('Y-m-d H:i:s', time() - 3600);
$st = $pdo->prepare('SELECT COUNT(*) FROM contactos WHERE origen = ? AND creado >= ?');
$st->execute([$ip, $hace1h]);
if ((int)$st->fetchColumn() >= 5) responder(429, ['ok' => false, 'error' => 'demasiados']);

$pdo->prepare('INSERT INTO contactos (nombre, correo, mensaje, origen, creado) VALUES (?, ?, ?, ?, ?)')
    ->execute([$nombre, $correo, $mensaje, $ip, ahora()]);

if (CORREO_AVISOS !== '') {
    $asunto = '=?UTF-8?B?' . base64_encode('Estación Nativa: mensaje de ' . $nombre) . '?=';
    $cuerpo = "Nombre: $nombre\nCorreo: $correo\n\n$mensaje\n";
    @mail(CORREO_AVISOS, $asunto, $cuerpo, "From: " . CORREO_AVISOS . "\r\nReply-To: $correo\r\nContent-Type: text/plain; charset=UTF-8");
}

$quiereJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
if (!$quiereJson && isset($_SERVER['HTTP_REFERER'])) { header('Location: ../?gracias=1#participar'); exit; }
responder(200, ['ok' => true]);
