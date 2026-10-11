# Prompts de imágenes — Estación Nativa

Guía para generar las 14 imágenes del proyecto de modo que **todas muestren el mismo dispositivo, el mismo lugar y la misma luz**. Sirve para Gemini (Nano Banana), Midjourney, Flux, Ideogram o cualquier generador que acepte una imagen de referencia.

Dónde va cada imagen: la web (`web/index.html`) y la presentación (`docs/presentacion-proyecto.pdf`) ya tienen el lugar reservado. Guardá cada resultado en `imagenes/renders/` **con el nombre exacto** de la tabla y corré `construir.ps1` (o hacé push: la web se publica sola).

## 1. Cómo trabajar para que salgan parecidas

1. **Primero la imagen 03 (ficha del dispositivo).** Es una foto de producto sobre fondo crema. Generá 4 variantes y elegí la que mejor cumpla la lista "debe verse". Esa imagen es la **referencia** del dispositivo.
2. **En todas las demás, adjuntá la 03 como imagen de referencia** y agregá al final del prompt: *"The station device must look exactly like the attached reference image."* En Midjourney usá `--cref` con esa imagen; en Gemini y Flux Kontext, adjuntala; en Ideogram, "Image reference".
3. Pegá siempre los **bloques A, B y el de estilo** que corresponda antes del prompt. Así el generador no inventa paneles gigantes, luces LED ni paisajes de montaña.
4. Formato: el que dice cada ficha. Si el generador no acepta proporción, pedí la más cercana y recortá.
5. Sin texto, sin logos, sin marcas de agua dentro de la imagen. El texto lo ponemos nosotros.
6. Si un detalle sale mal (dos lentes, tres paneles, pájaro deforme), **no lo arregles en el prompt: regenerá** con la misma semilla cambiada. Los generadores empeoran cuando se les discute.

## 2. Bloques fijos (copiar y pegar)

### Bloque A · El dispositivo

```
DEVICE (must be identical in every image): a small bird-monitoring station. A compact box of light warm-grey matte 3D-printed plastic, about 10 x 7.5 x 6 cm, with softly rounded vertical edges. On the front face: one small round camera lens window (about 10 mm) near the top center, with a short overhanging visor above it like a tiny roof; below the lens, one white half-sphere dome of a PIR motion sensor (about 23 mm). Above the box, on a small bracket, a solar panel made of two dark blue-black glossy cells side by side, total about 18 x 11 cm, tilted about 35 degrees toward the sun; one thin black cable runs from the panel down the back of the box. The whole thing is strapped to a rustic wooden post about 5 cm thick and 1 m tall; the box sits at about 40 cm from the ground. No screen, no buttons, no LEDs, no text, no logos, no antenna (except on the master station).
```

### Bloque A2 · La estación maestra (solo cuando se pide)

```
MASTER STATION: identical to DEVICE but the box is a little longer (about 14 cm) with a small transparent window on its right side showing a white USB 4G modem stick inside, a short black stub antenna on top of the box, and FOUR solar cells (two rows of two, about 22 x 18 cm) instead of two. It stands on a slightly thicker post on the highest spot near the pond.
```

### Bloque B · El lugar

```
PLACE: a small shallow pond about 6 m across, in a young native-tree plantation in the humid pampas near Buenos Aires, Argentina. Flat land, wide sky. Around the pond: tall soft grass, saplings 1 to 2 m tall each tied to a thin wooden stake (ceibo, tala, espinillo, sauce criollo), and a few mature willows and talas farther back. Water edge of mud and short grass, one shallow grey stone drinking bowl about 40 cm wide at ground level near the station, a dry natural branch planted upright as a perch 60 cm in front of the camera. No mountains, no palm trees, no pine forest, no buildings, no roads, no people unless requested.
```

### Bloque C · Estilo fotográfico (imágenes 01, 04, 05, 09, 11, 13)

```
STYLE: photorealistic nature documentary photograph, full-frame camera, natural light only. Warm, soft, slightly humid atmosphere. Colors true to life with a warm cream-and-green overall tone; no HDR, no neon, no blue tech glow, no lens flare. Composition with generous negative space for text to be added later. Sharp subject, gently blurred background.
```

### Bloque D · Estilo ilustración plana (imágenes 02, 06, 10, 12, 14)

```
STYLE: clean flat vector-style illustration with soft shading, on a warm cream background (#F6F1E8). Limited palette: deep green (#143F27), mid green (#1F5E3A), warm orange accent (#D9702B), light grey (#E6E1D6), white. Thin consistent outlines, no gradients, no 3D glossy effects, no text, no labels. Friendly but precise, like a good field-guide infographic.
```

