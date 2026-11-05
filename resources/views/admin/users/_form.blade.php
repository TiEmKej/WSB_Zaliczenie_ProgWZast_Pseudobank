@csrf
<div>
    <label for="name" class="block">Imię i nazwisko</label>
    <input id="name" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded border p-2">
</div>
<div>
    <label for="email" class="block">E-mail</label>
    <input id="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded border p-2">
</div>
<div>
    <label for="password" class="block">Hasło</label>
    <input id="password" type="password" name="password" class="w-full rounded border p-2">
    @if ($user->exists)
        <p class="text-sm text-slate-500">Zostaw puste, aby nie zmieniać hasła.</p>
    @endif
</div>
<div>
    <label for="role" class="block">Rola</label>
    <select id="role" name="role" class="w-full rounded border p-2">
        @foreach (['user', 'admin'] as $role)
            <option value="{{ $role }}" @selected(old('role', $user->role ?? 'user') === $role)>{{ $role }}</option>
        @endforeach
    </select>
</div>
<button class="w-full rounded bg-blue-600 p-2 text-white hover:bg-blue-700">Zapisz</button>
