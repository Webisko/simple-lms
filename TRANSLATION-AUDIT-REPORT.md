# Raport Audytu Tłumaczeń Simple LMS

## Data: 2025-01-XX
## Wersja: 1.4.0

---

## 🔴 KRYTYCZNE PROBLEMY

### 1. Hardcoded Polish Strings w funkcjach i18n

**Problem**: Stringi w funkcjach `esc_html__()`, `__()`, `_e()` powinny być po ANGIELSKU w `msgid`, a polskie tłumaczenie w `msgstr` plików .po.

**Dotknięte pliki** (84 wystąpienia polskich znaków w i18n):

| Plik | Polskie stringi |
|------|----------------|
| `includes/custom-meta-boxes.php` | 39 |
| `includes/class-access-meta-boxes.php` | 12 |
| `includes/admin-customizations.php` | 4 |
| `includes/elementor-dynamic-tags/widgets/course-overview-widget.php` | 4 |
| `includes/elementor-dynamic-tags/widgets/lesson-video-widget.php` | 3 |
| + 15 innych plików Elementor | 22 |

**Przykład błędu**:
```php
// ❌ ŹLE - msgid po polsku
echo '<h3>' . esc_html__('Harmonogram dostępu', 'simple-lms') . '</h3>';

// ✅ DOBRZE - msgid po angielsku
echo '<h3>' . esc_html__('Access Schedule', 'simple-lms') . '</h3>';
```

**Konsekwencje**:
- Gdy użytkownik wybierze język angielski lub niemiecki w WordPress, zobaczy polskie teksty
- Nie można przetłumaczyć tych stringów w Poedit/Loco Translate
- `.pot` template zawiera polskie `msgid` zamiast angielskich

---

### 2. Hardcoded Polish Text (bez i18n)

**Lokalizacje**:

1. **`includes/elementor-dynamic-tags/widgets/course-overview-widget.php:457`**
   ```php
   echo '<span class="unlock-date">Dostępne od: ' . esc_html($date_str) . '</span>';
   ```
   Powinno być:
   ```php
   echo '<span class="unlock-date">' . sprintf(__('Available from: %s', 'simple-lms'), esc_html($date_str)) . '</span>';
   ```

2. **`includes/class-shortcodes.php:810`** (3 wystąpienia)
   ```php
   $unlock_label_html = '<span class="unlock-date">Dostępne od: ' . esc_html($date_str) . '</span>';
   ```

3. **`includes/bricks/elements/course-purchase.php:40`**
   ```php
   esc_html($settings['buttonText']??'Kup kurs')
   ```

---

### 3. Niekompletne Pliki Tłumaczeń

#### Statystyki:

| Plik | msgid | Przetłumaczone | Brakuje | % Kompletności |
|------|-------|----------------|---------|----------------|
| `simple-lms.pot` | 275 | - | - | Template |
| `simple-lms-pl_PL.po` | 248 | 247 | **27** | 89.8% |
| `simple-lms-en_US.po` | 338 | 331 | 7 | 97.9% |
| `simple-lms-de_DE.po` | 107 | 100 | **168** | 38.9% |

#### Analiza rozbieżności:

**Dlaczego en_US ma 338 msgid, a .pot tylko 275?**
- Plik `simple-lms.pot` jest **przestarzały** (POT-Creation-Date: 2025-11-29)
- Dodano nowe stringi do kodu, ale nie wygenerowano nowego template
- W `en_US.po` stringi dodane ręcznie lub z poprzedniego skanowania

**Dlaczego de_DE ma tylko 107 msgid?**
- Tłumaczenia niemieckie zostały zaczynane od nowa (PO-Revision-Date: 2025-12-01)
- Nie zaimportowano wszystkich stringów z .pot
- Brakuje 168 stringów z 275 (61% brakujących!)

---

## 📋 PRZYKŁADOWE STRINGI DO NAPRAWY

### Z custom-meta-boxes.php:

| Linia | Hardcoded Polski | Powinien być (EN) |
|-------|------------------|-------------------|
| 1167 | `'Czas trwania dostępu'` | `'Access Duration'` |
| 1182 | `'Harmonogram dostępu'` | `'Access Schedule'` |
| 1187 | `'Po zakupie kursu (domyślne)'` | `'After course purchase (default)'` |
| 1191 | `'Od konkretnej daty'` | `'From specific date'` |
| 1200 | `'Stopniowo'` | `'Gradually (Drip)'` |
| 1205 | `'Każdy kolejny moduł po X dniach'` | `'Each module after X days'` |
| 1211 | `'Każdy moduł niezależnie (ustaw w module)'` | `'Each module independently (set in module)'` |
| 1173 | `'dni'` | `'days'` |
| 1174 | `'tygodni'` | `'weeks'` |
| 1175 | `'miesięcy'` | `'months'` |
| 1176 | `'lat'` | `'years'` |

### Z Elementor widgets:

| Widget | Polski String | Angielski |
|--------|---------------|-----------|
| course-overview-widget.php | `'Ustawienia'` | `'Settings'` |
| course-overview-widget.php | `'Pokaż postęp ukończenia'` | `'Show completion progress'` |
| course-overview-widget.php | `'Pokaż daty odblokowania'` | `'Show unlock dates'` |
| lesson-video-widget.php | `'Źródło wideo'` | `'Video source'` |
| access-status-widget.php | `'Stan dostępu użytkownika'` | `'User access status'` |

---

## 🛠️ PLAN NAPRAWY

### Faza 1: Zinternacjonalizować Hardcoded Texty (Priorytet: WYSOKI)

