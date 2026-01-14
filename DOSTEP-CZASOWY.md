# Czasowy dostęp do kursów - Dokumentacja

## Przegląd

Od wersji 1.3.0 Simple LMS wspiera **czasowy dostęp do kursów**. Możesz określić, ile dni po zakupie użytkownik będzie miał dostęp do treści kursu.

## Jak to działa?

### 1. Ustawienie czasu trwania dostępu w kursie

W panelu edycji kursu, w sekcji **"Ustawienia kursu"** (sidebar), znajdziesz nową opcję:

**"Czas trwania dostępu"**
- Pole liczbowe: wprowadź wartość (np. 30, 6, 5)
- Lista wyboru jednostki: dni / tygodni / miesięcy / lat
- Wartość domyślna: 0 (dostęp bezterminowy)
- Przykłady:
  - "30 dni" = dostęp przez miesiąc
  - "6 miesięcy" = dostęp przez pół roku
  - "5 lat" = dostęp przez 5 lat
  - "52 tygodni" = dostęp przez rok

### 2. Automatyczne przyznawanie dostępu

Gdy użytkownik zakupi kurs przez WooCommerce:
- System automatycznie obliczy datę wygaśnięcia
- Data wygaśnięcia zostanie zapisana w metadanych użytkownika
- Użytkownik otrzyma dostęp do wszystkich treści kursu

**⚠️ Ważne - interakcja z Harmonogramem dostępu:**

- **"Po zakupie kursu"** (domyślne): Czas liczy się **od momentu zakupu**
  - Przykład: Zakup 1.01 + 30 dni = wygasa 31.01
  
- **"Od konkretnej daty"**: Czas liczy się **od daty odblokowania**, nie od zakupu!
  - Przykład: Zakup 1.01, data odblokowania 15.01, czas 30 dni = wygasa 14.02
  - Jeśli data odblokowania jest w przeszłości, liczy się od momentu przyznania dostępu
  
- **"Stopniowo" (drip)**: Czas liczy się **od momentu zakupu**
  - Przykład: Zakup 1.01 + 30 dni = wygasa 31.01 (niezależnie od harmonogramu modułów)

### 3. Sprawdzanie dostępu

Przy każdym dostępie do lekcji system sprawdza:
- Czy użytkownik ma tag dostępu do kursu
- Czy dostęp nie wygasł (jeśli ustawiona jest data wygaśnięcia)
- Jeśli wygasł - dostęp jest automatycznie usuwany

### 4. Automatyczne czyszczenie

**Cron job** uruchamiany codziennie:
- Sprawdza wszystkie daty wygaśnięcia
- Usuwa wygasłe dostępy
- Loguje akcje w error_log

## Zarządzanie ręczne

### W profilu użytkownika

Administrator może w profilu użytkownika:
1. Zobaczyć listę wszystkich kursów
2. Przypisać/usunąć dostęp (checkbox)
3. Zobaczyć daty wygaśnięcia i pozostały czas
4. **Ręcznie zmienić datę wygaśnięcia** (pole datetime)
5. **Usunąć limit czasowy** (przycisk "Usuń limit" → dostęp bezterminowy)

### W meta boxie kursu

W edycji kursu, sidebar → "Użytkownicy z dostępem":
- Lista wszystkich użytkowników z dostępem
- Informacja o dacie wygaśnięcia każdego użytkownika
- Wizualne ostrzeżenie (czerwona ramka) dla dostępów wygasających w ciągu 7 dni
- Status "WYGASŁ" dla już wygasłych dostępów

## Shortcode - wyświetlanie informacji o wygaśnięciu

### Użycie w szablonach/treści

```php
[simple_lms_access_expiration]
```

**Parametry:**
- `course_id` - ID kursu (opcjonalnie, automatycznie wykrywa z kontekstu)
- `format` - format wyświetlania:
  - `full` (domyślny) - "Twój dostęp wygasa za X dni (2025-12-31)"
  - `days` - "Pozostało X dni dostępu"
  - `date` - "Dostęp do: 2025-12-31"
- `class` - dodatkowe klasy CSS

**Przykłady:**

