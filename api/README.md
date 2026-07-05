# User & Group Management API

Symfony 7.2 API zbudowane w architekturze **DDD** (Domain-Driven Design) z separacją **CQRS** (Command Query Responsibility Segregation).

## Architektura

```
src/
├── Shared/                              # Współdzielone komponenty
│   └── Infrastructure/
│       └── Bus/
│           ├── Command/                 # CommandBusInterface + implementacja Messenger
│           └── Query/                   # QueryBusInterface + implementacja Messenger
│
├── User/                                # Bounded Context: User
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── User.php                 # Aggregate Root
│   │   ├── ValueObject/
│   │   │   ├── UserId.php
│   │   │   ├── Email.php
│   │   │   ├── Username.php
│   │   │   ├── HashedPassword.php       # Argon2id
│   │   │   └── ActivationToken.php
│   │   ├── Repository/
│   │   │   └── UserRepositoryInterface.php
│   │   ├── Event/
│   │   │   ├── UserCreatedEvent.php
│   │   │   ├── UserActivatedEvent.php
│   │   │   └── UserDeactivatedEvent.php
│   │   └── Exception/
│   │       ├── UserNotFoundException.php
│   │       ├── EmailAlreadyExistsException.php
│   │       ├── InvalidActivationTokenException.php
│   │       ├── ActivationTokenExpiredException.php
│   │       ├── UserAlreadyActiveException.php
│   │       └── UserAlreadyInactiveException.php
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateUser/
│   │   │   ├── CreateUserAdmin/
│   │   │   ├── UpdateUser/
│   │   │   ├── UpdateUserAdmin/
│   │   │   ├── DeleteUser/
│   │   │   ├── DeactivateUser/
│   │   │   └── ActivateUserByToken/
│   │   ├── Query/
│   │   │   ├── GetUser/
│   │   │   ├── GetUsers/
│   │   │   └── GetCurrentUser/
│   │   └── DTO/
│   │       └── UserDTO.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── RegisterController.php   # POST /api/register (publiczny)
│       │   ├── LoginController.php      # POST /api/login (publiczny)
│       │   ├── ActivateController.php   # GET  /api/activate (publiczny)
│       │   ├── AdminUserController.php  # /api/admin/users/* (token)
│       │   └── UserController.php       # /api/me/* (JWT)
│       ├── Persistence/Doctrine/
│       │   ├── Repository/
│       │   │   └── DoctrineUserRepository.php
│       │   ├── Type/
│       │   │   ├── UserIdType.php
│       │   │   ├── EmailType.php
│       │   │   ├── UsernameType.php
│       │   │   ├── HashedPasswordType.php
│       │   │   └── ActivationTokenType.php
│       │   └── Mapping/
│       │       └── User.orm.xml
│       ├── Security/
│       │   ├── SecurityUser.php
│       │   ├── UserProvider.php
│       │   ├── ApiTokenAuthenticator.php
│       │   └── ApiAdminUser.php
│       ├── Mailer/
│       │   ├── ActivationMailerInterface.php
│       │   └── SymfonyActivationMailer.php
│       └── EventListener/
│           └── ExceptionListener.php
│
├── Group/                               # Bounded Context: Group
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── Group.php                # Aggregate Root
│   │   ├── ValueObject/
│   │   │   ├── GroupId.php
│   │   │   └── GroupName.php
│   │   ├── Repository/
│   │   │   └── GroupRepositoryInterface.php
│   │   └── Exception/
│   │       ├── GroupNotFoundException.php
│   │       ├── GroupNameAlreadyExistsException.php
│   │       ├── NotGroupOwnerException.php
│   │       ├── UserAlreadyInGroupException.php
│   │       ├── UserNotInGroupException.php
│   │       └── OwnerCannotLeaveGroupException.php
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── CreateGroup/
│   │   │   ├── CreateGroupByUser/
│   │   │   ├── UpdateGroup/
│   │   │   ├── DeleteGroup/
│   │   │   ├── DeleteGroupByUser/
│   │   │   ├── AssignUserToGroup/
│   │   │   ├── RemoveUserFromGroup/
│   │   │   ├── JoinGroup/
│   │   │   ├── LeaveGroup/              # Właściciel musi najpierw przekazać własność
│   │   │   ├── InviteUserToGroup/
│   │   │   └── TransferOwnership/       # Przekazanie własności innemu członkowi
│   │   ├── Query/
│   │   │   ├── GetGroup/                # Zwraca grupę z listą userów
│   │   │   └── GetGroups/
│   │   └── DTO/
│   │       └── GroupDTO.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── AdminGroupController.php # /api/admin/groups/* (token)
│       │   └── UserGroupController.php  # /api/groups/* (JWT)
│       └── Persistence/Doctrine/
│           ├── Repository/
│           │   └── DoctrineGroupRepository.php
│           ├── Type/
│           │   ├── GroupIdType.php
│           │   └── GroupNameType.php
│           └── Mapping/
│               └── Group.orm.xml
│
├── Puzzle/                              # Bounded Context: Puzzle
│   ├── Domain/
│   │   ├── Entity/
│   │   │   └── Puzzle.php               # Aggregate Root
│   │   ├── ValueObject/
│   │   │   └── PuzzleId.php
│   │   ├── Repository/
│   │   │   └── PuzzleRepositoryInterface.php
│   │   └── Exception/
│   │       └── PuzzleNotFoundException.php
│   ├── Application/
│   │   ├── Command/
│   │   │   ├── UploadPuzzle/
│   │   │   └── DeletePuzzle/
│   │   ├── Query/
│   │   │   ├── GetPuzzle/
│   │   │   └── GetPuzzles/
│   │   ├── DTO/
│   │   │   └── PuzzleDTO.php
│   │   └── Storage/
│   │       └── PuzzleStorageInterface.php
│   └── Infrastructure/
│       ├── Controller/
│       │   ├── AdminPuzzleController.php # /api/admin/puzzles/* (token)
│       │   └── UserPuzzleController.php  # /api/puzzles/* (JWT)
│       ├── Storage/
│       │   └── LocalPuzzleStorage.php   # Zapis na dysk (PUZZLE_STORAGE_PATH)
│       └── Persistence/Doctrine/
│           ├── Repository/
│           │   └── DoctrinePuzzleRepository.php
│           ├── Type/
│           │   └── PuzzleIdType.php
│           └── Mapping/
│               └── Puzzle.orm.xml
│
└── Campaign/                            # Bounded Context: Campaign
    ├── Domain/
    │   ├── Entity/
    │   │   └── Campaign.php             # Aggregate Root
    │   ├── ValueObject/
    │   │   ├── CampaignId.php
    │   │   └── CampaignName.php
    │   ├── Repository/
    │   │   └── CampaignRepositoryInterface.php
    │   └── Exception/
    │       ├── CampaignNotFoundException.php
    │       └── CampaignNameAlreadyExistsException.php
    ├── Application/
    │   ├── Command/
    │   │   ├── CreateCampaign/
    │   │   ├── UpdateCampaign/
    │   │   ├── DeleteCampaign/          # Odpina puzzle przed usunięciem
    │   │   ├── AssignPuzzleToCampaign/
    │   │   └── RemovePuzzleFromCampaign/
    │   ├── Query/
    │   │   ├── GetCampaign/             # Zwraca kampanię z listą puzzli
    │   │   └── GetCampaigns/
    │   └── DTO/
    │       └── CampaignDTO.php
    └── Infrastructure/
        ├── Controller/
        │   ├── AdminCampaignController.php # /api/admin/campaigns/* (token)
        │   └── UserCampaignController.php  # /api/campaigns/* (JWT)
        └── Persistence/Doctrine/
            ├── Repository/
            │   └── DoctrineCampaignRepository.php
            ├── Type/
            │   ├── CampaignIdType.php
            │   └── CampaignNameType.php
            └── Mapping/
                └── Campaign.orm.xml
```

