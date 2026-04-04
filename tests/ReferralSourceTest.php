<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAdhesionClient.php';
require_once 'db/mAdhesionType.php';
require_once 'lib/EmailHandler.php';

class ReferralSourceTest extends TestCase
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

        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_adhesion_client");
        self::$conn->exec("DELETE FROM adh_adhesion_type");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES (:name, :price, :email, :duration)"
        );
        $stmt->execute([':name' => 'Standard', ':price' => 5.00, ':email' => 'welcome.html', ':duration' => 365]);
    }

    protected function setUp(): void
    {
        $this->manager = new mAdhesionClient(self::$conn, self::$conf);
    }

    private function createClient(array $overrides = []): AdhesionClient
    {
        $defaults = [
            'last_name' => 'Test',
            'first_name' => 'User',
            'email' => 'test' . mt_rand(1, 99999) . '@example.com',
            'adhesion_type' => 'Standard',
            'date_debut' => date('Y-m-d H:i:s'),
            'date_fin' => date('Y-m-d H:i:s', strtotime('+365 days')),
            'newsletter' => 0,
            'referral_source' => null,
        ];
        $data = array_merge($defaults, $overrides);

        $client = new AdhesionClient();
        $client->last_name = $data['last_name'];
        $client->first_name = $data['first_name'];
        $client->email = $data['email'];
        $client->adhesion_type = $data['adhesion_type'];
        $client->date_debut = $data['date_debut'];
        $client->date_fin = $data['date_fin'];
        $client->newsletter = $data['newsletter'];
        $client->referral_source = $data['referral_source'];

        return $client;
    }

    public function testWriteAndReadReferralSource(): void
    {
        $client = $this->createClient(['referral_source' => 'instagram']);
        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertSame('instagram', $saved->referral_source);
    }

    public function testWriteAutreReferralSource(): void
    {
        $client = $this->createClient(['referral_source' => 'autre:Festival du Bus']);
        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertSame('autre:Festival du Bus', $saved->referral_source);
    }

    public function testWriteNullReferralSource(): void
    {
        $client = $this->createClient(['referral_source' => null]);
        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertNull($saved->referral_source);
    }

    public function testUpdatePreservesReferralSource(): void
    {
        $client = $this->createClient(['referral_source' => 'passant', 'newsletter' => 0]);
        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $saved->newsletter = true;
        $this->manager->write($saved);

        $updated = new AdhesionClient();
        $this->manager->read($client->id, $updated);
        $this->assertSame('passant', $updated->referral_source);
    }

    public function testCountByReferralSource(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $year = 2026;
        $debut = "$year-03-01 00:00:00";
        $fin = "$year-03-01 00:00:00";

        $c1 = $this->createClient(['referral_source' => 'passant', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c1);

        $c2 = $this->createClient(['referral_source' => 'passant', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c2);

        $c3 = $this->createClient(['referral_source' => 'instagram', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c3);

        $results = $this->manager->count_by_referral_source($year);
        $this->assertSame(2, $results['passant']);
        $this->assertSame(1, $results['instagram']);
    }

    public function testCountByReferralSourceExcludesNull(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $year = 2026;
        $debut = "$year-06-01 00:00:00";
        $fin = "$year-06-01 00:00:00";

        $c1 = $this->createClient(['referral_source' => null, 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c1);

        $c2 = $this->createClient(['referral_source' => 'facebook', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c2);

        $results = $this->manager->count_by_referral_source($year);
        $this->assertArrayNotHasKey('', $results);
        $this->assertArrayNotHasKey(null, $results);
        $this->assertSame(1, $results['facebook']);
    }

    public function testCountByReferralSourceGroupsAutre(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $year = 2026;
        $debut = "$year-07-01 00:00:00";
        $fin = "$year-07-01 00:00:00";

        $c1 = $this->createClient(['referral_source' => 'autre:Festival', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c1);

        $c2 = $this->createClient(['referral_source' => 'autre:Affiche', 'date_debut' => $debut, 'date_fin' => $fin]);
        $this->manager->write($c2);

        $results = $this->manager->count_by_referral_source($year);
        $this->assertSame(2, $results['autre']);
    }

    public function testNewFormShowsReferralSelect(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString('name="referral_source"', $html);
    }

    public function testEditFormShowsReferralReadOnly(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $client = $this->createClient(['referral_source' => 'instagram']);
        $this->manager->write($client);

        $html = $this->renderForm(['id' => $client->id]);

        $this->assertStringContainsString('Par Instagram', $html);
        $this->assertStringNotContainsString('name="referral_source"', $html);
    }

    private function renderForm(array $get = []): string
    {
        $_GET = $get;
        $_SESSION = [];

        if (!isset($get['id'])) {
            unset($_SESSION);
            $_SESSION = [];
        } else {
            $domain = self::$conf['db_name_mysql'] . ':';
            $_SESSION[$domain . 'user'] = serialize((object) ['login' => 'admin']);
        }

        $conn = self::$conn;
        $conf = self::$conf;
        $domain = self::$conf['db_name_mysql'] . ':';

        ob_start();
        include __DIR__ . '/../createAdhesionClient.php';
        return ob_get_clean();
    }
}
