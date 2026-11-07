# Plan aplikacji Pseudobank

Aplikacja bankowa na zaliczenie przedmiotu Programowanie w zastosowaniach.

Stos: Laravel 13, widoki Blade, Tailwind CSS przez Vite, MariaDB i Mailpit w kontenerach Sail.
Bez gotowych pakietów logowania (Breeze, Jetstream), bo logowanie jest częścią oceny i trzeba umieć je wyjaśnić.

Zasady:

- walidacja w kontrolerach przez `$request->validate()`, bez dodatkowych warstw,
- jeden użytkownik to jedno konto bankowe, więc numer konta i saldo są w tabeli `users`,
- po każdym kroku sprawdzam działanie w przeglądarce, uruchamiam testy i robię commit.

## Postęp

| Krok | Stan |
| --- | --- |
| 1. Uruchomienie projektu | zrobiony |
| 2. Baza danych i role | zrobiony |
| 3. Rejestracja, logowanie, wylogowanie | zrobiony, 12 testów przechodzi |
| 4. Uprawnienia i strony zależne od roli | zrobiony |
| 5. Panel administracyjny (CRUD) | zrobiony |
| 6. Przelewy | zrobiony |
| 7. Historia przelewów | zrobiony |
| 8. Aktywacja konta przez e-mail | zrobiony |
| 9. Wyrażenia regularne | zrobiony |
| 10. SQL Injection i zabezpieczenia | następny |
| 11. Przygotowanie do oddania | do zrobienia |

Zmiany względem planu:

- doinstalowany `laravel/tinker` do sprawdzania danych w bazie,
- Faker nie jest zainstalowany, więc `createUser()` w `tests/TestCase.php` używa `uniqid()` do e-maila,
- trasy przelewów mają middleware `auth`, `verified` i `client` (własny `IsClient`),
- opcjonalna polska treść maila aktywacyjnego nie została zrobiona,
- layout leży w `resources/views/layouts/app.blade.php`,
- konta z seedera: `admin@example.com`, `piekarskim@example.com`, `uczelnis@example.com`, hasło `Haslo123`.

Do kroku 4: `/` ma przekierowywać do `dashboard` albo `login`, więc `HomeTest` trzeba przerobić, a `/admin/users` na razie jest tymczasową trasą do czasu kroku 5.

## Testy

Testy PHPUnit są w `tests/Feature` i uruchamia je `./vendor/bin/sail test`. Korzystają z osobnej bazy `testing` w MariaDB, więc nie naruszają danych aplikacji.

Zasady pisania testów:

- `use RefreshDatabase;` w każdej klasie testów korzystającej z bazy,
- `$this->withoutVite();` w `setUp()`, jeśli test wyświetla stronę, żeby nie wymagał zbudowanego frontendu,
- `$this->actingAs($user)` zamiast logowania w każdym teście,
- metoda pomocnicza `createUser(string $role = 'user')` w `tests/TestCase.php`, która tworzy użytkownika przez `forceFill()`,
- `Notification::fake()` przy mailach aktywacyjnych.

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
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

`migrate:fresh` jest potrzebne, bo baza w kontenerze ma jeszcze tabele usunięte podczas czyszczenia projektu.

`npm run dev` zostawiam włączone w osobnym terminalu podczas pracy, bo odświeża style na bieżąco. Bez niego albo bez wcześniejszego `npm run build` strona kończy się błędem o braku pliku manifestu Vite.

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

- [x] model `Transfer` z relacjami `sender()` i `recipient()`,
- [x] w `User`: metoda `isAdmin()`, cast `'balance' => 'decimal:2'`, generowanie unikalnego numeru konta,
- [x] `DatabaseSeeder`: administrator i dwóch klientów z ustawionym `email_verified_at`.

`role`, `account_number` i `balance` nie trafiają do `#[Fillable]`. Ustawiam je przez `forceFill()`, żeby nikt nie nadał sobie roli ani salda, dopisując pole do formularza.

Commit: `Tabele użytkowników i przelewów`

## Krok 3. Rejestracja, logowanie, wylogowanie

Plik `resources/views/layouts/app.blade.php` z menu, komunikatami i błędami walidacji. Style dołącza `@vite(['resources/css/app.css', 'resources/js/app.js'])`, a wygląd powstaje z klas Tailwinda.

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

- [x] walidacja rejestracji: `required`, `email`, `unique:users`, `confirmed`, minimalna długość hasła,
- [x] polskie komunikaty jako drugi argument `$request->validate()`,
- [x] logowanie przez `Auth::attempt()`, potem `$request->session()->regenerate()`,
- [x] wylogowanie: `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`,
- [x] `@csrf` w każdym formularzu i `old('pole')` przy błędach.