## Model bazy danych

Tabela `users`:

| Kolumna            | Typ                    | Opis                                |
|--------------------|------------------------|-------------------------------------|
| id                 | INT AUTO_INCREMENT (PK)| Auto-increment ID                   |
| uuid               | VARCHAR(36) UNIQUE     | UUID v4 — identyfikator publiczny   |
| username           | VARCHAR(50)            | Generowany, edytowalny              |
| email              | VARCHAR(180) UNIQUE    | Logowanie tylko po mailu            |
| password           | VARCHAR(255)           | Argon2id hash                       |
| premium            | BOOLEAN DEFAULT FALSE  | Konto premium                       |
| active             | BOOLEAN DEFAULT FALSE  | Konto aktywne                       |
| activation_token   | VARCHAR(64) NULL       | Token aktywacyjny                   |
| token_expires_at   | DATETIME NULL          | Ważność tokena (24h)                |
| group_uuid               | VARCHAR(36) NULL       | FK do groups.uuid (nullable)        |
| reset_token              | VARCHAR(64) NULL       | Token resetu hasła (1h ważności)    |
| reset_token_expires_at   | DATETIME NULL          | Ważność tokenu resetu               |
| created_at               | DATETIME NOT NULL      | Data utworzenia                     |
| updated_at               | DATETIME NULL          | Data ostatniej zmiany               |

