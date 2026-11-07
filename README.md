# Pseudobank

Aplikacja bankowa na zaliczenie przedmiotu Programowanie w zastosowaniach.

Stos: Laravel 13, widoki Blade, Tailwind CSS (Vite), MariaDB i Mailpit w kontenerach Sail. Logowanie i rejestracja są napisane bez gotowych pakietów (Breeze, Jetstream).

## Funkcje

- rejestracja z numerem PESEL (suma kontrolna, data urodzenia, zgodność z wybraną płcią), logowanie i wylogowanie, hasła hashowane bcryptem,
- aktywacja konta linkiem z e-maila,
- dwie role: klient i administrator, z różnymi stronami po zalogowaniu,
- klient: numer konta w formacie `00 0000 0000 ...`, saldo, przelewy z potwierdzeniem (dane w sesji; formularz: numer konta, nazwa i adres odbiorcy, kwota, tytuł, z widocznym kontem źródłowym i saldem), historia przelewów z przyciskiem „Powtórz”, książka odbiorców,
- profil użytkownika z danymi (także data urodzenia i płeć odczytane z PESEL),
- administrator: dodawanie, podgląd, edycja i usuwanie użytkowników oraz wyszukiwarka po e-mailu,
- walidacja formularzy (w tym wyrażenia regularne), ochrona przed SQL Injection, XSS i CSRF.

## Uruchomienie

Wymagane są Docker i Docker Compose.

```bash
cp .env.example .env
composer install
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Podczas pracy nad wyglądem zamiast `npm run build` można uruchomić `./vendor/bin/sail npm run dev`.

- aplikacja: http://localhost
- skrzynka Mailpit (maile z linkami aktywacyjnymi): http://localhost:8025

Przy pierwszej instalacji, gdy `vendor/` jeszcze nie istnieje, `composer install` trzeba uruchomić lokalnie albo przez kontener `laravelsail/php85-composer`.

## Narzędzie deweloperskie

Na formularzu rejestracji (i dodawania użytkownika w panelu) jest przycisk „Generuj poprawny PESEL”. Losuje numer zgodny z wybraną płcią. Przycisk i trasa `/dev/pesel` istnieją tylko poza środowiskiem `production`.

## Konta testowe

Hasło wszystkich kont z seedera to `Haslo123`. Konta są już aktywne.

| E-mail | Rola |
| --- | --- |
| admin@example.com | administrator |
| piekarskim@example.com | klient |
| uczelnis@example.com | klient |

Każdy klient zaczyna z saldem 1000 zł. Nowo zarejestrowane konto trzeba aktywować linkiem z Mailpit.

## Testy

```bash
./vendor/bin/sail test
```

Testy używają osobnej bazy `testing`, więc nie naruszają danych aplikacji.

## Uwagi

W środowisku, które jest pokazywane poza lokalnym komputerem, w `.env` trzeba ustawić `APP_DEBUG=false`.

Plan i kolejność prac: [PLAN.md](PLAN.md). Ściągawka z komend: [commands.md](commands.md).
