<?php
// POST multipart: estacion, clave, tomada (AAAA-MM-DD HH:MM:SS o vacío), archivo (jpg)
require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(405, ['ok' => false]);
exigirClave($_POST['clave'] ?? null);
$estacion = idEstacionValido($_POST['estacion'] ?? null);

if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) responder(400, ['ok' => false, 'error' => 'archivo']);
$tmp = $_FILES['archivo']['tmp_name'];
$bytes = (int)$_FILES['archivo']['size'];
if ($bytes < 1000 || $bytes > MAX_FOTO_BYTES) responder(400, ['ok' => false, 'error' => 'tamano']);
$info = @getimagesize($tmp);
if (!$info || $info[2] !== IMAGETYPE_JPEG) responder(400, ['ok' => false, 'error' => 'no es jpg']);

$tomada = (string)($_POST['tomada'] ?? '');
$fecha = DateTime::createFromFormat('Y-m-d H:i:s', $tomada) ?: new DateTime();
$carpeta = DIR_FOTOS . '/' . $fecha->format('Y') . '/' . $fecha->format('m');
if (!is_dir($carpeta) && !mkdir($carpeta, 0755, true)) responder(500, ['ok' => false, 'error' => 'carpeta']);
if (!is_file(DIR_FOTOS . '/.htaccess')) {
    file_put_contents(DIR_FOTOS . '/.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
}

$nombre = $fecha->format('Ymd_His') . '_' . $estacion . '_' . bin2hex(random_bytes(3)) . '.jpg';
if (!move_uploaded_file($tmp, $carpeta . '/' . $nombre)) responder(500, ['ok' => false, 'error' => 'guardar']);

$relativo = $fecha->format('Y') . '/' . $fecha->format('m') . '/' . $nombre;
$st = db()->prepare('INSERT INTO fotos (estacion, tomada, recibida, archivo, bytes) VALUES (?, ?, ?, ?, ?)');
$st->execute([$estacion, $tomada !== '' ? $fecha->format('Y-m-d H:i:s') : null, ahora(), $relativo, $bytes]);

responder(200, ['ok' => true, 'id' => (int)db()->lastInsertId(), 'archivo' => $relativo]);
