<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PolishLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_jezykiem_aplikacji_jest_polski(): void
    {
        $this->assertSame('pl', app()->getLocale());
    }

    public function test_strony_bledow_sa_po_polsku(): void
    {
        $this->get('/nie-ma-takiej-strony')->assertNotFound()->assertSee('Nie znaleziono strony');
        $this->actingAs($this->createUser())->get('/admin/users')->assertForbidden()->assertSee('Brak dostępu');
    }

    public function test_paginacja_jest_po_polsku(): void
    {
        $this->actingAs($this->createUser('admin'));
        for ($i = 0; $i < 25; $i++) {
            $this->createUser();
        }

        $this->get('/admin/users')->assertSee('Następna')->assertSee('Wyświetlono')->assertDontSee('Showing');
    }

    public function test_komunikaty_walidacji_bez_wlasnego_tekstu_sa_po_polsku(): void
    {
        $blad = Validator::make(['title' => ''], ['title' => 'required'])->errors()->first('title');

        $this->assertSame('Pole tytuł jest wymagane.', $blad);
    }

    public function test_tlumaczenia_maila_aktywacyjnego(): void
    {
        $this->assertSame('Potwierdź adres e-mail', Lang::get('Verify Email Address'));
        $this->assertSame('Witaj!', Lang::get('Hello!'));
    }

    public function test_role_w_panelu_admina_sa_po_polsku(): void
    {
        $this->actingAs($this->createUser('admin'));

        $this->get('/admin/users')->assertSee('administrator')->assertDontSee('>admin<', false)->assertDontSee('>user<', false);
        $this->get('/admin/users/create')->assertSee('klient')->assertDontSee('>user<', false);
    }
}
