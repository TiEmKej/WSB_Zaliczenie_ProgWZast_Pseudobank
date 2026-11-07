<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_wyszukiwarka_znajduje_uzytkownika_po_emailu(): void
    {
        $admin = $this->createUser('admin');
        $this->createUser();
        $szukany = new User(['name' => 'Anna Nowak', 'email' => 'anna.szukana@example.com', 'password' => 'Haslo123', 'pesel' => '02070803628']);
        $szukany->save();

        $this->actingAs($admin)->get('/admin/users?q=szukana')
            ->assertOk()
            ->assertSee('anna.szukana@example.com')
            ->assertDontSee($admin->email);
    }

    public function test_iniekcja_w_wyszukiwarce_nie_zwraca_uzytkownikow(): void
    {
        $admin = $this->createUser('admin');
        $this->createUser();

        $this->actingAs($admin)->get('/admin/users?q='.urlencode("' OR 1=1 --"))
            ->assertOk()
            ->assertSee('Brak użytkowników.')
            ->assertDontSee($admin->email);
    }

    public function test_iniekcja_w_logowaniu_nie_loguje(): void
    {
        $this->createUser();

        $this->post('/login', ['email' => "' OR 1=1 --", 'password' => "' OR 1=1 --"])
            ->assertSessionHasErrors('email');
        $this->post('/login', ['email' => "x@example.com' OR '1'='1", 'password' => 'cokolwiek'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_drop_table_w_wyszukiwarce_nie_usuwa_tabeli(): void
    {
        $admin = $this->createUser('admin');

        $this->actingAs($admin)->get('/admin/users?q='.urlencode('x%"; DROP TABLE users; --'))->assertOk();

        $this->assertSame(1, User::count());
    }

    public function test_dane_uzytkownika_trafiaja_do_zapytania_jako_parametry(): void
    {
        $admin = $this->createUser('admin');
        $payload = "' OR 1=1 --";
        $zapytania = [];
        DB::listen(function ($query) use (&$zapytania) {
            $zapytania[] = $query;
        });

        $this->actingAs($admin)->get('/admin/users?q='.urlencode($payload));

        $like = collect($zapytania)->first(fn ($q) => str_contains($q->sql, 'like'));

        $this->assertNotNull($like);
        $this->assertStringNotContainsString('OR 1=1', $like->sql);
        $this->assertStringContainsString('?', $like->sql);
        $this->assertContains('%'.$payload.'%', $like->bindings);
    }
}
