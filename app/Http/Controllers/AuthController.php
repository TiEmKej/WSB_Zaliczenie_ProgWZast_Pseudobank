<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\ValidPesel;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-]{2,100}$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'gender' => ['required', 'in:K,M'],
            'pesel' => ['bail', 'required', 'regex:/^\d{11}$/', 'unique:users', new ValidPesel($request->input('gender'))],
            'password' => ['required', 'confirmed', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/'],
        ], [
            'name.required' => 'Podaj imię i nazwisko.',
            'name.regex' => 'Imię i nazwisko może zawierać tylko litery, spacje i myślniki.',
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Adres e-mail ma nieprawidłowy format.',
            'email.unique' => 'Ten adres e-mail jest już zajęty.',
            'gender.required' => 'Wybierz płeć.',
            'gender.in' => 'Wybrana płeć jest nieprawidłowa.',
            'pesel.required' => 'Podaj numer PESEL.',
            'pesel.regex' => 'PESEL musi składać się z 11 cyfr.',
            'pesel.unique' => 'Ten numer PESEL jest już zarejestrowany.',
            'password.required' => 'Podaj hasło.',
            'password.confirmed' => 'Hasła nie są takie same.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.regex' => 'Hasło musi zawierać małą i wielką literę oraz cyfrę.',
        ]);

        $user = User::create(Arr::except($data, 'gender'));

        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Podaj adres e-mail.',
            'email.email' => 'Adres e-mail ma nieprawidłowy format.',
            'password.required' => 'Podaj hasło.',
        ]);

        if (! Auth::attempt($credentials)) {
            return back()->withErrors(['email' => 'Nieprawidłowy e-mail lub hasło.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function verifyNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('dashboard')->with('status', 'Konto zostało aktywowane.');
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Wysłaliśmy nowy link aktywacyjny.');
    }
}
