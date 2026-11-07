<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function nieaktywny(): User
    {
        $user = $this->createUser();
        $user->forceFill(['email_verified_at' => null])->save();

        return $user;
    }

    private function link(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->getEmailForVerification()),
        ]);
    }

    public function test_rejestracja_wysyla_powiadomienie_i_przekierowuje_na_informacje(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Jan Kowalski',
            'email' => 'jan@example.com',
            'gender' => 'M',
            'pesel' => '44051401359',
            'password' => 'Haslo123',
            'password_confirmation' => 'Haslo123',
        ])->assertRedirect('/email/verify');

        $user = User::where('email', 'jan@example.com')->first();

        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_nieaktywne_konto_jest_przekierowane_na_informacje(): void
    {
        $this->actingAs($this->nieaktywny());

        $this->get('/dashboard')->assertRedirect('/email/verify');
        $this->get('/transfers')->assertRedirect('/email/verify');
        $this->get('/email/verify')->assertOk();
    }

    public function test_nieaktywny_administrator_nie_wejdzie_do_panelu(): void
    {
        $admin = $this->createUser('admin');
        $admin->forceFill(['email_verified_at' => null])->save();

        $this->actingAs($admin)->get('/admin/users')->assertRedirect('/email/verify');
    }

    public function test_poprawny_podpisany_link_aktywuje_konto(): void
    {
        $user = $this->nieaktywny();

        $this->actingAs($user)->get($this->link($user))->assertRedirect('/dashboard');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/dashboard')->assertOk();
    }

    public function test_zmieniony_link_nie_aktywuje_konta(): void
    {
        $user = $this->nieaktywny();

        $this->actingAs($user)->get($this->link($user).'x')->assertForbidden();
        $this->get($this->link($user, 'zlyhash'))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_ponowna_wysylka_linku(): void
    {
        Notification::fake();
        $user = $this->nieaktywny();

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_aktywne_konto_ma_dostep(): void
    {
        $this->actingAs($this->createUser())->get('/dashboard')->assertOk();
    }
}
