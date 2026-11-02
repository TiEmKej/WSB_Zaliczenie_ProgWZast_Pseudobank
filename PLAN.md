# Plan aplikacji Pseudobank

Aplikacja bankowa na zaliczenie przedmiotu Programowanie w zastosowaniach.

Stos: Laravel 13, widoki Blade, zwykły CSS, MariaDB i Mailpit w kontenerach Sail.
Bez gotowych pakietów logowania (Breeze, Jetstream), bo logowanie jest częścią oceny i trzeba umieć je wyjaśnić.

Zasady:

- walidacja w kontrolerach przez `$request->validate()`, bez dodatkowych warstw,
- jeden użytkownik to jedno konto bankowe, więc numer konta i saldo są w tabeli `users`,
- po każdym kroku sprawdzam działanie w przeglądarce i robię commit.

## Wymagania i kroki, które je realizują

| Wymaganie | Krok |
| --- | --- |
| Rejestracja i logowanie oparte o bazę, szyfrowanie hasła | 3 |
| Dodawanie użytkowników (rejestracja lub administrator) | 3, 5 |
| Minimum dwa poziomy uprawnień | 2, 4 |
| Różne strony po zalogowaniu zależnie od uprawnień | 4 |
| CRUD użytkowników w panelu administracyjnym | 5 |
| Walidacja formularzy (wymagane pola) | 3, 5, 6 |
| Przelewy między klientami (sesja) | 6 |
| Saldo konta | 4 |
| Historia przelewów | 7 |
| Ocena dobra lub wyższa: aktywacja konta mailem | 8 |
| Ocena dobra lub wyższa: wyrażenia regularne | 9 |
| Ocena dobra lub wyższa: zabezpieczenie przed SQL Injection | 10 |

## Krok 1. Uruchomienie projektu

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh
```

`migrate:fresh` jest potrzebne, bo baza w kontenerze ma jeszcze tabele usunięte podczas czyszczenia projektu.

Sprawdzam:

- http://localhost pokazuje stronę startową,
- http://localhost:8025 pokazuje skrzynkę Mailpit.

## Krok 2. Baza danych i role

Rozszerzam migrację `create_users_table` (na tym etapie można ją zmieniać, potem `migrate:fresh`):

| Kolumna | Typ | Uwagi |
| --- | --- | --- |
| `role` | `string`, domyślnie `user` | `user` lub `admin` |
| `account_number` | `char(26)`, unikalna | numer konta |
| `balance` | `decimal(12, 2)`, domyślnie `1000` | saldo startowe |

Nowa migracja `create_transfers_table`:

| Kolumna | Typ | Uwagi |
| --- | --- | --- |
| `sender_id` | `foreignId`, nullable | `nullOnDelete()` |
| `recipient_id` | `foreignId`, nullable | `nullOnDelete()` |
| `amount` | `decimal(12, 2)` | kwota |
| `title` | `string` | tytuł przelewu |
| `created_at`, `updated_at` | `timestamps()` | data przelewu |

`nullOnDelete()` zachowuje historię drugiej strony po usunięciu użytkownika.

Do zrobienia:

- [ ] model `Transfer` z relacjami `sender()` i `recipient()`,
- [ ] w `User`: metoda `isAdmin()`, cast `'balance' => 'decimal:2'`, generowanie unikalnego numeru konta,
- [ ] `DatabaseSeeder`: administrator i dwóch klientów z ustawionym `email_verified_at`.

`role`, `account_number` i `balance` nie trafiają do `#[Fillable]`. Ustawiam je przez `forceFill()`, żeby nikt nie nadał sobie roli ani salda, dopisując pole do formularza.

Commit: `Tabele użytkowników i przelewów`

## Krok 3. Rejestracja, logowanie, wylogowanie

Plik `resources/views/layouts/app.blade.php` z menu, komunikatami i błędami walidacji oraz `public/css/app.css` dołączony przez `asset()`.

`AuthController`:

| Metoda | Trasa | Nazwa | Middleware |
| --- | --- | --- | --- |
| `showRegister` | `GET /register` | `register` | `guest` |
| `register` | `POST /register` | | `guest` |
| `showLogin` | `GET /login` | `login` | `guest` |
| `login` | `POST /login` | | `guest`, `throttle:5,1` |
| `logout` | `POST /logout` | `logout` | `auth` |

Trasa musi nazywać się `login`, bo tam Laravel przekierowuje niezalogowanych.

Do zrobienia:

- [ ] walidacja rejestracji: `required`, `email`, `unique:users`, `confirmed`, minimalna długość hasła,
- [ ] polskie komunikaty jako drugi argument `$request->validate()`,
- [ ] logowanie przez `Auth::attempt()`, potem `$request->session()->regenerate()`,
- [ ] wylogowanie: `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`,
- [ ] `@csrf` w każdym formularzu i `old('pole')` przy błędach.

