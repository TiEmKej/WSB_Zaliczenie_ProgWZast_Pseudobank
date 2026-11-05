<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_klient_na_panelu_admina_dostaje_403(): void
    {
        $this->actingAs($this->createUser())->get('/admin/users')->assertForbidden();
    }

    public function test_administrator_widzi_panel(): void
    {
        $this->actingAs($this->createUser('admin'))->get('/admin/users')->assertOk();
    }

    public function test_niezalogowany_jest_przekierowany_na_logowanie(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_strona_glowna_przekierowuje_do_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
        $this->actingAs($this->createUser())->get('/')->assertRedirect('/dashboard');
    }

    public function test_dashboard_pokazuje_klientowi_saldo_i_numer_konta(): void
    {
        $user = $this->createUser()->fresh();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee($user->account_number)
            ->assertSee('1 000,00');
    }

    public function test_dashboard_przekierowuje_administratora_do_panelu(): void
    {
        $this->actingAs($this->createUser('admin'))->get('/dashboard')->assertRedirect('/admin/users');
    }
}
