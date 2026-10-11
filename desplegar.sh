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

TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
cp -r web/. "$TMP/"
mkdir -p "$TMP/img/dispositivos" "$TMP/img/renders"
cp -r imagenes/dispositivos/. "$TMP/img/dispositivos/"
if [ -d imagenes/renders ]; then cp -r imagenes/renders/. "$TMP/img/renders/"; fi
cp -r servidor/. "$TMP/"
rm -f "$TMP/config.local.php"
rm -rf "$TMP/datos" "$TMP/fotos"

tar -C "$TMP" -cf - . | ssh hostinger "cd $DEST && rm -f default.php && tar -xf -"
ssh hostinger "cd $DEST && if [ ! -f config.local.php ]; then cp config.local.php.ejemplo config.local.php; echo 'ATENCION: completar config.local.php en el servidor (claves)'; fi"
echo "Publicado en https://$DOM/  (web: /, registros: /registros/, app: /app/)"
