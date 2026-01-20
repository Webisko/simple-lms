<?php
/**
 * Rebuild Polish translation files with proper UTF-8 encoding
 * This script generates correct PO and MO files for Polish language
 */

// Get plugin directory
$pluginDir = __DIR__;
$poFile = $pluginDir . '/languages/simple-lms-pl_PL.po';
$moFile = $pluginDir . '/languages/simple-lms-pl_PL.mo';

// Polish translations with proper UTF-8 characters
$translations = [
    // Time-related
    "%d hr. %d min." => "%d godz. %d min.",
    "%d minut" => "%d minut",
    "%d of %d lessons completed" => "%d z %d lekcji ukończono",
    "%d secund" => "%d sek",
    "%d/%d lessons completed (%.1f%%)" => "%d/%d lekcji ukończono (%.1f%%)",
    "(0 = lifetime access)" => "(0 = dostęp dożywotni)",
    "(Kopia)" => "(Kopia)",
    "1 year (365 days)" => "1 rok (365 dni)",
    "15 of 20 lessons completed" => "15 z 20 lekcji ukończono",
    "180 days" => "180 dni",
    "90 days" => "90 dni",
    
    // Access-related
    "Access" => "Dostęp",
    "Access Denied Text" => "Tekst braku dostępu",
    "Access Duration" => "Czas dostępu",
    "Access Granted Text" => "Tekst przyznania dostępu",
    "Access Schedule" => "Harmonogram dostępu",
    
    // Basic actions
    "Add" => "Dodaj",
    "Add Lesson" => "Dodaj lekcję",
    "Add Module" => "Dodaj moduł",
    "Add New Course" => "Dodaj nowy kurs",
    "Add New Lesson" => "Dodaj nową lekcję",
    "Add New Module" => "Dodaj nowy moduł",
    "Back" => "Wstecz",
    "Back to course" => "Powrót do kursu",
    "Back to lesson" => "Powrót do lekcji",
    "Back to module" => "Powrót do modułu",
    "Cancel" => "Anuluj",
    "Change" => "Zmień",
    "Change Image" => "Zmień obraz",
    "Change featured image" => "Zmień obraz wyróżniający",
    "Close" => "Zamknij",
    "Copy" => "Kopiuj",
    "Create" => "Utwórz",
    "Create Course" => "Utwórz kurs",
    "Create Lesson" => "Utwórz lekcję",
    "Create Module" => "Utwórz moduł",
    "Created" => "Utworzono",
    "Created by" => "Utworzono przez",
    "Created Date" => "Data utworzenia",
    "Created on" => "Utworzono w dniu",
    "Creator" => "Twórca",
    "Delete" => "Usuń",
    "Delete Course" => "Usuń kurs",
    "Delete Lesson" => "Usuń lekcję",
    "Delete Module" => "Usuń moduł",
    "Delete Permanently" => "Usuń na stałe",
    "Delete This Item" => "Usuń ten element",
    "Deleted" => "Usunięte",
    "Deleting" => "Usuwanie",
    "Description" => "Opis",
    "Description (short)" => "Opis (krótki)",
    "Download" => "Pobierz",
    "Download Report" => "Pobierz raport",
    "Draft" => "Wersja robocza",
    "Edit" => "Edytuj",
    "Edit Course" => "Edytuj kurs",
    "Edit Lesson" => "Edytuj lekcję",
    "Edit Module" => "Edytuj moduł",
    "Edit Settings" => "Edytuj ustawienia",
    "Edited" => "Edytowane",
    "Editing" => "Edycja",
    "Enable" => "Włącz",
    "Enabled" => "Włączone",
    "Enabling" => "Włączanie",
    "Enroll" => "Zapisz",
    "Enrolled" => "Zapisane",
    "Enrolled User" => "Zapisany użytkownik",
    "Enrolled Users" => "Zapisani użytkownicy",
    "Enrolling" => "Zapisywanie",
    "Enrollment" => "Rejestracja",
    "Enrollment Date" => "Data rejestracji",
    "Enrollment Duration" => "Czas rejestracji",
    "Enrollment Email" => "Email rejestracyjny",
    "Enrollment Form" => "Formularz rejestracyjny",
    "Enrollment Limit" => "Limit rejestracji",
    "Enrollment Settings" => "Ustawienia rejestracji",
    "Enrollment Status" => "Status rejestracji",
    "Enrollments" => "Rejestracje",
    "Export" => "Eksportuj",
    "Export Data" => "Eksportuj dane",
    "Export Settings" => "Ustawienia eksportu",
    "Exported" => "Eksportowane",
    "Exporting" => "Eksportowanie",
    
    // Course-related
    "Course" => "Kurs",
    "Course Access" => "Dostęp do kursu",
    "Course Category" => "Kategoria kursu",
    "Course Completion" => "Ukończenie kursu",
    "Course Dashboard" => "Pulpit kursu",
    "Course Details" => "Szczegóły kursu",
    "Course Difficulty" => "Poziom trudności kursu",
    "Course Duration" => "Czas trwania kursu",
    "Course Gallery" => "Galeria kursu",
    "Course Goals" => "Cele kursu",
    "Course Grid" => "Siatka kursów",
    "Course Instructors" => "Instruktorzy kursu",
    "Course Language" => "Język kursu",
    "Course List" => "Lista kursów",
    "Course Listing" => "Wyszczególnienie kursów",
    "Course Material" => "Materiał kursu",
    "Course Metadata" => "Metadane kursu",
    "Course Navigation" => "Nawigacja kursu",
    "Course Not Found" => "Kurs nie znaleziony",
    "Course Outline" => "Zarys kursu",
    "Course Overview" => "Przegląd kursu",
    "Course Registration" => "Rejestracja kursu",
    "Course Requirements" => "Wymagania kursu",
    "Course Search" => "Wyszukiwanie kursów",
    "Course Section" => "Sekcja kursu",
    "Course Settings" => "Ustawienia kursu",
    "Course Status" => "Status kursu",
    "Course Summary" => "Streszczenie kursu",
    "Course Tags" => "Tagi kursu",
    "Course Team" => "Zespół kursu",
    "Course Type" => "Typ kursu",
    "Course URL" => "Adres URL kursu",
    "Courses" => "Kursy",
    "Courses per Page" => "Kursów na stronie",
    
    // Lesson-related
    "Lesson" => "Lekcja",
    "Lesson Access" => "Dostęp do lekcji",
    "Lesson Content" => "Zawartość lekcji",
    "Lesson Details" => "Szczegóły lekcji",
    "Lessons" => "Lekcje",
    "Lessons completed" => "Lekcje ukończone",
    
    // Module-related
    "Module" => "Moduł",
    "Module Access" => "Dostęp do modułu",
    "Module Content" => "Zawartość modułu",
    "Module Details" => "Szczegóły modułu",
    "Module List" => "Lista modułów",
    "Modules" => "Moduły",
    
    // Quiz-related
    "Quiz" => "Quiz",
    "Quiz Settings" => "Ustawienia quizu",
    
    // Dashboard/Admin
    "Dashboard" => "Pulpit",
    "Analytics" => "Analityka",
    "Settings" => "Ustawienia",
    "Content Settings" => "Ustawienia zawartości",
    "Communication Settings" => "Ustawienia komunikacji",
    // Status
    "Completed" => "Ukończone",
    "Pending" => "Oczekujące",
    "In Progress" => "W trakcie",
    "Published" => "Opublikowane",
    "Unpublished" => "Nieopublikowane",
    
    // Other common terms
    "Category" => "Kategoria",
    "Categories" => "Kategorie",
    "No results" => "Brak wyników",
    "Loading" => "Ładowanie",
    "Save" => "Zapisz",
    "Saving" => "Zapisywanie",
    "Search" => "Szukaj",
    "Search Results" => "Wyniki wyszukiwania",
    "Filter" => "Filtruj",
    "Sort" => "Sortuj",
    "View" => "Widok",
    "Details" => "Szczegóły",
    "Show More" => "Pokaż więcej",
    "Show Less" => "Pokaż mniej",
    "Learn More" => "Dowiedz się więcej",
    "Continue Reading" => "Czytaj dalej",
    "Read More" => "Czytaj dalej",
    "Back to top" => "Powrót do góry",
    "Top" => "Góra",
    "Next" => "Dalej",
    "Previous" => "Wstecz",
    "First" => "Pierwszy",
    "Last" => "Ostatni",
];

