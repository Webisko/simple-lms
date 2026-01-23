<?php
/**
 * Minimal PO -> MO compiler for Simple LMS.
 *
 * Usage:
 *   php languages/compile-mo.php languages/simple-lms-pl_PL.po languages/simple-lms-pl_PL.mo
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$poPath = $argv[1] ?? '';
$moPath = $argv[2] ?? '';

if ($poPath === '' || $moPath === '') {
    fwrite(STDERR, "Usage: php languages/compile-mo.php <input.po> <output.mo>\n");
    exit(1);
}

if (!is_file($poPath)) {
    fwrite(STDERR, "PO file not found: {$poPath}\n");
    exit(1);
}

/**
 * Unquote a PO string line content (supports escaped sequences).
 */
function po_unquote(string $quoted): string
{
    $quoted = trim($quoted);
    if ($quoted === '""') {
        return '';
    }
    if ($quoted !== '' && $quoted[0] === '"' && substr($quoted, -1) === '"') {
        $quoted = substr($quoted, 1, -1);
    }

    // Handle common escapes
    $quoted = str_replace([
        '\\n',
        '\\t',
        '\\r',
        '\\"',
        '\\\\',
    ], [
        "\n",
        "\t",
        "\r",
        '"',
        "\\",
    ], $quoted);

    return $quoted;
}

/**
 * Parse a PO file into an array of [key => value] where key includes context and plural forms.
 */
function parse_po(string $contents): array
{
    $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];

    $entries = [];

    $state = [
        'msgctxt' => null,
        'msgid' => null,
        'msgid_plural' => null,
        'msgstr' => [],
        'current' => null,
        'current_index' => null,
    ];

    $flush = function () use (&$entries, &$state): void {
        if ($state['msgid'] === null) {
            // Nothing to flush
            $state = [
                'msgctxt' => null,
                'msgid' => null,
                'msgid_plural' => null,
                'msgstr' => [],
                'current' => null,
                'current_index' => null,
            ];
            return;
        }

        $context = $state['msgctxt'];
        $msgid = (string) $state['msgid'];
        $msgidPlural = $state['msgid_plural'];

        $key = $msgid;
        if ($context !== null) {
            $key = $context . "\x04" . $msgid;
        }

        if ($msgidPlural !== null) {
            $key = $key . "\0" . $msgidPlural;
        }

        if ($msgidPlural !== null) {
            ksort($state['msgstr']);
            $value = implode("\0", array_values($state['msgstr']));
        } else {
            $value = (string) ($state['msgstr'][0] ?? '');
        }

        $entries[$key] = $value;

        $state = [
            'msgctxt' => null,
            'msgid' => null,
            'msgid_plural' => null,
            'msgstr' => [],
            'current' => null,
            'current_index' => null,
        ];
    };

    foreach ($lines as $line) {
        $trim = trim($line);

        // Blank line ends an entry
        if ($trim === '') {
            $flush();
            continue;
        }

        // Skip comments
        if ($trim[0] === '#') {
            continue;
        }

        if (str_starts_with($trim, 'msgctxt ')) {
            $state['msgctxt'] = po_unquote(substr($trim, 7));
            $state['current'] = 'msgctxt';
            $state['current_index'] = null;
            continue;
        }

        if (str_starts_with($trim, 'msgid_plural ')) {
            $state['msgid_plural'] = po_unquote(substr($trim, 13));
            $state['current'] = 'msgid_plural';
            $state['current_index'] = null;
            continue;
        }

        if (str_starts_with($trim, 'msgid ')) {
            $state['msgid'] = po_unquote(substr($trim, 5));
            $state['current'] = 'msgid';
            $state['current_index'] = null;
            continue;
        }

        if (preg_match('/^msgstr\[(\d+)\]\s+(.+)$/', $trim, $m)) {
            $idx = (int) $m[1];
            $state['msgstr'][$idx] = po_unquote($m[2]);
            $state['current'] = 'msgstr';
            $state['current_index'] = $idx;
            continue;
        }

        if (str_starts_with($trim, 'msgstr ')) {
            $state['msgstr'][0] = po_unquote(substr($trim, 6));
            $state['current'] = 'msgstr';
            $state['current_index'] = 0;
            continue;
        }

        // Continued quoted string lines
        if ($trim[0] === '"') {
            $append = po_unquote($trim);
            switch ($state['current']) {
                case 'msgctxt':
                    $state['msgctxt'] = (string) ($state['msgctxt'] ?? '') . $append;
                    break;
                case 'msgid':
                    $state['msgid'] = (string) ($state['msgid'] ?? '') . $append;
                    break;
                case 'msgid_plural':
                    $state['msgid_plural'] = (string) ($state['msgid_plural'] ?? '') . $append;
                    break;
                case 'msgstr':
                    $idx = $state['current_index'] ?? 0;
                    $state['msgstr'][$idx] = (string) ($state['msgstr'][$idx] ?? '') . $append;
                    break;
                default:
                    // ignore
                    break;
            }
        }
    }

    $flush();

    return $entries;
}

/**
 * Build MO binary from entries.
 */
function build_mo(array $entries): string
{
    // Sort by original key
    ksort($entries, SORT_STRING);

    $keys = array_keys($entries);
    $values = array_values($entries);
    $count = count($keys);

    // Header size is 7*4 bytes = 28
    $headerSize = 28;
    $origTableOffset = $headerSize;
    $transTableOffset = $origTableOffset + ($count * 8);
    $stringPoolOffset = $transTableOffset + ($count * 8);

    $origTable = '';
    $transTable = '';
    $origPool = '';
    $transPool = '';

    $origOffsets = [];
    $transOffsets = [];

    $offset = 0;
    foreach ($keys as $k) {
        $origOffsets[] = [$k, strlen($k), $offset];
        $origPool .= $k . "\0";
        $offset += strlen($k) + 1;
    }

    $offset = 0;
    foreach ($values as $v) {
        $transOffsets[] = [$v, strlen($v), $offset];
        $transPool .= $v . "\0";
        $offset += strlen($v) + 1;
    }

    $origPoolBase = $stringPoolOffset;
    $transPoolBase = $stringPoolOffset + strlen($origPool);

    foreach ($origOffsets as [$k, $len, $off]) {
        $origTable .= pack('V2', $len, $origPoolBase + $off);
    }

    foreach ($transOffsets as [$v, $len, $off]) {
        $transTable .= pack('V2', $len, $transPoolBase + $off);
    }

    // MO header
    $header = pack(
        'V7',
        0x950412de, // magic
        0,          // revision
        $count,
        $origTableOffset,
        $transTableOffset,
        0,          // hash table size
        0           // hash table offset
    );

    return $header . $origTable . $transTable . $origPool . $transPool;
}

$poContents = file_get_contents($poPath);
if ($poContents === false) {
    fwrite(STDERR, "Failed to read PO file: {$poPath}\n");
    exit(1);
}

$entries = parse_po($poContents);
$moBinary = build_mo($entries);

$result = file_put_contents($moPath, $moBinary);
if ($result === false) {
    fwrite(STDERR, "Failed to write MO file: {$moPath}\n");
    exit(1);
}

fwrite(STDOUT, "Wrote MO: {$moPath} (" . strlen($moBinary) . " bytes)\n");
