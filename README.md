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

## OpenAI Integration
- W panelu **Ustawienia → OpenAI Integration** uzupełnij pole **OpenAI API Key** oraz wybierz model z listy (np. `gpt-4`, `gpt-4o`, `gpt-3.5-turbo`).
- Endpointy REST wymagają uprawnień `edit_posts` i wykorzystują zapisany klucz i model.

### Endpointy REST
- `POST /wp-json/owp/v1/openai/summarize`
  - Parametry: `text` (tekst do streszczenia) lub `video_id`/`video_url` (pobierze opis z YouTube), opcjonalnie nagłówek autoryzacji WordPress.
  - Zwraca pole `summary`.
- `POST /wp-json/owp/v1/openai/titles`
  - Parametry: `text` oraz `count` (liczba tytułów, domyślnie 3).
  - Zwraca tablicę `titles`.
- `POST /wp-json/owp/v1/openai/description`
  - Parametry: `text` (tekst źródłowy).
  - Zwraca pole `description`.

### Przykładowe wywołania cURL
- Streszczenie krótkiego tekstu:
  ```bash
  curl -X POST "https://twoja-domena.pl/wp-json/owp/v1/openai/summarize" \
       -H "Content-Type: application/json" \
       -d '{"text": "WordPress to popularny CMS umożliwiający tworzenie stron i blogów."}'
  ```
- Tytuły (5 propozycji):
  ```bash
  curl -X POST "https://twoja-domena.pl/wp-json/owp/v1/openai/titles" \
       -H "Content-Type: application/json" \
       -d '{"text": "Artykuł o optymalizacji SEO w WordPressie", "count": 5}'
  ```
- Opis/metaopis SEO:
  ```bash
  curl -X POST "https://twoja-domena.pl/wp-json/owp/v1/openai/description" \
       -H "Content-Type: application/json" \
       -d '{"text": "Dowiedz się jak przyspieszyć swoją stronę na WordPressie za pomocą cache i optymalizacji obrazów."}'
  ```
- Streszczenie opisu filmu z YouTube:
  ```bash
  curl -X POST "https://twoja-domena.pl/wp-json/owp/v1/openai/summarize" \
       -H "Content-Type: application/json" \
       -d '{"video_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ"}'
  ```

## Instalacja
1. Sklonuj repozytorium do katalogu `wp-content/plugins/`.
2. Aktywuj wtyczkę w panelu WordPress.

## Licencja
Projekt udostępniany na licencji GPL-2.0-or-later. Szczegóły w pliku `LICENSE`.
