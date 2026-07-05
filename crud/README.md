# Puzzel CRUD — Panel Administracyjny

Panel administracyjny projektu Puzzel oparty o **CakePHP 5.x** z interfejsem **AdminLTE 3**.  
Posiada własną bazę danych MySQL (`puzzel_crud_db`) z jedną tabelą `admins`.  
Wszelka komunikacja z danymi biznesowymi odbywa się przez REST API (`puzzel_api`).

## Architektura

```
puzzel_crud    http://localhost:8084   — ten panel (CakePHP 5.x)
puzzel_api     http://localhost:8085   — główne API (Symfony)
puzzel_db_crud localhost:3309          — baza MySQL tylko dla panelu
puzzel_db      localhost:3308          — baza MySQL dla API
phpmyadmin     http://localhost:8090   — zarządzanie obiema bazami
```

## Funkcjonalności

### Administratorzy (`/admins`)
- Lista administratorów z paginacją
- Dodawanie przez formularz webowy (`/admins/add`)
- Edycja (`/admins/edit/{id}`) — zmiana hasła opcjonalna
- Usuwanie z potwierdzeniem (`POST /admins/delete/{id}`)
- Dodawanie przez CLI: `bin/cake add_admin <username> <email> <password> <role>`

### Autentykacja
- Logowanie przez formularz (`/login`) — sesja PHP
- Wszystkie trasy poza `/login` wymagają zalogowania
- Wylogowanie (`/logout`)

## Wymagania

- Docker + Docker Compose

## Uruchomienie

```bash
# start kontenerów (z katalogu głównego projektu)
docker compose up -d crud db_crud

# instalacja zależności
docker compose exec crud composer install

# migracja bazy danych
docker compose exec crud bin/cake migrations migrate

# dodanie pierwszego administratora
docker compose exec crud bin/cake add_admin admin admin@puzzel.pl TwojeHaslo123
```

Panel dostępny pod: `http://localhost:8084`

## Baza danych

Kontener: `puzzel_db_crud` (port `3309`)  
Baza: `puzzel_crud_db`  
Użytkownik: `puzzel_crud_user` / `puzzel_crud_pass`

Połączenie konfigurowane przez zmienną środowiskową `DATABASE_URL` (ustawiona w `docker-compose.yml`).

### Tabela `admins`

| Kolumna    | Typ           | Opis                              |
|------------|---------------|-----------------------------------|
| `id`       | INT UNSIGNED  | klucz główny                      |
| `username` | VARCHAR(100)  | unikalna nazwa                    |
| `email`    | VARCHAR(255)  | unikalny email                    |
| `password` | VARCHAR(255)  | hash bcrypt                       |
| `role`     | ENUM          | rola: `superadmin`, `admin`, `moderator` |
| `created`  | DATETIME      | data utworzenia (auto)            |
| `modified` | DATETIME      | data modyfikacji (auto)           |

## Struktura projektu

```
crud/
├── config/
│   ├── Migrations/          migracje bazy danych
│   ├── app.php              konfiguracja główna
│   ├── app_local.php        konfiguracja lokalna (nie commitować)
│   ├── plugins.php          ładowanie pluginów
│   └── routes.php           definicja tras
├── src/
│   ├── Application.php      bootstrap + middleware + autentykacja
│   ├── Command/
│   │   └── AddAdminCommand.php   CLI: bin/cake add_admin
│   ├── Controller/
│   │   ├── AppController.php     baza (auth + layout)
│   │   ├── AdminsController.php  CRUD administratorów
│   │   └── UsersController.php   login / logout
│   ├── Model/
│   │   ├── Entity/Admin.php      encja (hashowanie hasła)
│   │   └── Table/AdminsTable.php ORM + walidacja
│   └── View/
│       └── AppView.php           rejestracja helperów
├── templates/
│   ├── layout/
│   │   ├── adminlte.php     główny layout dashboardu
│   │   └── login.php        layout strony logowania
│   ├── Admins/
│   │   ├── index.php        lista administratorów
│   │   ├── add.php          formularz dodawania
│   │   └── edit.php         formularz edycji
│   └── Users/
│       └── login.php        formularz logowania
└── composer.json
```

## Zależności (composer)

| Pakiet                      | Wersja  | Opis                        |
|-----------------------------|---------|-----------------------------|
| `cakephp/cakephp`           | 5.1.*   | framework                   |
| `cakephp/authentication`    | ^3.0    | logowanie / sesja           |
| `cakephp/migrations`        | ^4.0    | migracje DB                 |
| `robmorgan/phinx`           | 0.16.10 | silnik migracji (pinowany)  |

> **Uwaga:** `robmorgan/phinx` jest spinnowany na `0.16.10` — wersja `0.16.11` jest niekompatybilna z `cakephp/migrations 4.x`.
