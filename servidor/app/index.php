<?php
// Panel para quien cuida el predio: estado, avisos, órdenes y mensajes del formulario. Clave única: CLAVE_APP en config.local.php.
require __DIR__ . '/../config.php';
session_start();

if (CLAVE_APP === '') { http_response_code(503); echo 'La app no está habilitada: falta CLAVE_APP en config.local.php.'; exit; }
if (isset($_POST['clave_app'])) {
    if (hash_equals(CLAVE_APP, (string)$_POST['clave_app'])) $_SESSION['app'] = true;
    header('Location: ./'); exit;
}
if (isset($_GET['salir'])) { session_destroy(); header('Location: ./'); exit; }
$pdo = db();

if (empty($_SESSION['app'])):
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Estación Nativa · app</title>
<style>body{font-family:"Source Sans 3",Arial,sans-serif;background:#f6f1e8;display:grid;place-items:center;height:100vh;margin:0}form{background:#fff;padding:24px;border-radius:16px}input,button{font-size:1rem;padding:10px;border-radius:8px;border:1px solid #ccc}button{background:#1f5e3a;color:#fff;border:0}</style></head>
<body><form method="post"><h3>Estación Nativa</h3><input type="password" name="clave_app" placeholder="Clave" autofocus> <button>Entrar</button></form></body></html>
<?php exit; endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['orden'], $_POST['estacion'])) {
    $tipo = in_array($_POST['orden'], ['comida', 'foto', 'horario'], true) ? $_POST['orden'] : null;
    $estacion = preg_match('/^[a-z0-9-]{3,32}$/', $_POST['estacion']) ? $_POST['estacion'] : null;
    $valor = $tipo === 'horario' ? preg_replace('/[^0-9,]/', '', $_POST['valor'] ?? '') : null;
    if ($tipo && $estacion) {
        $pdo->prepare('INSERT INTO ordenes (estacion, tipo, valor, creada) VALUES (?, ?, ?, ?)')->execute([$estacion, $tipo, $valor, ahora()]);
    }
    header('Location: ./'); exit;
}
if (isset($_GET['visto'])) { $pdo->prepare('UPDATE avisos SET visto = 1 WHERE id = ?')->execute([(int)$_GET['visto']]); header('Location: ./'); exit; }
if (isset($_GET['leido'])) { $pdo->prepare('UPDATE contactos SET leido = 1 WHERE id = ?')->execute([(int)$_GET['leido']]); header('Location: ./'); exit; }

