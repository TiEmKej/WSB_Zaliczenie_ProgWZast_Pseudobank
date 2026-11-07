<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_profil_klienta_pokazuje_dane(): void
    {
        $user = $this->createUser()->fresh();
        $user->forceFill(['pesel' => '02070803628', 'name' => 'Anna Nowak'])->save();

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Anna Nowak')
            ->assertSee($user->email)
            ->assertSee('02070803628')
            ->assertSee('08.07.1902')
            ->assertSee('kobieta')
            ->assertSee('klient')
            ->assertSee($user->formattedAccountNumber())
            ->assertSee('1 000,00');
    }

    public function test_profil_administratora_nie_ma_konta_bankowego(): void
    {
        $this->actingAs($this->createUser('admin'))->get('/profile')
            ->assertOk()
            ->assertSee('administrator')
            ->assertDontSee('Numer konta')
            ->assertDontSee('Saldo');
    }

    public function test_profil_wymaga_logowania_i_aktywacji(): void
    {
        $this->get('/profile')->assertRedirect('/login');

        $user = $this->createUser();
        $user->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($user)->get('/profile')->assertRedirect('/email/verify');
    }

    public function test_menu_ma_linki_do_profilu_i_odbiorcow(): void
    {
        $this->actingAs($this->createUser())->get('/dashboard')
            ->assertSee(route('profile'))
            ->assertSee(route('recipients.index'));

        $this->actingAs($this->createUser('admin'))->get('/profile')
            ->assertSee(route('profile'))
            ->assertDontSee(route('recipients.index'));
    }
}
