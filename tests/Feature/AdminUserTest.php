<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->createUser('admin');
        $this->actingAs($this->admin);
    }

    private function dane(array $zmiany = []): array
    {
        return array_merge([
            'name' => 'Anna Nowak',
            'email' => 'anna@example.com',
            'gender' => 'K',
            'pesel' => '02070803628',
            'password' => 'Haslo123',
            'role' => 'user',
        ], $zmiany);
    }

    public function test_lista_i_podglad_dzialaja(): void
    {
        $user = $this->createUser();

        $this->get('/admin/users')->assertOk()->assertSee($user->email);
        $this->get("/admin/users/{$user->id}")->assertOk()->assertSee($user->formattedAccountNumber());
    }

    public function test_klient_nie_ma_dostepu_do_crud(): void
    {
        $klient = $this->createUser();
        $this->actingAs($klient);

        $this->post('/admin/users', $this->dane())->assertForbidden();
        $this->delete("/admin/users/{$klient->id}")->assertForbidden();
    }

    public function test_dodawanie_tworzy_aktywne_konto_z_zahashowanym_haslem(): void
    {
        $this->post('/admin/users', $this->dane(['role' => 'admin']))->assertRedirect('/admin/users');

        $user = User::where('email', 'anna@example.com')->first();

        $this->assertSame('admin', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Haslo123', $user->password));
        $this->assertSame(26, strlen($user->account_number));
    }

    public function test_dodawanie_odrzuca_puste_pola_zla_role_i_zajety_email(): void
    {
        $this->post('/admin/users', [])->assertSessionHasErrors(['name', 'email', 'password', 'role']);
        $this->post('/admin/users', $this->dane(['role' => 'superadmin']))->assertSessionHasErrors('role');
        $this->post('/admin/users', $this->dane(['email' => $this->admin->email]))->assertSessionHasErrors('email');
    }

    public function test_edycja_zmienia_dane(): void
    {
        $user = $this->createUser();

        $this->put("/admin/users/{$user->id}", $this->dane(['password' => 'Nowe12345']))
            ->assertRedirect('/admin/users');

        $user->refresh();
        $this->assertSame('Anna Nowak', $user->name);
        $this->assertSame('anna@example.com', $user->email);
        $this->assertTrue(Hash::check('Nowe12345', $user->password));
    }

    public function test_edycja_z_pustym_haslem_nie_zmienia_hasla(): void
    {
        $user = $this->createUser();
        $hash = $user->password;

        $this->put("/admin/users/{$user->id}", $this->dane(['password' => '']))->assertRedirect('/admin/users');

        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_edycja_pozwala_zachowac_wlasny_email(): void
    {
        $user = $this->createUser();

        $this->put("/admin/users/{$user->id}", $this->dane(['email' => $user->email, 'password' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_administrator_nie_moze_odebrac_sobie_roli(): void
    {
        $this->put("/admin/users/{$this->admin->id}", $this->dane(['email' => $this->admin->email, 'role' => 'user']))
            ->assertSessionHasErrors('role');

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_usuwanie_klienta_bez_salda(): void
    {
        $user = $this->createUser();
        $user->forceFill(['balance' => 0])->save();

        $this->delete("/admin/users/{$user->id}")->assertRedirect('/admin/users');

        $this->assertModelMissing($user);
    }

    public function test_administrator_nie_moze_usunac_siebie(): void
    {
        $this->delete("/admin/users/{$this->admin->id}")->assertSessionHasErrors('user');

        $this->assertModelExists($this->admin);
    }

    public function test_nie_mozna_usunac_klienta_z_saldem(): void
    {
        $user = $this->createUser();

        $this->delete("/admin/users/{$user->id}")->assertSessionHasErrors('user');

        $this->assertModelExists($user);
    }

    public function test_saldo_nie_jest_edytowalne(): void
    {
        $user = $this->createUser();

        $this->put("/admin/users/{$user->id}", $this->dane(['balance' => 999999]));

        $this->assertEquals(1000, $user->fresh()->balance);
    }

    public function test_administrator_dodaje_uzytkownika_tylko_z_poprawnym_peselem(): void
    {
        $this->post('/admin/users', $this->dane(['pesel' => '44051401358']))->assertSessionHasErrors('pesel');
        $this->post('/admin/users', $this->dane(['gender' => 'M']))->assertSessionHasErrors('pesel');
        $this->post('/admin/users', $this->dane(['pesel' => '']))->assertSessionHasErrors('pesel');
        $this->post('/admin/users', $this->dane())->assertSessionHasNoErrors();

        $this->assertSame('02070803628', User::where('email', 'anna@example.com')->value('pesel'));
    }

    public function test_edycja_pozwala_zachowac_wlasny_pesel(): void
    {
        $user = $this->createUser();
        $pesel = $user->pesel;

        $this->put("/admin/users/{$user->id}", $this->dane(['pesel' => $pesel, 'password' => '']))
            ->assertSessionHasNoErrors();
    }
}
