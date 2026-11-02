# Ściągawka z komend

Wszystko uruchamiane w kontenerze przez Sail. Opcjonalny skrót: `alias sail='./vendor/bin/sail'`

## Kontenery

```bash
./vendor/bin/sail up                # uruchom kontenery (na pierwszym planie)
./vendor/bin/sail up -d             # uruchom w tle
./vendor/bin/sail down              # zatrzymaj i usuń kontenery
./vendor/bin/sail down -v           # j.w. + usuń wolumeny (kasuje dane bazy!)
./vendor/bin/sail restart           # restart kontenerów
./vendor/bin/sail build --no-cache  # przebuduj obrazy
./vendor/bin/sail ps                # status kontenerów
./vendor/bin/sail shell             # shell w kontenerze aplikacji
./vendor/bin/sail root-shell        # shell jako root
./vendor/bin/sail mariadb           # klient SQL
```

## Artisan

```bash
./vendor/bin/sail artisan route:list    # lista tras
./vendor/bin/sail artisan about         # informacje o aplikacji
./vendor/bin/sail artisan key:generate  # wygeneruj APP_KEY
./vendor/bin/sail artisan config:show session  # aktualne ustawienia sesji
```

### Generowanie kodu

```bash
./vendor/bin/sail artisan make:model Transfer -m      # model + migracja
./vendor/bin/sail artisan make:controller AuthController
./vendor/bin/sail artisan make:controller Admin/UserController --resource
./vendor/bin/sail artisan make:middleware IsAdmin
./vendor/bin/sail artisan make:migration create_transfers_table
./vendor/bin/sail artisan make:seeder AccountSeeder
./vendor/bin/sail artisan make:test TransferTest      # test w tests/Feature
```

### Baza danych

```bash
./vendor/bin/sail artisan migrate                # uruchom migracje
./vendor/bin/sail artisan migrate:rollback       # cofnij ostatni batch
./vendor/bin/sail artisan migrate:fresh          # usuń wszystkie tabele i migruj od nowa
./vendor/bin/sail artisan migrate:fresh --seed   # j.w. + seedery
./vendor/bin/sail artisan migrate:status         # status migracji
./vendor/bin/sail artisan db:seed                # uruchom seedery
./vendor/bin/sail artisan db:show                # podsumowanie bazy
```

### Cache i konfiguracja

```bash
./vendor/bin/sail artisan optimize:clear   # wyczyść wszystkie cache
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:publish   # skopiuj domyślny plik konfiguracji do config/
```

## Testy

```bash
./vendor/bin/sail test                      # wszystkie testy
./vendor/bin/sail test --filter=TransferTest
./vendor/bin/sail bin phpunit               # bezpośrednio przez PHPUnit
```

## Composer

```bash
./vendor/bin/sail composer install
./vendor/bin/sail composer update
./vendor/bin/sail composer require vendor/package
./vendor/bin/sail composer require vendor/package --dev
./vendor/bin/sail composer dump-autoload
```

## Frontend (npm / Vite)

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev        # Vite z hot reload
./vendor/bin/sail npm run build      # build produkcyjny
```

## Poczta i logi

```bash
./vendor/bin/sail logs -f                # logi kontenerów na żywo
tail -f storage/logs/laravel.log         # logi aplikacji
```

Skrzynka Mailpit: http://localhost:8025
