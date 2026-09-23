# Puzzle Demo API

Skrócona wersja demonstracyjna API w Symfony 7.2, zorganizowana zgodnie z DDD i CQRS.

## Zakres

- rejestracja, logowanie, aktywacja i reset haseł użytkowników,
- administracja użytkownikami,
- dodawanie, edycja, usuwanie i pobieranie puzzli,
- publiczne sesje puzzli, postęp oraz WebSocket.

Wersja demonstracyjna nie zawiera użytkowników premium, grup ani kampanii.

## Uruchomienie

Wymagane jest PHP 8.3 oraz skonfigurowane zmienne środowiskowe z pliku `.env`.

```bash
composer install
php bin/console doctrine:migrations:migrate
php -S 127.0.0.1:8000 -t public
```

Dokumentacja endpointów jest dostępna przez konfigurację Nelmio API Doc.

## Testy

```bash
composer test
```

Migracja `Version20260923000001` usuwa ze starszej bazy tabele i kolumny związane z wyłączonymi funkcjami. Przed jej wykonaniem wykonaj kopię bazy danych.
