# Estación Nativa

Estación solar de agua y registro fotográfico de aves para el proyecto de árboles nativos de Iván Espeche (Ecoestudio Solar, Del Viso, Buenos Aires). Varias estaciones por sector comparten un módem 4G. Hardware de catálogo, firmware y servidor abiertos.

- **Web del proyecto:** https://skyblue-gorilla-834934.hostingersite.com/ (espejo estático en https://nik0793.github.io/estacion-nativa/)
- **Registros públicos:** https://skyblue-gorilla-834934.hostingersite.com/registros/
- **Informe técnico para el taller:** [docs/informe-tecnico.pdf](docs/informe-tecnico.pdf)
- **Presentación del proyecto (problema → solución):** [docs/presentacion-proyecto.pdf](docs/presentacion-proyecto.pdf)

## Qué hay en el repositorio

| Carpeta | Contenido |
|---|---|
| `docs/` | Informe técnico y presentación (HTML fuente y PDF), prompts para los renders |
| `web/` | Página pública estática (se publica sola en GitHub Pages con cada push; en Hostinger con `desplegar.sh`) |
| `servidor/` | API, registros públicos y panel, en PHP 8 con SQLite, para Hostinger |
| `firmware/estacion/` | Sketch Arduino para ESP32-CAM, un solo archivo para los tres tipos de estación |
| `imagenes/dispositivos/` | Fotos de cada componente, tomadas de las publicaciones de MercadoLibre del 11/10/2026 |
| `imagenes/renders/` | Renders generados con `docs/prompts-imagenes.md` (nombres fijos: `01-hero.png`, `02-estanque.png`, …) |
| `construir.ps1` | Regenera los PDF, la captura de la web y la imagen de previsualización con Edge sin ventana |
| `desplegar.sh` | Publica web + servidor en Hostinger por SSH |

## Cómo funciona, en una frase

Un ESP32-CAM duerme hasta que un sensor PIR detecta un ave, saca dos fotos y las guarda; una vez por hora la estación maestra enciende un módem 4G USB con WiFi, todas las estaciones del sector se conectan, suben sus fotos a un servidor PHP y bajan órdenes. El agua va por gravedad con válvula flotante. Panel de 1,5 W y dos pilas 18650 por estación.

## Publicar

- **Hostinger (web + servidor):** `./desplegar.sh skyblue-gorilla-834934.hostingersite.com` desde Git Bash. Las claves viven solo en el servidor, en `config.local.php` (copiar `config.local.php.ejemplo`). No hay base de datos que crear: SQLite se crea solo en `domains/<dominio>/datos/`.
- **GitHub Pages (espejo de la web):** cada push a `main` que toque `web/` o `imagenes/` (workflow en `.github/workflows/pages.yml`). El formulario de contacto del espejo habla con la API de Hostinger.
- **Firmware:** Arduino IDE 2.x, placa "AI Thinker ESP32-CAM", partición "Huge APP", editar las constantes del principio de `estacion.ino` (ID, tipo, WiFi del módem, CLAVE_API). Compila con el núcleo esp32 3.3.11.

## Estado (11/10/2026)

- Documentación, presupuesto, web y servidor: publicados. Precios de MercadoLibre verificados ese día.
- Servidor probado en local y en Hostinger: subida de foto, estado, órdenes, contacto, registros y app.
- Firmware: compila; falta probarlo en un ESP32-CAM real (paso 8 del armado en el informe).
- Renders: pendientes (se generan con los prompts; la web y la presentación muestran dónde falta cada uno).
- Próximo paso físico: medir la señal 4G en el estanque y comprar la primera estación.

## Licencias

Software: MIT. Hardware: CERN-OHL-P v2. Documentación e imágenes propias: CC BY-SA 4.0. Ver [LICENSE](LICENSE). Las fotos de productos pertenecen a sus vendedores.

## Quiénes

Iván Espeche (Ecoestudio Solar): predio, plantación y vínculos. Nicolás Gutiérrez: electrónica, firmware, servidor, web. Alumnos de escuela técnica: versión educativa del comedero, origen del proyecto.
