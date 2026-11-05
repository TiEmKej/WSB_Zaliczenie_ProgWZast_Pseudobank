<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const MESSAGES = [
        'name.required' => 'Podaj imię i nazwisko.',
        'email.required' => 'Podaj adres e-mail.',
        'email.email' => 'Adres e-mail ma nieprawidłowy format.',
        'email.unique' => 'Ten adres e-mail jest już zajęty.',
        'password.required' => 'Podaj hasło.',
        'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
        'role.required' => 'Wybierz rolę.',
        'role.in' => 'Wybrana rola jest nieprawidłowa.',
    ];

    public function index()
    {
        return view('admin.users.index', ['users' => User::orderBy('id')->paginate(20)]);
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'min:8'],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ], self::MESSAGES);

        $user = new User($data);
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => ['nullable', 'min:8'],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ], self::MESSAGES);

        if ($user->is($request->user()) && $data['role'] !== $user->role) {
            return back()->withErrors(['role' => 'Nie możesz odebrać sobie roli.'])->withInput();
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data)->forceFill(['role' => $data['role']])->save();

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