Tabela `groups`:

| Kolumna     | Typ                        | Opis                              |
|-------------|----------------------------|-----------------------------------|
| id          | INT AUTO_INCREMENT (PK)    | Auto-increment ID                 |
| uuid        | VARCHAR(36) UNIQUE         | UUID v4 — identyfikator publiczny |
| name        | VARCHAR(100) UNIQUE        | Nazwa grupy                       |
| description | VARCHAR(500) NULL          | Opis grupy                        |
| owner_uuid  | VARCHAR(36) NULL           | UUID właściciela (FK do users.uuid)|
| created_at  | DATETIME NOT NULL          | Data utworzenia                   |
| updated_at  | DATETIME NULL              | Data ostatniej zmiany             |

Tabela `puzzles`:

| Kolumna         | Typ                     | Opis                                      |
|-----------------|-------------------------|-------------------------------------------|
| id              | INT AUTO_INCREMENT (PK) | Auto-increment ID                         |
| uuid            | VARCHAR(36) UNIQUE      | UUID v4 — identyfikator publiczny         |
| original_name   | VARCHAR(255)            | Oryginalna nazwa pliku                    |
| stored_filename | VARCHAR(255)            | Nazwa pliku na dysku (`{uuid}.{ext}`)     |
| mime_type       | VARCHAR(100)            | Typ MIME (np. image/jpeg)                 |
| size            | INT                     | Rozmiar pliku w bajtach                   |
| campaign_uuid   | VARCHAR(36) NULL        | FK do campaigns.uuid (nullable)           |
| difficulty      | INT NULL                | Poziom trudności (nullable)               |
| pieces_count    | INT NULL                | Liczba fragmentów puzzla (np. 100, 200, 250) |
| created_at      | DATETIME NOT NULL       | Data przesłania                           |

Tabela `campaigns`:

| Kolumna     | Typ                     | Opis                              |
|-------------|-------------------------|-----------------------------------|
| id          | INT AUTO_INCREMENT (PK) | Auto-increment ID                 |
| uuid        | VARCHAR(36) UNIQUE      | UUID v4 — identyfikator publiczny |
| name        | VARCHAR(100) UNIQUE     | Nazwa kampanii                    |
| description | VARCHAR(500) NULL       | Opis kampanii                     |
| created_at  | DATETIME NOT NULL       | Data utworzenia                   |
| updated_at  | DATETIME NULL           | Data ostatniej zmiany             |

Tabela `puzzle_progress`:

