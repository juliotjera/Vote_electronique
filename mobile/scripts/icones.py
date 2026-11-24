# Dessine assets/logo.png (symbole blanc du logo sur fond transparent) pour « capacitor-assets » :
# icône = symbole sur fond bleu #1d4ed8, écran de démarrage = symbole sur fond marine #0b1f4d.
# Même dessin que resources/views/components/logo.blade.php (grille de 36 unités).
from pathlib import Path
from PIL import Image, ImageDraw

TAILLE = 1024
E = TAILLE / 36            # une unité de la grille du logo, en pixels
BLEU = (29, 78, 216, 255)
BLANC = (255, 255, 255, 255)

image = Image.new('RGBA', (TAILLE, TAILLE), (0, 0, 0, 0))
d = ImageDraw.Draw(image)


def p(x, y):
    return (round(x * E), round(y * E))


# bulletin
d.rounded_rectangle((*p(11.5, 6.5), *p(24.5, 20)), radius=round(2 * E), fill=BLANC)
# coche bleue
d.line([p(14.6, 13.2), p(17, 15.6), p(21.4, 10.8)], fill=BLEU, width=round(2.2 * E), joint='curve')
for x, y in ((14.6, 13.2), (21.4, 10.8)):
    r = 1.1 * E
    d.ellipse((x * E - r, y * E - r, x * E + r, y * E + r), fill=BLEU)
# urne : haut droit, coins du bas arrondis
d.rounded_rectangle((*p(7.5, 21.5), *p(28.5, 30)), radius=round(2.5 * E), fill=BLANC)
d.rectangle((*p(7.5, 21.5), *p(28.5, 25)), fill=BLANC)

dossier = Path(__file__).resolve().parent.parent / 'assets'
dossier.mkdir(exist_ok=True)
image.save(dossier / 'logo.png')
image.save(dossier / 'logo-dark.png')
print('assets/logo.png', image.size)
