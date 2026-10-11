#!/bin/bash
# Publica la web y el servidor en Hostinger por SSH.
# Uso (Git Bash):  ./desplegar.sh midominio.hostingersite.com
# Requiere el alias "hostinger" en ~/.ssh/config y que el sitio ya exista en hPanel
# (Sitios web → Agregar sitio → sitio vacío → dominio temporal o propio).
set -euo pipefail
DOM="${1:?Uso: ./desplegar.sh dominio}"
cd "$(dirname "$0")"
DEST="domains/$DOM/public_html"

ssh hostinger "test -d $DEST" || { echo "No existe $DEST en el servidor. Creá el sitio en hPanel primero."; exit 1; }

python -I herramientas/miniaturas.py imagenes/renders imagenes/renders 1600 2>/dev/null || echo "(sin Python/Pillow: se suben los renders que ya estén en JPEG)"

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
cp -r web/. "$TMP/"
rm -rf "$TMP/img"
mkdir -p "$TMP/img/dispositivos" "$TMP/img/renders"
cp imagenes/dispositivos/*.webp "$TMP/img/dispositivos/"
if ls imagenes/renders/*.jpg >/dev/null 2>&1; then cp imagenes/renders/*.jpg "$TMP/img/renders/"; fi
cp -r servidor/. "$TMP/"
rm -f "$TMP/config.local.php"
rm -rf "$TMP/datos" "$TMP/fotos"

tar -C "$TMP" -cf - . | ssh hostinger "cd $DEST && rm -f default.php && tar -xf -"
ssh hostinger "cd $DEST && if [ ! -f config.local.php ]; then cp config.local.php.ejemplo config.local.php; echo 'ATENCION: completar config.local.php en el servidor (claves)'; fi"
echo "Publicado en https://$DOM/  (web: /, registros: /registros/, app: /app/)"