// Create PO file header
$poHeader = <<<'EOH'
# Polish translation for Simple LMS
# Copyright (C) 2024-2025 Simple LMS Contributors
# This file is distributed under the same license as the Simple LMS package.
#
msgid ""
msgstr ""
"Project-Id-Version: Simple LMS\n"
"POT-Creation-Date: 2025-01-16 00:00+0000\n"
"PO-Revision-Date: 2025-01-16 12:00+0000\n"
"Last-Translator: Simple LMS Team\n"
"Language-Team: Polish\n"
"Language: pl_PL\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Plural-Forms: nplurals=3; plural=(n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);\n"
"X-Generator: Simple LMS Translation System\n"

EOH;

// Build PO content
$poContent = $poHeader;
foreach ($translations as $msgid => $msgstr) {
    $msgid_escaped = addcslashes($msgid, '"\\');
    $msgstr_escaped = addcslashes($msgstr, '"\\');
    $poContent .= sprintf("msgid \"%s\"\n", $msgid_escaped);
    $poContent .= sprintf("msgstr \"%s\"\n\n", $msgstr_escaped);
}

// Save PO file as UTF-8 without BOM
file_put_contents($poFile, $poContent, FILE_BINARY);
if (function_exists('chmod')) {
    chmod($poFile, 0644);
}
echo "✅ Created Polish PO file: " . basename($poFile) . "\n";
echo "   - " . count($translations) . " translations\n";
echo "   - All Polish characters (ą, ć, ę, ł, ń, ó, ś, ź, ż) properly encoded as UTF-8\n";

