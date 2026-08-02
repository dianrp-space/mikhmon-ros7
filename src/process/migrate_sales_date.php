<?php
/*
 * One-time migration: fix source/owner/tdate stored as RouterOS date format
 * (aug/01/2026) to the ISO format (2026-08-01) expected by the report filters.
 */

error_reporting(E_ALL);

require_once dirname(__DIR__) . '/lib/db.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$pdo = mikhmon_pdo();
$rows = $pdo->query('SELECT id, name FROM sales')->fetchAll(PDO::FETCH_ASSOC);

$fixed = 0;
$stmt = $pdo->prepare('UPDATE sales SET source = :source, owner = :owner, tdate = :tdate WHERE id = :id');

foreach ($rows as $row) {
    $parts = explode('-|-', $row['name']);
    $raw = isset($parts[0]) ? $parts[0] : '';
    $date = mikhmon_normalize_date($raw);
    $owner = mikhmon_sale_owner($date);

    if ($date === $raw && $owner === '') {
        continue;
    }

    $stmt->execute(array(
        ':source' => $date,
        ':owner'  => $owner,
        ':tdate'  => $date,
        ':id'     => $row['id'],
    ));
    $fixed++;
    echo "id={$row['id']} name=[{$raw}] -> date=[{$date}] owner=[{$owner}]" . PHP_EOL;
}

echo "Fixed $fixed row(s)" . PHP_EOL;
exit(0);
