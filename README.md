# Puzzel

System złożony z trzech usług uruchamianych razem przez Docker Compose:

| Usługa | Opis                                            | Adres                     |
|--------|--------------------------------------------------|---------------------------|
| `app`  | API (Symfony 7.2, DDD/CQRS)                      | http://localhost:8085     |
| `ws`   | Serwer WebSocket puzzli (ten sam kod co `app`)    | ws://localhost:8086       |
| `crud` | Panel administracyjny (CakePHP 5)                | http://localhost:8084     |
| `db`   | MySQL dla API                                    | localhost:3308            |
| `db_crud` | MySQL dla panelu admina                       | localhost:3309            |
| `phpmyadmin` | Podgląd obu baz                             | http://localhost:8090     |
| `mailcatcher` | Podgląd maili wysyłanych przez API          | http://localhost:2080     |

`crud` komunikuje się z `app` wyłącznie przez REST API (nagłówek `X-API-TOKEN`), `ws` obsługuje wspólną grę na puzzlach po WebSocket i uwierzytelnia się tokenem JWT wydanym przez `app`.

## Wymagania

- Docker + Docker Compose
- Nic więcej — PHP, MySQL itd. działają w kontenerach.

## Pierwsze uruchomienie

### 1. Zmienne środowiskowe (katalog główny)

```bash
cp .env.example .env
```

Domyślne wartości w `.env.example` wystarczają do lokalnego developmentu — zmień je tylko jeśli świadomie chcesz inne hasła/nazwy baz.

### 2. Konfiguracja API (`api/.env`)

Plik `api/.env` nie jest wersjonowany. Utwórz go z poniższą zawartością (albo skopiuj od innego developera):

```dotenv
###> symfony/framework-bundle ###
APP_ENV=dev
APP_SECRET=zmień_na_losowy_ciąg
###< symfony/framework-bundle ###

###> doctrine/doctrine-bundle ###
DATABASE_URL="mysql://puzzel_user:puzzel_pass@db:3306/puzzel_db?serverVersion=8.2"
###< doctrine/doctrine-bundle ###

###> lexik/jwt-authentication-bundle ###
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=zmień_na_własne_hasło
###< lexik/jwt-authentication-bundle ###

###> symfony/mailer ###
MAILER_DSN=smtp://mailcatcher:1025
MAILER_FROM=noreply@app.local
###< symfony/mailer ###

###> app ###
API_ADMIN_TOKEN=wygeneruj_losowy_token
ACTIVATION_BASE_URL=http://localhost:8085/api/activate
PASSWORD_RESET_BASE_URL=http://localhost:8085/api/password-reset/confirm
PUZZLE_STORAGE_PATH=/var/puzzles
WS_HOST=0.0.0.0
WS_PORT=8080
###< app ###

###> nelmio/cors-bundle ###
CORS_ALLOW_ORIGIN=^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$
###< nelmio/cors-bundle ###
```

`API_ADMIN_TOKEN` musi być identyczny z tokenem w `crud/config/app_local.php` (patrz krok 3) — to nim panel admina uwierzytelnia się do API.

Jeśli `api/config/jwt/private.pem` i `public.pem` jeszcze nie istnieją, wygeneruj parę kluczy po pierwszym `composer install`:

```bash
docker compose run --rm app php bin/console lexik:jwt:generate-keypair
```

### 3. Konfiguracja panelu admina (`crud/config/app_local.php`)

Też nie jest wersjonowany:

```bash
cp crud/config/app_local.example.php crud/config/app_local.php
```

W wygenerowanym pliku ustaw:
- `Api.token` — ten sam token co `API_ADMIN_TOKEN` w `api/.env`,
- `Security.salt` — dowolny losowy ciąg.

### 4. Start kontenerów

```bash
docker compose up -d --build
```

Przy starcie każdy kontener automatycznie:
- instaluje zależności (`composer install`),
- wykonuje migracje bazy danych (`doctrine:migrations:migrate` dla API, `bin/cake migrations migrate` dla panelu).

> Migracja `Version20260923000001` usuwa tabele/kolumny związane z wyłączonymi funkcjami (grupy, kampanie) — jeśli startujesz na bazie z wcześniejszej wersji projektu, zrób jej kopię przed pierwszym `up`.

### 5. Pierwszy administrator panelu

```bash
docker compose exec crud bin/cake add_admin admin admin@puzzel.pl TwojeHaslo123
```

Panel logowania: http://localhost:8084/login

## Codzienna praca

```bash
docker compose up -d          # start
docker compose down           # stop
docker compose logs -f app    # logi API
docker compose logs -f ws     # logi WebSocket
docker compose exec app php bin/console ...   # konsola Symfony
docker compose exec crud bin/cake ...         # konsola CakePHP
```

Testy API:

```bash
docker compose exec app composer test
```

Więcej szczegółów o poszczególnych usługach: [api/README.md](api/README.md), [crud/README.md](crud/README.md).