$estaciones = $pdo->query('SELECT * FROM estaciones ORDER BY id')->fetchAll();
$avisos = $pdo->query('SELECT * FROM avisos WHERE visto = 0 ORDER BY creado DESC')->fetchAll();
$pendientes = $pdo->query('SELECT * FROM ordenes WHERE entregada IS NULL ORDER BY id DESC')->fetchAll();
$fotos = $pdo->query('SELECT * FROM fotos ORDER BY id DESC LIMIT 24')->fetchAll();
$contactos = $pdo->query('SELECT * FROM contactos WHERE leido = 0 ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estación Nativa · app</title>
<style>
  body { font-family: "Source Sans 3", "Segoe UI", Arial, sans-serif; margin: 0; background: #f6f1e8; color: #1b1b1b; }
  header { background: #143f27; color: #fff; padding: 14px 18px; display: flex; justify-content: space-between; }
  header a { color: #f0a76a; }
  main { max-width: 900px; margin: 0 auto; padding: 16px; }
  h2 { color: #1f5e3a; font-size: 1.1rem; margin: 20px 0 8px; }
  .aviso { background: #fde7e3; border-left: 4px solid #b3261e; padding: 10px 12px; border-radius: 8px; margin-bottom: 8px; font-size: .9rem; }
  .msj { background: #fff; padding: 10px 12px; border-radius: 8px; margin-bottom: 8px; font-size: .9rem; white-space: pre-wrap; }
  .est { background: #fff; border-radius: 12px; padding: 12px 14px; margin-bottom: 10px; }
  .est b { color: #1f5e3a; }
  .est form { display: inline; }
  button { background: #1f5e3a; color: #fff; border: 0; border-radius: 999px; padding: 6px 12px; font-size: .85rem; margin: 4px 4px 0 0; }
  button.sec { background: #d9702b; }
  input[type=text] { padding: 5px 8px; border-radius: 8px; border: 1px solid #ccc; width: 70px; }
  .fotos { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; }
  .fotos img { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; }
  small { color: #5f5b54; }
</style>
</head>
<body>
<header><strong>Estación Nativa · app</strong><a href="?salir=1">Salir</a></header>
<main>
  <?php if ($avisos): ?><h2>Avisos</h2>
    <?php foreach ($avisos as $a): ?><div class="aviso"><?= h($a['texto']) ?> <small>(<?= h($a['creado']) ?>)</small> <a href="?visto=<?= (int)$a['id'] ?>">ok</a></div><?php endforeach; ?>
  <?php endif; ?>

  <?php if ($contactos): ?><h2>Mensajes del formulario</h2>
    <?php foreach ($contactos as $c): ?><div class="msj"><b><?= h($c['nombre']) ?></b> · <a href="mailto:<?= h($c['correo']) ?>"><?= h($c['correo']) ?></a> <small>(<?= h($c['creado']) ?>)</small> <a href="?leido=<?= (int)$c['id'] ?>">leído</a>
<?= h($c['mensaje']) ?></div><?php endforeach; ?>
  <?php endif; ?>

  <h2>Estaciones</h2>
  <?php foreach ($estaciones as $e): ?>
  <div class="est">
    <b><?= h($e['nombre'] ?: $e['id']) ?></b> <small><?= h($e['id']) ?> · <?= h($e['tipo']) ?></small><br>
    <small>Último contacto <?= h($e['ultimo_contacto'] ?: 'nunca') ?> · Pila <?= $e['pila_mv'] ? round($e['pila_mv'] / 1000, 2) . ' V' : '—' ?>
    <?php if ($e['tanque'] !== 'na'): ?> · Tanque <?= h($e['tanque']) ?><?php endif; ?> · Pendientes <?= (int)$e['pendientes'] ?> · fw <?= h($e['version']) ?></small><br>
    <form method="post"><input type="hidden" name="estacion" value="<?= h($e['id']) ?>"><input type="hidden" name="orden" value="foto"><button>Sacar foto</button></form>
    <?php if ($e['tipo'] === 'comedero'): ?>
    <form method="post"><input type="hidden" name="estacion" value="<?= h($e['id']) ?>"><input type="hidden" name="orden" value="comida"><button class="sec">Dar comida ahora</button></form>
    <form method="post"><input type="hidden" name="estacion" value="<?= h($e['id']) ?>"><input type="hidden" name="orden" value="horario"><input type="text" name="valor" value="8,17" title="horas separadas por coma"><button>Horarios</button></form>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php if ($pendientes): ?><h2>Órdenes esperando la próxima ventana</h2>
    <?php foreach ($pendientes as $o): ?><div><small><?= h($o['estacion']) ?> · <?= h($o['tipo']) ?> <?= h($o['valor']) ?> · <?= h($o['creada']) ?></small></div><?php endforeach; ?>
  <?php endif; ?>

  <h2>Últimas fotos</h2>
  <div class="fotos">
    <?php foreach ($fotos as $f): ?><a href="../fotos/<?= h($f['archivo']) ?>" target="_blank"><img src="../fotos/<?= h($f['archivo']) ?>" alt="" loading="lazy"></a><?php endforeach; ?>
    <?php if (!$fotos): ?><small>Todavía no hay fotos.</small><?php endif; ?>
  </div>
</main>
</body>
</html>
