<?php
// Pretty print Elementor JSON (which is double-encoded in DB: outer slashes)
$f = $argv[1] ?? null;
if (!$f) { fwrite(STDERR, "usage: php pretty_elementor.php <file>\n"); exit(1); }
$raw = trim(file_get_contents($f));
// The dump already unescaped \\/ to /, and mysql returned the JSON as stored.
// Try direct decode first.
$data = json_decode($raw, true);
if ($data === null) {
    // Try unslash first
    $data = json_decode(stripslashes($raw), true);
}
if ($data === null) {
    fwrite(STDERR, "json decode failed: ".json_last_error_msg()."\n");
    echo substr($raw, 0, 500)."\n";
    exit(1);
}
// Walk and print a summary tree
function walk($nodes, $depth = 0) {
    foreach ($nodes as $n) {
        $indent = str_repeat('  ', $depth);
        $type = $n['elType'] ?? '?';
        $widget = $n['widgetType'] ?? '';
        $id = $n['id'] ?? '?';
        $label = $type . ($widget ? "[$widget]" : '');
        // Extract a hint from settings
        $hint = '';
        $s = $n['settings'] ?? [];
        foreach (['title','heading_title','title_text','editor','text','html','shortcode'] as $k) {
            if (!empty($s[$k]) && is_string($s[$k])) {
                $hint = ' :: ' . substr(strip_tags($s[$k]), 0, 80);
                break;
            }
        }
        if (!empty($s['background_color'])) $hint .= " bg={$s['background_color']}";
        if (!empty($s['background_background'])) $hint .= " bgType={$s['background_background']}";
        echo "{$indent}[{$id}] {$label}{$hint}\n";
        if (!empty($n['elements'])) walk($n['elements'], $depth + 1);
    }
}
walk($data);