Hasło szyfruje cast `'password' => 'hashed'` w modelu `User`, więc nie wywołuję dodatkowo `Hash::make()`.

Sprawdzam: w bazie hasło jest hashem `$2y$...`, a złe hasło daje komunikat błędu.

Testy `AuthTest`:

- [x] poprawna rejestracja tworzy użytkownika z zahashowanym hasłem i rolą `user`,
- [x] puste pola i zajęty e-mail dają błędy walidacji,
- [x] dopisanie `role=admin` do formularza nie nadaje uprawnień,
- [x] logowanie poprawnym hasłem działa, a złym nie,
- [x] wylogowanie kończy sesję.

Commit: `Rejestracja i logowanie`

## Krok 4. Uprawnienia i strony zależne od roli

- [x] middleware `IsAdmin` (`artisan make:middleware IsAdmin`), który dla innych ról zwraca `abort(403)`,
- [x] alias w `bootstrap/app.php`: `$middleware->alias(['admin' => IsAdmin::class]);`,
- [x] trasa `GET /dashboard` o nazwie `dashboard`: administratora przekierowuje do `/admin/users`, a klientowi pokazuje numer konta i saldo,
- [x] `/` przekierowuje do `dashboard` albo `login`.

Sprawdzam: klient wchodzący na `/admin/users` dostaje 403, a niezalogowany trafia na logowanie.

Testy `RoleAccessTest`:

- [x] klient na `/admin/users` dostaje 403, a administrator 200,
- [x] niezalogowany jest przekierowany na `/login`,
- [x] `/dashboard` pokazuje klientowi saldo, a administratora przekierowuje do panelu.

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

- [x] przy edycji e-maila: `Rule::unique('users')->ignore($user)`,
- [x] rola tylko z listy: `Rule::in(['user', 'admin'])`,
- [x] administrator nie może usunąć swojego konta ani odebrać sobie roli,
- [x] nie można usunąć klienta z saldem większym od zera,
- [x] saldo nie jest edytowalne, zmieniają je tylko przelewy.

Testy `AdminUserTest`:

- [x] lista i podgląd działają, dodawanie, edycja i usuwanie zmieniają bazę,
- [x] edycja z pustym hasłem nie zmienia hasła,
- [x] administrator nie może usunąć siebie ani odebrać sobie roli,
- [x] nie można usunąć klienta z saldem większym od zera.

Commit: `Panel administracyjny do zarządzania użytkownikami`

## Krok 6. Przelewy (sesja)

Przelew ma dwa etapy, a jego dane między nimi są przechowywane w sesji:

1. `GET /transfers/create`: formularz z numerem konta odbiorcy, kwotą i tytułem.
2. `POST /transfers`: walidacja, zapis w `session(['pending_transfer' => [...]])`, przekierowanie do potwierdzenia.
3. `GET /transfers/confirm`: podsumowanie danych z sesji.
4. `POST /transfers/confirm`: `session()->pull('pending_transfer')` i wykonanie przelewu. Ponowne wysłanie formularza nie wykona go drugi raz, bo `pull` usuwa dane z sesji.

Trasy są w grupie `auth`, `verified`, dostępne tylko dla klientów.

Walidacja:

- [x] odbiorca istnieje i jest klientem: `Rule::exists('users', 'account_number')->where('role', 'user')`,
- [x] odbiorca to nie ja: `Rule::notIn([$user->account_number])`,
- [x] kwota większa od zera, tytuł wymagany.

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

Testy `TransferTest`:

- [x] poprawny przelew zmniejsza saldo nadawcy, zwiększa saldo odbiorcy i tworzy wpis w `transfers`,
- [x] przed potwierdzeniem salda się nie zmieniają,
- [x] ponowne wysłanie potwierdzenia nie wykonuje przelewu drugi raz,
- [x] przelew bez środków, na własne konto, na nieistniejący numer oraz kwoty `0` i `-5` są odrzucane, a salda bez zmian.

Commit: `Przelewy między klientami`

## Krok 7. Historia przelewów

- [x] `GET /transfers`: przelewy, w których jestem nadawcą lub odbiorcą, od najnowszych, z paginacją,
- [x] kolumny: data, druga strona, tytuł, kwota (wychodzące na minus, przychodzące na plus),
- [x] `with(['sender', 'recipient'])`, żeby nie wykonywać zapytania dla każdego wiersza,
- [x] jeśli druga strona to `null`, wyświetlam „konto usunięte”,
- [x] link do historii i ostatnie przelewy na stronie klienta.

