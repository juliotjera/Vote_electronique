# Icônes « anciennes » (Android 7 et avant, sans icône adaptative) : capacitor-assets y pose le logo blanc
# sans fond. On les redessine : tuile en dégradé bleu foncé → bleu clair et symbole blanc, comme le favicon du site.
from pathlib import Path
from PIL import Image, ImageDraw

RES = Path(__file__).resolve().parent.parent / 'android' / 'app' / 'src' / 'main' / 'res'
LOGO = Path(__file__).resolve().parent.parent / 'assets' / 'logo.png'
TAILLES = {'mdpi': 48, 'hdpi': 72, 'xhdpi': 96, 'xxhdpi': 144, 'xxxhdpi': 192}
GRAND = 1024


def degrade(taille):
    # bleu foncé #1e40af en haut à gauche → bleu clair #3b82f6 en bas à droite
    image = Image.new('RGBA', (taille, taille))
    px = image.load()
    for y in range(taille):
        for x in range(taille):
            t = (x + y) / (2 * (taille - 1))
            px[x, y] = (round(30 + (59 - 30) * t), round(64 + (130 - 64) * t), round(175 + (246 - 175) * t), 255)
    return image


fond = degrade(GRAND)
symbole = Image.open(LOGO).convert('RGBA')
fond.alpha_composite(symbole)

carre = Image.new('L', (GRAND, GRAND), 0)
ImageDraw.Draw(carre).rounded_rectangle((0, 0, GRAND - 1, GRAND - 1), radius=round(GRAND * 9 / 36), fill=255)
rond = Image.new('L', (GRAND, GRAND), 0)
ImageDraw.Draw(rond).ellipse((0, 0, GRAND - 1, GRAND - 1), fill=255)

for densite, taille in TAILLES.items():
    for nom, masque in (('ic_launcher.png', carre), ('ic_launcher_round.png', rond)):
        icone = Image.new('RGBA', (GRAND, GRAND), (0, 0, 0, 0))
        icone.paste(fond, (0, 0), masque)
        icone.resize((taille, taille), Image.LANCZOS).save(RES / f'mipmap-{densite}' / nom)
print('icônes anciennes : ', ', '.join(TAILLES))
