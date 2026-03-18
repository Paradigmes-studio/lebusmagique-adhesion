<?php
require_once("init.php");
require_once("get_login_info.php");

echo "<h2>Diagnostic connexion</h2>";
$query = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
echo "<table border='1'>";
while ($row = $query->fetch()) {
    printf("<tr><td>%s</td><td>%s</td></tr>", $row[0], $row[1]);
}
echo "</table>";

echo "<h2>Diagnostic encodage</h2>";

// Check adhesion types
$query = $conn->query("SELECT id, name, HEX(name) as hex_name FROM adh_adhesion_type");
while ($row = $query->fetch()) {
    printf("<p>ID %d: <b>%s</b><br>HEX: %s</p>", $row['id'], htmlspecialchars($row['name']), $row['hex_name']);
}

// Check a few clients
$query = $conn->query("SELECT id, last_name, first_name, adhesion_type, HEX(adhesion_type) as hex_type FROM adh_adhesion_client LIMIT 5");
while ($row = $query->fetch()) {
    printf("<p>Client %d: %s %s — type: <b>%s</b><br>HEX: %s</p>",
        $row['id'], htmlspecialchars($row['first_name']), htmlspecialchars($row['last_name']),
        htmlspecialchars($row['adhesion_type']), $row['hex_type']);
}

// Fix: convert latin1 columns to utf8mb4 via BINARY (preserves raw bytes)
if (isset($_POST['fix'])) {
    echo "<h2>Conversion des colonnes latin1 → utf8mb4...</h2>";
    $alters = [
        "ALTER TABLE adh_adhesion_type MODIFY name VARCHAR(200) CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_type MODIFY name VARCHAR(200) CHARACTER SET utf8mb4",
        "ALTER TABLE adh_adhesion_client MODIFY last_name VARCHAR(200) CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_client MODIFY last_name VARCHAR(200) CHARACTER SET utf8mb4",
        "ALTER TABLE adh_adhesion_client MODIFY first_name VARCHAR(200) CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_client MODIFY first_name VARCHAR(200) CHARACTER SET utf8mb4",
        "ALTER TABLE adh_adhesion_client MODIFY email VARCHAR(200) CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_client MODIFY email VARCHAR(200) CHARACTER SET utf8mb4",
        "ALTER TABLE adh_adhesion_client MODIFY adhesion_type VARCHAR(200) CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_client MODIFY adhesion_type VARCHAR(200) CHARACTER SET utf8mb4",
        "ALTER TABLE adh_adhesion_type_description MODIFY description TEXT CHARACTER SET binary",
        "ALTER TABLE adh_adhesion_type_description MODIFY description TEXT CHARACTER SET utf8mb4",
    ];
    foreach ($alters as $sql) {
        try {
            $conn->exec($sql);
            printf("<p style='color:green'>OK : %s</p>", htmlspecialchars($sql));
        } catch (Exception $e) {
            printf("<p style='color:red'>ERREUR : %s<br>%s</p>", htmlspecialchars($sql), htmlspecialchars($e->getMessage()));
        }
    }
    echo "<p><b>Termine.</b> <a href='fix_encoding.php'>Reverifier</a></p>";
} else {
    echo '<form method="POST"><button type="submit" name="fix" value="1" style="padding:10px 20px;font-size:1.2em;background:#ff6969;color:white;border:none;cursor:pointer;">Convertir les colonnes en utf8mb4</button></form>';
}
?>
