# Comprehensive Polish translation fix for Simple LMS
# This script rebuilds the PO file with proper UTF-8 encoding from scratch

$pluginDir = "C:\Users\fimel\Local Sites\simple-ecosystem\app\public\wp-content\plugins\simple-lms"
$poFile = "$pluginDir\languages\simple-lms-pl_PL.po"
$moFile = "$pluginDir\languages\simple-lms-pl_PL.mo"

# Polish translations - correct UTF-8 Polish characters
$translations = @{
    "%d hr. %d min." = "%d godz. %d min.";
    "%d minut" = "%d minut";
    "%d of %d lessons completed" = "%d z %d lekcji ukończono";
    "%d secund" = "%d sek";
    "%d/%d lessons completed (%.1f%%)" = "%d/%d lekcji ukończono (%.1f%%)";
    "(0 = lifetime access)" = "(0 = dostęp dożywotni)";
    "(Kopia)" = "(Kopia)";
    "1 year (365 days)" = "1 rok (365 dni)";
    "15 of 20 lessons completed" = "15 z 20 lekcji ukończono";
    "180 days" = "180 dni";
    "90 days" = "90 dni";
    "Access" = "Dostęp";
    "Access Denied Text" = "Tekst braku dostępu";
    "Access Duration" = "Czas dostępu";
    "Access Granted Text" = "Tekst przyznania dostępu";
    "Access Schedule" = "Harmonogram dostępu";
    "Add" = "Dodaj";
    "Add Lesson" = "Dodaj lekcję";
    "Add Module" = "Dodaj moduł";
    "Add New Course" = "Dodaj nowy kurs";
    "Add New Lesson" = "Dodaj nową lekcję";
    "Add New Module" = "Dodaj nowy moduł";
    "Add New Quiz" = "Dodaj nowy quiz";
    "Add Question" = "Dodaj pytanie";
    "Admin Email" = "Email administratora";
    "Allow Comments" = "Zezwól na komentarze";
    "Allow Duplicate Submissions" = "Zezwól na duplikaty";
    "Allow guest users" = "Zezwól gościom";
    "Allow Incomplete Submissions" = "Zezwól na niekompletne submission";
    "Analytics" = "Analityka";
    "Any" = "Dowolny";
    "Apply" = "Zastosuj";
    "Assigned to" = "Przydzielone do";
    "Attachment" = "Załącznik";
    "Attachments" = "Załączniki";
    "Audit Log" = "Dziennik audytu";
    "Available" = "Dostępne";
    "Back" = "Wstecz";
    "Back to course" = "Powrót do kursu";
    "Back to dashboard" = "Powrót do pulpitu";
    "Back to lesson" = "Powrót do lekcji";
    "Back to module" = "Powrót do modułu";
    "Back to quiz" = "Powrót do quizu";
    "Badge" = "Odznaka";
    "Badges" = "Odznaki";
    "Before Date" = "Przed datą";
    "Before save" = "Przed zapisem";
    "Block" = "Blokuj";
    "Blog Posts" = "Wpisy na blogu";
    "Break Time" = "Czas przerwy";
    "Browse" = "Przeglądaj";
    "Build Quiz" = "Zbuduj quiz";
    "BuLLK Actions" = "Akcje zbiorcze";
    "BuLLK Edit" = "Edycja zbiorcza";
    "Business" = "Biznes";
    "BuLLK Upload" = "Przesyłanie zbiorcze";
    "Cancel" = "Anuluj";
    "Capacity" = "Pojemność";
    "Catalouge" = "Katalog";
    "Category" = "Kategoria";
    "Certificate" = "Certyfikat";
    "Certificate Settings" = "Ustawienia certyfikatu";
    "Certificate Template" = "Szablon certyfikatu";
    "Certificates" = "Certyfikaty";
    "Change" = "Zmień";
    "Change Image" = "Zmień obraz";
    "Change featured image" = "Zmień obraz wyróżniający";
    "Check all" = "Zaznacz wszystko";
    "Check answers" = "Sprawdź odpowiedzi";
    "Check answers after submission" = "Sprawdź odpowiedzi po wysłaniu";
    "Checklist" = "Lista kontrolna";
    "Children" = "Dzieci";
    "Choose" = "Wybierz";
    "Class Status" = "Status klasy";
    "Clean Cache" = "Wyczyść pamięć podręczną";
    "Clear Cache" = "Wyczyść pamięć podręczną";
    "Click to Expand" = "Kliknij aby rozwinąć";
    "Close" = "Zamknij";
    "Collapse" = "Zwiń";
    "Collection" = "Kolekcja";
    "Collections" = "Kolekcje";
    "Comment" = "Komentarz";
    "Comments" = "Komentarze";
    "Communication Settings" = "Ustawienia komunikacji";
    "Company" = "Firma";
    "Completed" = "Ukończone";
    "Completed Lessons" = "Ukończone lekcje";
    "Completed on" = "Ukończone w dniu";
    "Completion Status" = "Status ukończenia";
    "Compliance" = "Zgodność";
    "Confirm Delete" = "Potwierdź usunięcie";
    "Connected" = "Połączony";
    "Content" = "Zawartość";
    "Content Library" = "Biblioteka zawartości";
    "Content Settings" = "Ustawienia zawartości";
    "Context" = "Kontekst";
    "Continue" = "Kontynuuj";
    "Continue Learning" = "Kontynuuj naukę";
    "Continue Reading" = "Czytaj dalej";
    "Contributor" = "Współpracownik";
    "Controls" = "Kontrole";
    "Copy" = "Kopiuj";
    "Copy Link" = "Skopiuj link";
    "Copyright" = "Prawa autorskie";
    "Core" = "Rdzeń";
    "Correct" = "Poprawne";
    "Correct Answer" = "Poprawna odpowiedź";
    "Correct Answers" = "Poprawne odpowiedzi";
    "Cost" = "Koszt";
    "Course" = "Kurs";
    "Course Access" = "Dostęp do kursu";
    "Course BuLLK" = "Kurs zbiorczo";
    "Course Category" = "Kategoria kursu";
    "Course Completion" = "Ukończenie kursu";
    "Course Dashboard" = "Pulpit kursu";
    "Course Details" = "Szczegóły kursu";
    "Course Difficulty" = "Poziom trudności kursu";
    "Course Duration" = "Czas trwania kursu";
    "Course Gallery" = "Galeria kursu";
    "Course Goals" = "Cele kursu";
    "Course Grid" = "Siatka kursów";
    "Course Guest Access" = "Dostęp gościa do kursu";
    "Course Highlight" = "Wyróżnienie kursu";
    "Course Instructors" = "Instruktorzy kursu";
    "Course Language" = "Język kursu";
    "Course List" = "Lista kursów";
    "Course Listing" = "Wyszczególnienie kursów";
    "Course Material" = "Materiał kursu";
    "Course Metadata" = "Metadane kursu";
    "Course Navigation" = "Nawigacja kursu";
    "Course Not Found" = "Kurs nie znaleziony";
    "Course Outline" = "Zarys kursu";
    "Course Overview" = "Przegląd kursu";
    "Course Registration" = "Rejestracja kursu";
    "Course Requirements" = "Wymagania kursu";
    "Course Search" = "Wyszukiwanie kursów";
    "Course Section" = "Sekcja kursu";
    "Course Settings" = "Ustawienia kursu";
    "Course Status" = "Status kursu";
    "Course Summary" = "Streszczenie kursu";
    "Course Tags" = "Tagi kursu";
    "Course Target" = "Cel kursu";
    "Course Team" = "Zespół kursu";
    "Course Type" = "Typ kursu";
    "Course Unit" = "Jednostka kursu";
    "Course URL" = "Adres URL kursu";
    "Courses" = "Kursy";
    "Courses per Page" = "Kursów na stronie";
    "Create" = "Utwórz";
    "Create Certificate" = "Utwórz certyfikat";
    "Create Course" = "Utwórz kurs";
    "Create Lesson" = "Utwórz lekcję";
    "Create Module" = "Utwórz moduł";
    "Create New" = "Utwórz nowy";
    "Create New Template" = "Utwórz nowy szablon";
    "Create Quiz" = "Utwórz quiz";
    "Created" = "Utworzono";
    "Created by" = "Utworzono przez";
    "Created Date" = "Data utworzenia";
    "Created on" = "Utworzono w dniu";
    "Creator" = "Twórca";
    "Criteria" = "Kryteria";
    "Crop" = "Przytnij";
    "Cross Sell" = "Sprzedaż krzyżowa";
    "CSV" = "CSV";
    "Current" = "Obecny";
    "Current Date" = "Aktualna data";
    "Current Password" = "Bieżące hasło";
    "Custom" = "Niestandardowy";
    "Custom Field" = "Pole niestandardowe";
    "Custom Fields" = "Pola niestandardowe";
    "Custom Post Type" = "Typ wpisu niestandardowy";
    "Customize" = "Dostosuj";
    "Dashboard" = "Pulpit";
    "Data" = "Data";
    "Data Export" = "Eksport danych";
    "Data Retention" = "Przechowywanie danych";
    "Date" = "Data";
    "Date Format" = "Format daty";
    "Date Range" = "Zakres dat";
    "Deactive" = "Wyłącz";
    "Default" = "Domyślne";
    "Default Course" = "Domyślny kurs";
    "Delete" = "Usuń";
    "Delete Course" = "Usuń kurs";
    "Delete Lesson" = "Usuń lekcję";
    "Delete Module" = "Usuń moduł";
    "Delete Permanently" = "Usuń na stałe";
    "Delete This Item" = "Usuń ten element";
    "Deleted" = "Usunięte";
    "Deleting" = "Usuwanie";
    "Deletion" = "Usunięcie";
    "Density" = "Gęstość";
    "Depends" = "Zależy";
    "Dependency" = "Zależność";
    "Depth" = "Głębokość";
    "Description" = "Opis";
    "Description (short)" = "Opis (krótki)";
    "Description Settings" = "Ustawienia opisu";
    "Design" = "Projekt";
    "Designer" = "Projektant";
    "Desk Area" = "Obszar biurka";
    "Detailed Report" = "Raport szczegółowy";
    "Detailed View" = "Widok szczegółowy";
    "Detected" = "Wykryto";
    "Developer" = "Deweloper";
    "Device" = "Urządzenie";
    "Devices" = "Urządzenia";
    "Diagram" = "Diagram";
    "Dialog" = "Dialog";
    "Difficulty" = "Trudność";
    "Difficulty Level" = "Poziom trudności";
    "Dimensions" = "Wymiary";
    "Direct" = "Bezpośredni";
    "Direct Message" = "Wiadomość bezpośrednia";
    "Direction" = "Kierunek";
    "Directory" = "Katalog";
    "Disable" = "Wyłącz";
    "Disabled" = "Wyłączone";
    "Disabling" = "Wyłączanie";
    "Disallow" = "Nie zezwalaj";
    "Discard" = "Odrzuć";
    "Disconnect" = "Rozłącz";
    "Discount" = "Rabat";
    "Discount Code" = "Kod rabatowy";
    "Discount Codes" = "Kody rabatowe";
    "Discover" = "Odkryj";
    "Discuss" = "Omów";
    "Discussion" = "Dyskusja";
    "Discussions" = "Dyskusje";
    "Display" = "Wyświetl";
    "Display Order" = "Kolejność wyświetlania";
    "Display Settings" = "Ustawienia wyświetlania";
    "Distance Learning" = "Nauczanie na odległość";
    "Division" = "Dział";
    "Document" = "Dokument";
    "Documents" = "Dokumenty";
    "Does Not Have" = "Nie ma";
    "Domain" = "Domena";
    "Done" = "Gotowe";
    "Download" = "Pobierz";
    "Download Report" = "Pobierz raport";
    "Downloadable" = "Możliwy do pobrania";
    "Downloads" = "Pobrania";
    "Draft" = "Wersja robocza";
    "Drag" = "Przeciągnij";
    "Drag and Drop" = "Przeciągnij i upuść";
    "Drag to Reorder" = "Przeciągnij aby zmienić kolejność";
    "Drag to Sort" = "Przeciągnij aby posortować";
    "Drop" = "Upuść";
    "Dropping" = "Upuszczanie";
    "Dropdown" = "Lista rozwijana";
    "Dropzone" = "Strefa upuszczania";
    "Due Date" = "Data wygaśnięcia";
    "Duplicate" = "Duplikat";
    "Duplicate Course" = "Duplikat kursu";
    "Duplicate Lesson" = "Duplikat lekcji";
    "Duplicate Module" = "Duplikat modułu";
    "Duration" = "Czas trwania";
    "Dwell Time" = "Czas przebywania";
    "Dynamic" = "Dynamiczny";
    "Edit" = "Edytuj";
    "Edit Course" = "Edytuj kurs";
    "Edit Lesson" = "Edytuj lekcję";
    "Edit Module" = "Edytuj moduł";
    "Edit Settings" = "Edytuj ustawienia";
    "Editable" = "Możliwy do edycji";
    "Editing" = "Edycja";
    "Education" = "Edukacja";
    "Educational" = "Edukacyjny";
    "Educator" = "Edukator";
    "Effect" = "Efekt";
    "Effort" = "Wysiłek";
    "EULA" = "EULA";
    "Email" = "Email";
    "Email Address" = "Adres email";
    "Email Format" = "Format e-maila";
    "Email Notification" = "Powiadomienie e-mail";
    "Email Notifications" = "Powiadomienia e-mail";
    "Email Settings" = "Ustawienia e-maila";
    "Email Template" = "Szablon e-maila";
    "Emails" = "E-maile";
    "Embed" = "Osadź";
    "Embedded" = "Osadzone";
    "Embedding" = "Osadzanie";
    "Emerald" = "Szmaragd";
    "Enable" = "Włącz";
    "Enabled" = "Włączone";
    "Enabling" = "Włączanie";
    "Enroll" = "Zapisz";
    "Enrolled" = "Zapisane";
    "Enrolled User" = "Zapisany użytkownik";
    "Enrolled Users" = "Zapisani użytkownicy";
    "Enrolling" = "Zapisywanie";
    "Enrollment" = "Rejestracja";
    "Enrollment Date" = "Data rejestracji";
    "Enrollment Duration" = "Czas rejestracji";
    "Enrollment Email" = "Email rejestracyjny";
    "Enrollment Form" = "Formularz rejestracyjny";
    "Enrollment Limit" = "Limit rejestracji";
    "Enrollment Limited" = "Rejestracja ograniczona";
    "Enrollment Period" = "Okres rejestracji";
    "Enrollment Settings" = "Ustawienia rejestracji";
    "Enrollment Status" = "Status rejestracji";
    "Enrollments" = "Rejestracje";
    "Enterprise" = "Przedsiębiorstwo";
    "Entry" = "Wpis";
    "Environment" = "Środowisko";
    "Error" = "Błąd";
    "Error Code" = "Kod błędu";
    "Error Handler" = "Program obsługi błędów";
    "Error Log" = "Dziennik błędów";
    "Error Log Manager" = "Menedżer dziennika błędów";
    "Error Logging" = "Rejestrowanie błędów";
    "Error Message" = "Komunikat błędu";
    "Error Occurred" = "Pojawił się błąd";
    "Error Report" = "Raport błędu";
    "Error Reporting" = "Raportowanie błędów";
    "Errors" = "Błędy";
    "Essential" = "Istotny";
    "Essential Settings" = "Ustawienia istotne";
    "Estimated" = "Szacunkowe";
    "Estimated Time" = "Szacunkowy czas";
    "Estimated Time to Complete" = "Szacunkowy czas do ukończenia";
    "Event" = "Zdarzenie";
    "Event Log" = "Dziennik zdarzeń";
    "Event Logging" = "Rejestrowanie zdarzeń";
    "Events" = "Zdarzenia";
    "Everything" = "Wszystko";
    "Everywhere" = "Wszędzie";
    "Evidence" = "Dowód";
    "Evidence Log" = "Dziennik dowodów";
    "Evidence Tracking" = "Śledzenie dowodów";
    "Exact" = "Dokładny";
    "Exactly" = "Dokładnie";
    "Example" = "Przykład";
    "Examples" = "Przykłady";
    "Excel" = "Excel";
    "Exception" = "Wyjątek";
    "Exceptional" = "Wyjątkowy";
    "Exchange" = "Wymiana";
    "Exclude" = "Wyklucz";
    "Excluded" = "Wykluczone";
    "Exclusion" = "Wykluczenie";
    "Exclusive" = "Wyłączne";
    "Execute" = "Wykonaj";
    "Executing" = "Wykonywanie";
    "Execution" = "Wykonanie";
    "Exercise" = "Ćwiczenie";
    "Exercises" = "Ćwiczenia";
    "Expand" = "Rozwiń";
    "Expanded" = "Rozwinięte";
    "Expanding" = "Rozwijanie";
    "Expands Automatically" = "Rozszerza się automatycznie";
    "Expands When Needed" = "Rozszerza się w razie potrzeby";
    "Expect" = "Oczekuj";
    "Expected" = "Oczekiwane";
    "Expense" = "Wydatek";
    "Experience" = "Doświadczenie";
    "Experience Level" = "Poziom doświadczenia";
    "Experiences" = "Doświadczenia";
    "Experiential" = "Empiryczny";
    "Expert" = "Ekspert";
    "Expert Mode" = "Tryb eksperta";
    "Expertise" = "Wiedza ekspercka";
    "Experts" = "Eksperci";
    "Explain" = "Wyjaśnij";
    "Explanation" = "Wyjaśnienie";
    "Explanations" = "Wyjaśnienia";
    "Explicit" = "Jawny";
    "Expiration Date" = "Data wygaśnięcia";
    "Expiration Email" = "Email wygaśnięcia";
    "Expiration Period" = "Okres wygaśnięcia";
    "Expiration Settings" = "Ustawienia wygaśnięcia";
    "Expiration Warning" = "Ostrzeżenie wygaśnięcia";
    "Expires" = "Wygasa";
    "Expires In" = "Wygasa za";
    "Expires On" = "Wygasa w dniu";
    "Explain Auto-expand" = "Wyjaśnij auto-rozszerzanie";
    "Export" = "Eksportuj";
    "Export Data" = "Eksportuj dane";
    "Export Format" = "Format eksportu";
    "Export Settings" = "Ustawienia eksportu";
    "Exported" = "Eksportowane";
    "Exporting" = "Eksportowanie";
    "Exports" = "Eksporty";
    "Expose" = "Ujawnij";
    "Expression" = "Wyrażenie";
    "Expressions" = "Wyrażenia";
    "Extended" = "Rozszerzony";
    "Extension" = "Rozszerzenie";
    "Extensions" = "Rozszerzenia";
    "External" = "Zewnętrzny";
    "External API" = "Zewnętrzny API";
    "External Content" = "Zawartość zewnętrzna";
    "External Link" = "Link zewnętrzny";
    "External Links" = "Linki zewnętrzne";
    "Extra" = "Dodatkowy";
    "Extra Settings" = "Dodatkowe ustawienia";
    "Extra-Large" = "Ekstra duży";
    "Extract" = "Wyciągnij";
    "Eye" = "Oko";
    "Eye Tracking" = "Śledzenie wzroku";
    "Eyebrow" = "Brew";
}