### Bloque E · Estilo interfaz (imágenes 07 y 08)

```
STYLE: realistic UI mockup, light theme, warm cream background (#F6F1E8), deep green primary (#1F5E3A), orange accent (#D9702B), rounded 16 px cards, generous spacing, serif display font for headings and a clean sans-serif for body text, Spanish language UI, realistic bird photos inside the cards (no placeholder boxes, no lorem ipsum), no brand logos.
```

### Prompt negativo (si el generador lo acepta)

```
text, letters, watermark, logo, signature, people faces close-up, pigeon, sparrow, parakeet, parrot, toucan, flamingo, tropical birds, palm trees, mountains, snow, desert, pine forest, city, buildings, roads, cars, drone, robot, neon, blue LED, glowing, futuristic, screen on the device, multiple lenses, oversized solar panel, cartoon, 3D render look, plastic toy, blurry, deformed bird, extra legs, extra wings
```

## 3. Aves y plantas que sí queremos (nombrar siempre con el nombre científico)

| Ave | Nombre científico | Cómo describirla al generador |
|---|---|---|
| Benteveo | Pitangus sulphuratus | yellow belly, brown back, black-and-white striped head, stout black bill |
| Cardenal común | Paroaria coronata | bright red head and crest, grey back, white chest |
| Zorzal colorado | Turdus rufiventris | brown bird with rufous-orange belly, yellow bill and eye ring |
| Calandria grande | Mimus saturninus | slim grey-brown bird, long tail, white eyebrow |
| Picaflor común | Chlorostilbon lucidus | tiny iridescent green hummingbird, red bill with black tip |
| Hornero | Furnarius rufus | plain rufous-brown bird on the ground, slightly curved bill |
| Jilguero dorado | Sicalis flaveola | small bright yellow finch |
| Celestino | Thraupis sayaca | pale blue-grey tanager |
| Tacuarita azul | Polioptila dumicola | tiny blue-grey bird, long black-and-white tail |
| Chingolo | Zonotrichia capensis | small streaked sparrow-like bird with a short crest and rufous collar |

Árboles: ceibo (Erythrina crista-galli, red flowers in summer), tala (Celtis tala), espinillo (Vachellia caven, yellow pompom flowers), sauce criollo (Salix humboldtiana), pezuña de vaca (Bauhinia forficata). **Nunca**: paloma, gorrión, cotorra (son las que no queremos favorecer).

## 4. Orden de producción

**Esta noche (con estas cinco la web y la presentación quedan completas):** 03 → 01 → 02 → 04 → 07.
**Después:** 12, 09, 08, 10, 05, 06, 13, 11, 14.

| Nº | Archivo | Formato | Dónde se usa |
|---|---|---|---|
| 03 | `03-explotada.png` | 1:1 | Referencia de todas las demás; sección "Tecnología" |
| 01 | `01-hero.png` | 16:9 | Portada de la web y de la presentación |
| 02 | `02-estanque.png` | 4:3 | Sección "Sector estanque" (web y lámina 6) |
| 04 | `04-percha.png` | 3:2 | Lámina 4 "La solución" |
| 07 | `07-app.png` | 9:16 | Lámina 9 y sección "Datos" |
| 12 | `12-antes-despues.png` | 16:9 | Lámina 2 "El problema" |
| 09 | `09-arboles.png` | 3:2 | Sección "La solución" de la web |
| 08 | `08-web.png` | 16:9 | Lámina 8 y sección "Datos" |
| 10 | `10-infografia.png` | 16:5 | Sección "Cómo funciona" de la web |
| 05 | `05-maestra.png` | 4:3 | Informe técnico y web |
| 06 | `06-agua-comida.png` | 4:3 | Informe técnico |
| 13 | `13-cartel.png` | 4:5 | Carpeta para sponsors |
| 11 | `11-alumnos.png` | 3:2 | Sección educativa |
| 14 | `14-isotipo.png` | 1:1 | Ícono de la web y de la app |

---

## 03 · Ficha del dispositivo (la referencia)

**Qué queremos ver:** la estación sola, de tres cuartos de frente, sobre fondo crema, como foto de catálogo. Lente arriba, cúpula del PIR abajo, visera, dos celdas solares inclinadas sobre el soporte, un cable, el poste cortado a 50 cm. Nada más.

**Bloques:** A + C. Formato 1:1.