Sprawdzam: klient widzi tylko własne przelewy.

Testy `TransferHistoryTest`:

- [x] nadawca i odbiorca widzą przelew, a osoba trzecia nie,
- [x] po usunięciu drugiej strony przelew dalej jest w historii.

Commit: `Historia przelewów`

## Krok 8. Aktywacja konta przez e-mail (ocena dobra lub wyższa)

Używam wbudowanej weryfikacji Laravela:

- [x] `User implements MustVerifyEmail`,
- [x] po rejestracji: `Auth::login($user)` i `$user->sendEmailVerificationNotification()`,
- [x] trasy:

| Trasa | Nazwa | Middleware | Działanie |
| --- | --- | --- | --- |
| `GET /email/verify` | `verification.notice` | `auth` | informacja o wysłaniu maila |
| `GET /email/verify/{id}/{hash}` | `verification.verify` | `auth`, `signed` | `EmailVerificationRequest`, `$request->fulfill()` |
| `POST /email/verification-notification` | `verification.send` | `auth`, `throttle:6,1` | ponowna wysyłka |

- [x] middleware `verified` na trasach klienta i panelu administracyjnym,
- [ ] polska treść maila przez `VerifyEmail::toMailUsing()` (opcjonalnie).

Sprawdzam: nowe konto nie ma dostępu do `/dashboard`, a po kliknięciu linku w Mailpit (http://localhost:8025) ma.

Testy `EmailVerificationTest`:

- [x] rejestracja wysyła powiadomienie `VerifyEmail`,
- [x] nieaktywne konto jest przekierowane na `/email/verify`,
- [x] poprawny podpisany link (`URL::temporarySignedRoute`) aktywuje konto, a zmieniony nie.

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

Testy dopisuję do `AuthTest` i `TransferTest`:

- [x] dla każdego wyrażenia przynajmniej jedna wartość poprawna i jedna błędna, np. numer konta z 25 cyframi, kwota `10.999`, hasło bez cyfry.

Commit: `Walidacja wyrażeniami regularnymi`

## Krok 10. SQL Injection i pozostałe zabezpieczenia (ocena dobra lub wyższa)

Eloquent i Query Builder używają zapytań przygotowanych: wartość jest przekazywana osobno od treści SQL.

- [ ] w kodzie nie ma `DB::raw`, `whereRaw` ani `DB::statement` z danymi użytkownika,
- [ ] wyszukiwarka użytkowników w `index` panelu:

```php
User::where('email', 'like', '%'.$request->query('q').'%')->paginate(20);
```

- [ ] pokaz na obronie: wpisanie `' OR 1=1 --` w wyszukiwarkę i logowanie nic nie daje, a `DB::listen` lub log zapytań pokazuje `?` w SQL.

Testy `SqlInjectionTest`:

- [ ] `' OR 1=1 --` w wyszukiwarce nie zwraca żadnego użytkownika,
- [ ] `' OR 1=1 --` jako e-mail przy logowaniu nie loguje,
- [ ] `'; DROP TABLE users; --` w wyszukiwarce nie usuwa tabeli.

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

Wszystkie testy muszą przechodzić: `./vendor/bin/sail test`.

Pełny test ręczny na czystej bazie (`./vendor/bin/sail artisan migrate:fresh --seed`):

- [ ] rejestracja, mail, aktywacja, logowanie, wylogowanie,
- [ ] puste formularze i błędne dane dają komunikaty po polsku,
- [ ] klient: saldo, przelew, potwierdzenie, historia po obu stronach,
- [ ] administrator: dodanie, podgląd, edycja, usunięcie, lista,
- [ ] klient nie ma dostępu do panelu, a niezalogowany do żadnej strony poza logowaniem i rejestracją,
- [ ] suma sald wszystkich kont jest taka sama przed przelewami i po nich.

Do zrobienia:

- [ ] `README.md`: opis, uruchomienie (w tym `npm install` i `npm run build`), dane kont testowych z seedera,
- [ ] w środowisku oddania `APP_DEBUG=false`, jeśli aplikacja będzie pokazywana poza komputerem lokalnym.

Do powtórzenia przed obroną:

- droga żądania: trasa, middleware, kontroler, model, widok,
- różnica między hashowaniem a szyfrowaniem oraz dlaczego bcrypt ma sól,
- do czego służy sesja w tej aplikacji (logowanie, przelew w toku, komunikaty),
- jak działa podpisany link aktywacyjny,
- każda migracja, regex i transakcja przelewu.
