<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class DbMigrationTest extends TestCase
{
    private static PDO $conn;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conn = $GLOBALS['conn'];
    }

    private function tableExists(string $name): bool
    {
        $q = self::$conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t");
        $q->execute([':t' => $name]);
        return (int)$q->fetchColumn() > 0;
    }

    // --- migrate_7_8 drops adh_mailchimp_tag ---

    public function testMigrate78DropsMailchimpTag(): void
    {
        self::$conn->exec("CREATE TABLE IF NOT EXISTS adh_mailchimp_tag (id INT AUTO_INCREMENT, name VARCHAR(200), active BOOLEAN, PRIMARY KEY(id))");
        $this->assertTrue($this->tableExists('adh_mailchimp_tag'));

        $version = migrate_7_8(self::$conn, $GLOBALS['conf']);

        $this->assertSame(8, $version);
        $this->assertFalse($this->tableExists('adh_mailchimp_tag'));
    }

    public function testMigrate78IsIdempotent(): void
    {
        // Running twice shouldn't error even if the table is already gone.
        migrate_7_8(self::$conn, $GLOBALS['conf']);
        $version = migrate_7_8(self::$conn, $GLOBALS['conf']);
        $this->assertSame(8, $version);
    }

    // --- migrate_8_9 drops alert tables + alerts_enabled ---

    public function testMigrate89DropsAlertTablesAndParam(): void
    {
        self::$conn->exec("CREATE TABLE IF NOT EXISTS adh_alert_rule (id INT AUTO_INCREMENT, name VARCHAR(200), PRIMARY KEY(id))");
        self::$conn->exec("CREATE TABLE IF NOT EXISTS adh_alert_sent (id INT AUTO_INCREMENT, rule_id INT, PRIMARY KEY(id))");
        self::$conn->exec("REPLACE INTO adh_param(id, value) VALUES ('alerts_enabled', '1')");

        $this->assertTrue($this->tableExists('adh_alert_rule'));
        $this->assertTrue($this->tableExists('adh_alert_sent'));
        $stmt = self::$conn->query("SELECT value FROM adh_param WHERE id = 'alerts_enabled'");
        $this->assertNotFalse($stmt->fetch());

        $version = migrate_8_9(self::$conn, $GLOBALS['conf']);

        $this->assertSame(9, $version);
        $this->assertFalse($this->tableExists('adh_alert_rule'));
        $this->assertFalse($this->tableExists('adh_alert_sent'));
        $stmt = self::$conn->query("SELECT value FROM adh_param WHERE id = 'alerts_enabled'");
        $this->assertFalse($stmt->fetch());
    }

    public function testMigrate89IsIdempotent(): void
    {
        migrate_8_9(self::$conn, $GLOBALS['conf']);
        $version = migrate_8_9(self::$conn, $GLOBALS['conf']);
        $this->assertSame(9, $version);
    }

    // --- migrate_6_7 is a no-op (alerts feature removed) ---

    public function testMigrate67IsNoopAndReturns7(): void
    {
        // Should not create any alert tables or param
        self::$conn->exec("DROP TABLE IF EXISTS adh_alert_rule");
        self::$conn->exec("DROP TABLE IF EXISTS adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_param WHERE id = 'alerts_enabled'");

        $version = migrate_6_7(self::$conn, $GLOBALS['conf']);

        $this->assertSame(7, $version);
        $this->assertFalse($this->tableExists('adh_alert_rule'));
        $this->assertFalse($this->tableExists('adh_alert_sent'));
        $stmt = self::$conn->query("SELECT value FROM adh_param WHERE id = 'alerts_enabled'");
        $this->assertFalse($stmt->fetch());
    }

    // --- current_version ---

    public function testCurrentVersionIs10(): void
    {
        $this->assertSame(10, $GLOBALS['current_version']);
    }

    // --- migrate_9_10: edit_token column ---

    public function testMigrate9To10AddsEditTokenColumn(): void
    {
        self::$conn->exec("DROP TABLE IF EXISTS adh_adhesion_client");
        self::$conn->exec("CREATE TABLE adh_adhesion_client(id INTEGER AUTO_INCREMENT, last_name VARCHAR(200), first_name VARCHAR(200), email VARCHAR(200), adhesion_type VARCHAR(200), date_debut DATETIME, date_fin DATETIME, newsletter BINARY, referral_source VARCHAR(200), PRIMARY KEY(id))");
        self::$conn->exec("INSERT INTO adh_adhesion_client(last_name, first_name, email) VALUES ('Existing', 'Member', 'existing@example.com')");

        $version = migrate_9_10(self::$conn, $GLOBALS['conf']);

        $this->assertSame(10, $version);
        $stmt = self::$conn->query("SHOW COLUMNS FROM adh_adhesion_client LIKE 'edit_token'");
        $this->assertEquals(1, $stmt->rowCount());

        $row = self::$conn->query("SELECT edit_token FROM adh_adhesion_client WHERE email = 'existing@example.com'")->fetch();
        $this->assertNotEmpty($row['edit_token']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $row['edit_token']);
    }
}
