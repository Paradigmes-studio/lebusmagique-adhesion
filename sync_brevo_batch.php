<?php
/**
 * Sync active members (date_fin >= today) to Brevo via batch import.
 *
 * CLI usage:
 *   docker compose exec app php /var/www/html/sync_brevo_batch.php --dry-run
 *   docker compose exec app php /var/www/html/sync_brevo_batch.php
 *
 * Web usage (token required, configure $conf['brevoSyncToken']):
 *   https://.../sync_brevo_batch.php?token=XXX&dry-run=1
 *   https://.../sync_brevo_batch.php?token=XXX
 *
 * Flags / query params:
 *   dry-run     Show what would be sent, no API call.
 *   limit=N     Only process the first N rows (debug).
 *   batch=N     Contacts per API call (default 2000, max ~5000).
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$IS_CLI = PHP_SAPI === 'cli';

if ($IS_CLI) {
    $opts = getopt('', ['dry-run', 'limit::', 'batch::']);
    $DRY_RUN = isset($opts['dry-run']);
    $LIMIT = isset($opts['limit']) ? (int)$opts['limit'] : 0;
    $BATCH_SIZE = isset($opts['batch']) ? (int)$opts['batch'] : 2000;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    $expected = (string)($conf['brevoSyncToken'] ?? '');
    $provided = (string)($_GET['token'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit("Forbidden.\n");
    }
    $DRY_RUN = isset($_GET['dry-run']) && $_GET['dry-run'] !== '0';
    $LIMIT = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
    $BATCH_SIZE = isset($_GET['batch']) ? (int)$_GET['batch'] : 2000;
    set_time_limit(0);
    ignore_user_abort(true);
    @ob_implicit_flush(true);
    while (ob_get_level() > 0) { ob_end_flush(); }
}

$apiKey = $conf['brevoApiKey'] ?? '';
$listId = (int)($conf['brevoListIdAdhesion'] ?? 0);

if ($apiKey === '' || $listId <= 0) {
    if ($IS_CLI) {
        fwrite(STDERR, "Config Brevo manquante (brevoApiKey / brevoListIdAdhesion).\n");
    } else {
        echo "Config Brevo manquante (brevoApiKey / brevoListIdAdhesion).\n";
    }
    exit(1);
}

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $conf['ip_mysql'], $conf['db_name_mysql']),
    $conf['user_mysql'],
    $conf['password_mysql'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$sql = "SELECT id, email, first_name, last_name, date_debut, date_fin, referral_source, edit_token
        FROM adh_adhesion_client
        WHERE date_fin >= CURDATE()
          AND email IS NOT NULL AND email <> ''
        ORDER BY id";
if ($LIMIT > 0) {
    $sql .= " LIMIT {$LIMIT}";
}

$all = $pdo->query($sql)->fetchAll();
$total = count($all);

echo "=== Sync Brevo (Adhésion, listId={$listId}) ===\n";
echo "Mode        : " . ($DRY_RUN ? 'DRY-RUN' : 'LIVE') . "\n";
echo "Batch size  : {$BATCH_SIZE}\n";
echo "Total actifs: {$total}\n\n";

// Deduplicate by email (keep row with latest date_fin)
$byEmail = [];
$dupes = 0;
foreach ($all as $row) {
    $email = strtolower(trim($row['email']));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        continue;
    }
    if (isset($byEmail[$email])) {
        $dupes++;
        if ($row['date_fin'] > $byEmail[$email]['date_fin']) {
            $byEmail[$email] = $row;
        }
    } else {
        $byEmail[$email] = $row;
    }
}
$uniqueContacts = array_values($byEmail);
$totalUnique = count($uniqueContacts);
echo "Doublons email (fusionnés) : {$dupes}\n";
echo "Contacts uniques à pousser : {$totalUnique}\n\n";

$formatDate = static fn($v) => $v ? date('Y-m-d', strtotime((string)$v)) : null;

function brevoPost(string $path, array $payload, string $apiKey): array {
    $ch = curl_init('https://api.brevo.com' . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 60,
    ]);
    $body = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return ['http' => $http, 'body' => $body, 'err' => $err];
}

$batches = array_chunk($uniqueContacts, $BATCH_SIZE);
$nbBatches = count($batches);
$processIds = [];
$errors = 0;
$startTs = microtime(true);

foreach ($batches as $i => $chunk) {
    $idx = $i + 1;
    $jsonBody = [];
    foreach ($chunk as $c) {
        $attrs = [
            'PRENOM' => (string)($c['first_name'] ?? ''),
            'NOM' => (string)($c['last_name'] ?? ''),
            'ID_ADHERENT' => (int)$c['id'],
        ];
        if ($c['date_debut']) { $attrs['DATE_ADHESION'] = $formatDate($c['date_debut']); }
        if ($c['date_fin']) { $attrs['DATE_FIN'] = $formatDate($c['date_fin']); }
        if (!empty($c['referral_source'])) { $attrs['SOURCE'] = (string)$c['referral_source']; }
        if (!empty($c['edit_token'])) { $attrs['EDIT_TOKEN'] = (string)$c['edit_token']; }
        $jsonBody[] = [
            'email' => strtolower(trim((string)$c['email'])),
            'attributes' => $attrs,
        ];
    }

    printf("[%d/%d] Batch de %d contacts... ", $idx, $nbBatches, count($jsonBody));

    if ($DRY_RUN) {
        if ($idx === 1) {
            echo "\n  Exemple payload (premier contact) :\n  ";
            echo json_encode($jsonBody[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }
        echo "skip (dry-run)\n";
        continue;
    }

    $res = brevoPost('/v3/contacts/import', [
        'listIds' => [$listId],
        'jsonBody' => $jsonBody,
        'updateExistingContacts' => true,
        'emptyContactsAttributes' => false,
        'disableNotification' => true,
    ], $apiKey);

    if ($res['http'] >= 200 && $res['http'] < 300) {
        $data = json_decode($res['body'] ?? '', true);
        $pid = $data['processId'] ?? 'unknown';
        $processIds[] = $pid;
        printf("OK (processId=%s)\n", $pid);
    } else {
        $errors++;
        printf("KO [HTTP %d] %s\n", $res['http'], $res['err'] ?: substr((string)$res['body'], 0, 300));
    }

    // Gentle pacing between batches (avoid hammering)
    if (!$DRY_RUN && $idx < $nbBatches) { sleep(1); }
}

$elapsed = microtime(true) - $startTs;
echo "\n=== Résumé ===\n";
printf("Batches envoyés : %d\n", $nbBatches - ($DRY_RUN ? $nbBatches : $errors));
printf("Erreurs         : %d\n", $errors);
printf("Durée           : %.1fs\n", $elapsed);
if ($processIds) {
    echo "ProcessIds (à checker dans Brevo → Contacts → Imports & exports) :\n";
    foreach ($processIds as $pid) { echo "  - {$pid}\n"; }
}
