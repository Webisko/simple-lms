<?php
/**
 * Restore translations from .original files
 */

echo "========================================\n";
echo "  Restore Original Translations\n";
echo "========================================\n\n";

// Parse PO file - simple version
function parseSimple($file) {
    if (!file_exists($file)) {
        return ['header' => '', 'trans' => []];
    }
    
    $content = file_get_contents($file);
    
    // Get header
    preg_match('/(^.*?msgid ""\nmsgstr ".*?")\n/s', $content, $hm);
    $header = $hm[1] ?? '';
    
    // Get all msgid/msgstr pairs (allowing whitespace between them)
    preg_match_all('/msgid "([^"]*)"\s+msgstr "([^"]*)"/', $content, $m, PREG_SET_ORDER);
    
    $trans = [];
    foreach ($m as $match) {
        if (!empty($match[1])) {
            $trans[$match[1]] = $match[2];
        }
    }
    
    return ['header' => $header, 'trans' => $trans];
}

// Get POT entries
$pot = parseSimple(__DIR__ . '/languages/simple-lms.pot');
echo "📖 POT: " . count($pot['trans']) . " entries\n\n";

// Process each locale
foreach (['pl_PL', 'en_US', 'de_DE'] as $locale) {
    echo "🔄 $locale\n";
    
    $origFile = __DIR__ . "/languages/simple-lms-{$locale}.po.original.utf8";
    $newFile = __DIR__ . "/languages/simple-lms-{$locale}.po";
    
    if (!file_exists($origFile)) {
        echo "   ⚠️  Original not found\n\n";
        continue;
    }
    
    // Parse original
    $orig = parseSimple($origFile);
    echo "   📚 Original: " . count($orig['trans']) . " entries\n";
    
    // Count translated
    $origTranslated = 0;
    foreach ($orig['trans'] as $msgstr) {
        if (!empty($msgstr)) $origTranslated++;
    }
    echo "   ✅ Translated: $origTranslated\n";
    
    // Build new file
    $out = $orig['header'] . "\n\n";
    
    $matched = 0;
    foreach ($pot['trans'] as $msgid => $dummy) {
        $msgstr = isset($orig['trans'][$msgid]) ? $orig['trans'][$msgid] : '';
        
        if (!empty($msgstr)) $matched++;
        
        $out .= "msgid \"$msgid\"\n";
        $out .= "msgstr \"$msgstr\"\n\n";
    }
    
    file_put_contents($newFile, $out);
    
    $pct = round(($matched / count($pot['trans'])) * 100, 1);
    echo "   💾 Restored: $matched/$pct% ($pct%)\n\n";
}

echo "✅ Done! Compile with: php languages/compile-translations.php\n\n";
