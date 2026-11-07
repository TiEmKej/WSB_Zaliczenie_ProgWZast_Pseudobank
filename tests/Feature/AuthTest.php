<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function dane(array $zmiany = []): array
    {
        return array_merge([
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'gender' => 'M',
            'pesel' => '44051401359',
            'password' => 'Haslo123',
            'password_confirmation' => 'Haslo123',
        ], $zmiany);
    }

    public function test_formularze_sa_dostepne_dla_goscia(): void
    {
        $this->get('/register')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_rejestracja_tworzy_uzytkownika_z_zahashowanym_haslem_i_rola_user(): void
    {
        Notification::fake();

        $this->post('/register', $this->dane())->assertRedirect('/email/verify');

        $user = User::where('email', 'jan@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotSame('Haslo123', $user->password);
        $this->assertTrue(Hash::check('Haslo123', $user->password));
        $this->assertSame('user', $user->role);
        $this->assertSame(26, strlen($user->account_number));
        $this->assertAuthenticatedAs($user);
    }

    public function test_puste_pola_daja_bledy_walidacji(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_zajety_email_daje_blad(): void
    {
        $this->createUser();
        $istniejacy = User::first();

        $this->post('/register', $this->dane(['email' => $istniejacy->email]))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_rozne_hasla_daja_blad(): void
    {
        $this->post('/register', $this->dane(['password_confirmation' => 'Inne1234']))
            ->assertSessionHasErrors('password');
    }

    public function test_dopisanie_roli_admin_nie_nadaje_uprawnien(): void
    {
        $this->post('/register', $this->dane(['role' => 'admin', 'balance' => 999999]));

        $user = User::where('email', 'jan@example.com')->first();

        $this->assertSame('user', $user->role);
        $this->assertEquals(1000, $user->balance);
    }

    public function test_logowanie_poprawnym_haslem_dziala(): void
    {
        $user = $this->createUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'Haslo123'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_logowanie_zlym_haslem_nie_dziala(): void
    {
        $user = $this->createUser();

        $this->post('/login', ['email' => $user->email, 'password' => 'Zle12345'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wylogowanie_konczy_sesje(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_zalogowany_nie_widzi_formularza_logowania(): void
    {
        $this->actingAs($this->createUser())->get('/login')->assertRedirect();
    }

    public function test_imie_i_nazwisko_sprawdza_wyrazenie_regularne(): void
    {
        Notification::fake();

        foreach (['Jan123', 'Jan_Kowalski', 'J', 'Jan <b>'] as $imie) {
            $this->post('/register', $this->dane(['name' => $imie]))->assertSessionHasErrors('name');
        }

        $this->post('/register', $this->dane(['name' => 'Żaneta Nowak-Kowalska']))->assertSessionHasNoErrors();
    }

    public function test_haslo_sprawdza_wyrazenie_regularne(): void
    {
        Notification::fake();

        foreach (['Haslobezcyfry', 'haslo1234', 'HASLO1234', 'Haslo12'] as $haslo) {
            $this->post('/register', $this->dane(['password' => $haslo, 'password_confirmation' => $haslo]))
                ->assertSessionHasErrors('password');
        }

        $this->post('/register', $this->dane())->assertSessionHasNoErrors();
    }

    public function test_pesel_jest_wymagany_przy_rejestracji(): void
    {
        Notification::fake();

        $this->post('/register', $this->dane(['pesel' => '']))->assertSessionHasErrors('pesel');
        $this->post('/register', $this->dane(['gender' => '']))->assertSessionHasErrors('gender');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_zly_pesel_jest_odrzucony(): void
    {
        Notification::fake();

        foreach (['4405140135', '44051401358', '44051401359a', '99999999999'] as $pesel) {
            $this->post('/register', $this->dane(['pesel' => $pesel]))->assertSessionHasErrors('pesel');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_pesel_o_innej_plci_niz_wybrana_jest_odrzucony(): void
    {
        Notification::fake();

        $this->post('/register', $this->dane(['gender' => 'K']))
            ->assertSessionHasErrors(['pesel' => 'Płeć zapisana w numerze PESEL nie zgadza się z wybraną płcią.']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_zajety_pesel_jest_odrzucony(): void
    {
        Notification::fake();
        $this->createUser()->forceFill(['pesel' => '44051401359'])->save();

        $this->post('/register', $this->dane())->assertSessionHasErrors('pesel');
    }

    public function test_pesel_poprawnej_plci_jest_zapisywany(): void
    {
        Notification::fake();

        $this->post('/register', $this->dane(['gender' => 'M']))->assertSessionHasNoErrors();

        $this->assertSame('44051401359', User::where('email', 'jan@example.com')->value('pesel'));
    }
}
