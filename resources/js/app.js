// Polices hébergées avec l'application (aucun appel à un service externe, utile sur réseau faible)
import '@fontsource-variable/plus-jakarta-sans';
import '@fontsource/ibm-plex-mono/400.css';
import '@fontsource/ibm-plex-mono/600.css';
import QRCode from 'qrcode';

const langue = () => (document.documentElement.lang === 'mg' ? 'mg' : 'fr');
const textes = {
    fr: { clos: 'Vote clos', ouvre: 'Ouverture dans', ferme: 'Clôture dans', j: 'j', h: 'h', min: 'min', s: 's' },
    mg: { clos: 'Vita ny fifidianana', ouvre: 'Hisokatra afaka', ferme: 'Hikatona afaka', j: 'a', h: 'o', min: 'min', s: 'seg' },
};

document.addEventListener('alpine:init', () => {
    // Compte à rebours jusqu'à une date (clôture ou ouverture) : « Clôture dans 2 j 5 h 12 min »
    window.Alpine.data('compteRebours', (cible, sens = 'ferme') => ({
        texte: '',
        minuterie: null,
        init() {
            this.calculer();
            this.minuterie = setInterval(() => this.calculer(), 1000);
        },
        destroy() {
            clearInterval(this.minuterie);
        },
        calculer() {
            const t = textes[langue()];
            let reste = Math.floor((new Date(cible).getTime() - Date.now()) / 1000);
            if (reste <= 0) {
                this.texte = t.clos;
                clearInterval(this.minuterie);
                return;
            }
            const j = Math.floor(reste / 86400);
            reste %= 86400;
            const h = Math.floor(reste / 3600);
            reste %= 3600;
            const min = Math.floor(reste / 60);
            const s = reste % 60;
            const morceaux = j > 0 ? [`${j} ${t.j}`, `${h} ${t.h}`, `${min} ${t.min}`]
                : h > 0 ? [`${h} ${t.h}`, `${min} ${t.min}`]
                : [`${min} ${t.min}`, `${s} ${t.s}`];
            this.texte = `${t[sens]} ${morceaux.join(' ')}`;
        },
    }));

    // Reçu de vote : QR code et image téléchargeable, calculés dans le navigateur.
    // Le QR ouvre l'urne publique avec le reçu après « # » : cette partie de l'adresse
    // n'est jamais envoyée au serveur, ni écrite dans ses journaux.
    // Code QR d'un lien (téléchargement de l'application Android), dessiné dans la page
    window.Alpine.data('qrLien', (lien) => ({
        qr: '',
        dansApplication: navigator.userAgent.includes('VoteElectroniqueApp'),
        async init() {
            this.qr = await QRCode.toString(lien, {
                type: 'svg', margin: 0, errorCorrectionLevel: 'M', color: { dark: '#0b1f4d', light: '#ffffff' },
            });
        },
    }));

    window.Alpine.data('recuDeVote', (code, scrutin, lienUrne) => ({
        qr: '',
        copie: false,
        // ouvert depuis l'application Android (Capacitor ajoute ce marqueur à l'agent utilisateur)
        dansApplication: navigator.userAgent.includes('VoteElectroniqueApp'),
        async init() {
            this.qr = await QRCode.toString(`${lienUrne}#${code}`, {
                type: 'svg', margin: 0, errorCorrectionLevel: 'M', color: { dark: '#0b1f4d', light: '#ffffff' },
            });
        },
        copier() {
            navigator.clipboard?.writeText(code);
            this.copie = true;
            setTimeout(() => (this.copie = false), 2000);
        },
        // Le reçu imprimé comme un document de sécurité : fond guilloché, tampon « A voté »
        async telecharger() {
            const mg = langue() === 'mg';
            const L = 720, H = 1000, e = 2;
            const toile = document.createElement('canvas');
            toile.width = L * e;
            toile.height = H * e;
            const c = toile.getContext('2d');
            c.scale(e, e);
            await document.fonts.ready;

            c.fillStyle = '#fff';
            c.fillRect(0, 0, L, H);
            // guilloché : ondes croisées à l'encre bleu pâle
            c.strokeStyle = '#cdd6f6';
            c.lineWidth = 0.7;
            for (let k = 0; k < 26; k++) {
                for (const signe of [1, -1]) {
                    c.beginPath();
                    for (let x = 0; x <= L; x += 4) {
                        const y = 40 + k * 36 + signe * 14 * Math.sin((x / L) * Math.PI * 6 + k * 0.45);
                        x ? c.lineTo(x, y) : c.moveTo(x, y);
                    }
                    c.stroke();
                }
            }
            // cadre et filet
            c.strokeStyle = '#0b1f4d';
            c.lineWidth = 3;
            c.strokeRect(24, 24, L - 48, H - 48);

            c.fillStyle = '#0f172a';
            c.textAlign = 'left';
            c.font = '400 22px "Plus Jakarta Sans Variable", sans-serif';
            c.fillText(mg ? 'Tapakila fanamarinana ny fifidianana' : 'Reçu de vote', 64, 92);
            c.font = '800 42px "Plus Jakarta Sans Variable", sans-serif';
            c.fillText(scrutin, 64, 146, L - 128);

            c.fillStyle = '#fff';
            c.fillRect(64, 190, L - 128, 120);
            c.strokeStyle = '#0b1f4d';
            c.lineWidth = 2;
            c.strokeRect(64, 190, L - 128, 120);
            c.fillStyle = '#5f6573';
            c.font = '500 18px "Plus Jakarta Sans Variable", sans-serif';
            c.fillText(mg ? 'Kaody' : 'Code du reçu', 84, 224);
            c.fillStyle = '#0f172a';
            c.font = '600 52px "IBM Plex Mono", monospace';
            c.fillText(code, 84, 284, L - 168);

            const image = new Image();
            image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(this.qr);
            await image.decode();
            c.fillStyle = '#fff';
            c.fillRect(64, 350, 300, 300);
            c.drawImage(image, 79, 365, 270, 270);

            c.fillStyle = '#0f172a';
            c.font = '400 21px "Plus Jakarta Sans Variable", sans-serif';
            const texte = mg
                ? ['Skaneo ity kaody ity: hisokatra ny vata', 'fandatsaham-bato ho an’ny besinimaro', 'ary hiseho ny vatonao, tsy misy', 'na inona na inona momba ny safidinao.']
                : ['Scannez ce code : l’urne publique', 's’ouvre et votre bulletin y est', 'surligné, sans rien révéler', 'de votre choix.'];
            texte.forEach((ligne, i) => c.fillText(ligne, 392, 390 + i * 32, 270));

            // tampon « A voté », encre bleue légèrement tournée
            c.save();
            c.translate(500, 760);
            c.rotate(-0.16);
            c.strokeStyle = '#1b3bb3';
            c.fillStyle = '#1b3bb3';
            c.lineWidth = 6;
            c.strokeRect(-170, -62, 340, 124);
            c.lineWidth = 2;
            c.strokeRect(-158, -50, 316, 100);
            c.textAlign = 'center';
            c.font = '800 60px "Plus Jakarta Sans Variable", sans-serif';
            c.fillText(mg ? 'NIFIDY' : 'A VOTÉ', 0, 22);
            c.restore();

            c.fillStyle = '#5f6573';
            c.font = '400 17px "Plus Jakarta Sans Variable", sans-serif';
            c.fillText(lienUrne.replace(/^https?:\/\//, ''), 64, H - 64, L - 128);

            const lien = document.createElement('a');
            lien.download = `recu-vote-${code}.png`;
            lien.href = toile.toDataURL('image/png');
            lien.click();
        },
    }));

    // Urne publique : retrouver son bulletin. Le reçu est transformé en empreinte SHA-256
    // dans le navigateur (même règle que le serveur) puis comparé à la liste publiée.
    window.Alpine.data('chercherDansUrne', (lignes) => ({
        recu: '',
        etat: null,
        numero: null,
        async init() {
            const code = decodeURIComponent(location.hash.slice(1));
            if (/^[A-Za-z2-9-]{12,16}$/.test(code)) {
                history.replaceState(null, '', location.pathname); // le reçu ne reste pas dans l'adresse
                this.recu = code;
                await this.chercher();
            }
        },
        async chercher() {
            const normal = this.recu.trim().replace(/[\s-]/g, '').toUpperCase();
            const octets = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(normal));
            const empreinte = [...new Uint8Array(octets)].map((o) => o.toString(16).padStart(2, '0')).join('');
            const ligne = lignes.find(([, e]) => e === empreinte);
            this.numero = ligne ? ligne[0] : null;
            this.etat = ligne ? 'trouve' : 'absent';
            if (ligne) {
                this.$nextTick(() => document.getElementById(`bulletin-${ligne[0]}`)?.scrollIntoView({ block: 'center', behavior: 'smooth' }));
            }
        },
    }));

    // Page de vérification ouverte depuis un ancien lien : le reçu est lu après « # »
    window.Alpine.data('recuDepuisAdresse', () => ({
        init() {
            const code = decodeURIComponent(location.hash.slice(1));
            if (/^[A-Za-z2-9-]{12,16}$/.test(code)) {
                history.replaceState(null, '', location.pathname);
                this.$wire.set('recu', code).then(() => this.$wire.verifier());
            }
        },
    }));
});

// La barre de progression de wire:navigate porte role="bar", qui n'existe pas en ARIA : on la rend purement décorative
new MutationObserver(() => {
    document.querySelectorAll('[role="bar"]').forEach((barre) => {
        barre.removeAttribute('role');
        barre.setAttribute('aria-hidden', 'true');
    });
}).observe(document.documentElement, { childList: true, subtree: true });

// Session expirée (longue inactivité, souvent sur l'écran de vote) : un message dans la langue de la page,
// à la place de la boîte de dialogue en anglais de Livewire
let sessionExpiree = false;
document.addEventListener('livewire:init', () => {
    window.Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status !== 419) return;
            preventDefault();
            if (sessionExpiree) return;
            sessionExpiree = true;
            const message = document.documentElement.lang === 'mg'
                ? 'Lany ny fotoana nidiranao noho ny fahanginana ela.\nTsy voaray ny taratasim-pifidiananao raha mbola tsy nisy « Nifidy ».\nHamerina hamaky ny pejy ?'
                : 'Votre session a expiré après une longue inactivité.\nSi « A voté » ne s’est pas affiché, votre bulletin n’a pas été déposé.\nRecharger la page ?';
            if (window.confirm(message)) window.location.reload();
        });
    });
});
