<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAdhesionClient.php';

class EditTokenTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private mAdhesionClient $manager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];
        self::$conn->exec("DELETE FROM adh_adhesion_client");
    }

    protected function setUp(): void
    {
        $this->manager = new mAdhesionClient(self::$conn, self::$conf);
    }

    public function testEditTokenColumnExists(): void
    {
        $stmt = self::$conn->query("SHOW COLUMNS FROM adh_adhesion_client LIKE 'edit_token'");
        $this->assertEquals(1, $stmt->rowCount(), 'Column edit_token must exist on adh_adhesion_client');
    }

    public function testEditTokenColumnIsUnique(): void
    {
        $stmt = self::$conn->query("SHOW INDEX FROM adh_adhesion_client WHERE Column_name = 'edit_token'");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($indexes, 'edit_token must have an index');
        $nonUnique = array_column($indexes, 'Non_unique');
        $this->assertContains('0', array_map('strval', $nonUnique), 'edit_token must have a UNIQUE index');
    }

    public function testNewAdhesionGeneratesEditToken(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Token';
        $client->first_name = 'Gen';
        $client->email = 'token-gen@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = '2026-01-01';
        $client->date_fin = '2027-01-01';
        $client->newsletter = 0;

        $this->manager->write($client);

        $this->assertNotEmpty($client->edit_token);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $client->edit_token);
    }

    public function testEditTokenIsPersisted(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Persist';
        $client->first_name = 'Token';
        $client->email = 'persist@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = '2026-01-01';
        $client->date_fin = '2027-01-01';
        $client->newsletter = 0;

        $this->manager->write($client);
        $originalToken = $client->edit_token;

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);

        $this->assertEquals($originalToken, $saved->edit_token);
    }

    public function testTwoAdhesionsGetDifferentTokens(): void
    {
        $a = new AdhesionClient();
        $a->last_name = 'A'; $a->first_name = 'A'; $a->email = 'a@example.com';
        $a->adhesion_type = 'Standard'; $a->date_debut = '2026-01-01';
        $a->date_fin = '2027-01-01'; $a->newsletter = 0;
        $this->manager->write($a);

        $b = new AdhesionClient();
        $b->last_name = 'B'; $b->first_name = 'B'; $b->email = 'b@example.com';
        $b->adhesion_type = 'Standard'; $b->date_debut = '2026-01-01';
        $b->date_fin = '2027-01-01'; $b->newsletter = 0;
        $this->manager->write($b);

        $this->assertNotEquals($a->edit_token, $b->edit_token);
    }

    public function testEditTokenPreservedOnUpdate(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Update'; $client->first_name = 'Test';
        $client->email = 'update@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = '2026-01-01';
        $client->date_fin = '2027-01-01';
        $client->newsletter = 0;
        $this->manager->write($client);
        $originalToken = $client->edit_token;

        $client->email = 'updated@example.com';
        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertEquals($originalToken, $saved->edit_token);
    }

    public function testFindByTokenReturnsMatchingAdhesion(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Find'; $client->first_name = 'Me';
        $client->email = 'find@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = '2026-01-01';
        $client->date_fin = '2027-01-01';
        $client->newsletter = 0;
        $this->manager->write($client);

        $found = new AdhesionClient();
        $ok = $this->manager->find_by_token($client->edit_token, $found);

        $this->assertTrue($ok);
        $this->assertEquals($client->id, $found->id);
        $this->assertEquals('find@example.com', $found->email);
    }

    public function testFindByTokenReturnsFalseForUnknownToken(): void
    {
        $found = new AdhesionClient();
        $ok = $this->manager->find_by_token('deadbeef'.str_repeat('0', 24), $found);
        $this->assertFalse($ok);
    }

    public function testFindByTokenRejectsEmptyToken(): void
    {
        $found = new AdhesionClient();
        $ok = $this->manager->find_by_token('', $found);
        $this->assertFalse($ok);
    }
}