```php
// Pełna informacja
[simple_lms_access_expiration]

// Tylko liczba dni
[simple_lms_access_expiration format="days"]

// Tylko data
[simple_lms_access_expiration format="date"]

// Z niestandardową klasą CSS
[simple_lms_access_expiration format="full" class="my-warning-box"]

// Dla konkretnego kursu
[simple_lms_access_expiration course_id="123"]
```

### W PHP

```php
// Sprawdź datę wygaśnięcia
$expiration = \SimpleLMS\simple_lms_get_course_access_expiration($user_id, $course_id);
// Zwraca: timestamp lub null (bezterminowy)

// Sprawdź pozostałe dni
$days = \SimpleLMS\simple_lms_get_course_access_days_remaining($user_id, $course_id);
// Zwraca: liczba dni lub null (bezterminowy)

// Przykład wyświetlania
if ($days !== null) {
    if ($days === 0) {
        echo "Dostęp wygasł!";
    } elseif ($days <= 7) {
        echo "Uwaga! Pozostało tylko $days dni!";
    } else {
        echo "Pozostało $days dni dostępu.";
    }
} else {
    echo "Dostęp bezterminowy";
}
```

## Ostrzeżenia wizualne

### Dla użytkowników (frontend)

Shortcode automatycznie dodaje klasy CSS:
- `.simple-lms-access-expiration` - podstawowa klasa
- `.simple-lms-expiration-warning` - dodawana gdy zostało ≤7 dni

Style domyślne:
- Niebieskie tło dla normalnych powiadomień
- Żółte tło dla ostrzeżeń (≤7 dni)

### Dla administratorów (backend)

W panelu użytkowników:
- Zielona ramka: dostęp aktywny
- Czerwona ramka: dostęp wygasa w ciągu 7 dni lub wygasł
- Status "WYGASŁ" wyróżniony bold i czerwony

## Scenariusze użycia

### 1. Kurs z 30-dniowym dostępem
```
1. Ustaw w kursie: "30" + wybierz "dni"
2. Klient kupuje kurs 1 stycznia
3. Dostęp wygasa 31 stycznia
4. 25 stycznia - ostrzeżenie (zostało 6 dni)
5. 1 lutego - dostęp automatycznie usunięty
```

### 1b. Kurs z 2-letnim dostępem
```
1. Ustaw w kursie: "2" + wybierz "lat"
2. Klient kupuje kurs 1 stycznia 2025
3. Dostęp wygasa 1 stycznia 2027
4. System automatycznie przelicza 2 lata = 730 dni
```

### 1c. Kurs z dostępem "Od konkretnej daty" + limit czasowy
```
Ustawienia kursu:
- Harmonogram: "Od konkretnej daty" → 15 stycznia 2025
- Czas trwania: "30" + "dni"

Scenariusz:
1. Klient kupuje 1 stycznia 2025
2. Dostęp do treści ZABLOKOWANY do 15 stycznia
3. 15 stycznia - treści odblokowują się automatycznie
4. Licznik 30 dni START od 15 stycznia (nie od 1 stycznia!)
5. Dostęp wygasa 14 lutego 2025

Ważne: Klient ma dostęp przez 30 dni OD MOMENTU ODBLOKOWANIA,
nie od momentu zakupu!
```

### 2. Przedłużanie dostępu
```
Administrator w profilu użytkownika:
1. Znajduje kurs z wygasającym dostępem
2. Zmienia datę w polu "Zmień datę wygaśnięcia"
3. Zapisuje profil
4. Użytkownik automatycznie odzyskuje dostęp
```

### 3. Dostęp bezterminowy po okresie próbnym
```
1. Kurs ma ustawione 30 dni
2. Klient kupuje i ma dostęp przez 30 dni
3. Po zakończeniu okresu chce przedłużyć
4. Administrator w profilu klika "Usuń limit"
5. Dostęp staje się bezterminowy
```

## Bezpieczeństwo i wydajność

### Cache
- Wyniki sprawdzania dostępu są cache'owane przez 12h (transient)
- Cache jest czyszczony przy każdej zmianie dostępu
- Cache jest czyszczony przy zmianie daty wygaśnięcia

