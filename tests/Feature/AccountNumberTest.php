<?php

namespace Tests\Feature;

use App\Support\AccountNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_formatowanie_to_dwie_cyfry_a_potem_grupy_po_cztery(): void
    {
        $this->assertSame(
            '29 5234 6767 1873 3454 8751 4799',
            AccountNumber::format('29523467671873345487514799'),
        );
    }

    public function test_niepoprawny_numer_nie_jest_formatowany(): void
    {
        $this->assertSame('123', AccountNumber::format('123'));
        $this->assertSame('', AccountNumber::format(null));
    }

    public function test_normalizacja_usuwa_spacje(): void
    {
        $this->assertSame('29523467671873345487514799', AccountNumber::normalize(" 29 5234 6767\t1873 3454 8751 4799 "));
    }

    public function test_numer_jest_sformatowany_w_widokach(): void
    {
        $klient = $this->createUser()->fresh();
        $format = $klient->formattedAccountNumber();

        $this->assertMatchesRegularExpression('/^\d{2}( \d{4}){6}$/', $format);
        $this->actingAs($klient)->get('/dashboard')->assertSee($format);
        $this->get('/profile')->assertSee($format);
    }

    public function test_przelew_przyjmuje_numer_ze_spacjami(): void
    {
        $nadawca = $this->createUser()->fresh();
        $odbiorca = $this->createUser()->fresh();

        $this->actingAs($nadawca)->post('/transfers', [
            'account_number' => $odbiorca->formattedAccountNumber(),
            'recipient_name' => 'Jan Kowalski',
            'recipient_address' => 'ul. Długa 5, Warszawa',
            'amount' => '10',
            'title' => 'Za obiad',
        ])->assertRedirect('/transfers/confirm');

        $this->get('/transfers/confirm')->assertSee($odbiorca->formattedAccountNumber());
        $this->post('/transfers/confirm');

        $this->assertEquals(1010, $odbiorca->fresh()->balance);
    }
}
