<?php
// jQuery UI autocomplete source
include_once '../includes/db.php';

$rows = db_fetch_all("SELECT id, name, company FROM customers WHERE name LIKE '%" . $_GET['term'] . "%' OR company LIKE '%" . $_GET['term'] . "%' LIMIT 10");

$out = '[';
foreach ($rows as $i => $r) {
    if ($i > 0) $out .= ',';
    $out .= '{"id":"' . $r['id'] . '","label":"' . $r['name'] . ' (' . $r['company'] . ')"}';
}
$out .= ']';
echo $out;