```
Product photograph of the DEVICE alone, three-quarter front view, slightly from above, centered on a seamless warm cream studio background. Soft daylight from the upper left, gentle shadow under the box. The wooden post is cut at about 50 cm height. Every detail of the DEVICE description visible and correct: one round lens window with a small visor, one white PIR dome below it, two dark solar cells side by side on a bracket above, tilted, one cable. Matte light-grey plastic with the faint texture of 3D printing. Nothing else in frame.
```

**Debe verse:** un solo lente, una sola cúpula, exactamente dos celdas, poste de madera. **No:** pantalla, luces, antena, texto.

## 01 · Hero: la estación al amanecer

**Qué queremos ver:** el estanque al amanecer, la estación en su poste a la izquierda del cuadro, un benteveo bebiendo en el bebedero de piedra frente a ella, bruma sobre el agua, árboles jóvenes con tutor detrás. Mucho aire a la derecha para el título.

**Bloques:** A + B + C. Formato 16:9.

```
Wide shot at golden hour, 35 mm lens, camera at 50 cm height. The DEVICE stands on its post on the left third of the frame at the edge of the PLACE pond. In front of it, 60 cm from the lens, a great kiskadee (Pitangus sulphuratus: yellow belly, brown back, black-and-white striped head) drinks from the shallow grey stone bowl. Thin morning mist floats over the water; young staked saplings and two old willows in the background catch the first warm light; the sky is pale gold to soft blue. The right half of the frame is open sky and misty field, calm and empty. Dew on the grass. Hopeful, quiet mood.
```

**Debe verse:** estación completa con panel, bebedero, benteveo reconocible, tutores en los arbolitos, espacio libre a la derecha. **No:** segunda estación, personas, construcciones.

## 02 · El sector estanque

**Qué queremos ver:** vista en picado suave (isométrica) del estanque redondo con cuatro estaciones alrededor, una de ellas la maestra (más grande, con módem). Una percha frente a una estación, el bebedero frente a otra, el comedero con su tolva frente a la tercera. Líneas punteadas finas desde cada estación hasta la maestra. Pájaros nativos chicos en distintos puntos.

**Bloques:** A + A2 + B + D. Formato 4:3.

```
Isometric flat illustration, viewed from above at about 45 degrees, of the PLACE pond with four stations around its shore. Three are the DEVICE; the one on the small rise at the top right is the MASTER STATION. In front of one station a dry branch perch with a red-crested cardinal (Paroaria coronata) on it; in front of another the grey stone drinking bowl with a rufous-bellied thrush (Turdus rufiventris) drinking; in front of the third a small light-grey seed hopper on a short post with a saffron finch (Sicalis flaveola) on its tray. Thin orange dotted lines connect each of the three stations to the master station. Young staked saplings and tall grass around, two willows at the back. Lots of calm cream space around the scene.
```

**Debe verse:** cuatro estaciones, una claramente distinta (maestra), líneas punteadas, percha, bebedero, comedero. **No:** cables entre estaciones, personas, texto.

## 04 · El cardenal en la percha

**Qué queremos ver:** primer plano de un cardenal común posado en la rama-percha, nítido, con la estación desenfocada detrás pero reconocible (lente, cúpula, panel). Es la foto que "sacaría" la estación.

**Bloques:** A + B + C. Formato 3:2.

```
Close-up, 85 mm lens, shallow depth of field, eye level with the bird. A red-crested cardinal (Paroaria coronata: bright red head and crest, grey back, white chest) perches on the upright dry branch, body turned slightly toward the camera, catchlight in the eye. About 60 cm behind it, softly out of focus but clearly recognizable, the DEVICE on its post with its lens pointing at the bird. Bright overcast light, a few drops of dew on the branch, the PLACE blurred to soft greens behind. Documentary feel, no retouching look.
```

**Debe verse:** cardenal fiel a la especie, percha natural, estación detrás con sus dos celdas. **No:** pájaro de frente a la cámara del fotógrafo como un retrato de estudio, fondo negro, flash.

## 07 · La app en el celular

**Qué queremos ver:** un teléfono con la app abierta en la pantalla principal: arriba el nombre del lugar, debajo tarjetas con fotos reales de aves tomadas por una cámara fija (zorzal, benteveo, cardenal), cada una con nombre de especie, hora y estación ("Estanque norte"); abajo una barra con batería y agua de las estaciones y un botón naranja "Dar comida".

**Bloques:** E. Formato 9:16 (una pantalla). Opcional: generar también una versión 16:9 con tres pantallas en fila (inicio, especies de la semana, estado de estaciones).

