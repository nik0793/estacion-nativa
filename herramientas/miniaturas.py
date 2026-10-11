"""Convierte imágenes a JPEG liviano para los PDF y la web.

Uso:
  python -I herramientas/miniaturas.py imagenes/dispositivos imagenes/dispositivos/mini 600
  python -I herramientas/miniaturas.py imagenes/renders imagenes/renders 1600

origen: carpeta con .webp o .png · destino: carpeta de salida (puede ser la misma) · lado máximo en píxeles.
Solo convierte lo que falta o cambió.
"""
import glob
import os
import sys

from PIL import Image

origen, destino = sys.argv[1], sys.argv[2]
lado = int(sys.argv[3]) if len(sys.argv) > 3 else 600
os.makedirs(destino, exist_ok=True)
hechas = 0
for ruta in sorted(glob.glob(os.path.join(origen, "*.webp")) + glob.glob(os.path.join(origen, "*.png"))):
    nombre = os.path.splitext(os.path.basename(ruta))[0] + ".jpg"
    salida = os.path.join(destino, nombre)
    if os.path.exists(salida) and os.path.getmtime(salida) >= os.path.getmtime(ruta):
        continue
    imagen = Image.open(ruta)
    if imagen.mode in ("RGBA", "LA", "P"):
        fondo = Image.new("RGB", imagen.size, (246, 241, 232))
        fondo.paste(imagen.convert("RGBA"), mask=imagen.convert("RGBA").split()[-1])
        imagen = fondo
    else:
        imagen = imagen.convert("RGB")
    imagen.thumbnail((lado, lado))
    imagen.save(salida, "JPEG", quality=85, optimize=True, progressive=True)
    hechas += 1
print(f"miniaturas: {hechas} nuevas en {destino} (máx. {lado} px)")
