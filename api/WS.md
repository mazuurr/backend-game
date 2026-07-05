# Real-time układanie puzzli — zakres prac

Dokument zakresu (analiza, przed implementacją). Backend: Symfony 7.2, DDD/CQRS,
Doctrine, JWT (lexik). Nie zastępuje istniejącego `PuzzleProgress` — uzupełnia go
o drobnoziarniste, real-time śledzenie pozycji pojedynczych elementów.

## 1. Cel

WebSocket monitorujący, w jakie miejsce użytkownik przesunął konkretny element
puzzla i czy zrobił to poprawnie. Plansza jest **współdzielona** — wielu graczy w
jednym pokoju widzi nawzajem swoje ruchy na żywo, łącznie z **podglądem ciągnięcia
elementu w czasie rzeczywistym**.

## 2. Decyzje (ustalone)

| Temat | Decyzja | Konsekwencja |
|------|---------|--------------|
| Tryb | Współdzielona plansza (multiplayer broadcast) | Pokoje/sesje, zarządzanie subskrybentami |
| Zapis | Podwójny: log zdarzeń (append-only) **+** materializowany stan planszy | 3 tabele; brak problemu dołączającego (stan czytany wprost) |
| Real-time | **Jeden raw WebSocket** (bez Mercure) | Pełen dwukierunkowy kanał: ciągnięcie + upuszczenie |
| Technologia | **PHP / Workerman**, osobny kontener, długożyjący proces | Reużycie domeny DDD i repozytoriów Doctrine; jeden język |
| Poprawność | **Deklarowana przez klienta** (`correct` z frontu) | Serwer nie weryfikuje logicznie; tylko walidacja zakresów |
| Auth | JWT (lexik), walidacja na handshake WS | Spójne z resztą API |
| Widoczność pokoju | `public` (dla wszystkich) lub `group` (ograniczony do grupy) | Pole `visibility` |
| Dołączanie | Każdy w aplikacji; zakładka „aktywne sesje” | `GET /api/sessions?status=open` |

### Założenie kluczowe
Pozycje podczas **ciągnięcia są ulotne i NIE trafiają do bazy** — do logu i stanu
planszy zapisywane jest wyłącznie **finalne upuszczenie**. (Inaczej log urósłby do
milionów wierszy.)

## 3. Architektura / kontenery

Dwa komponenty backendu współdzielące kod i bazę `puzzel_db`:

- **`api`** (istniejący, Apache+PHP) — HTTP: cykl życia sesji, odczyty, autoryzacja.
- **`ws` (NOWY kontener)** — długożyjący proces PHP/Workerman (CLI). Obsługuje
  połączenia WebSocket, trzyma pokoje w pamięci, przekazuje ciągnięcia (ulotne) i
  zapisuje upuszczenia (przez command bus → Doctrine).

```
docker-compose: usługa `ws`
  - build z tego samego kodu api (lub współdzielony obraz)
  - command: php bin/console app:ws:serve   (nowa komenda Symfony)
  - port: np. 8086 -> 8080 (ws://)
  - sieć: puzzel-back-app ; dostęp do `db`
  - restart: on-failure
```

## 4. Model danych — 3 tabele (migracje Doctrine)

### `puzzle_sessions` — pokój / współdzielona plansza
`id`, `uuid`, `puzzle_uuid`, `visibility` (public|group), `group_uuid` (nullable),
`created_by_user_uuid`, `status` (open|closed), `created_at`, `expires_at`
(= `created_at + 24h`), `closed_at`.

### `puzzle_piece_moves` — append-only log (wszystkie upuszczenia)
`id`, `uuid`, `session_uuid`, `user_uuid`, `puzzle_uuid`, `piece_index`,
`to_x`, `to_y`, `correct` (BOOL, deklarowane przez klienta), `seq`, `moved_at`.
Indeks: (`session_uuid`, `seq`).

### `puzzle_board_state` — materializowany aktualny stan (1 wiersz / element)
`id`, `session_uuid`, `piece_index`, `to_x`, `to_y`, `correct`, `updated_at`,
`last_move_uuid`. **Unikalny** indeks: (`session_uuid`, `piece_index`).

Każde upuszczenie: `INSERT` do logu **i** `UPSERT` do stanu — w jednej transakcji.

## 5. Protokół WebSocket (typy wiadomości)

Klient → serwer:
- `join`     — `{ sessionUuid }` (po handshake z JWT) → dołączenie do pokoju.
- `drag`     — `{ pieceIndex, x, y }` — ulotne, **nie zapisywane**, tylko relay.
- `drop`     — `{ pieceIndex, x, y, correct }` — trwałe: log + stan + broadcast.
- `leave`    — opuszczenie pokoju.

Serwer → klient (broadcast w pokoju):
- `peer_drag`  — ruch ciągnięcia innego gracza (ulotny).
- `peer_drop`  — zatwierdzone upuszczenie (po zapisie).
- `board_state`— pełny stan planszy przy dołączeniu (z `puzzle_board_state`).
- `presence`   — kto jest w pokoju (dołączył/wyszedł).

