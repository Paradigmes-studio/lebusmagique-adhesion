<?php
$host = $_SERVER['HTTP_HOST'] ?? '';
if (PHP_SAPI !== 'cli' && !in_array($host, ['localhost', 'localhost:8080', '127.0.0.1', '127.0.0.1:8080'], true)) {
    http_response_code(403);
    die('Fixtures are only allowed in local development.');
}

require_once("init.php");

$first_names = ['Marie', 'Jean', 'Pierre', 'Sophie', 'Lucas', 'Emma', 'Louis', 'Chloé', 'Hugo', 'Léa', 'Thomas', 'Camille', 'Antoine', 'Julie', 'Nicolas', 'Manon', 'Alexandre', 'Laura', 'Maxime', 'Sarah', 'Paul', 'Margaux', 'Julien', 'Clara', 'Romain', 'Inès', 'Mathieu', 'Alice', 'Théo', 'Charlotte', 'Adrien', 'Pauline', 'Quentin', 'Justine', 'Florian', 'Océane', 'Guillaume', 'Anaïs', 'Clément', 'Émilie'];

$last_names = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefèvre', 'Mercier', 'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez'];

// Insert adhesion types if none exist
$count = $conn->query("SELECT COUNT(*) FROM adh_adhesion_type")->fetch()[0];
if ($count == 0) {
    $conn->exec("INSERT INTO adh_adhesion_type(name, price, duration) VALUES ('Individuel', 10, 12)");
    $conn->exec("INSERT INTO adh_adhesion_type(name, price, duration) VALUES ('Famille', 20, 12)");
    $conn->exec("INSERT INTO adh_adhesion_type(name, price, duration) VALUES ('Soutien', 50, 12)");
}

$types_query = $conn->query("SELECT name FROM adh_adhesion_type");
$types = [];
while ($row = $types_query->fetch()) {
    $types[] = $row['name'];
}

$query = $conn->prepare(
    "INSERT INTO adh_adhesion_client(last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter)
     VALUES (:last_name, :first_name, :email, :adhesion_type, :date_debut, :date_fin, :newsletter)"
);

$inserted = 0;

// Monthly distribution weights to simulate realistic patterns
// More inscriptions in September-October (rentrée) and January
$weights = [
    1 => 15, 2 => 8, 3 => 10, 4 => 7, 5 => 6, 6 => 5,
    7 => 3, 8 => 2, 9 => 20, 10 => 18, 11 => 12, 12 => 6
];

for ($year = 2023; $year <= 2026; $year++) {
    $max_month = ($year == 2026) ? (int)date('m') : 12;

    for ($month = 1; $month <= $max_month; $month++) {
        $base = $weights[$month];
        // Add some randomness (+/- 40%)
        $count = max(1, $base + rand(-intval($base * 0.4), intval($base * 0.4)));

        for ($i = 0; $i < $count; $i++) {
            $first = $first_names[array_rand($first_names)];
            $last = $last_names[array_rand($last_names)];
            $type = $types[array_rand($types)];
            $day = rand(1, (int)date('t', mktime(0, 0, 0, $month, 1, $year)));
            $date_debut = sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
            $date_fin = date('Y-m-d 00:00:00', strtotime($date_debut . ' + 1 year'));
            $newsletter = rand(0, 1);
            $email = strtolower(remove_accents($first) . '.' . remove_accents($last) . rand(1, 99) . '@example.com');

            $query->execute([
                ':last_name' => $last,
                ':first_name' => $first,
                ':email' => $email,
                ':adhesion_type' => $type,
                ':date_debut' => $date_debut,
                ':date_fin' => $date_fin,
                ':newsletter' => $newsletter,
            ]);
            $inserted++;
        }
    }
}

function remove_accents($str) {
    return strtr($str, [
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ô' => 'o', 'ö' => 'o',
        'î' => 'i', 'ï' => 'i',
        'ç' => 'c',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E',
        'À' => 'A', 'Â' => 'A',
        'Ù' => 'U', 'Û' => 'U',
        'Ô' => 'O', 'Î' => 'I',
        'Ç' => 'C',
    ]);
}

echo "$inserted adhérents insérés (2023-2026).\n";