### Cron
- Uruchamiany codziennie o północy (czas serwera)
- Przetwarza tylko wygasłe dostępy (filtrowane przez timestamp)
- Loguje wszystkie usunięcia do error_log

### Walidacja
- Wszystkie daty są validowane przez strtotime()
- Wartości ujemne/nieprawidłowe są ignorowane
- Użycie prepared statements w zapytaniach SQL

## Rozwiązywanie problemów

### Dostęp nie wygasa automatycznie
1. Sprawdź czy WP Cron jest włączony
2. Ręcznie uruchom: `wp cron event run simple_lms_cleanup_expired_access`
3. Sprawdź error_log czy są błędy

### Data wygaśnięcia się nie zmienia
1. Upewnij się że zapisujesz profil użytkownika
2. Sprawdź format daty (YYYY-MM-DDTHH:MM)
3. Wyczyść cache (transient): `wp transient delete slms_access_{user_id}_{course_id}`

### Shortcode nie wyświetla się
1. Sprawdź czy użytkownik ma dostęp do kursu
2. Sprawdź czy dostęp ma ustawioną datę wygaśnięcia (bezterminowy nie wyświetla nic)
3. Sprawdź czy shortcode jest w odpowiednim kontekście (strona kursu/lekcji)

## API dla developerów

### Funkcje globalne

```php
namespace SimpleLMS;

// Przypisz dostęp z automatyczną datą wygaśnięcia (jeśli ustawiona w kursie)
simple_lms_assign_course_access_tag(int $user_id, int $course_id): bool

// Usuń dostęp
simple_lms_remove_course_access_tag(int $user_id, int $course_id): bool

// Sprawdź dostęp (z walidacją wygaśnięcia)
simple_lms_user_has_course_access(int $user_id, int $course_id): bool

// Pobierz datę wygaśnięcia
simple_lms_get_course_access_expiration(int $user_id, int $course_id): ?int

// Pobierz pozostałe dni
simple_lms_get_course_access_days_remaining(int $user_id, int $course_id): ?int

// Ręczne czyszczenie wygasłych dostępów
simple_lms_cleanup_expired_access(): void
```

### Hooki WordPress

```php
// Wykonaj akcję po usunięciu wygasłego dostępu
add_action('simple_lms_access_expired', function($user_id, $course_id) {
    // Wyślij email, zaloguj, itp.
}, 10, 2);
```

### Meta klucze

```php
// User meta
'simple_lms_course_access' // array - lista ID kursów
'simple_lms_course_access_expiration_{course_id}' // int - timestamp wygaśnięcia

// Post meta (course)
'_access_duration_value' // int - wartość czasu (np. 5, 30, 2)
'_access_duration_unit' // string - jednostka: 'days', 'weeks', 'months', 'years'
'_access_duration_days' // int - DEPRECATED (stary format, zachowany dla kompatybilności)
```

### Przeliczanie jednostek

System automatycznie przelicza jednostki na dni:
- 1 dzień = 1 dzień
- 1 tydzień = 7 dni
- 1 miesiąc = 30 dni (przybliżenie)
- 1 rok = 365 dni (przybliżenie)

## Migracja z poprzednich wersji

### Z wersji bez czasowego dostępu
Jeśli aktualizujesz z wersji bez czasowego dostępu:
1. Wszyscy istniejący użytkownicy zachowają dostęp bezterminowy
2. Nowi użytkownicy po zakupie otrzymają dostęp zgodny z ustawieniem kursu
3. Możesz ręcznie dodać daty wygaśnięcia dla istniejących użytkowników w profilu

### Z wersji 1.3.0 (stary format - tylko dni)
Jeśli używałeś wcześniejszej wersji z polem "Liczba dni":
- Stare ustawienia (`_access_duration_days`) są automatycznie rozpoznawane
- System traktuje je jako "X dni"
- Możesz zaktualizować kursy do nowego formatu (wartość + jednostka)
- Stare meta klucze pozostają jako backup, nie są usuwane

## Licencja

Część wtyczki Simple LMS v1.3.0+
© 2025 Filip Meyer-Lüters
GPL-2.0+
