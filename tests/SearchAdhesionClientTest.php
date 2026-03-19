<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAdhesionClient.php';

class SearchAdhesionClientTest extends TestCase
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
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_client (last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter)
             VALUES (:last_name, :first_name, :email, :type, :debut, :fin, :news)"
        );
        $fixtures = [
            ['Dupont', 'Marie', 'marie@test.com', 'Standard', '2024-01-01', '2025-01-01', 0],
            ['Dupond', 'Lucas', 'lucas@test.com', 'Standard', '2024-06-01', '2025-06-01', 1],
            ['Martin', 'Camille', 'camille@test.com', 'Premium', '2023-01-01', '2024-01-01', 0],
        ];
        foreach ($fixtures as $f) {
            $stmt->execute([
                ':last_name' => $f[0], ':first_name' => $f[1], ':email' => $f[2],
                ':type' => $f[3], ':debut' => $f[4], ':fin' => $f[5], ':news' => $f[6],
            ]);
        }
    }

    protected function setUp(): void
    {
        $this->manager = new mAdhesionClient(self::$conn, self::$conf);
    }

    public function testSearchByLastName(): void
    {
        $results = $this->manager->search(['last_name' => 'Dupont']);
        $this->assertNotEmpty($results);
        $this->assertSame('Dupont', $results[0]->last_name);
    }

    public function testSearchByFirstName(): void
    {
        $results = $this->manager->search(['first_name' => 'Camille']);
        $this->assertNotEmpty($results);
        $this->assertSame('Camille', $results[0]->first_name);
    }

    public function testSearchByEmail(): void
    {
        $results = $this->manager->search(['email' => 'lucas@test.com']);
        $this->assertCount(1, $results);
        $this->assertSame('Dupond', $results[0]->last_name);
    }

    public function testSearchByLastNameAndFirstName(): void
    {
        $results = $this->manager->search([
            'last_name' => 'Dupont',
            'first_name' => 'Marie',
        ]);
        $this->assertCount(1, $results);
        $this->assertSame('Dupont', $results[0]->last_name);
        $this->assertSame('Marie', $results[0]->first_name);
    }

    public function testSearchByAllFilters(): void
    {
        $results = $this->manager->search([
            'last_name' => 'Martin',
            'first_name' => 'Camille',
            'email' => 'camille@test.com',
        ]);
        $this->assertCount(1, $results);
        $this->assertSame('Martin', $results[0]->last_name);
    }

    public function testSearchNoFiltersReturnsAll(): void
    {
        $results = $this->manager->search([]);
        $this->assertCount(3, $results);
    }

    public function testSearchNoMatch(): void
    {
        $results = $this->manager->search(['email' => 'nobody@test.com']);
        $this->assertEmpty($results);
    }
}