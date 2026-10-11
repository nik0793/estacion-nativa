# Genera los PDF y la captura de la web con Edge sin ventana.
# Uso: clic derecho > Ejecutar con PowerShell, o desde una consola: .\construir.ps1
# Cada vez que agregues renders en imagenes\renders (01-hero.png, 02-estanque.png, ...) volvé a ejecutarlo.

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$edge = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
if (-not (Test-Path $edge)) { $edge = "C:\Program Files\Google\Chrome\Application\chrome.exe" }
$perfil = Join-Path $env:TEMP "edge-estacion-nativa"
$rootUrl = "file:///" + ($root -replace '\\', '/')

function Correr($lista) {
    $p = Start-Process -FilePath $edge -ArgumentList $lista -Wait -PassThru -WindowStyle Hidden
    return $p.ExitCode
}

# 1. Copiar imágenes a la web (la web se sube a Hostinger con su carpeta img)
New-Item -ItemType Directory -Force (Join-Path $root "web\img\dispositivos") | Out-Null
New-Item -ItemType Directory -Force (Join-Path $root "web\img\renders") | Out-Null
New-Item -ItemType Directory -Force (Join-Path $root "imagenes\renders") | Out-Null
Copy-Item (Join-Path $root "imagenes\dispositivos\*") (Join-Path $root "web\img\dispositivos\") -Force
Copy-Item (Join-Path $root "imagenes\renders\*") (Join-Path $root "web\img\renders\") -Force -ErrorAction SilentlyContinue

# 1b. Miniaturas JPEG de las fotos de componentes (los PDF las usan para pesar poco)
try {
    & python -I (Join-Path $root "herramientas\miniaturas.py") (Join-Path $root "imagenes\dispositivos") (Join-Path $root "imagenes\dispositivos\mini") 600
    # 1c. Los renders PNG se convierten a JPEG (máx. 1600 px) para la web y la presentación
    & python -I (Join-Path $root "herramientas\miniaturas.py") (Join-Path $root "imagenes\renders") (Join-Path $root "imagenes\renders") 1600
} catch { Write-Host "  (sin Python/Pillow: faltan las versiones JPEG)" }

$comunes = @("--headless=new", "--disable-gpu", "--hide-scrollbars", "--run-all-compositor-stages-before-draw", "--user-data-dir=`"$perfil`"")

# 2. Captura de la web para la presentación
$captura = Join-Path $root "imagenes\renders\web-captura.png"
$argumentos = $comunes + @("--window-size=1440,4200", "--virtual-time-budget=8000", "--screenshot=`"$captura`"", "`"$rootUrl/web/index.html`"")
Write-Host "Captura de la web..." -NoNewline
$null = Correr $argumentos
Write-Host (" " + (Test-Path $captura))

# 2a. Convertir la captura a JPEG liviano para la presentación (si hay Python con Pillow)
$capturaJpg = Join-Path $root "imagenes\renders\web-captura.jpg"
try {
    & python -I -c "from PIL import Image; im=Image.open(r'$captura').convert('RGB'); im.thumbnail((1200, 3500)); im.save(r'$capturaJpg', 'JPEG', quality=82, optimize=True)" 2>$null
    if (Test-Path $capturaJpg) { Remove-Item $captura -Force }
} catch { Write-Host "  (sin Pillow: se usa el PNG)" }

# 2b. Imagen de previsualización para WhatsApp y redes (og.png, 1200x630)
$og = Join-Path $root "web\og.png"
$argumentos = $comunes + @("--window-size=1200,630", "--virtual-time-budget=5000", "--screenshot=`"$og`"", "`"$rootUrl/web/og.html`"")
Write-Host "Imagen og.png..." -NoNewline
$null = Correr $argumentos
Write-Host (" " + (Test-Path $og))

# 3. PDF del informe técnico (A4 vertical)
$pdf1 = Join-Path $root "docs\informe-tecnico.pdf"
$argumentos = $comunes + @("--no-pdf-header-footer", "--virtual-time-budget=10000", "--print-to-pdf=`"$pdf1`"", "`"$rootUrl/docs/informe-tecnico.html`"")
Write-Host "Informe técnico..." -NoNewline
$null = Correr $argumentos
Write-Host (" " + (Test-Path $pdf1))

# 4. PDF de la presentación (A4 apaisado)
$pdf2 = Join-Path $root "docs\presentacion-proyecto.pdf"
$argumentos = $comunes + @("--no-pdf-header-footer", "--virtual-time-budget=10000", "--print-to-pdf=`"$pdf2`"", "`"$rootUrl/docs/presentacion-proyecto.html`"")
Write-Host "Presentación..." -NoNewline
$null = Correr $argumentos
Write-Host (" " + (Test-Path $pdf2))

Write-Host "Listo. PDF en docs\, web lista para subir en web\."
