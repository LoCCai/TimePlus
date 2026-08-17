<?php

require_once dirname(__DIR__, 2) . '/functions.php';

function expect_same($expected, $actual, $message)
{
  if ($expected !== $actual) {
    fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
    exit(1);
  }
}

expect_same(
  ['https://example.com/a.jpg', '/usr/uploads/b.webp', '//cdn.example.com/c.png'],
  timeplus_normalize_images("\r\n https://example.com/a.jpg \r\njavascript:alert(1)\n/usr/uploads/b.webp\n//cdn.example.com/c.png\n"),
  'Image normalization must trim, reindex, and reject dangerous schemes.'
);
expect_same([], timeplus_normalize_images("\n\r\ndata:text/html,test"), 'Empty and data URLs must fail closed.');
expect_same(null, timeplus_safe_url('javascript:alert(1)'), 'Script URLs must be rejected.');
expect_same(null, timeplus_safe_url('JaVaScRiPt:alert(1)'), 'URL scheme checks must be case insensitive.');
expect_same(null, timeplus_safe_url('ftp://example.com/a.jpg'), 'Non-HTTP network schemes must be rejected.');
expect_same('/images/a.jpg', timeplus_safe_url('/images/a.jpg'), 'Root-relative URLs must remain compatible.');
expect_same('//cdn.example.com/a.jpg', timeplus_safe_url('//cdn.example.com/a.jpg'), 'Protocol-relative image URLs must remain compatible.');
expect_same('https://example.com/a.jpg?width=800', timeplus_append_image_rule('https://example.com/a.jpg', '?width=800'), 'Trusted image processing suffixes must remain compatible.');

$json = timeplus_json_encode(['value' => "</script><script>alert('x')</script>"]);
if (str_contains($json, '</script>') || str_contains($json, '<script>')) {
  fwrite(STDERR, "JSON script payload was not hex escaped.\n");
  exit(1);
}

$tree = timeplus_build_category_tree([
  ['id' => 1, 'name' => 'Root', 'permalink' => '/root', 'parent' => 0],
  ['id' => 2, 'name' => 'Child', 'permalink' => '/child', 'parent' => 1],
  ['id' => 3, 'name' => 'Orphan', 'permalink' => '/orphan', 'parent' => 99],
  ['id' => 4, 'name' => 'Cycle A', 'permalink' => '/a', 'parent' => 5],
  ['id' => 5, 'name' => 'Cycle B', 'permalink' => '/b', 'parent' => 4]
]);
expect_same(3, count($tree), 'Root, orphan, and cyclic components must all remain reachable.');
expect_same(2, $tree[0]['children'][0]['id'], 'The normal child must remain nested under its parent.');

expect_same('2.20', timeplus_parse_release_manifest('{"tag_name":"2.20"}'), 'A valid release version must parse.');
expect_same(null, timeplus_parse_release_manifest('{"tag_name":"2.20-beta<script>"}'), 'Invalid release versions must be rejected.');

$cacheFile = tempnam(sys_get_temp_dir(), 'timeplus-test-');
file_put_contents($cacheFile, json_encode(['version' => '2.20', 'checked_at' => 123]));
expect_same(['version' => '2.20', 'checked_at' => 123], timeplus_read_release_cache($cacheFile), 'Valid cache data must round-trip.');
unlink($cacheFile);

echo "PHP helper tests passed.\n";