Hasło szyfruje cast `'password' => 'hashed'` w modelu `User`, więc nie wywołuję dodatkowo `Hash::make()`.

Sprawdzam: w bazie hasło jest hashem `$2y$...`, a złe hasło daje komunikat błędu.

Commit: `Rejestracja i logowanie`

## Krok 4. Uprawnienia i strony zależne od roli

- [ ] middleware `IsAdmin` (`artisan make:middleware IsAdmin`), który dla innych ról zwraca `abort(403)`,
- [ ] alias w `bootstrap/app.php`: `$middleware->alias(['admin' => IsAdmin::class]);`,
- [ ] trasa `GET /dashboard` o nazwie `dashboard`: administratora przekierowuje do `/admin/users`, a klientowi pokazuje numer konta i saldo,
- [ ] `/` przekierowuje do `dashboard` albo `login`.

Sprawdzam: klient wchodzący na `/admin/users` dostaje 403, a niezalogowany trafia na logowanie.

Commit: `Role i strona klienta z saldem`

## Krok 5. Panel administracyjny (CRUD)

`Admin\UserController`, trasy w grupie z middleware `auth` i `admin`:

```php
Route::resource('admin/users', UserController::class)->names('admin.users');
```

| Akcja | Widok lub działanie |
| --- | --- |
| `index` | lista wszystkich użytkowników, paginacja |
| `show` | dane jednego użytkownika |
| `create`, `store` | dodawanie, od razu aktywne konto |
| `edit`, `update` | edycja, puste hasło oznacza brak zmiany |
| `destroy` | usuwanie z potwierdzeniem |

Reguły:

- [ ] przy edycji e-maila: `Rule::unique('users')->ignore($user)`,
- [ ] rola tylko z listy: `Rule::in(['user', 'admin'])`,
- [ ] administrator nie może usunąć swojego konta ani odebrać sobie roli,
- [ ] nie można usunąć klienta z saldem większym od zera,
- [ ] saldo nie jest edytowalne, zmieniają je tylko przelewy.

Commit: `Panel administracyjny do zarządzania użytkownikami`

## Krok 6. Przelewy (sesja)

Przelew ma dwa etapy, a jego dane między nimi są przechowywane w sesji:

1. `GET /transfers/create`: formularz z numerem konta odbiorcy, kwotą i tytułem.
2. `POST /transfers`: walidacja, zapis w `session(['pending_transfer' => [...]])`, przekierowanie do potwierdzenia.
3. `GET /transfers/confirm`: podsumowanie danych z sesji.
4. `POST /transfers/confirm`: `session()->pull('pending_transfer')` i wykonanie przelewu. Ponowne wysłanie formularza nie wykona go drugi raz, bo `pull` usuwa dane z sesji.

Trasy są w grupie `auth`, `verified`, dostępne tylko dla klientów.

Walidacja:

- [ ] odbiorca istnieje i jest klientem: `Rule::exists('users', 'account_number')->where('role', 'user')`,
- [ ] odbiorca to nie ja: `Rule::notIn([$user->account_number])`,
- [ ] kwota większa od zera, tytuł wymagany.

Wykonanie w transakcji, z ponownym sprawdzeniem salda pod blokadą:

```php
DB::transaction(function () use ($user, $recipient, $amount, $title) {
    $accounts = User::whereIn('id', [$user->id, $recipient->id])
        ->orderBy('id')
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

    if ($accounts[$user->id]->balance < $amount) {
        throw ValidationException::withMessages(['amount' => 'Brak środków na koncie.']);
    }

    $accounts[$user->id]->decrement('balance', $amount);
    $accounts[$recipient->id]->increment('balance', $amount);

    Transfer::create([...]);
});
```

Do wyjaśnienia na obronie:

- transakcja: oba salda zmieniają się razem albo wcale,
- `lockForUpdate`: dwa równoczesne przelewy nie zejdą poniżej zera,
- `orderBy('id')`: stała kolejność blokad zapobiega zakleszczeniu.

Sprawdzam: przelew większy niż saldo, na własne konto, na nieistniejący numer, kwota `0` i `-5`.

Commit: `Przelewy między klientami`

## Krok 7. Historia przelewów

- [ ] `GET /transfers`: przelewy, w których jestem nadawcą lub odbiorcą, od najnowszych, z paginacją,
- [ ] kolumny: data, druga strona, tytuł, kwota (wychodzące na minus, przychodzące na plus),
- [ ] `with(['sender', 'recipient'])`, żeby nie wykonywać zapytania dla każdego wiersza,
- [ ] jeśli druga strona to `null`, wyświetlam „konto usunięte”,
- [ ] link do historii i ostatnie przelewy na stronie klienta.

Sprawdzam: klient widzi tylko własne przelewy.

Commit: `Historia przelewów`

