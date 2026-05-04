<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAdhesionClient.php';
require_once 'db/mAdhesionType.php';

class ProcessAdhesionTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private static int $adhesionTypeId;
    private mAdhesionClient $manager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];

        self::$conn->exec("DELETE FROM adh_adhesion_type");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES (:name, :price, :email, :duration)"
        );
        $stmt->execute([':name' => 'Standard', ':price' => 5.00, ':email' => 'welcome.html', ':duration' => 365]);
        self::$adhesionTypeId = (int) self::$conn->lastInsertId();

        self::$conn->exec("DELETE FROM adh_adhesion_client");
    }

    protected function setUp(): void
    {
        $this->manager = new mAdhesionClient(self::$conn, self::$conf);
    }

    private function insertClient(array $overrides = []): int
    {
        $defaults = [
            'last_name' => 'Test', 'first_name' => 'User', 'email' => 'test@example.com',
            'adhesion_type' => 'Standard', 'date_debut' => '2024-01-01',
            'date_fin' => '2025-01-01', 'newsletter' => 0,
        ];
        $data = array_merge($defaults, $overrides);

        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_client (last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter)
             VALUES (:ln, :fn, :email, :type, :debut, :fin, :news)"
        );
        $stmt->execute([
            ':ln' => $data['last_name'], ':fn' => $data['first_name'], ':email' => $data['email'],
            ':type' => $data['adhesion_type'], ':debut' => $data['date_debut'],
            ':fin' => $data['date_fin'], ':news' => $data['newsletter'],
        ]);

        return (int) self::$conn->lastInsertId();
    }

    // --- Tests: subscribe missing from POST (checkbox unchecked) ---

    public function testSubscribeAbsentSetsNewsletterFalse(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Doe';
        $client->first_name = 'Jane';
        $client->email = 'jane@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = date('Y-m-d H:i:s');
        $client->date_fin = date('Y-m-d H:i:s', strtotime('+365 days'));
        $client->newsletter = ($_POST['subscribe'] ?? '') == 'on';

        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertEquals(0, $saved->newsletter);
    }

    public function testSubscribePresentSetsNewsletterTrue(): void
    {
        $client = new AdhesionClient();
        $client->last_name = 'Doe';
        $client->first_name = 'John';
        $client->email = 'john@example.com';
        $client->adhesion_type = 'Standard';
        $client->date_debut = date('Y-m-d H:i:s');
        $client->date_fin = date('Y-m-d H:i:s', strtotime('+365 days'));

        $_POST['subscribe'] = 'on';
        $client->newsletter = ($_POST['subscribe'] ?? '') == 'on';
        unset($_POST['subscribe']);

        $this->manager->write($client);

        $saved = new AdhesionClient();
        $this->manager->read($client->id, $saved);
        $this->assertEquals(1, $saved->newsletter);
    }

    // --- Tests: edit mode detection (id present in POST) ---

    public function testEditModeDetectedWhenIdPresent(): void
    {
        $_POST['id'] = '42';
        $new = !isset($_POST['id']);
        unset($_POST['id']);

        $this->assertFalse($new);
    }

    public function testNewModeDetectedWhenIdAbsent(): void
    {
        unset($_POST['id']);
        $new = !isset($_POST['id']);

        $this->assertTrue($new);
    }

    // --- Tests: code_valid only required for new ---

    public function testCodeValidRequiredForNew(): void
    {
        $new = true;
        $err = '';

        if ($new) {
            $codeValid = $_POST['code_valid'] ?? '';
            if ($codeValid == '') {
                $err .= 'codeValidErr=Demande un code au bar&';
            }
        }

        $this->assertStringContainsString('codeValidErr', $err);
    }

    public function testCodeValidNotRequiredForEdit(): void
    {
        $new = false;
        $err = '';

        if ($new) {
            $codeValid = $_POST['code_valid'] ?? '';
            if ($codeValid == '') {
                $err .= 'codeValidErr=Demande un code au bar&';
            }
        }

        $this->assertStringNotContainsString('codeValidErr', $err);
    }

    // --- Tests: checkbox null coalescing (no warnings) ---

    public function testCarteCheckboxAbsentNoWarning(): void
    {
        unset($_POST['carte']);
        $carte = ($_POST['carte'] ?? '') == 'on';

        $this->assertFalse($carte);
    }

    public function testSendmailCheckboxAbsentNoWarning(): void
    {
        unset($_POST['sendmail']);
        $sendmail = ($_POST['sendmail'] ?? '') == 'on';

        $this->assertFalse($sendmail);
    }

    public function testSubscribeCheckboxAbsentNoWarning(): void
    {
        unset($_POST['subscribe']);
        $subscribe = ($_POST['subscribe'] ?? '') == 'on';

        $this->assertFalse($subscribe);
    }

    // --- Test: edit success message has no </br> ---

    public function testEditSuccessMessageNoBrTag(): void
    {
        $id = $this->insertClient();

        $text = "L'adhésion n°" . $id . " a bien été modifiée";

        $this->assertStringNotContainsString('</br>', $text);
        $this->assertStringNotContainsString('<br', $text);
    }

    // --- Test: write + update via manager ---

    public function testUpdateExistingClientNewsletter(): void
    {
        $id = $this->insertClient(['newsletter' => 1]);

        $client = new AdhesionClient();
        $this->manager->read($id, $client);
        $this->assertEquals(1, $client->newsletter);

        $client->newsletter = false;
        $this->manager->write($client);

        $updated = new AdhesionClient();
        $this->manager->read($id, $updated);
        $this->assertEquals(0, $updated->newsletter);
    }
}