| Kolumna       | Typ                     | Opis                                          |
|---------------|-------------------------|-----------------------------------------------|
| id            | INT AUTO_INCREMENT (PK) | Auto-increment ID                             |
| user_uuid     | VARCHAR(36) NOT NULL    | FK do users.uuid                              |
| puzzle_uuid   | VARCHAR(36) NOT NULL    | FK do puzzles.uuid                            |
| pieces_placed | INT NOT NULL            | Liczba ułożonych fragmentów                   |
| completed     | TINYINT(1) NOT NULL     | Czy ukończone (automatyczne gdy pieces_placed >= pieces_count) |
| started_at    | DATETIME NOT NULL       | Data rozpoczęcia                              |
| completed_at  | DATETIME NULL           | Data ukończenia                               |
| updated_at    | DATETIME NOT NULL       | Data ostatniej aktualizacji                   |

> UNIQUE(user_uuid, puzzle_uuid) — jeden wiersz progress per użytkownik per puzzle.

> Użytkownik może należeć tylko do **jednej** grupy (`group_uuid` w tabeli `users`).
> Puzzle może należeć do jednej kampanii (`campaign_uuid` w tabeli `puzzles`) lub być bez kampanii.

## Autoryzacja

### 1. API Token (Admin Panel)
Header: `X-API-TOKEN: <token>`

Pełen dostęp do zarządzania użytkownikami (CRUD + dezaktywacja).

### 2. JWT (Użytkownicy systemu)
Header: `Authorization: Bearer <jwt_token>`

Ograniczony dostęp — edycja i dezaktywacja tylko własnego konta.

## Endpointy API

### Publiczne (bez autoryzacji)

| Metoda | URL               | Opis                          |
|--------|-------------------|-------------------------------|
| POST   | `/api/register`                  | Rejestracja (email + hasło)              |
| POST   | `/api/login`                     | Logowanie → JWT token                    |
| GET    | `/api/activate`                  | Aktywacja konta (query: token)           |
| POST   | `/api/password-reset/request`    | Wysłanie linku resetującego na email     |
| POST   | `/api/password-reset/confirm`    | Reset hasła tokenem z emaila             |

### Admin (X-API-TOKEN)

#### Użytkownicy

| Metoda     | URL                                   | Opis                    |
|------------|---------------------------------------|-------------------------|
| GET        | `/api/admin/users`                    | Lista użytkowników      |
| GET        | `/api/admin/users/{uuid}`             | Szczegóły użytkownika   |
| POST       | `/api/admin/users`                    | Tworzenie użytkownika   |
| PUT/PATCH  | `/api/admin/users/{uuid}`             | Edycja (wszystkie pola) |
| DELETE     | `/api/admin/users/{uuid}`             | Usunięcie               |
| POST       | `/api/admin/users/{uuid}/activate`            | Aktywacja konta                       |
| POST       | `/api/admin/users/{uuid}/deactivate`          | Dezaktywacja konta                    |
| POST       | `/api/admin/users/{uuid}/send-password-reset` | Wyślij email z linkiem resetującym    |
| POST       | `/api/admin/users/{uuid}/reset-password`      | Force-reset hasła (bez emaila)        |

#### Kampanie

| Metoda    | URL                                                      | Opis                                      |
|-----------|----------------------------------------------------------|-------------------------------------------|
| GET       | `/api/admin/campaigns`                                   | Lista kampanii                            |
| GET       | `/api/admin/campaigns/{uuid}`                            | Szczegóły kampanii + lista puzzli         |
| POST      | `/api/admin/campaigns`                                   | Tworzenie kampanii                        |
| PUT/PATCH | `/api/admin/campaigns/{uuid}`                            | Edycja kampanii                           |
| DELETE    | `/api/admin/campaigns/{uuid}`                            | Usunięcie kampanii (odpina puzzle)        |
| POST      | `/api/admin/campaigns/{campaignUuid}/puzzles/{puzzleUuid}`| Przypisanie puzzla do kampanii            |
| DELETE    | `/api/admin/campaigns/{campaignUuid}/puzzles/{puzzleUuid}`| Odpięcie puzzla od kampanii              |

