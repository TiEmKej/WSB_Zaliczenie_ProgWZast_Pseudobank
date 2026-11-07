<?php

namespace Tests\Feature;

use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function przelew($nadawca, $odbiorca, string $tytul = 'Za obiad'): Transfer
    {
        return Transfer::create([
            'sender_id' => $nadawca->id,
            'recipient_id' => $odbiorca->id,
            'amount' => 25,
            'title' => $tytul,
        ]);
    }

    public function test_nadawca_i_odbiorca_widza_przelew_a_osoba_trzecia_nie(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $c = $this->createUser();
        $this->przelew($a, $b);

        $this->actingAs($a)->get('/transfers')->assertOk()->assertSee('Za obiad')->assertSee('−25,00');
        $this->actingAs($b)->get('/transfers')->assertOk()->assertSee('Za obiad')->assertSee('+25,00');
        $this->actingAs($c)->get('/transfers')->assertOk()->assertDontSee('Za obiad');
    }

    public function test_po_usunieciu_drugiej_strony_przelew_dalej_jest_w_historii(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $this->przelew($a, $b);

        $b->delete();

        $this->actingAs($a)->get('/transfers')
            ->assertOk()
            ->assertSee('Za obiad')
            ->assertSee('konto usunięte');
    }

    public function test_dashboard_pokazuje_ostatnie_przelewy(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $this->przelew($a, $b, 'Czynsz');

        $this->actingAs($a)->get('/dashboard')->assertOk()->assertSee('Czynsz');
    }

    public function test_administrator_i_gosc_nie_maja_dostepu(): void
    {
        $this->get('/transfers')->assertRedirect('/login');
        $this->actingAs($this->createUser('admin'))->get('/transfers')->assertForbidden();
    }
}
