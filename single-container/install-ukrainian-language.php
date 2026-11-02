<?php
/**
 * Registers the uk-ua language (installed as extension/ukrainian/ by the Dockerfile) in the
 * OpenCart database — i.e. does programmatically what clicking "Install" on
 * Extensions > Installer + Extensions > Languages > Ukrainian would do in the admin UI.
 * Values mirror admin/controller/language/ukrainian.php from the OpenCartBot Ukrainian pack.
 *
 * Usage: php install-ukrainian-language.php <db_host> <db_user> <db_pass> <db_name> <db_prefix>
 */

[$script, $host, $user, $pass, $name, $prefix] = $argv + [null, null, null, null, null, 'oc_'];

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "[lang] DB connection failed: {$mysqli->connect_error}\n");
    exit(1);
}

$existing = $mysqli->query("SELECT language_id FROM {$prefix}language WHERE code = 'uk-ua'");
if ($existing && $existing->num_rows > 0) {
    echo "[lang] uk-ua already registered, skipping.\n";
    exit(0);
}

$mysqli->query("INSERT INTO {$prefix}extension (extension, type, code) VALUES ('ukrainian', 'language', 'ukrainian')");

$name_uk = $mysqli->real_escape_string('Українська');
$locale  = $mysqli->real_escape_string('uk,ua,uk_ua,uk-ua,uk_UA,uk_UA.UTF-8,ukrainian');

$mysqli->query("
    INSERT INTO {$prefix}language (name, code, locale, extension, sort_order, status)
    VALUES ('{$name_uk}', 'uk-ua', '{$locale}', 'ukrainian', 2, 1)
");
$uk_id = $mysqli->insert_id;

$result = $mysqli->query("SELECT language_id FROM {$prefix}language WHERE code = 'en-gb' LIMIT 1");
$en_id = $result ? ($result->fetch_assoc()['language_id'] ?? 1) : 1;

// Duplicate the default-language description rows into uk-ua so admin forms (country, zone,
// order/stock status, weight/length class, customer group, CMS pages, attributes, ...) show
// pre-filled Ukrainian-tab fields instead of empty ones.
$description_tables = [
    'country_description'        => ['country_id', 'name'],
    'zone_description'           => ['zone_id', 'name'],
    'order_status'                => ['order_status_id', 'name'],
    'stock_status'                => ['stock_status_id', 'name'],
    'weight_class_description'   => ['weight_class_id', 'title', 'unit'],
    'length_class_description'   => ['length_class_id', 'title', 'unit'],
    'customer_group_description' => ['customer_group_id', 'name', 'description'],
    'information_description'    => ['information_id', 'title', 'description', 'meta_title', 'meta_description', 'meta_keyword'],
    'attribute_group_description'=> ['attribute_group_id', 'name'],
    'attribute_description'      => ['attribute_id', 'name'],
];

foreach ($description_tables as $table => $columns) {
    $column_list = implode(', ', $columns);
    $mysqli->query("
        INSERT INTO {$prefix}{$table} ({$column_list}, language_id)
        SELECT {$column_list}, {$uk_id} FROM {$prefix}{$table} WHERE language_id = {$en_id}
    ");
}

// SEO keyword so /uk-ua/ language switching works (mirrors the loop in the official
// addLanguage() admin model).
$mysqli->query("
    INSERT INTO {$prefix}seo_url (store_id, language_id, `key`, value, keyword, sort_order)
    SELECT 0, language_id, 'language', 'uk-ua', 'uk-ua', -2 FROM {$prefix}language
");
$mysqli->query("
    INSERT INTO {$prefix}seo_url (store_id, language_id, `key`, value, keyword, sort_order)
    SELECT s.store_id, l.language_id, 'language', 'uk-ua', 'uk-ua', -2
    FROM {$prefix}store s CROSS JOIN {$prefix}language l
");

// Patch the config_description setting (per-language store meta tags) with an English
// fallback for uk-ua, otherwise System > Settings > Store fails validation on save for the
// new language tab (a known issue with this pack — see opencartbot.com reviews).
$result = $mysqli->query("SELECT value FROM {$prefix}setting WHERE code = 'config' AND `key` = 'config_description' AND store_id = 0 LIMIT 1");
if ($result && ($row = $result->fetch_assoc())) {
    $data = json_decode($row['value'], true);
    if (is_array($data) && !isset($data[(string) $uk_id]) && isset($data['1'])) {
        $data[(string) $uk_id] = $data['1'];
        $new_value = $mysqli->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));
        $mysqli->query("UPDATE {$prefix}setting SET value = '{$new_value}' WHERE code = 'config' AND `key` = 'config_description' AND store_id = 0");
    }
}

echo "[lang] Ukrainian (uk-ua) registered, language_id={$uk_id}.\n";