```
UI mockup of a mobile app on a modern phone, light theme. Top: small header "Estanque norte · hoy". Then a vertical feed of cards; each card has a realistic photo of a bird taken by a fixed low camera near water (first: Turdus rufiventris drinking; second: Pitangus sulphuratus on a branch; third: Paroaria coronata at a bowl), with the species name in Spanish ("Zorzal colorado", "Benteveo", "Cardenal común"), the time ("07:42") and the station ("Estanque norte"). At the bottom, a compact status bar with two small icons: battery 3.9 V and water tank OK, and one orange rounded button "Dar comida". Rounded cards, warm cream background, deep green headings, Spanish text only, no lorem ipsum, no brand logos.
```

**Debe verse:** fotos de aves creíbles dentro de las tarjetas, textos en español legibles, botón naranja. **No:** íconos genéricos de "tech", gráficos de criptomonedas, inglés.

## 12 · Antes y después

**Qué queremos ver:** imagen dividida al medio con una línea vertical fina. Izquierda: campo pelado y sobrepastoreado bajo cielo gris, un poste de alambrado, ningún pájaro. Derecha: el mismo encuadre años después con árboles nativos (un ceibo en flor roja, talas, espinillos) alrededor del estanque, muchas aves nativas y la estación en su poste junto al agua, luz cálida.

**Bloques:** A + B + D (ilustración) o C (foto, según cuál salga mejor). Formato 16:9.

```
Split image, left and right halves separated by a thin vertical line, same camera angle on both sides. LEFT: a bare, flat, overgrazed pampas field under a grey sky, cracked dry mud where the pond would be, one old fence post, no birds, no trees. RIGHT: the same place years later: a small pond with young native trees around it (a ceibo in red bloom, talas, espinillos with yellow pompom flowers), tall grass, many native birds (Pitangus sulphuratus, Paroaria coronata, Turdus rufiventris, a tiny Chlorostilbon lucidus hummingbird), and the DEVICE on its post beside the water, warm afternoon light. Clear emotional contrast, no text.
```

## 09 · Árboles nativos jóvenes y la estación

**Qué queremos ver:** filas de arbolitos recién plantados con tutor en el pasto alto, y la estación en primer plano junto a un bebedero. Mañana, luz baja, bandada de jilgueros levantando vuelo.

**Bloques:** A + B + C. Formato 3:2.

```
Wide shot, 35 mm lens, early morning. Rows of young native saplings (ceibo, tala, espinillo), each 1 to 2 m tall and tied to a thin wooden stake, in tall soft grass of the humid pampas; a line of mature willows far behind. In the left foreground the DEVICE on its post next to the shallow grey stone bowl. Long shadows, soft warm light, a small flock of saffron finches (Sicalis flaveola, bright yellow) taking off on the right. Documentary, hopeful.
```

## 08 · El tablero web

**Qué queremos ver:** una notebook mostrando el tablero público: a la izquierda un mapa simple del predio con el estanque y cuatro puntos; en el centro una grilla de fotos de aves con nombre de especie; a la derecha un gráfico de barras "Especies esta semana" y uno de línea "Visitas por hora". Abajo una franja de logos vacíos (rectángulos grises).

**Bloques:** E. Formato 16:9.

```
UI mockup of a public web dashboard shown on a laptop screen, light theme, Spanish language. Left column: a simple flat map of a rural property with a small pond and four green station dots. Center: a grid of realistic bird photos taken by fixed cameras, each with a species name in Spanish under it ("Benteveo", "Zorzal colorado", "Cardenal común", "Calandria", "Picaflor común", "Chingolo"). Right column: a bar chart titled "Especies esta semana" and a line chart titled "Visitas por hora" (peak in the early morning). Footer strip with six empty light-grey rounded rectangles as sponsor logo placeholders. Serif headings, warm cream background, green and orange accents, no brand logos.
```

## 10 · Infografía "cómo funciona"

**Qué queremos ver:** cinco pasos de izquierda a derecha unidos por una línea punteada naranja: pájaro en el bebedero → cúpula del PIR iluminada → estación con destello de foto → reloj con un sol (una vez por hora) → módem USB con ondas → nube/servidor → celular con la foto del pájaro. Mucho espacio vacío debajo de cada paso para poner los textos después.

**Bloques:** A + D. Formato 16:5 (panorámico).

```
Horizontal flat infographic with five steps from left to right, connected by a thin orange dotted path, no text. 1) a small bird at the grey stone bowl; 2) the white PIR dome of the DEVICE lit with a small orange glow; 3) the DEVICE taking a photo, suggested by two small white arcs in front of the lens; 4) a simple clock icon with a small sun above it; 5) a white USB 4G modem stick with three signal arcs, then a small cloud, then a phone showing a tiny bird photo. All in the flat style palette, generous empty space under each step for captions.
```

