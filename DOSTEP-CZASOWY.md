# Czasowy dostęp do kursów

Simple LMS pozwala nadawać dostęp do kursu na czas określony (z datą wygaśnięcia) albo bez limitu.

## Jak to działa

- Dostęp do kursu jest zapisywany w `user_meta` jako lista ID kursów (tag dostępu).
- Opcjonalnie można ustawić datę wygaśnięcia dostępu per kurs.
- Gdy dostęp wygaśnie, Simple LMS traktuje go jako nieważny i usuwa wpis dostępu dla danego kursu.

## Ustawianie daty wygaśnięcia (panel admina)

1. Wejdź w edycję użytkownika w WordPress (Użytkownicy → Profil / Edytuj).
2. W sekcji dostępu Simple LMS zaznacz kurs, do którego użytkownik ma mieć dostęp.
3. Ustaw datę w polu **Set expiration date** / **Change expiration date**.
4. Zapisz profil użytkownika.

Aby usunąć limit (dostęp bezterminowy), wyczyść pole daty albo użyj przycisku **Remove limit** i zapisz profil.

## Dla deweloperów (meta keys)

- Lista kursów z dostępem:
  - `simple_lms_course_access` (tablica ID kursów)
- Wygaśnięcie dostępu per kurs:
  - `simple_lms_course_access_expiration_{course_id}` (timestamp)
- (Opcjonalnie) start dostępu / start harmonogramu (drip):
  - `simple_lms_course_access_start_{course_id}` (timestamp GMT)

## Uwagi

- Jeśli korzystasz z WooCommerce: zakup kursu może nadawać dostęp automatycznie; limit czasowy możesz nadal ustawić ręcznie w profilu użytkownika.
- Wygaśnięcie jest oceniane po czasie serwera (`current_time('timestamp')`).
