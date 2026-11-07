@csrf
<x-field name="name" label="Imię i nazwisko" :value="old('name', $user->name)" />
<x-field name="email" label="E-mail" :value="old('email', $user->email)" />
@include('partials.pesel-fields')
<x-field name="password" label="Hasło" type="password" :hint="$user->exists ? 'Zostaw puste, aby nie zmieniać hasła.' : null" />
<x-select name="role" label="Rola" :options="['user' => 'klient', 'admin' => 'administrator']" :selected="old('role', $user->role ?? 'user')" />
<button class="btn btn-primary btn-block sm:col-span-2">Zapisz</button>