## 05 · La estación maestra

**Qué queremos ver:** la maestra sola en una loma al borde del estanque, con sus cuatro celdas, la ventanita con el módem blanco y la antena corta; un hornero caminando en el pasto; atardecer.

**Bloques:** A2 + B + C. Formato 4:3.

```
Medium shot, 50 mm lens, late afternoon. The MASTER STATION on its thicker post on a small grassy rise next to the PLACE pond, clear view of the sky behind it. The transparent side window shows the white USB modem; the short stub antenna and the four solar cells are clearly visible. A rufous hornero (Furnarius rufus) walks on the ground near the post. Warm low light, long shadow of the post across the grass.
```

## 06 · Agua y comida

**Qué queremos ver:** corte explicativo: bidón de 20 litros sobre una base de madera baja, manguera fina que baja al bebedero de piedra, dentro del bebedero una válvula flotante pequeña; al lado, la tolva de semillas con ventana transparente y compuerta con un servo chico cayendo sobre una bandeja; la estación en su poste mirando al bebedero; sensor de boya en la tapa del bidón con su cable fino.

**Bloques:** A + D. Formato 4:3.

```
Clean cutaway flat illustration, manual style. A 20-liter white plastic drum on a low wooden stand feeds by gravity, through a thin hose, a shallow grey stone drinking bowl at ground level; a cutaway of the bowl shows a small mechanical float valve keeping the water level. Next to it, a light-grey seed hopper with a transparent window and a small servo-driven gate at the bottom dropping a few seeds onto a small tray. The DEVICE on its post watches the bowl and the tray. On the drum lid, a small float level sensor with a thin cable running to the station. No text, no labels.
```

## 13 · Cartel del predio

**Qué queremos ver:** cartel de madera chico junto a la estación con un pájaro bebiendo grabado, un rectángulo vacío donde irá el código QR y espacio para texto; pasto y arbolitos detrás.

**Bloques:** A + B + C. Formato 4:5.

```
Photograph, 50 mm lens. A small weatherproof wooden sign on a post, about 40 x 50 cm, next to the DEVICE at the edge of the PLACE pond. The sign has a simple engraved line drawing of a bird drinking from a drop, an empty light square where a QR code will be placed, and blank space below for text. Soft daylight, grass and staked saplings behind, slightly out of focus.
```

## 11 · Alumnos armando la estación

**Qué queremos ver:** taller de electrónica de una escuela técnica, tres estudiantes adolescentes alrededor de una mesa de madera armando la estación: uno suelda una placa chica, otro coloca el panel en la caja impresa, otro mira el código en una notebook; impresora 3D al fondo; manos y trabajo en foco, caras de perfil o inclinadas.

**Bloques:** A + C. Formato 3:2.

```
Photograph, warm indoor light of a school electronics workshop in Argentina. Three teenage students in grey work coats around a wooden bench assembling the DEVICE: one soldering a small green circuit board, one fitting the two solar cells onto the light-grey 3D-printed box, one looking at a laptop. A 3D printer and shelves with boxes in the background. Focus on hands and the device; faces turned toward the work, natural and candid, no logos.
```

## 14 · Isotipo

**Qué queremos ver:** marca mínima: un pájaro de perfil bebiendo de una gota, trazo continuo, una hoja; un detalle sutil de pista de circuito integrado en la línea. Dos colores: verde profundo y naranja. Fondo blanco.

**Bloques:** D. Formato 1:1.

```
Minimal flat logo mark on a white background: a small bird in profile drinking from a single water drop, drawn with one continuous line, plus one leaf; one subtle circuit-trace detail integrated into the line, nothing more. Two colors only: deep green (#143F27) and warm orange (#D9702B). Centered, lots of white space, crisp vector style, no text.
```

---

## 5. Lista de control antes de guardar una imagen

- El dispositivo tiene **un** lente, **una** cúpula blanca debajo, **dos** celdas solares (cuatro solo en la maestra), poste de madera. Sin pantalla, sin luces, sin texto.
- El paisaje es llano, con pasto alto y arbolitos con tutor. Sin montañas, palmeras ni pinos.
- Las aves son de la lista; ninguna paloma, gorrión ni cotorra.
- Los colores están en la familia crema / verde / naranja. Nada azul neón.
- Hay espacio libre donde después va el texto (sobre todo en 01, 10 y 12).
- Guardada con el nombre exacto en `imagenes/renders/`.