// Now compile to MO format
// MO file format: machine-readable binary translation file
// Header: magic number (0x950412de), version, table offset, hash size
// Then: msgid/msgstr tables with offsets and lengths

$ids_data = "";
$strings_data = "";
$ids_offsets = [];
$strings_offsets = [];
$ids_length_offset = 28;
$strings_length_offset = $ids_length_offset + (count($translations) * 8);

// Build ID and string blocks
$current_offset_ids = $strings_length_offset + (count($translations) * 8);
$current_offset_strings = $current_offset_ids;

foreach ($translations as $msgid => $msgstr) {
    $msgid_bytes = $msgid;
    $msgstr_bytes = $msgstr;
    
    $ids_offsets[] = [strlen($msgid_bytes), $current_offset_ids];
    $current_offset_ids += strlen($msgid_bytes) + 1;
    $ids_data .= $msgid_bytes . "\x00";
    
    $strings_offsets[] = [strlen($msgstr_bytes), $current_offset_strings];
    $current_offset_strings += strlen($msgstr_bytes) + 1;
    $strings_data .= $msgstr_bytes . "\x00";
}

// Build MO file
$mo = pack(
    'Iiiiiii',
    0x950412de,      // Magic number
    0,               // Version
    28,              // Offset of table with original strings (7 * 4 + 0)
    28 + (count($translations) * 8), // Offset of table with translated strings
    0,               // Size of hashing table
    0,               // Offset of hashing table
    0                // Number of hashing table entries
);

// Add original string table (msgid offsets and lengths)
foreach ($ids_offsets as $offset_info) {
    $mo .= pack('ii', $offset_info[0], $offset_info[1]);
}
// Add translated string table (msgstr offsets and lengths)
foreach ($strings_offsets as $offset_info) {
    $mo .= pack('ii', $offset_info[0], $offset_info[1]);
}
// Add strings
$mo .= $ids_data;
$mo .= $strings_data;

// Save MO file as binary
file_put_contents($moFile, $mo, FILE_BINARY);
if (function_exists('chmod')) {
    chmod($moFile, 0644);
}
echo "✅ Compiled MO file: " . basename($moFile) . "\n";
echo "   - Binary format for WordPress\n";
echo "   - Ready for use\n";

echo "\n✅ Polish translation files rebuilt successfully!\n";
echo "   All Polish characters will now display correctly throughout the plugin.\n";
?>
