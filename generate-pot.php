<?php
/**
 * Generate .pot template file from PHP files
 * Simple scanner for __(), _e(), esc_html__(), esc_attr__(), etc.
 */

$plugin_dir = __DIR__;
$pot_file = $plugin_dir . '/languages/simple-lms.pot';
$text_domain = 'simple-lms';

// Collect all strings
$strings = [];

function scan_file($file, $text_domain) {
    global $strings;
    $content = file_get_contents($file);
    
    // Patterns for i18n functions
    $patterns = [
        '/(?:__|_e|esc_html__|esc_attr__|esc_html_e|esc_attr_e)\s*\(\s*[\'"](.+?)[\'"]\s*,\s*[\'"]\s*' . preg_quote($text_domain, '/') . '\s*[\'"]\s*\)/s',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $string) {
                // Clean up string
                $string = trim($string);
                $string = str_replace(['\\"', "\\'"], ['"', "'"], $string);
                
                if (!empty($string) && !isset($strings[$string])) {
                    $strings[$string] = [
                        'msgid' => $string,
                        'locations' => []
                    ];
                }
                
                $rel_file = str_replace($GLOBALS['plugin_dir'] . '/', '', $file);
                $strings[$string]['locations'][] = $rel_file;
            }
        }
    }
}

// Scan all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($plugin_dir)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        // Skip vendor and tests
        $path = $file->getPathname();
        if (strpos($path, '/vendor/') !== false || strpos($path, '/tests/') !== false) {
            continue;
        }
        scan_file($path, $text_domain);
    }
}

// Sort strings alphabetically
ksort($strings);

// Generate POT content
$pot_content = '# Simple LMS Translation Template
# Copyright (C) ' . date('Y') . ' Simple LMS
# This file is distributed under the same license as the Simple LMS package.
msgid ""
msgstr ""
"Project-Id-Version: Simple LMS 1.4.0\n"
"Report-Msgid-Bugs-To: https://github.com/Webisko/simple-lms\n"
"POT-Creation-Date: ' . date('Y-m-d H:i') . '+0000\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language-Team: LANGUAGE <LL@li.org>\n"

';

foreach ($strings as $data) {
    // Add file references
    foreach ($data['locations'] as $location) {
        $pot_content .= '#: ' . $location . "\n";
    }
    
    // Add msgid
    $msgid = addcslashes($data['msgid'], '"\\');
    $pot_content .= 'msgid "' . $msgid . '"' . "\n";
    $pot_content .= 'msgstr ""' . "\n\n";
}

// Write POT file
file_put_contents($pot_file, $pot_content);

echo "✅ Generated: $pot_file\n";
echo "📊 Total strings: " . count($strings) . "\n";
echo "\nFirst 10 strings:\n";
$i = 0;
foreach ($strings as $string => $data) {
    if ($i++ >= 10) break;
    echo "  - $string\n";
}
