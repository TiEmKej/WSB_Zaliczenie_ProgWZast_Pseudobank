<?php

namespace Tests\Feature;

use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
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
        $this->actingAs($this->sender);
    }

    private function dane(array $zmiany = []): array
    {
        return array_merge([
            'account_number' => $this->recipient->account_number,
            'recipient_name' => 'Jan Kowalski',
            'recipient_address' => 'ul. Długa 5, 00-001 Warszawa',
            'amount' => '100.50',
            'title' => 'Za obiad',
        ], $zmiany);
    }

    private function zlozPrzelew(array $zmiany = [])
    {
        return $this->post('/transfers', $this->dane($zmiany));
    }

    private function assertSaldaBezZmian(): void
    {
        $this->assertEquals(1000, $this->sender->fresh()->balance);
        $this->assertEquals(1000, $this->recipient->fresh()->balance);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_poprawny_przelew_zmienia_salda_i_tworzy_wpis(): void
    {
        $this->zlozPrzelew()->assertRedirect('/transfers/confirm');
        $this->get('/transfers/confirm')->assertOk()->assertSee('Za obiad');
        $this->post('/transfers/confirm')->assertRedirect('/dashboard');

        $this->assertEquals(899.50, $this->sender->fresh()->balance);
        $this->assertEquals(1100.50, $this->recipient->fresh()->balance);
        $this->assertDatabaseHas('transfers', [
            'sender_id' => $this->sender->id,
            'recipient_id' => $this->recipient->id,
            'recipient_name' => 'Jan Kowalski',
            'recipient_address' => 'ul. Długa 5, 00-001 Warszawa',
            'amount' => '100.50',
            'title' => 'Za obiad',
        ]);
    }

    public function test_przed_potwierdzeniem_salda_sie_nie_zmieniaja(): void
    {
        $this->zlozPrzelew()->assertRedirect('/transfers/confirm');

        $this->assertSaldaBezZmian();
    }

    public function test_ponowne_potwierdzenie_nie_wykonuje_przelewu_drugi_raz(): void
    {
        $this->zlozPrzelew();
        $this->post('/transfers/confirm');
        $this->post('/transfers/confirm')->assertRedirect('/transfers/create');

        $this->assertEquals(899.50, $this->sender->fresh()->balance);
        $this->assertSame(1, Transfer::count());
    }

    public function test_potwierdzenie_bez_danych_w_sesji_przekierowuje_do_formularza(): void
    {
        $this->get('/transfers/confirm')->assertRedirect('/transfers/create');
        $this->post('/transfers/confirm')->assertRedirect('/transfers/create');

        $this->assertSaldaBezZmian();
    }

    public function test_przelew_bez_srodkow_jest_odrzucony(): void
    {
        $this->zlozPrzelew(['amount' => '1000.01'])->assertSessionHasErrors('amount');

        $this->assertSaldaBezZmian();
    }

    public function test_brak_srodkow_przy_potwierdzeniu_jest_odrzucony(): void
    {
        $this->zlozPrzelew(['amount' => '800']);
        $this->sender->forceFill(['balance' => 100])->save();

        $this->post('/transfers/confirm')->assertRedirect('/transfers/create')->assertSessionHasErrors('amount');

        $this->assertEquals(100, $this->sender->fresh()->balance);
        $this->assertEquals(1000, $this->recipient->fresh()->balance);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_przelew_na_wlasne_konto_jest_odrzucony(): void
    {
        $this->zlozPrzelew(['account_number' => $this->sender->account_number])
            ->assertSessionHasErrors('account_number');

        $this->assertSaldaBezZmian();
    }

    public function test_przelew_na_nieistniejacy_numer_jest_odrzucony(): void
    {
        $this->zlozPrzelew(['account_number' => '00000000000000000000000000'])
            ->assertSessionHasErrors('account_number');

        $this->assertSaldaBezZmian();
    }

    public function test_przelew_na_konto_administratora_jest_odrzucony(): void
    {
        $admin = $this->createUser('admin');

        $this->zlozPrzelew(['account_number' => $admin->account_number])
            ->assertSessionHasErrors('account_number');
    }

    public function test_zle_kwoty_sa_odrzucane(): void
    {
        foreach (['0', '-5', 'abc', ''] as $kwota) {
            $this->zlozPrzelew(['amount' => $kwota])->assertSessionHasErrors('amount');
        }

        $this->assertSaldaBezZmian();
    }

    public function test_pusty_tytul_jest_odrzucony(): void
    {
        $this->zlozPrzelew(['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_administrator_i_gosc_nie_maja_dostepu(): void
    {
        $this->actingAs($this->createUser('admin'))->get('/transfers/create')->assertForbidden();

        auth()->logout();
        $this->get('/transfers/create')->assertRedirect('/login');
    }

    public function test_numer_konta_sprawdza_wyrazenie_regularne(): void
    {
        $numer = $this->recipient->account_number;

        foreach ([substr($numer, 0, 25), $numer.'1', substr($numer, 0, 25).'a'] as $zly) {
            $this->zlozPrzelew(['account_number' => $zly])->assertSessionHasErrors('account_number');
        }

        $this->zlozPrzelew()->assertSessionHasNoErrors();
    }

    public function test_kwota_sprawdza_wyrazenie_regularne(): void
    {
        foreach (['10.999', '10,50', '1e3', '12345678901', '.5'] as $zla) {
            $this->zlozPrzelew(['amount' => $zla])->assertSessionHasErrors('amount');
        }

        foreach (['10', '10.5', '10.99'] as $dobra) {
            $this->zlozPrzelew(['amount' => $dobra])->assertSessionHasNoErrors();
        }
    }

    public function test_tytul_sprawdza_wyrazenie_regularne(): void
    {
        foreach (['ab', '<script>', 'Tytul; DROP', str_repeat('a', 141)] as $zly) {
            $this->zlozPrzelew(['title' => $zly])->assertSessionHasErrors('title');
        }

        $this->zlozPrzelew(['title' => 'Zwrot za bilety, nr 12-3.'])->assertSessionHasNoErrors();
    }

    public function test_suma_sald_jest_taka_sama_przed_i_po_przelewach(): void
    {
        $suma = fn () => User::sum('balance');
        $przed = $suma();

        $this->zlozPrzelew(['amount' => '123.45']);
        $this->post('/transfers/confirm');
        $this->zlozPrzelew(['amount' => '0.01']);
        $this->post('/transfers/confirm');

        $this->assertEquals($przed, $suma());
    }

    public function test_formularz_pokazuje_konto_zrodlowe_i_saldo(): void
    {
        $this->get('/transfers/create')
            ->assertOk()
            ->assertSee('Przelew z konta')
            ->assertSee($this->sender->formattedAccountNumber())
            ->assertSee('1 000,00');
    }

    public function test_potwierdzenie_pokazuje_konto_dane_odbiorcy_i_saldo_po_przelewie(): void
    {
        $this->zlozPrzelew();

        $this->get('/transfers/confirm')
            ->assertOk()
            ->assertSee($this->sender->formattedAccountNumber())
            ->assertSee('1 000,00')
            ->assertSee($this->recipient->formattedAccountNumber())
            ->assertSee('Jan Kowalski')
            ->assertSee('ul. Długa 5, 00-001 Warszawa')
            ->assertSee('100,50')
            ->assertSee('899,50');
    }

    public function test_formularz_ma_cztery_pola_przelewu(): void
    {
        $this->get('/transfers/create')
            ->assertSee('name="account_number"', false)
            ->assertSee('name="recipient_name"', false)
            ->assertSee('name="recipient_address"', false)
            ->assertSee('name="amount"', false)
            ->assertSee('name="title"', false);
    }

    public function test_nazwa_i_adres_odbiorcy_sa_wymagane(): void
    {
        $this->zlozPrzelew(['recipient_name' => ''])
            ->assertSessionHasErrors(['recipient_name' => 'Podaj nazwę odbiorcy.']);
        $this->zlozPrzelew(['recipient_address' => ''])
            ->assertSessionHasErrors(['recipient_address' => 'Podaj adres odbiorcy.']);

        $this->assertSaldaBezZmian();
    }

    public function test_nazwa_i_adres_odbiorcy_sprawdzaja_wyrazenie_regularne(): void
    {
        foreach (['J', '<script>', 'Jan; DROP', str_repeat('a', 101)] as $zla) {
            $this->zlozPrzelew(['recipient_name' => $zla])->assertSessionHasErrors('recipient_name');
        }
        foreach (['ul', '<b>adres</b>', str_repeat('a', 151)] as $zly) {
            $this->zlozPrzelew(['recipient_address' => $zly])->assertSessionHasErrors('recipient_address');
        }

        $this->zlozPrzelew(['recipient_name' => "Anna O'Neil-Nowak", 'recipient_address' => 'ul. Polna 3/5, 00-950 Łódź'])
            ->assertSessionHasNoErrors();
    }
}