#### Puzzles

| Metoda | URL                                  | Opis                                                                               |
|--------|--------------------------------------|------------------------------------------------------------------------------------|
| GET    | `/api/admin/puzzles`                 | Lista puzzli                                                                       |
| GET    | `/api/admin/puzzles/{uuid}`          | Szczegóły puzzla (metadane)                                                        |
| GET    | `/api/admin/puzzles/{uuid}/image`    | Pobranie grafiki (binary)                                                          |
| POST   | `/api/admin/puzzles`                 | Upload grafiki (multipart `image`, opcjonalne: `difficulty`, `pieces_count`)       |
| PATCH  | `/api/admin/puzzles/{uuid}`          | Aktualizacja (`difficulty`, `pieces_count` — liczba lub `null` aby wyczyścić)      |
| GET    | `/api/admin/puzzles/{uuid}/progress` | Postępy wszystkich użytkowników dla danego puzzla                                  |
| DELETE | `/api/admin/puzzles/{uuid}`          | Usunięcie grafiki                                                                  |

#### Grupy

| Metoda    | URL                                               | Opis                                   |
|-----------|---------------------------------------------------|----------------------------------------|
| GET       | `/api/admin/groups`                               | Lista grup                             |
| GET       | `/api/admin/groups/{uuid}`                        | Szczegóły grupy + lista użytkowników   |
| POST      | `/api/admin/groups`                               | Tworzenie grupy                        |
| PUT/PATCH | `/api/admin/groups/{uuid}`                        | Edycja grupy                           |
| DELETE    | `/api/admin/groups/{uuid}`                        | Usunięcie grupy (odpisuje userów)      |
| POST      | `/api/admin/groups/{groupUuid}/users/{userUuid}`  | Przypisanie użytkownika do grupy       |
| DELETE    | `/api/admin/groups/{groupUuid}/users/{userUuid}`  | Wypisanie użytkownika z grupy          |

#### Zaproszenia do grup (admin)

| Metoda | URL                                            | Opis                                                    |
|--------|------------------------------------------------|---------------------------------------------------------|
| GET    | `/api/admin/groups/{groupUuid}/invitations`    | Lista zaproszeń/próśb dla grupy                         |
| POST   | `/api/admin/invitations/{uuid}/accept`         | Akceptacja zaproszenia lub prośby o dołączenie          |
| POST   | `/api/admin/invitations/{uuid}/reject`         | Odrzucenie zaproszenia lub prośby o dołączenie          |

### Użytkownik (JWT Bearer)

#### Profil

| Metoda     | URL                  | Opis                                   |
|------------|----------------------|----------------------------------------|
| GET        | `/api/me`                  | Moje dane                                |
| PUT/PATCH  | `/api/me`                  | Edycja profilu (username, email, hasło)  |
| POST       | `/api/me/change-password`  | Zmiana hasła (wymaga aktualnego hasła)   |
| POST       | `/api/me/deactivate`       | Dezaktywacja konta                       |

#### Kampanie

| Metoda | URL                        | Opis                                      |
|--------|----------------------------|-------------------------------------------|
| GET    | `/api/campaigns`           | Lista kampanii                            |
| GET    | `/api/campaigns/{uuid}`    | Szczegóły kampanii + lista puzzli         |

#### Puzzles

| Metoda | URL                               | Opis                                                      |
|--------|-----------------------------------|-----------------------------------------------------------|
| GET    | `/api/puzzles`                    | Lista puzzli                                              |
| GET    | `/api/puzzles/{uuid}`             | Szczegóły puzzla (metadane)                               |
| GET    | `/api/puzzles/{uuid}/image`       | Pobranie grafiki (binary)                                 |
| POST   | `/api/puzzles/{uuid}/progress`    | Zapisz/aktualizuj postęp (`pieces_placed`)                |
| GET    | `/api/puzzles/{uuid}/progress`    | Mój postęp dla konkretnego puzzla                         |
| GET    | `/api/me/progress`                | Lista wszystkich moich postępów                           |

