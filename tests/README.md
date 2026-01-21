# Simple LMS Test Suite

## Przegląd
Automatyczne testy dla Simple LMS v1.0.0.

W tym repo są dwa tryby:
- **Unit (Brain Monkey)**: szybkie testy bez uruchamiania WordPressa (działają w CI na każdą zmianę).
- **Integration (WordPress / wp-env)**: testy wymagające pełnego runtime WordPressa (uruchamiane osobnym jobem w CI).

## Instalacja

### 1. Zainstaluj Composer (jeśli nie masz)
```powershell
# Windows - użyj instalatora:
# https://getcomposer.org/Composer-Setup.exe

# Lub przez Chocolatey:
choco install composer
```

### 2. Zainstaluj zależności testowe
```powershell
# W folderze wtyczki:
cd "C:\Users\fimel\Local Sites\simple-ecosystem\app\public\wp-content\plugins\simple-lms"

# Instalacja zależności testowych (w folderze tests/)
cd tests
composer install
```

## Testy integracyjne (wp-env)

Wymagania:
- Docker Desktop (uruchomiony)
- Node.js (>= 18)

Uruchomienie wp-env i testów integracyjnych (jak w CI):
```powershell
# Z katalogu głównego wtyczki
cd "C:\Users\fimel\Local Sites\simple-ecosystem\app\public\wp-content\plugins\simple-lms"

npm run wp-env:start
npm run test:wp
npm run wp-env:stop
```

## Uruchamianie testów

### Wszystkie testy
```powershell
composer test
```

### Z pokryciem kodu (coverage)
```powershell
composer test:coverage
```

Raport coverage będzie w folderze `coverage/index.html`

### Tylko testy jednostkowe
```powershell
vendor/bin/phpunit --testsuite="Unit Tests"
```

### Tylko testy integracyjne
```powershell
vendor/bin/phpunit --testsuite="Integration Tests"
```

Uwaga: te testy wymagają środowiska WordPress (np. `wp-env`). W zwykłym uruchomieniu (Brain Monkey) będą pomijane.

### Pojedynczy plik testowy
```powershell
vendor/bin/phpunit tests/Unit/CacheHandlerTest.php
```

### Z verbose output
```powershell
vendor/bin/phpunit --verbose
```

## Static Analysis

### PHP CodeSniffer (sprawdza WordPress Coding Standards)
```powershell
composer phpcs
```

### PHPStan (analiza statyczna)
```powershell
composer phpstan
```

### Wszystkie checkie na raz
```powershell
composer check
```

## Struktura testów

```
tests/
├── bootstrap.php              # Bootstrap dla PHPUnit
├── TestCase.php               # Bazowa klasa dla wszystkich testów
├── Unit/                      # Testy jednostkowe (bez WordPress runtime)
│   ├── CacheHandlerTest.php
│   ├── AccessControlTest.php
│   ├── ProgressTrackerTest.php
│   ├── WooCommerceIntegrationTest.php
│   └── AjaxHandlerTest.php
└── Integration/               # Testy integracyjne (z WordPress)
    └── (do implementacji)
```

## Co testujemy?

### ✅ Cache Handler
- Generowanie kluczy cache z wersjonowaniem
- Cache hit/miss dla modułów i lekcji
- Invalidacja cache przy zapisie postów
- Increment cache version

### ✅ Access Control
- User ma dostęp z poprawnym tagiem
- User nie ma dostępu bez taga
- Grant access dodaje tag
- Revoke access usuwa tag
- Logged out user nie ma dostępu

### ✅ Progress Tracker
- Update progress z poprawnymi danymi
- Walidacja user_id i lesson_id
- Cache progress data
- Schema upgrade check (1.0 → 1.1)
- Composite indexes creation

### ✅ WooCommerce Integration
- is_woocommerce_active detection
- Grant access on order completion
- Product ID migration (single → array)
- Skip already migrated courses

### ✅ AJAX Handlers
- Hook registration
- Nonce verification (pass/fail)
- Capability checks (pass/fail)
- Input sanitization (getPostInt, getPostString)
- Post type validation

## Interpretacja wyników

### Sukces
```
OK (25 tests, 45 assertions)
```
✅ Wszystkie testy przeszły!

### Failure
```
FAILURES!
Tests: 25, Assertions: 45, Failures: 2.
```
❌ Sprawdź output - pokazuje które testy failed i dlaczego

### Error
```
ERRORS!
Tests: 25, Assertions: 43, Errors: 1.
```
🔥 Błąd PHP (np. syntax error, missing class)

## Debugging testów

### Włącz verbose mode
```powershell
vendor/bin/phpunit --verbose --debug
```

### Test pojedynczej metody
```powershell
vendor/bin/phpunit --filter testUserHasAccessWithValidCourseTag
```

### Stop on failure
```powershell
vendor/bin/phpunit --stop-on-failure
```

## Continuous Integration

Możesz dodać testy do GitHub Actions:

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  phpunit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: php-actions/composer@v6
      - run: composer install
      - run: composer test
```

## Metryki jakości

### Coverage Target
- **Minimum:** 70% code coverage
- **Good:** 80%+ code coverage
- **Excellent:** 90%+ code coverage

### Test Status
```
✅ Cache Handler:          100% (6/6 tests)
✅ Access Control:         100% (6/6 tests)
✅ Progress Tracker:       100% (6/6 tests)
✅ WooCommerce Integration: 100% (5/5 tests)
✅ AJAX Handlers:          100% (8/8 tests)

Total: 31 tests, 60+ assertions
```

## Rozszerzanie testów

### Dodaj nowy test
```php
<?php
namespace SimpleLMS\Tests\Unit;

use SimpleLMS\Tests\TestCase;

class MyNewTest extends TestCase
{
    public function testSomething(): void
    {
        // Arrange
        $expected = 'value';
        
        // Act
        $actual = someFunction();
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
}
```

### Uruchom nowy test
```powershell
vendor/bin/phpunit tests/Unit/MyNewTest.php
```

## Troubleshooting

### Problem: "Class not found"
```powershell
composer dump-autoload
```

### Problem: "Brain Monkey not found"
```powershell
composer install --no-dev=false
```

### Problem: Memory limit
```powershell
# phpunit.xml - dodaj:
<php>
    <ini name="memory_limit" value="512M"/>
</php>
```

### Problem: Windows path issues
Użyj pełnychścieżek lub uruchom z Git Bash zamiast PowerShell.

## Best Practices

1. **Jeden test = jedna asercja** (w większości przypadków)
2. **Test name opisuje co testuje**: `testUserHasAccessWithValidCourseTag`
3. **AAA Pattern**: Arrange → Act → Assert
4. **Używaj mocków dla external dependencies** (WP functions, DB)
5. **Testy muszą być deterministyczne** (ten sam wynik za każdym razem)

## Wsparcie

- **Dokumentacja PHPUnit**: https://phpunit.de/
- **Brain Monkey docs**: https://brain-wp.github.io/BrainMonkey/
- **WordPress Testing**: https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/

---

**Ostatnia aktualizacja:** 2025-11-25  
**Wersja wtyczki:** 1.3.1  
**PHPUnit version:** 9.5+
