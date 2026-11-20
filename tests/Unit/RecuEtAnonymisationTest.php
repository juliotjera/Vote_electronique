<?php

namespace Tests\Unit;

use App\Models\Journal;
use App\Services\Urne;
use PHPUnit\Framework\TestCase;

// Fonctions pures, sans base ni application : le reçu de vote et l'adresse IP tronquée du journal
class RecuEtAnonymisationTest extends TestCase
{
    public function test_un_recu_fait_douze_caracteres_lisibles_en_trois_groupes(): void
    {
        $recu = Urne::nouveauRecu();

        // ni 0, 1, O ou I : impossibles à confondre en recopiant le reçu
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $recu);
        $this->assertNotSame($recu, Urne::nouveauRecu());
    }

    public function test_l_empreinte_du_recu_ignore_espaces_tirets_et_casse(): void
    {
        // même normalisation que la recherche dans l'urne publique, faite dans le navigateur (resources/js/app.js)
        $attendue = hash('sha256', 'K7QF2MXP9TRA');

        foreach (['K7QF-2MXP-9TRA', 'k7qf 2mxp 9tra', '  K7QF2MXP9TRA  ', 'k7qf-2mxp 9TRA'] as $saisie) {
            $this->assertSame($attendue, Urne::empreinteRecu($saisie), $saisie);
        }
    }

    public function test_l_adresse_ip_du_journal_ne_garde_que_le_reseau(): void
    {
        $this->assertSame('41.188.12.0', Journal::anonymiser('41.188.12.203'));
        $this->assertSame('2001:db8:85a3::', Journal::anonymiser('2001:db8:85a3:8d3:1319:8a2e:370:7348'));
        $this->assertNull(Journal::anonymiser('pas une adresse'));
        $this->assertNull(Journal::anonymiser(null));
    }
}