## 6. Domena (src/Puzzle, zgodnie z DDD)

- Encje: `PuzzleSession`, `PuzzlePieceMove` (log), `PuzzleBoardPiece` (stan).
- VO: `PuzzleSessionId`, `PieceMoveId`, `PiecePosition(x,y)`.
- Repozytoria: interfejsy + implementacje Doctrine (wzorzec
  `DoctrinePuzzleProgressRepository`), mapowania ORM XML, typy UUID.

## 7. Aplikacja (CQRS)

Commands:
- `StartPuzzleSession` — tworzy pokój, zwraca `sessionUuid`.
- `RecordPieceMove` — atomowo: INSERT log + UPSERT stan. Wołane z serwera WS przy `drop`.
- `ClosePuzzleSession`.

Queries:
- `GetBoardState` — czyta wprost z `puzzle_board_state` (dla dołączających).
- `GetSessionMoves` — historia z logu (analityka/replay/audyt).
- `GetOpenSessions` — lista otwartych pokoi (zakładka „aktywne sesje”).

DTO: `PieceMoveDTO`, `BoardStateDTO`, `SessionDTO`.

## 8. Endpointy HTTP (na `api`)

- `POST /api/puzzles/{uuid}/sessions` — utwórz pokój (`visibility`, opc. `group_uuid`).
- `GET  /api/sessions?status=open` — aktywne sesje (filtrowane po widoczności/grupie).
- `GET  /api/sessions/{sessionUuid}/board` — aktualny stan planszy.
- `GET  /api/sessions/{sessionUuid}/moves` — historia ruchów.
- `POST /api/sessions/{sessionUuid}/close` — zamknij pokój.

Real-time (ciągnięcie + upuszczenie) idzie kanałem **WebSocket**, nie HTTP.

## 9. Bezpieczeństwo

- Handshake WS autoryzowany **JWT lexik** (token w query/`Sec-WebSocket-Protocol`),
  weryfikowany tym samym kluczem publicznym co API. Ratchet nie przechodzi przez
  firewall Symfony → walidacja JWT ręcznie.
- Pokój `group`: dołączyć mogą tylko członkowie grupy (sprawdzenie w domenie Group).
- `correct` przyjmowane bez weryfikacji logicznej; walidacja zakresów:
  `0 <= piece_index < totalPieces`, `to_x/to_y` w granicach siatki `piecesX/piecesY`.

## 10. Etapy

| Etap | Zawartość |
|------|-----------|
| 0 | Kontener `ws` + komenda `app:ws:serve` (szkielet Ratchet) + walidacja JWT na handshake |
| 1 | Migracje (3 tabele) + encje + repozytoria Doctrine |
| 2 | Pokoje w pamięci + `join`/`drag`/`peer_drag` (ulotny relay, bez DB) |
| 3 | `drop` → `RecordPieceMove` (log + stan, transakcja) + `peer_drop` broadcast |
| 4 | Endpointy HTTP (start/close/lista/board/moves) + `board_state` przy dołączeniu |
| 5 | Widoczność public/group + presence + zamykanie pokoju (po ukończeniu) |
| 6 | Auto-zamykanie po 24h (`expires_at` + komenda cron) |

## 11. Ryzyka / kwestie techniczne

- **Doctrine w procesie długożyjącym**: po każdym zapisie resetować/`clear()`
  EntityManager; obsłużyć rozłączenia z DB (reconnect).
- **Wygasanie JWT na długim połączeniu WS**: wygaśnięcie tokena **nie zamyka
  pokoju ani nie zrywa sesji** — pokój pozostaje otwarty. JWT walidowany tylko na
  handshake; brak wymuszonego re-auth w trakcie połączenia.
- **Czas życia pokoju**: pokój zamyka się, gdy puzzle zostaną **ukończone**, a
  jeśli wcześniej nikt nie skończy — **auto-zamknięcie po 24h** od utworzenia.
  Realizacja: pole `expires_at` (= `created_at + 24h`) + zadanie cykliczne
  (cron / komenda Symfony) zamykające przeterminowane pokoje (`status = closed`).
- **Skalowanie**: pojedynczy proces Ratchet trzyma pokoje w pamięci — przy >1
  instancji potrzebny wspólny bus (Redis pub/sub). Na teraz: 1 instancja.
- **Throttling `drag`**: front powinien dławić ramki (np. ~20/s), serwer może
  dodatkowo ograniczać, by nie zalać pokoju.

## 12. Otwarte kwestie (do domknięcia)

1. **Relacja do istniejącego `PuzzleProgress`** — czy upuszczenia mają też
   aktualizować stare liczniki (`piecesPlaced`, `completed`), czy oba mechanizmy
   pozostają niezależne?

Rozstrzygnięte: zachowanie przy wygaśnięciu JWT (pokój zostaje otwarty) oraz czas
życia pokoju (zamknięcie po ukończeniu lub auto po 24h) — patrz sekcja 11.