1. ✅ Zamienić wszystkie polskie `msgid` na angielskie w:
   - `includes/custom-meta-boxes.php` (39 stringów)
   - `includes/class-access-meta-boxes.php` (12 stringów)
   - `includes/admin-customizations.php` (4 stringi)
   - `includes/elementor-dynamic-tags/widgets/*.php` (32 stringi w 19 plikach)

2. ✅ Owinąć hardcoded teksty w funkcje i18n:
   - `'Dostępne od: '` → `__('Available from: ', 'simple-lms')`
   - `'Kup kurs'` → `__('Buy Course', 'simple-lms')`

### Faza 2: Regeneracja Template .pot (Priorytet: WYSOKI)

```powershell
# Opcja A: WP-CLI (jeśli zainstalowany)
wp i18n make-pot . languages/simple-lms.pot --domain=simple-lms

# Opcja B: Composer (jeśli jest wp-cli/i18n-command)
composer require wp-cli/i18n-command --dev
vendor/bin/wp i18n make-pot . languages/simple-lms.pot

# Opcja C: Poedit Pro (GUI)
# File → New Catalog from Sources → Scan includes/
```

### Faza 3: Aktualizacja Tłumaczeń (Priorytet: ŚREDNI)

1. **Polski (pl_PL)**:
   - Otworzyć `simple-lms-pl_PL.po` w Poedit
   - Update from POT file → `simple-lms.pot`
   - Przetłumaczyć 27+ nowych stringów (z angielskiego na polski)
   - Zapisać i skompilować .mo

2. **Niemiecki (de_DE)**:
   - Otworzyć `simple-lms-de_DE.po` w Poedit
   - Update from POT file → `simple-lms.pot`
   - Przetłumaczyć **168 brakujących stringów** (z angielskiego na niemiecki)
   - Możliwa pomoc AI (DeepL API / ChatGPT) dla pierwszej wersji
   - Zapisać i skompilować .mo

3. **Angielski (en_US)**:
   - Teoretycznie nie wymagany (fallback do msgid)
   - Ale utrzymany dla spójności
   - Update i weryfikacja 7 brakujących

### Faza 4: Kompilacja i Weryfikacja (Priorytet: ŚREDNI)

```powershell
# Skompilować wszystkie .po → .mo
cd languages
php compile-translations.php

# Lub WP-CLI:
wp i18n make-mo languages/ --skip-mo
```

**Weryfikacja**:
1. Zmienić język WordPress na Polski → Sprawdzić ustawienia kursu
2. Zmienić język WordPress na Niemiecki → Sprawdzić czy wszystko przetłumaczone
3. Zmienić na Angielski → Sprawdzić czy brak polskich tekstów

---

## 📊 SZACOWANY CZAS NAPRAWY

| Zadanie | Czas | Trudność |
|---------|------|----------|
| Zamiana PL→EN w custom-meta-boxes.php | 30 min | Łatwe |
| Zamiana PL→EN w Elementor widgets | 45 min | Łatwe |
| Fix hardcoded 'Dostępne od:' | 10 min | Łatwe |
| Regeneracja .pot | 5 min | Łatwe |
| Tłumaczenie pl_PL (27 stringów) | 20 min | Łatwe |
| Tłumaczenie de_DE (168 stringów) | 2-3 h | Średnie |
| Weryfikacja i testy | 30 min | Łatwe |
| **RAZEM** | **~5h** | - |

---

## 🎯 PRIORYTETYZACJA

### Teraz (blokuje polskich użytkowników):
1. ✅ Zamiana polskich `msgid` na angielskie w `custom-meta-boxes.php`
2. ✅ Fix 'Dostępne od:' (widoczne na froncie)

### Potem (blokuje niemieckich użytkowników):
3. ✅ Tłumaczenia de_DE (168 stringów)

### Opcjonalne:
4. Zamiana PL→EN w Elementor (jeśli używasz Elementor)
5. Kompletne tłumaczenie en_US (7 brakujących)

---

## 🔍 TOOL DO AUDYTU

Użyty skrypt PowerShell:
```powershell
# Znajdź wszystkie polskie znaki w funkcjach i18n
Get-ChildItem -Path includes -Filter *.php -Recurse | ForEach-Object {
    $count = (Select-String -Path $_.FullName -Pattern "esc_html__\('[^']*[ąćęłńóśźżĄĆĘŁŃÓŚŹŻ]" | Measure-Object).Count
    if ($count -gt 0) {
        [PSCustomObject]@{ File = $_.Name; PolishStrings = $count }
    }
} | Format-Table -AutoSize
```

---

## 📝 NOTATKI

- **Zalecenie**: Używać angielskich stringów w `msgid` (standard i18n)
- **Debug mode**: Dodać `define('WP_DEBUG', true);` w wp-config.php aby zobaczyć brakujące tłumaczenia
- **Poedit**: Użyj "Validate translations" przed zapisaniem
- **Git**: Commitować .po, NIE commitować .mo (generowane automatycznie)

---

## ✅ CHECKLIST PO NAPRAWIE

- [ ] Wszystkie `msgid` w kodzie PHP są po angielsku
- [ ] Brak hardcoded polskiego tekstu (grep test passes)
- [ ] `simple-lms.pot` ma wszystkie stringi (338+)
- [ ] `simple-lms-pl_PL.po` - 100% kompletności
- [ ] `simple-lms-de_DE.po` - 100% kompletności  
- [ ] `simple-lms-en_US.po` - 100% kompletności
- [ ] Wszystkie .mo skompilowane
- [ ] Test: WordPress PL → wszystko po polsku
- [ ] Test: WordPress DE → wszystko po niemiecku
- [ ] Test: WordPress EN → wszystko po angielsku
- [ ] Commit i push do repo

---

**Koniec raportu**
