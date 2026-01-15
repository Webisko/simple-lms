<?php
/**
 * Update .po files from new .pot template
 * Merges new strings from .pot into existing .po files
 */

class PoUpdater {
    private $potFile;
    private $potStrings = [];
    
    public function __construct($potFile) {
        $this->potFile = $potFile;
        $this->parsePot();
    }
    
    private function parsePot() {
        $content = file_get_contents($this->potFile);
        $entries = preg_split('/\n\n+/', $content);
        
        foreach ($entries as $entry) {
            if (empty(trim($entry)) || strpos($entry, 'msgid ""') === 0) {
                continue;
            }
            
            // Extract msgid
            if (preg_match('/msgid\s+"(.+?)"/s', $entry, $matches)) {
                $msgid = $matches[1];
                
                // Extract context if exists
                $context = '';
                if (preg_match('/msgctxt\s+"(.+?)"/s', $entry, $ctxMatches)) {
                    $context = $ctxMatches[1];
                }
                
                // Extract references (file locations)
                $references = [];
                if (preg_match_all('/#:\s+(.+)$/m', $entry, $refMatches)) {
                    $references = $refMatches[1];
                }
                
                $key = $context ? "$context|$msgid" : $msgid;
                $this->potStrings[$key] = [
                    'msgid' => $msgid,
                    'context' => $context,
                    'references' => $references,
                    'entry' => $entry
                ];
            }
        }
        
        echo "📖 Loaded " . count($this->potStrings) . " strings from .pot\n";
    }
    
    public function updatePoFile($poFile) {
        echo "\n🔄 Updating: " . basename($poFile) . "\n";
        
        if (!file_exists($poFile)) {
            echo "   ⚠️  File not found, skipping\n";
            return;
        }
        
        $content = file_get_contents($poFile);
        $header = $this->extractHeader($content);
        $existingTranslations = $this->parsePoFile($content);
        
        echo "   📚 Found " . count($existingTranslations) . " existing translations\n";
        
        // Build new .po content
        $newContent = $header . "\n";
        
        $matched = 0;
        $new = 0;
        $obsolete = 0;
        
        // Add all strings from .pot
        foreach ($this->potStrings as $key => $potEntry) {
            $msgid = $potEntry['msgid'];
            $context = $potEntry['context'];
            
            // Check if translation exists
            if (isset($existingTranslations[$key]) && !empty($existingTranslations[$key]['msgstr'])) {
                $msgstr = $existingTranslations[$key]['msgstr'];
                $matched++;
            } else {
                $msgstr = '';
                $new++;
            }
            
            // Build entry
            $entry = '';
            
            // Add references
            if (!empty($potEntry['references'])) {
                foreach ($potEntry['references'] as $ref) {
                    $entry .= "#: $ref\n";
                }
            }
            
            // Add context
            if ($context) {
                $entry .= "msgctxt \"$context\"\n";
            }
            
            // Add msgid and msgstr
            $entry .= "msgid \"$msgid\"\n";
            $entry .= "msgstr \"$msgstr\"\n";
            
            $newContent .= "\n" . $entry;
        }
        
        // Check for obsolete translations (in .po but not in .pot)
        foreach ($existingTranslations as $key => $trans) {
            if (!isset($this->potStrings[$key])) {
                $obsolete++;
            }
        }
        
        // Save updated .po file
        file_put_contents($poFile, $newContent);
        
        echo "   ✅ Updated successfully!\n";
        echo "      - Matched existing: $matched\n";
        echo "      - New (untranslated): $new\n";
        echo "      - Obsolete (removed): $obsolete\n";
        echo "      - Total strings: " . count($this->potStrings) . "\n";
        
        return [
            'matched' => $matched,
            'new' => $new,
            'obsolete' => $obsolete,
            'total' => count($this->potStrings)
        ];
    }
    
    private function extractHeader($content) {
        if (preg_match('/^(.*?msgid ""\nmsgstr ".*?")\n\n/s', $content, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    private function parsePoFile($content) {
        $translations = [];
        $entries = preg_split('/\n\n+/', $content);
        
        foreach ($entries as $entry) {
            if (empty(trim($entry)) || strpos($entry, 'msgid ""') === 0) {
                continue;
            }
            
            // Extract msgid
            if (!preg_match('/msgid\s+"(.+?)"/s', $entry, $matches)) {
                continue;
            }
            $msgid = $matches[1];
            
            // Extract msgstr
            $msgstr = '';
            if (preg_match('/msgstr\s+"(.+?)"/s', $entry, $strMatches)) {
                $msgstr = $strMatches[1];
            }
            
            // Extract context
            $context = '';
            if (preg_match('/msgctxt\s+"(.+?)"/s', $entry, $ctxMatches)) {
                $context = $ctxMatches[1];
            }
            
            $key = $context ? "$context|$msgid" : $msgid;
            $translations[$key] = [
                'msgid' => $msgid,
                'msgstr' => $msgstr,
                'context' => $context
            ];
        }
        
        return $translations;
    }
}

// Main execution
echo "========================================\n";
echo "  Simple LMS - PO Files Update Tool\n";
echo "========================================\n\n";

$potFile = __DIR__ . '/languages/simple-lms.pot';

if (!file_exists($potFile)) {
    die("❌ Error: .pot file not found at $potFile\n");
}

$updater = new PoUpdater($potFile);

// Update all .po files
$poFiles = [
    __DIR__ . '/languages/simple-lms-pl_PL.po',
    __DIR__ . '/languages/simple-lms-de_DE.po',
    __DIR__ . '/languages/simple-lms-en_US.po',
];

$totalStats = ['matched' => 0, 'new' => 0, 'obsolete' => 0];

foreach ($poFiles as $poFile) {
    $stats = $updater->updatePoFile($poFile);
    if ($stats) {
        $totalStats['matched'] += $stats['matched'];
        $totalStats['new'] += $stats['new'];
        $totalStats['obsolete'] += $stats['obsolete'];
    }
}

echo "\n========================================\n";
echo "  Summary\n";
echo "========================================\n";
echo "Files updated: " . count($poFiles) . "\n";
echo "Total matched translations: {$totalStats['matched']}\n";
echo "Total new strings: {$totalStats['new']}\n";
echo "Total obsolete strings: {$totalStats['obsolete']}\n";
echo "\n✅ All .po files updated successfully!\n";
echo "\nNext step: Compile .mo files with:\n";
echo "  php languages/compile-translations.php\n\n";
