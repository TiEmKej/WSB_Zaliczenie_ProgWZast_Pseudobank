<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepeatTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;
    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->sender = $this->createUser()->fresh();
        $this->recipient = $this->createUser()->fresh();
    }

    private function przelew(string $kwota = '100.50'): Transfer
    {
        return Transfer::create([
            'sender_id' => $this->sender->id,
            'recipient_id' => $this->recipient->id,
            'amount' => $kwota,
            'title' => 'Czynsz',
        ]);
    }

    public function test_powtorzenie_prowadzi_do_potwierdzenia_z_tymi_samymi_danymi(): void
    {
        $przelew = $this->przelew();

        $this->actingAs($this->sender)->post("/transfers/{$przelew->id}/repeat")->assertRedirect('/transfers/confirm');

        $this->get('/transfers/confirm')->assertSee('Czynsz')->assertSee('100,50')->assertSee($this->recipient->name);
        $this->assertDatabaseCount('transfers', 1);
        $this->assertEquals(1000, $this->sender->fresh()->balance);
    }

    public function test_potwierdzenie_wykonuje_nowy_przelew(): void
    {
        $przelew = $this->przelew();

        $this->actingAs($this->sender)->post("/transfers/{$przelew->id}/repeat");
        $this->post('/transfers/confirm')->assertRedirect('/dashboard');

        $this->assertDatabaseCount('transfers', 2);
        $this->assertEquals(899.50, $this->sender->fresh()->balance);
        $this->assertEquals(1100.50, $this->recipient->fresh()->balance);
    }

    public function test_powtorzenie_nie_zapisuje_odbiorcy(): void
    {
        $przelew = $this->przelew();

        $this->actingAs($this->sender)->post("/transfers/{$przelew->id}/repeat");
        $this->post('/transfers/confirm');

        $this->assertDatabaseCount('recipients', 0);
    }

    public function test_nie_mozna_powtorzyc_cudzego_przelewu_ani_przychodzacego(): void
    {
        $przelew = $this->przelew();

        $this->actingAs($this->recipient)->post("/transfers/{$przelew->id}/repeat")->assertForbidden();
        $this->actingAs($this->createUser())->post("/transfers/{$przelew->id}/repeat")->assertForbidden();

        $this->assertNull(session('pending_transfer'));
    }

    public function test_powtorzenie_przy_braku_srodkow_jest_odrzucone(): void
    {
        $przelew = $this->przelew('500');
        $this->sender->forceFill(['balance' => 100])->save();

        $this->actingAs($this->sender)->from('/transfers')->post("/transfers/{$przelew->id}/repeat")
            ->assertRedirect('/transfers')
            ->assertSessionHasErrors('amount');
    }

    public function test_powtorzenie_przelewu_do_usunietego_odbiorcy_jest_odrzucone(): void
    {
        $przelew = $this->przelew();
        $this->recipient->delete();

        $this->actingAs($this->sender)->from('/transfers')->post("/transfers/{$przelew->id}/repeat")
            ->assertRedirect('/transfers')
            ->assertSessionHasErrors('transfer');
    }

    public function test_historia_ma_przycisk_tylko_przy_przelewach_wychodzacych(): void
    {
        $this->przelew();

        $this->actingAs($this->sender)->get('/transfers')->assertSee('Powtórz');
        $this->actingAs($this->recipient)->get('/transfers')->assertDontSee('Powtórz');
    }

    public function test_przycisk_nie_pojawia_sie_gdy_odbiorca_zostal_usuniety(): void
    {
        $this->przelew();
        $this->recipient->delete();

        $this->actingAs($this->sender)->get('/transfers')->assertDontSee('Powtórz');
    }

    public function test_gosc_i_administrator_nie_moga_powtarzac(): void
    {
        $przelew = $this->przelew();

        $this->post("/transfers/{$przelew->id}/repeat")->assertRedirect('/login');
        $this->actingAs($this->createUser('admin'))->post("/transfers/{$przelew->id}/repeat")->assertForbidden();
    }
}