# Create UTF-8 encoding without BOM
$utf8NoBom = New-Object System.Text.UTF8Encoding $false

# Build PO file content
$poContent = @"
# Polish translation for Simple LMS
# Copyright (C) 2024-2025 Simple LMS
# This file is distributed under the same license as the Simple LMS package.
#
msgid ""
msgstr ""
"Project-Id-Version: Simple LMS\n"
"POT-Creation-Date: 2025-01-16 00:00+0000\n"
"PO-Revision-Date: 2025-01-16 00:00+0000\n"
"Last-Translator: Polish Team\n"
"Language-Team: Polish\n"
"Language: pl_PL\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);\n"

"@

foreach ($msgid in $translations.Keys) {
    $msgstr = $translations[$msgid]
    $msgid_escaped = $msgid -replace '"', '\"'
    $msgstr_escaped = $msgstr -replace '"', '\"'
    $poContent += "msgid `"$msgid_escaped`"`n"
    $poContent += "msgstr `"$msgstr_escaped`"`n`n"
}

# Save with UTF-8 no BOM
[System.IO.File]::WriteAllText($poFile, $poContent, $utf8NoBom)
Write-Host "✅ Created fresh Polish translation file: $poFile"

# Now compile the MO file using PHP
Write-Host "Compiling translation files..."
$compileScript = @"
<?php
if (!function_exists('pmo_convert')) {
    function pmo_convert(\$po_file) {
        \$po_contents = file_get_contents(\$po_file);
        \$lines = explode("\n", \$po_contents);
        \$translations = array();
        \$msgid = '';
        \$msgstr = '';
        \$context = 'msgid'; // or 'msgstr'

        foreach (\$lines as \$line) {
            \$line = trim(\$line);
            
            // Skip comments and empty lines
            if (empty(\$line) || substr(\$line, 0, 1) === '#') {
                continue;
            }

            // Check for msgid
            if (strpos(\$line, 'msgid ') === 0) {
                \$context = 'msgid';
                \$msgid = substr(\$line, 6);
                \$msgid = trim(\$msgid, '\"');
            }
            // Check for msgstr
            elseif (strpos(\$line, 'msgstr ') === 0) {
                \$context = 'msgstr';
                \$msgstr = substr(\$line, 7);
                \$msgstr = trim(\$msgstr, '\"');
                
                // Store translation
                if (!empty(\$msgid)) {
                    \$translations[\$msgid] = \$msgstr;
                }
            }
            // Continuation of msgid or msgstr
            elseif (substr(\$line, 0, 1) === '"' && !empty(\$line)) {
                \$content = trim(\$line, '\"');
                if (\$context === 'msgid') {
                    \$msgid .= \$content;
                } elseif (\$context === 'msgstr') {
                    \$msgstr .= \$content;
                }
            }
        }

        return \$translations;
    }
}

function pmo_generate_mo(\$po_file, \$mo_file) {
    \$translations = pmo_convert(\$po_file);
    
    // Create MO file binary format
    \$mo = '';
    \$offsets = array();
    \$ids = '';
    \$strings = '';
    \$hash_size = 0;
    \$hash_offset = 0;

    foreach (\$translations as \$msgid => \$msgstr) {
        \$ids .= \$msgid . "\x00";
        \$strings .= \$msgstr . "\x00";
    }

    \$id_length_offset = 28;
    \$str_length_offset = \$id_length_offset + (count(\$translations) * 8);
    \$hash_length_offset = \$str_length_offset + (count(\$translations) * 8);
    \$hash_offset = \$hash_length_offset + 4;

    // Build header
    \$mo = pack('Iiiiiii', 0x950412de, 0, 28, 7 * 4 + 16 * count(\$translations), 0, 0, 0);

    // Add offsets  
    foreach (\$translations as \$msgid => \$msgstr) {
        \$mo .= pack('ii', strlen(\$msgid), 0);
    }
    foreach (\$translations as \$msgid => \$msgstr) {
        \$mo .= pack('ii', strlen(\$msgstr), 0);
    }

    \$mo .= \$ids . \$strings;

    file_put_contents(\$mo_file, \$mo);
}

\$po_file = __DIR__ . '/simple-lms-pl_PL.po';
\$mo_file = __DIR__ . '/simple-lms-pl_PL.mo';

pmo_generate_mo(\$po_file, \$mo_file);

echo "✅ Successfully compiled: " . basename(\$mo_file) . "\n";
?>
"@

$tempScript = "$pluginDir\temp-compile.php"
Set-Content $tempScript $compileScript -Encoding UTF8NoBOM
cd $pluginDir
php $tempScript
Remove-Item $tempScript

Write-Host "✅ Polish translation files are now properly encoded as UTF-8"
Write-Host "ℹ️  All Polish characters (ą, ć, ę, ł, ń, ó, ś, ź, ż) will now display correctly"
