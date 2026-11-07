<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\ValidPesel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const MESSAGES = [
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
        'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
        'password.regex' => 'Hasło musi zawierać małą i wielką literę oraz cyfrę.',
        'role.required' => 'Wybierz rolę.',
        'role.in' => 'Wybrana rola jest nieprawidłowa.',
    ];

    public function index(Request $request)
    {
        $search = (string) $request->query('q', '');

        $users = User::where('email', 'like', '%'.$search.'%')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'search' => $search]);
    }

    public function show(User $user)
    {
        return view('admin.users.show', ['user' => $user]);
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-]{2,100}$/u'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'gender' => ['required', 'in:K,M'],
            'pesel' => ['bail', 'required', 'regex:/^\d{11}$/', 'unique:users', new ValidPesel($request->input('gender'))],
            'password' => ['required', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/'],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ], self::MESSAGES);

        $user = new User(Arr::except($data, 'gender'));
        $user->forceFill(['role' => $data['role'], 'email_verified_at' => now()])->save();

        return redirect()->route('admin.users.index')->with('status', 'Użytkownik został dodany.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[\pL\s\-]{2,100}$/u'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'gender' => ['required', 'in:K,M'],
            'pesel' => ['bail', 'required', 'regex:/^\d{11}$/', Rule::unique('users')->ignore($user), new ValidPesel($request->input('gender'))],
            'password' => ['nullable', 'min:8', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/'],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ], self::MESSAGES);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'Nie możesz odebrać sobie roli.'])->withInput();
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill(Arr::except($data, 'gender'))->forceFill(['role' => $data['role']])->save();

        return redirect()->route('admin.users.index')->with('status', 'Użytkownik został zapisany.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Nie możesz usunąć własnego konta.']);
        }

        if ($user->balance > 0) {
            return back()->withErrors(['user' => 'Nie można usunąć użytkownika z saldem większym od zera.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Użytkownik został usunięty.');
    }
}
