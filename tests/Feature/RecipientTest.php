<?php

namespace Tests\Feature;

use App\Models\Recipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->user = $this->createUser()->fresh();
        $this->other = $this->createUser()->fresh();
        $this->actingAs($this->user);
    }

    private function dodaj(array $zmiany = [])
    {
        return $this->post('/recipients', array_merge([
            'name' => 'Mama Kowalska',
            'address' => 'ul. Polna 3, 00-950 Warszawa',
            'account_number' => $this->other->formattedAccountNumber(),
        ], $zmiany));
    }

    public function test_dodanie_odbiorcy_do_ksiazki(): void
    {
        $this->dodaj()->assertRedirect('/recipients');

        $this->assertDatabaseHas('recipients', [
            'user_id' => $this->user->id,
            'name' => 'Mama Kowalska',
            'address' => 'ul. Polna 3, 00-950 Warszawa',
            'account_number' => $this->other->account_number,
        ]);
        $this->get('/recipients')->assertOk()->assertSee('Mama Kowalska')->assertSee('ul. Polna 3, 00-950 Warszawa')->assertSee($this->other->formattedAccountNumber());
    }

    public function test_walidacja_odbiorcy(): void
    {
        $this->dodaj(['name' => ''])->assertSessionHasErrors('name');
        $this->dodaj(['name' => '<b>'])->assertSessionHasErrors('name');
        $this->dodaj(['address' => ''])->assertSessionHasErrors('address');
        $this->dodaj(['address' => '<b>'])->assertSessionHasErrors('address');
        $this->dodaj(['account_number' => ''])->assertSessionHasErrors('account_number');
        $this->dodaj(['account_number' => '123'])->assertSessionHasErrors('account_number');
        $this->dodaj(['account_number' => '00000000000000000000000000'])->assertSessionHasErrors('account_number');
        $this->dodaj(['account_number' => $this->user->account_number])->assertSessionHasErrors('account_number');
        $this->dodaj(['account_number' => $this->createUser('admin')->account_number])->assertSessionHasErrors('account_number');

        $this->assertDatabaseCount('recipients', 0);
    }

    public function test_ten_sam_odbiorca_nie_moze_byc_dodany_dwa_razy(): void
    {
        $this->dodaj();
        $this->dodaj(['name' => 'Mama druga'])->assertSessionHasErrors('account_number');

        $this->assertDatabaseCount('recipients', 1);
    }

    public function test_dwaj_uzytkownicy_moga_zapisac_tego_samego_odbiorce(): void
    {
        $this->dodaj();
        $this->actingAs($this->other)->post('/recipients', [
            'name' => 'Kolega',
            'address' => 'ul. Krótka 1, Gdańsk',
            'account_number' => $this->user->account_number,
        ])->assertSessionHasNoErrors();
        $trzeci = $this->createUser()->fresh();
        $this->actingAs($trzeci)->post('/recipients', [
            'name' => 'Ktos',
            'address' => 'ul. Krótka 2, Gdańsk',
            'account_number' => $this->other->account_number,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('recipients', 3);
    }

    public function test_ksiazka_pokazuje_tylko_wlasnych_odbiorcow(): void
    {
        $this->dodaj();
        $this->actingAs($this->other)->get('/recipients')->assertOk()->assertDontSee('Mama Kowalska');
    }

    public function test_usuniecie_odbiorcy(): void
    {
        $this->dodaj();
        $recipient = Recipient::first();

        $this->delete("/recipients/{$recipient->id}")->assertRedirect('/recipients');

        $this->assertModelMissing($recipient);
    }

    public function test_nie_mozna_usunac_cudzego_odbiorcy(): void
    {
        $this->dodaj();
        $recipient = Recipient::first();

        $this->actingAs($this->other)->delete("/recipients/{$recipient->id}")->assertForbidden();

        $this->assertModelExists($recipient);
    }

    public function test_formularz_przelewu_podstawia_odbiorce_z_ksiazki(): void
    {
        $this->dodaj();
        $recipient = Recipient::first();

        $this->get('/transfers/create')->assertSee('Mama Kowalska');
        $this->get("/transfers/create?recipient={$recipient->id}")
            ->assertSee('value="'.$this->other->formattedAccountNumber().'"', false)
            ->assertSee('value="Mama Kowalska"', false)
            ->assertSee('value="ul. Polna 3, 00-950 Warszawa"', false);
    }

    public function test_cudzy_odbiorca_nie_jest_podstawiany(): void
    {
        $this->dodaj();
        $recipient = Recipient::first();

        $this->actingAs($this->other)->get("/transfers/create?recipient={$recipient->id}")
            ->assertDontSee($this->other->formattedAccountNumber().'"', false);
    }

    public function test_przelew_z_zapisaniem_odbiorcy(): void
    {
        $this->post('/transfers', [
            'account_number' => $this->other->account_number,
            'recipient_name' => 'Kolega z pracy',
            'recipient_address' => 'ul. Krótka 1, Gdańsk',
            'amount' => '10',
            'title' => 'Za obiad',
            'save_recipient' => '1',
        ]);

        $this->assertDatabaseCount('recipients', 0);

        $this->post('/transfers/confirm');

        $this->assertDatabaseHas('recipients', [
            'user_id' => $this->user->id,
            'name' => 'Kolega z pracy',
            'address' => 'ul. Krótka 1, Gdańsk',
            'account_number' => $this->other->account_number,
        ]);
    }

    public function test_przelew_bez_zaznaczenia_nie_zapisuje_odbiorcy(): void
    {
        $this->post('/transfers', [
            'account_number' => $this->other->account_number,
            'recipient_name' => 'Kolega z pracy',
            'recipient_address' => 'ul. Krótka 1, Gdańsk',
            'amount' => '10',
            'title' => 'Za obiad',
        ]);
        $this->post('/transfers/confirm');

        $this->assertDatabaseCount('recipients', 0);
    }

    public function test_ponowny_zapis_tego_samego_odbiorcy_nie_tworzy_duplikatu(): void
    {
        $this->dodaj();

        $this->post('/transfers', [
            'account_number' => $this->other->account_number,
            'recipient_name' => 'Kolega z pracy',
            'recipient_address' => 'ul. Krótka 1, Gdańsk',
            'amount' => '10',
            'title' => 'Za obiad',
            'save_recipient' => '1',
        ]);
        $this->post('/transfers/confirm')->assertRedirect('/dashboard');

        $this->assertDatabaseCount('recipients', 1);
    }

    public function test_odbiorcy_znikaja_razem_z_wlascicielem(): void
    {
        $this->dodaj();

        $this->user->delete();

        $this->assertDatabaseCount('recipients', 0);
    }

    public function test_administrator_i_gosc_nie_maja_dostepu(): void
    {
        $this->actingAs($this->createUser('admin'))->get('/recipients')->assertForbidden();

        auth()->logout();
        $this->get('/recipients')->assertRedirect('/login');
    }
}
