# Instrukcja naprawy etykiet statusu SimpleLMS

1. Sprawdź, czy w assets/dist nie ma starych plików JS/CSS. Usuń je ręcznie lub przez skrypt.
2. Wykonaj pełne przebudowanie frontu: npm run build w katalogu pluginu.
3. Wyczyść cache przeglądarki i cache WordPressa (jeśli używasz wtyczek cache).
4. Upewnij się, że w PHP (custom-meta-boxes.php) oraz JS (admin.js) etykiety statusu są wszędzie ustawione na 'Opublikowano'/'Szkic'.
5. Jeśli problem nadal występuje, sprawdź, czy nie masz zduplikowanych plików JS/CSS w innych katalogach pluginu.

Po tych krokach etykiety powinny być poprawne. Jeśli nie, zgłoś dokładną lokalizację starej etykiety w kodzie lub zrób zrzut ekranu z inspektora elementów.
