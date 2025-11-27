# OpenAI WP Integration Pro

Podstawowy szkielet wtyczki WordPress integrującej OpenAI/ChatGPT i YouTube.

## Wymagania
- WordPress 6.0+
- PHP 7.4+

## Struktura
- `openai-wp-integration-pro.php` – plik główny wtyczki.
- `includes/` – katalog z klasami pomocniczymi.

## YouTube Integration
- Konfiguracja klucza API YouTube znajduje się w panelu **Ustawienia → OpenAI Integration**.
- Po zapisaniu klucza można korzystać z endpointu REST `POST /wp-json/owp/v1/youtube/fetch` przekazując `video_url` lub `video_id`.

## Aktualizacja metadanych
- W tym samym ekranie ustawień można wkleić token OAuth 2.0 w polu **YouTube OAuth Access Token**. Token musi być wydany dla zakresów `youtube` oraz `youtube.force-ssl` i posiadać prawo do edycji filmów w kanale.
- Endpoint `POST /wp-json/owp/v1/youtube/update-meta` przyjmuje dane w JSON:
  - `video_id` lub `video_url` – identyfikator filmu do aktualizacji,
  - `title` – nowy tytuł,
  - `description` – nowy opis,
  - `tags` – lista tagów oddzielonych przecinkami,
  - `category_id` – identyfikator kategorii (opcjonalny, wymagany przez API YouTube).
- Przykładowe wywołanie cURL:
  ```bash
  curl -X POST "https://twoja-domena.pl/wp-json/owp/v1/youtube/update-meta" \
       -H "Content-Type: application/json" \
       -H "Authorization: Bearer <wp-rest-nonce-jeśli-wymagany>" \
       -d '{
         "video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
         "title": "Nowy tytuł",
         "description": "Zmieniony opis",
         "tags": "wordpress,api,przyklad",
         "category_id": "22"
       }'
  ```
- Po pomyślnej aktualizacji odpowiedź zawiera zwrócone przez YouTube informacje o filmie (tytuł, opis, tagi, kategoria).

## Instalacja
1. Sklonuj repozytorium do katalogu `wp-content/plugins/`.
2. Aktywuj wtyczkę w panelu WordPress.

## Licencja
Projekt udostępniany na licencji GPL-2.0-or-later. Szczegóły w pliku `LICENSE`.