#### Grupy

| Metoda | URL                                            | Opis                                              |
|--------|------------------------------------------------|---------------------------------------------------|
| GET    | `/api/groups`                                  | Lista wszystkich grup                             |
| GET    | `/api/groups/{uuid}`                           | Szczegóły grupy + lista członków                  |
| POST   | `/api/groups`                                  | Utwórz grupę (stajesz się właścicielem)           |
| POST   | `/api/groups/{uuid}/join`                      | Wyślij prośbę o dołączenie do grupy (pending)     |
| POST   | `/api/groups/leave`                            | Opuść swoją grupę (niedostępne dla właściciela)   |
| DELETE | `/api/groups/{uuid}`                           | Usuń grupę (tylko właściciel)                     |
| POST   | `/api/groups/{uuid}/transfer-ownership`        | Przekaż własność grupy innemu członkowi           |
| POST   | `/api/groups/{uuid}/invite/{userUuid}`         | Zaproś użytkownika (tylko właściciel, pending)    |

#### Zaproszenia do grup (użytkownik)

| Metoda | URL                               | Opis                                                               |
|--------|-----------------------------------|--------------------------------------------------------------------|
| GET    | `/api/invitations`                | Moje zaproszenia i prośby (wszystkie typy, wszystkie statusy)      |
| POST   | `/api/invitations/{uuid}/accept`  | Zaakceptuj zaproszenie od grupy (type=invitation)                  |
| POST   | `/api/invitations/{uuid}/reject`  | Odrzuć zaproszenie od grupy lub własną prośbę                      |

> Użytkownik może należeć tylko do **jednej** grupy. Właściciel grupy (`owner_uuid`) ma wyłączne prawo do jej usunięcia, zapraszania i przekazania własności. Przed opuszczeniem grupy właściciel musi najpierw przekazać własność innemu członkowi.
>
> **Typy zaproszeń:** `invitation` = grupa zaprasza użytkownika (akceptuje użytkownik), `request` = użytkownik prosi o dołączenie (akceptuje właściciel grupy lub admin).
> **Statusy:** `pending`, `accepted`, `rejected`.

## Paginacja i filtrowanie

Wszystkie endpointy listujące zasoby obsługują paginację oraz opcjonalne filtrowanie za pomocą query params.

### Parametry ogólne

| Param      | Typ   | Domyślnie | Opis                               |
|------------|-------|-----------|------------------------------------|
| `page`     | int   | `1`       | Numer strony (min. 1)              |
| `per_page` | int   | `20`      | Elementów na stronę (1–100)        |

### Dodatkowe filtry per endpoint

| Endpoint                       | Dodatkowe filtry                                            |
|-------------------------------|-------------------------------------------------------------|
| `GET /api/admin/users`        | `search` (username/email), `active` (true/false), `premium` (true/false) |
| `GET /api/admin/groups`       | `search` (nazwa grupy)                                      |
| `GET /api/groups`             | `search` (nazwa grupy)                                      |
| `GET /api/admin/campaigns`    | `search` (nazwa kampanii)                                   |
| `GET /api/campaigns`          | `search` (nazwa kampanii)                                   |
| `GET /api/admin/puzzles`      | `campaign_uuid` (UUID kampanii)                             |
| `GET /api/puzzles`            | `campaign_uuid` (UUID kampanii)                             |

### Format odpowiedzi

```json
{
  "data": [ ... ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 42,
    "pages": 3
  }
}
```

### Przykłady

```bash
# Strona 2, 10 elementów na stronę, tylko aktywni i premium
GET /api/admin/users?page=2&per_page=10&active=true&premium=true

# Szukaj grup po nazwie
GET /api/groups?search=admin

# Puzzle z konkretnej kampanii
GET /api/puzzles?campaign_uuid=550e8400-e29b-41d4-a716-446655440000
```

## Instalacja i uruchomienie

