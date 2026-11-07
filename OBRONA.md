# Ściągawka na obronę

## 1. Droga żądania

Przykład: klient wchodzi na `GET /transfers`.

1. `public/index.php` uruchamia aplikację z `bootstrap/app.php`.
2. Router dopasowuje trasę w `routes/web.php` i uruchamia jej middleware po kolei: `auth` (czy zalogowany), `verified` (czy konto aktywne), `client` (czy rola `user`, własny `IsClient`). Każdy z nich może przerwać żądanie przekierowaniem albo `abort(403)`.
3. Kontroler (`TransferController@index`) pobiera dane z modelu (`$user->transfers()`), a model zamienia je na zapytanie SQL.
4. Kontroler zwraca widok Blade (`resources/views/transfers/index.blade.php`) w layoucie `layouts/app.blade.php`. Wynik trafia do przeglądarki jako HTML.

Dla `POST` dodatkowo działa sprawdzenie tokenu CSRF (`@csrf` w formularzu).

## 2. Hashowanie a szyfrowanie

- **Szyfrowanie** jest odwracalne: z kluczem odzyskasz tekst jawny.
- **Hashowanie** jest jednokierunkowe: z hasha nie da się odtworzyć hasła, można tylko zahashować podane hasło i porównać wyniki.
- Hasła są hashowane bcryptem. Robi to cast `'password' => 'hashed'` w [User.php:22](app/Models/User.php#L22), więc nie wywołuję ręcznie `Hash::make()`. Logowanie porównuje przez `Auth::attempt()` ([AuthController.php:60](app/Http/Controllers/AuthController.php#L60)).
- **Sól:** bcrypt losuje sól dla każdego hasła i zapisuje ją w samym hashu (`$2y$koszt$sól+hash`). Dwie osoby z tym samym hasłem mają różne hashe, a tablice gotowych hashy (rainbow tables) nie działają.
- Bcrypt jest celowo wolny (parametr kosztu), co utrudnia zgadywanie haseł.
- `#[Hidden(['password', 'remember_token'])]` ukrywa te pola przy serializacji modelu do JSON.

## 3. Do czego służy sesja

1. **Logowanie:** po `Auth::attempt()` identyfikator użytkownika jest w sesji, a przeglądarka dostaje tylko ciasteczko z identyfikatorem sesji.
2. **Przelew w toku:** `session(['pending_transfer' => ...])` między formularzem a potwierdzeniem. `session()->pull()` ([TransferController.php:74](app/Http/Controllers/TransferController.php#L74)) odczytuje i od razu usuwa dane, więc ponowne wysłanie potwierdzenia nie wykona przelewu drugi raz.
3. **Komunikaty** po akcji (`with('status', ...)`, błędy walidacji, `old()`): zapisane w sesji na jedno kolejne żądanie (flash).
4. Token CSRF też leży w sesji.

Sesje są w plikach (`SESSION_DRIVER=file`). Po zalogowaniu `session()->regenerate()` ([AuthController.php:64](app/Http/Controllers/AuthController.php#L64)) zmienia identyfikator, co chroni przed przejęciem sesji (session fixation). Wylogowanie: `Auth::logout()`, `invalidate()`, `regenerateToken()`.

## 4. Podpisany link aktywacyjny

Link ma postać `/email/verify/{id}/{hash}?expires=...&signature=...`.

- `hash` to `sha1` adresu e-mail, więc link pasuje tylko do tego konta.
- `expires` to czas ważności (domyślnie 60 minut).
- `signature` to podpis HMAC-SHA256 całego adresu, liczony z kluczem aplikacji (`APP_KEY`). Zmiana dowolnego fragmentu (id, hash, czasu) unieważnia podpis.
- Middleware `signed` na trasie `verification.verify` sprawdza podpis i datę, a `EmailVerificationRequest` sprawdza zgodność `id` i `hash` z zalogowanym użytkownikiem. Przy niepowodzeniu jest 403.
- Po sukcesie `fulfill()` ustawia `email_verified_at`.
- Trasy w [routes/web.php](routes/web.php), test: [EmailVerificationTest.php](tests/Feature/EmailVerificationTest.php).

## 5. Migracje

| Migracja | Co robi |
| --- | --- |
| `0001_01_01_000000_create_users_table` | tabela `users`: `role` (domyślnie `user`), `account_number` `char(26)` unikalny, `balance` `decimal(12,2)` domyślnie 1000 |
| `2026_11_02_172820_create_transfers_table` | tabela `transfers`: `sender_id` i `recipient_id` (klucze obce do `users`, `nullable`, `nullOnDelete()`), `recipient_name` i `recipient_address` (dane odbiorcy z formularza), `amount`, `title`, `timestamps` |
| `2026_11_08_100000_create_recipients_table` | tabela `recipients` (książka odbiorców): `user_id` (klucz obcy, `cascadeOnDelete()`), `name`, `address`, `account_number`, unikalna para `(user_id, account_number)` |

- `decimal` zamiast `float`, bo pieniądze nie mogą mieć błędów zaokrągleń.
- `nullOnDelete()`: po usunięciu użytkownika historia przelewów drugiej strony zostaje, a w widoku jest „konto usunięte”.
- `role`, `account_number` i `balance` nie są w `#[Fillable]`, więc nikt nie nada sobie roli ani salda przez dopisanie pola do formularza. Numer konta losuje `creating` w [User.php:29](app/Models/User.php#L29).

## 6. Wyrażenia regularne

| Pole | Wyrażenie | Czytamy |
| --- | --- | --- |
| Imię i nazwisko | `/^[\pL\s\-]{2,100}$/u` | od początku do końca: 2–100 znaków, każdy to litera Unicode (`\pL`, także polskie), spacja lub myślnik; `u` włącza tryb Unicode |
| Hasło | `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/` | trzy warunki „zajrzenia w przód” (`(?=...)`): gdzieś jest mała litera, wielka litera i cyfra; potem co najmniej 8 dowolnych znaków |
| Numer konta | `/^\d{26}$/` | dokładnie 26 cyfr |
| Kwota | `/^\d{1,10}(\.\d{1,2})?$/` | 1–10 cyfr, opcjonalnie kropka i 1–2 cyfry (`10`, `10.5`, `10.99`, ale nie `10.999` ani `10,50`) |
| Tytuł | `/^[\pL\d\s.,\-]{3,140}$/u` | 3–140 znaków: litery, cyfry, spacje, kropka, przecinek, myślnik |
| Nazwa odbiorcy | `/^[\pL\d\s.,'\-]{2,100}$/u` | jak imię, ale dopuszcza cyfry, kropkę, przecinek i apostrof (np. `O'Neil`) |
| Adres odbiorcy | `/^[\pL\d\s.,\-\/]{3,150}$/u` | litery, cyfry, spacje, kropka, przecinek, myślnik i ukośnik (np. `ul. Polna 3/5, 00-950 Łódź`) |

`^` i `$` to początek i koniec tekstu (bez nich wystarczyłby fragment pasujący), `{n,m}` to liczba powtórzeń, `?` oznacza „opcjonalnie”. Reguły są zapisane w tablicach, bo znak `|` w regexie psułby zapis tekstowy. Miejsca: [AuthController.php](app/Http/Controllers/AuthController.php), [UserController.php](app/Http/Controllers/Admin/UserController.php), [TransferController.php:31-36](app/Http/Controllers/TransferController.php#L31-L36).

## 7. Transakcja przelewu

[TransferController.php:89](app/Http/Controllers/TransferController.php#L89)

```php
DB::transaction(function () {
    $accounts = User::whereIn('id', [$sender->id, $recipient->id])
        ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
    // sprawdzenie salda, decrement, increment, Transfer::create
});
```

- **Transakcja:** zmiana obu sald i zapis przelewu dzieją się razem albo wcale. Wyjątek w środku cofa wszystko.
- **`lockForUpdate`:** wiersze obu kont są zablokowane do końca transakcji. Dwa równoczesne przelewy z tego samego konta nie odczytają tego samego salda, więc nie zejdziemy poniżej zera. Saldo jest sprawdzane ponownie już pod blokadą.
- **`orderBy('id')`:** blokady zawsze zakładamy w tej samej kolejności. Gdy A płaci B, a B płaci A jednocześnie, bez stałej kolejności każdy trzymałby swój wiersz i czekał na drugi (zakleszczenie).
- Suma sald przed przelewem i po nim jest taka sama (test w [TransferTest.php](tests/Feature/TransferTest.php)).
- Kwoty są sprawdzane regexem, `numeric` i `gt:0`, a odbiorca: istnieje, jest klientem, to nie nadawca.
- Formularz zbiera: numer konta, nazwę i adres odbiorcy, kwotę i tytuł. Nazwa i adres to dane z formularza (jak w prawdziwym przelewie), zapisane w `transfers`; konto odbiorcy identyfikuje wyłącznie numer. Na formularzu i potwierdzeniu widać konto źródłowe z saldem, a na potwierdzeniu także saldo po przelewie.

## 8. Zabezpieczenia w skrócie

| Zagrożenie | Zabezpieczenie |
| --- | --- |
| SQL Injection | Eloquent używa zapytań przygotowanych (wartość osobno od SQL), brak `DB::raw` i `whereRaw`; test z `DB::listen` pokazuje `?` w SQL |
| XSS | `{{ }}` w Blade escapuje HTML, nigdzie nie ma `{!! !!}` |
| CSRF | `@csrf` w każdym formularzu |
| Przejęcie sesji | `session()->regenerate()` po logowaniu |
| Zgadywanie hasła | `throttle:5,1` na logowaniu |
| Nadanie sobie uprawnień | `role` i `balance` poza `#[Fillable]` |
| Wyciek hasła | bcrypt, `#[Hidden]` |
| Dostęp bez uprawnień | middleware `auth`, `verified`, `admin`, `client` na trasach |

## 9. PESEL

Kod: [Pesel.php](app/Support/Pesel.php), reguła [ValidPesel.php](app/Rules/ValidPesel.php), użycie w [AuthController.php](app/Http/Controllers/AuthController.php) i [UserController.php](app/Http/Controllers/Admin/UserController.php).

Budowa numeru `RRMMDDPPPQK`:

- `RR`, `MM`, `DD`: rok, miesiąc i dzień urodzenia. Wiek jest zakodowany w miesiącu: 1800–1899 dodaje 80, 1900–1999 nic, 2000–2099 dodaje 20, 2100–2199 dodaje 40, 2200–2299 dodaje 60. Dlatego `05230912345` to 9 marca 2005, a nie 1905.
- `PPP`: numer porządkowy, `Q`: cyfra płci (nieparzysta to mężczyzna, parzysta to kobieta), `K`: cyfra kontrolna.

Suma kontrolna: cyfry 1–10 mnożymy przez wagi `1 3 7 9 1 3 7 9 1 3` i sumujemy. Cyfra kontrolna to `(10 − suma mod 10) mod 10`. Przykład dla `44051401359`: suma = 101, więc `(10 − 1) mod 10 = 9` i numer jest poprawny. Suma kontrolna wyłapuje pomyłkę w jednej cyfrze i większość zamian sąsiednich.

Co sprawdza walidacja przy rejestracji:

1. `regex:/^\d{11}$/`: dokładnie 11 cyfr (reguła `bail` przerywa dalsze sprawdzanie, gdy to nie przejdzie).
2. `unique:users`: numer nie jest już zarejestrowany.
3. `ValidPesel`: suma kontrolna, istniejąca data urodzenia (`checkdate`, nie z przyszłości) i zgodność płci z polem „Płeć” z formularza.

Płeć z formularza nie jest zapisywana, bo wynika z PESEL (`gender()` w modelu). Data urodzenia i płeć w profilu też są z niego odczytywane.

Generator („Generuj poprawny PESEL”): losuje datę, koduje miesiąc dla wieku, dobiera cyfrę płci i liczy cyfrę kontrolną tym samym kodem co walidacja. Przycisk i trasa `/dev/pesel` ([DevController.php](app/Http/Controllers/DevController.php)) istnieją tylko poza środowiskiem `production`.

## 10. Książka odbiorców i powtórzenie przelewu

- Tabela `recipients` (`user_id`, `name`, `address`, `account_number`) ma klucz obcy `user_id` z `cascadeOnDelete()`, więc odbiorcy znikają razem z właścicielem, oraz unikalność pary `(user_id, account_number)`. Dwie osoby mogą zapisać to samo konto, ale jedna nie może zapisać go dwa razy.
- Przy usuwaniu sprawdzam, czy odbiorca należy do zalogowanego (`abort_unless(..., 403)`), bo samo `{recipient}` w adresie można podmienić. To samo przy powtarzaniu przelewu: tylko nadawca może go powtórzyć.
- Wybranie odbiorcy z książki (`/transfers/create?recipient=ID`, tylko własnego) wypełnia numer konta, nazwę i adres. Zaznaczenie „Zapisz odbiorcę” dodaje go do książki dopiero po wykonaniu przelewu (`firstOrCreate`, więc bez duplikatów).
- Powtórzenie to `POST /transfers/{transfer}/repeat`, z nazwą i adresem zapisanymi w pierwotnym przelewie. Dane przelewu trafiają przez te same reguły walidacji i sprawdzenie salda co zwykły przelew, a potem do sesji i na ekran potwierdzenia. Nic nie jest wykonywane bez drugiego kliknięcia.

## 11. Format numeru konta

[AccountNumber.php](app/Support/AccountNumber.php): `format()` robi `29 5234 6767 1873 3454 8751 4799` (dwie cyfry, potem grupy po cztery), a `normalize()` usuwa spacje z wpisanego numeru przed walidacją. W bazie numer jest zawsze zapisany bez spacji (`char(26)`), formatowanie dotyczy tylko wyświetlania.
