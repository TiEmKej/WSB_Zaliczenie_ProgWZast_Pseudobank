<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Pesel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeselTest extends TestCase
{
    use RefreshDatabase;

    public function test_poprawne_numery_pesel(): void
    {
        $this->assertTrue(Pesel::isValid('44051401359'));
        $this->assertTrue(Pesel::isValid('02070803628'));
    }

    public function test_zla_suma_kontrolna_dlugosc_i_znaki(): void
    {
        foreach (['44051401358', '4405140135', '440514013590', '4405140135a', '', '00000000000'] as $zly) {
            $this->assertFalse(Pesel::isValid($zly), $zly);
        }
    }

    public function test_nieistniejaca_data_urodzenia_jest_odrzucona(): void
    {
        $this->assertNull(Pesel::birthDate('44023001357'));
        $this->assertNull(Pesel::birthDate('44131401357'));
        $this->assertFalse(Pesel::isValid($this->zControlna('4402300135')));
        $this->assertFalse(Pesel::isValid($this->zControlna('9952300135')));
    }

    public function test_data_z_przyszlosci_jest_odrzucona(): void
    {
        $przyszly = now()->addYear();
        $pesel = sprintf('%02d%02d%02d123', $przyszly->year % 100, $przyszly->month + 20, $przyszly->day);

        $this->assertFalse(Pesel::isValid($this->zControlna($pesel.'4')));
    }

    public function test_data_urodzenia_i_plec_wynikaja_z_numeru(): void
    {
        $this->assertSame('1944-05-14', Pesel::birthDate('44051401359')->toDateString());
        $this->assertSame('M', Pesel::gender('44051401359'));
        $this->assertSame('1902-07-08', Pesel::birthDate('02070803628')->toDateString());
        $this->assertSame('K', Pesel::gender('02070803628'));
    }

    public function test_wiek_jest_odczytywany_z_miesiaca(): void
    {
        $this->assertSame('1999-12-31', Pesel::birthDate($this->zControlna('99123112345'))->toDateString());
        $this->assertSame('2005-03-09', Pesel::birthDate($this->zControlna('05230912345'))->toDateString());
        $this->assertSame('1899-06-15', Pesel::birthDate($this->zControlna('99861512345'))->toDateString());
    }

    public function test_generator_tworzy_poprawne_numery_z_wlasciwa_plcia(): void
    {
        foreach (['K', 'M'] as $plec) {
            for ($i = 0; $i < 200; $i++) {
                $pesel = Pesel::generate($plec);

                $this->assertTrue(Pesel::isValid($pesel), $pesel);
                $this->assertSame($plec, Pesel::gender($pesel), $pesel);
            }
        }
    }

    public function test_generator_uwzglednia_date_urodzenia(): void
    {
        foreach (['1850-01-02', '1944-05-14', '2003-12-31'] as $data) {
            $pesel = Pesel::generate('K', now()->setDateFrom(new \DateTime($data)));

            $this->assertSame($data, Pesel::birthDate($pesel)->toDateString());
        }
    }

    public function test_model_zwraca_date_i_plec(): void
    {
        $user = new User(['pesel' => '44051401359']);

        $this->assertSame('14.05.1944', $user->birthDate()->format('d.m.Y'));
        $this->assertSame('mężczyzna', $user->genderLabel());
        $this->assertSame('brak danych', (new User)->genderLabel());
    }

    public function test_endpoint_deweloperski_generuje_pesel(): void
    {
        $odpowiedz = $this->getJson('/dev/pesel?gender=K')->assertOk()->json();

        $this->assertTrue(Pesel::isValid($odpowiedz['pesel']));
        $this->assertSame('K', Pesel::gender($odpowiedz['pesel']));
        $this->assertSame('K', $odpowiedz['gender']);

        $losowy = $this->getJson('/dev/pesel')->assertOk()->json();
        $this->assertSame($losowy['gender'], Pesel::gender($losowy['pesel']));

        $this->getJson('/dev/pesel?gender=X')->assertStatus(422);
    }

    public function test_formularz_rejestracji_ma_przycisk_generatora(): void
    {
        $this->withoutVite();

        $this->get('/register')->assertOk()->assertSee('Generuj poprawny PESEL');
    }

    private function zControlna(string $dziesiec): string
    {
        $wagi = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];
        $suma = 0;
        foreach ($wagi as $i => $waga) {
            $suma += (int) $dziesiec[$i] * $waga;
        }

        return substr($dziesiec, 0, 10).((10 - $suma % 10) % 10);
    }
}