## Krok 8. Aktywacja konta przez e-mail (ocena dobra lub wyższa)

Używam wbudowanej weryfikacji Laravela:

- [ ] `User implements MustVerifyEmail`,
- [ ] po rejestracji: `Auth::login($user)` i `$user->sendEmailVerificationNotification()`,
- [ ] trasy:

| Trasa | Nazwa | Middleware | Działanie |
| --- | --- | --- | --- |
| `GET /email/verify` | `verification.notice` | `auth` | informacja o wysłaniu maila |
| `GET /email/verify/{id}/{hash}` | `verification.verify` | `auth`, `signed` | `EmailVerificationRequest`, `$request->fulfill()` |
| `POST /email/verification-notification` | `verification.send` | `auth`, `throttle:6,1` | ponowna wysyłka |

- [ ] middleware `verified` na trasach klienta i panelu administracyjnym,
- [ ] polska treść maila przez `VerifyEmail::toMailUsing()` (opcjonalnie).

Sprawdzam: nowe konto nie ma dostępu do `/dashboard`, a po kliknięciu linku w Mailpit (http://localhost:8025) ma.

Commit: `Aktywacja konta przez e-mail`

## Krok 9. Wyrażenia regularne (ocena dobra lub wyższa)

Reguły z `regex:` zapisuję jako tablicę, bo znak `|` w regexie psuje zapis tekstowy.

| Pole | Wyrażenie | Znaczenie |
| --- | --- | --- |
| Imię i nazwisko | `/^[\pL\s\-]{2,100}$/u` | litery, w tym polskie, spacje, myślniki |
| Hasło | `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/` | min. 8 znaków, mała i wielka litera, cyfra |
| Numer konta | `/^\d{26}$/` | dokładnie 26 cyfr |
| Kwota | `/^\d{1,10}(\.\d{1,2})?$/` | maks. 2 miejsca po kropce |
| Tytuł przelewu | `/^[\pL\d\s.,\-]{3,140}$/u` | litery, cyfry, podstawowa interpunkcja |

Na obronie trzeba umieć przeczytać każde z tych wyrażeń znak po znaku.

Commit: `Walidacja wyrażeniami regularnymi`

## Krok 10. SQL Injection i pozostałe zabezpieczenia (ocena dobra lub wyższa)

Eloquent i Query Builder używają zapytań przygotowanych: wartość jest przekazywana osobno od treści SQL.

- [ ] w kodzie nie ma `DB::raw`, `whereRaw` ani `DB::statement` z danymi użytkownika,
- [ ] wyszukiwarka użytkowników w `index` panelu:

```php
User::where('email', 'like', '%'.$request->query('q').'%')->paginate(20);
```

- [ ] pokaz na obronie: wpisanie `' OR 1=1 --` w wyszukiwarkę i logowanie nic nie daje, a `DB::listen` lub log zapytań pokazuje `?` w SQL.

Pozostałe zabezpieczenia do omówienia:

| Zagrożenie | Zabezpieczenie |
| --- | --- |
| XSS | `{{ }}` w Blade, bez `{!! !!}` |
| CSRF | `@csrf` |
| Przejęcie sesji | `session()->regenerate()` po logowaniu |
| Zgadywanie hasła | `throttle:5,1` na logowaniu |
| Nadanie sobie uprawnień | `role` i `balance` poza `#[Fillable]` |
| Wyciek hasła | hash bcrypt, `#[Hidden]` w modelu |

Commit: `Wyszukiwarka użytkowników i przegląd zabezpieczeń`

## Krok 11. Przygotowanie do oddania

Pełny test ręczny na czystej bazie (`./vendor/bin/sail artisan migrate:fresh --seed`):

- [ ] rejestracja, mail, aktywacja, logowanie, wylogowanie,
- [ ] puste formularze i błędne dane dają komunikaty po polsku,
- [ ] klient: saldo, przelew, potwierdzenie, historia po obu stronach,
- [ ] administrator: dodanie, podgląd, edycja, usunięcie, lista,
- [ ] klient nie ma dostępu do panelu, a niezalogowany do żadnej strony poza logowaniem i rejestracją,
- [ ] suma sald wszystkich kont jest taka sama przed przelewami i po nich.

Do zrobienia:

- [ ] `README.md`: opis, uruchomienie, dane kont testowych z seedera,
- [ ] w środowisku oddania `APP_DEBUG=false`, jeśli aplikacja będzie pokazywana poza komputerem lokalnym.

Do powtórzenia przed obroną:

- droga żądania: trasa, middleware, kontroler, model, widok,
- różnica między hashowaniem a szyfrowaniem oraz dlaczego bcrypt ma sól,
- do czego służy sesja w tej aplikacji (logowanie, przelew w toku, komunikaty),
- jak działa podpisany link aktywacyjny,
- każda migracja, regex i transakcja przelewu.
