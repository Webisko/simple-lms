<?php
/**
 * Simple PO to MO compiler
 * Run this file from command line: php compile-translations.php
 */

function compile_po_to_mo($po_file, $mo_file) {
    if (!file_exists($po_file)) {
        echo "Error: $po_file does not exist\n";
        return false;
    }

    $po_content = file_get_contents($po_file);
    $entries = [];
    $current_msgid = '';
    $current_msgstr = '';
    $in_msgid = false;
    $in_msgstr = false;

    $lines = explode("\n", $po_content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Start of msgid
        if (preg_match('/^msgid\s+"(.*)"\s*$/', $line, $matches)) {
            // Save previous entry if exists
            if ($current_msgid !== '' && $current_msgstr !== '') {
                $entries[$current_msgid] = $current_msgstr;
            }
            
            $current_msgid = stripcslashes($matches[1]);
            $current_msgstr = '';
            $in_msgid = true;
            $in_msgstr = false;
            continue;
        }
        
        // Start of msgstr
        if (preg_match('/^msgstr\s+"(.*)"\s*$/', $line, $matches)) {
            $current_msgstr = stripcslashes($matches[1]);
            $in_msgid = false;
            $in_msgstr = true;
            continue;
        }
        
        // Continuation line
        if (preg_match('/^"(.*)"\s*$/', $line, $matches)) {
            $str = stripcslashes($matches[1]);
            if ($in_msgid) {
                $current_msgid .= $str;
            } elseif ($in_msgstr) {
                $current_msgstr .= $str;
            }
        }
    }
    
    // Save last entry
    if ($current_msgid !== '' && $current_msgstr !== '') {
        $entries[$current_msgid] = $current_msgstr;
    }

    // Generate MO file
    $mo_content = generate_mo_content($entries);
    
    if (file_put_contents($mo_file, $mo_content) !== false) {
        echo "Successfully compiled: $mo_file\n";
        return true;
    }
    
    echo "Error: Could not write $mo_file\n";
    return false;
}

function generate_mo_content($entries) {
    // MO file format (simplified)
    // https://www.gnu.org/software/gettext/manual/html_node/MO-Files.html
    
    $keys = array_keys($entries);
    $values = array_values($entries);
    $count = count($entries);
    
    // Build string tables
    $key_offsets = [];
    $value_offsets = [];
    $key_table = '';
    $value_table = '';
    
    foreach ($keys as $key) {
        $key_offsets[] = strlen($key_table);
        $key_table .= $key . "\0";
    }
    
    foreach ($values as $value) {
        $value_offsets[] = strlen($value_table);
        $value_table .= $value . "\0";
    }
    
    // Calculate offsets
    $keys_index = 28;
    $values_index = $keys_index + ($count * 8);
    $key_table_offset = $values_index + ($count * 8);
    $value_table_offset = $key_table_offset + strlen($key_table);
    
    // Build header
    $mo = '';
    $mo .= pack('L', 0x950412de);  // Magic number
    $mo .= pack('L', 0);            // Version
    $mo .= pack('L', $count);       // Number of entries
    $mo .= pack('L', $keys_index);  // Offset of key index
    $mo .= pack('L', $values_index);// Offset of value index
    $mo .= pack('L', 0);            // Hash table size
    $mo .= pack('L', 0);            // Hash table offset
    
    // Build key index
    for ($i = 0; $i < $count; $i++) {
        $mo .= pack('L', strlen($keys[$i]));
        $mo .= pack('L', $key_table_offset + $key_offsets[$i]);
    }
    
    // Build value index
    for ($i = 0; $i < $count; $i++) {
        $mo .= pack('L', strlen($values[$i]));
        $mo .= pack('L', $value_table_offset + $value_offsets[$i]);
    }
    
    // Append string tables
    $mo .= $key_table;
    $mo .= $value_table;
    
    return $mo;
}

// Compile all PO files
$dir = __DIR__;

echo "Compiling translation files...\n\n";

$files = [
    'simple-lms-en_US.po' => 'simple-lms-en_US.mo',
    'simple-lms-pl_PL.po' => 'simple-lms-pl_PL.mo',
    'simple-lms-de_DE.po' => 'simple-lms-de_DE.mo',
];

foreach ($files as $po => $mo) {
    $po_path = $dir . '/' . $po;
    $mo_path = $dir . '/' . $mo;
    
    if (file_exists($po_path)) {
        compile_po_to_mo($po_path, $mo_path);
    } else {
        echo "Skipping $po (file not found)\n";
    }
}

echo "\nDone!\n";
