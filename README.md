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

## Instalacja
1. Sklonuj repozytorium do katalogu `wp-content/plugins/`.
2. Aktywuj wtyczkę w panelu WordPress.

## Licencja
Projekt udostępniany na licencji GPL-2.0-or-later. Szczegóły w pliku `LICENSE`.
