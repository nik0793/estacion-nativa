<?php
// Registros públicos: últimas fotos, por día, y estado de las estaciones. Vive en /registros/ junto a la web.
require __DIR__ . '/../config.php';
$pdo = db();
$dia = isset($_GET['dia']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dia']) ? $_GET['dia'] : null;

if ($dia) {
    $st = $pdo->prepare('SELECT * FROM fotos WHERE publicada = 1 AND date(COALESCE(tomada, recibida)) = ? ORDER BY COALESCE(tomada, recibida) DESC');
    $st->execute([$dia]);
} else {
    $st = $pdo->query('SELECT * FROM fotos WHERE publicada = 1 ORDER BY COALESCE(tomada, recibida) DESC LIMIT 60');
}
$fotos = $st->fetchAll();
$estaciones = $pdo->query('SELECT * FROM estaciones ORDER BY id')->fetchAll();
$dias = $pdo->query('SELECT date(COALESCE(tomada, recibida)) d, COUNT(*) n FROM fotos WHERE publicada = 1 GROUP BY d ORDER BY d DESC LIMIT 30')->fetchAll();
$st = $pdo->prepare('SELECT especie, COUNT(*) n FROM fotos WHERE especie IS NOT NULL AND tomada >= ? GROUP BY especie ORDER BY n DESC LIMIT 15');
$st->execute([date('Y-m-d H:i:s', strtotime('-7 days'))]);
$especies = $st->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Estación Nativa — registros</title>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,700&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<style>
  :root { --verde:#1f5e3a; --naranja:#d9702b; --crema:#f6f1e8; --gris:#5f5b54; }
  body { font-family: "Source Sans 3", "Segoe UI", Arial, sans-serif; margin: 0; background: var(--crema); color: #1b1b1b; }
  header { background: #143f27; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; font-family: Newsreader, Georgia, serif; font-size: 1.2rem; }
  header a { color: #f0a76a; text-decoration: none; font-family: "Source Sans 3", sans-serif; font-size: .95rem; }
  main { max-width: 1120px; margin: 0 auto; padding: 20px; }
  h2 { color: var(--verde); font-family: Newsreader, Georgia, serif; }
  .estaciones { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
  .est { background: #fff; border-radius: 12px; padding: 12px 14px; font-size: .9rem; }
  .est b { display: block; color: var(--verde); }
  .mal { color: #b3261e; font-weight: 600; }
  .galeria { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
  .foto { background: #fff; border-radius: 12px; overflow: hidden; }
  .foto img { width: 100%; aspect-ratio: 4/3; object-fit: cover; display: block; }
  .foto div { padding: 8px 10px; font-size: .8rem; color: var(--gris); }
  .foto div b { color: var(--naranja); }
  .dias a { display: inline-block; margin: 0 8px 8px 0; padding: 4px 10px; background: #fff; border-radius: 999px; text-decoration: none; color: var(--verde); font-size: .85rem; }
  .vacio { background: #fff; border-radius: 12px; padding: 24px; color: var(--gris); }
</style>
</head>
<body>
<header><span>Estación Nativa · registros</span><a href="../">Volver al proyecto</a></header>
<main>
  <h2>Estaciones</h2>
  <div class="estaciones">
    <?php foreach ($estaciones as $e): $viejo = $e['ultimo_contacto'] && strtotime($e['ultimo_contacto']) < time() - 3 * 3600; ?>
    <div class="est">
      <b><?= h($e['nombre'] ?: $e['id']) ?></b>
      <span class="<?= $viejo ? 'mal' : '' ?>">Último contacto: <?= h($e['ultimo_contacto'] ?: 'todavía no instalada') ?></span><br>
      Pila: <?= $e['pila_mv'] ? round($e['pila_mv'] / 1000, 2) . ' V' : '—' ?>
      <?php if ($e['tanque'] !== 'na'): ?> · Tanque: <span class="<?= $e['tanque'] === 'bajo' ? 'mal' : '' ?>"><?= h($e['tanque']) ?></span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($especies): ?>
  <h2>Especies de la última semana</h2>
  <p><?php foreach ($especies as $s): ?><span style="display:inline-block;background:#fff;border-radius:999px;padding:4px 12px;margin:0 6px 6px 0"><?= h($s['especie']) ?> · <?= (int)$s['n'] ?></span><?php endforeach; ?></p>
  <?php endif; ?>

  <h2><?= $dia ? 'Fotos del ' . h($dia) : 'Últimas fotos' ?></h2>
  <div class="dias">
    <?php foreach ($dias as $d): ?><a href="?dia=<?= h($d['d']) ?>"><?= h($d['d']) ?> (<?= (int)$d['n'] ?>)</a><?php endforeach; ?>
  </div>
  <?php if (!$fotos): ?>
    <div class="vacio">Todavía no hay fotos. Las primeras estaciones se instalan en el estanque de Ecoestudio Solar; cuando suban su primera imagen va a aparecer acá, con fecha, hora y estación.</div>
  <?php else: ?>
  <div class="galeria">
    <?php foreach ($fotos as $f): ?>
    <div class="foto">
      <a href="../fotos/<?= h($f['archivo']) ?>" target="_blank"><img src="../fotos/<?= h($f['archivo']) ?>" alt="" loading="lazy"></a>
      <div><b><?= h($f['especie'] ?: 'sin identificar') ?></b><br><?= h($f['estacion']) ?> · <?= h($f['tomada'] ?: $f['recibida']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
</body>
</html>