### 1. Zainstaluj zależności

```bash
composer install
```

### 2. Skonfiguruj środowisko

Skopiuj `.env` do `.env.local` i uzupełnij:

```bash
cp .env .env.local
```

Ustaw wartości:
- `DATABASE_URL` — połączenie z bazą MySQL
- `JWT_PASSPHRASE` — hasło do kluczy JWT
- `API_ADMIN_TOKEN` — bezpieczny token admina
- `MAILER_DSN` — konfiguracja SMTP (dev: `smtp://mailcatcher:1025`)
- `ACTIVATION_BASE_URL` — URL aktywacji (np. `http://localhost:8085/api/activate`)
- `PUZZLE_STORAGE_PATH` — ścieżka do katalogu z grafikami puzzli (np. `/var/puzzles`)

### 3. Wygeneruj klucze JWT

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

### 4. Uruchom migracje

```bash
php bin/console doctrine:migrations:migrate
```

Lub wygeneruj nową migrację z aktualnego mappingu:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 5. Uruchom serwer (dev)

```bash
symfony server:start
```

## Przykłady użycia (cURL)

### Rejestracja

```bash
curl -X POST http://localhost:8085/api/register \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "securepass123"}'
```

### Logowanie

```bash
curl -X POST http://localhost:8085/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "securepass123"}'
```

### Pobranie profilu (JWT)

```bash
curl http://localhost:8085/api/me \
  -H "Authorization: Bearer <jwt_token>"
```

### Tworzenie usera (Admin)

```bash
curl -X POST http://localhost:8085/api/admin/users \
  -H "Content-Type: application/json" \
  -H "X-API-TOKEN: your_secure_admin_api_token_here" \
  -d '{"username": "jan_kowalski", "email": "jan@example.com", "password": "pass1234", "premium": true, "active": true}'
```

### Dezaktywacja siebie (JWT)

```bash
curl -X POST http://localhost:8085/api/me/deactivate \
  -H "Authorization: Bearer <jwt_token>"
```

### Tworzenie grupy (Admin)

```bash
curl -X POST http://localhost:8085/api/admin/groups \
  -H "Content-Type: application/json" \
  -H "X-API-TOKEN: your_secure_admin_api_token_here" \
  -d '{"name": "Administratorzy", "description": "Grupa adminska"}'
```

### Przypisanie użytkownika do grupy (Admin)

```bash
curl -X POST http://localhost:8085/api/admin/groups/{groupUuid}/users/{userUuid} \
  -H "X-API-TOKEN: your_secure_admin_api_token_here"
```

## Zasady DDD

1. **Entity** — Aggregate Root z logiką biznesową (factory methods, guard clauses)
2. **Value Objects** — immutable obiekty z walidacją (Email, UserId, Username, HashedPassword, ActivationToken, GroupId, GroupName)
3. **Domain Events** — UserCreatedEvent, UserActivatedEvent, UserDeactivatedEvent
4. **Repository Interface** — kontrakt w domenie, implementacja w infrastrukturze
5. **Domain Exceptions** — specyficzne wyjątki domenowe per bounded context

## Zasady CQRS

1. **Command Bus** (`command.bus`) — operacje zmieniające stan (Create, Update, Delete, Activate, Deactivate, Assign, Remove)
2. **Query Bus** (`query.bus`) — operacje odczytu (GetUser, GetUsers, GetCurrentUser, GetGroup, GetGroups)
3. **Separacja** — Handlery komend nie zwracają danych, handlery zapytań nie zmieniają stanu
4. **Symfony Messenger** — implementacja obu busów z doctrine_transaction middleware na command bus

## Technologie

- PHP 8.3+
- Symfony 7.2
- Doctrine ORM 3.x + Migrations
- Lexik JWT Authentication Bundle
- Symfony Messenger (CQRS)
- Symfony Mailer + Twig (szablony HTML)
- MySQL 8.2
- Mailcatcher (dev — UI: http://localhost:1080)
