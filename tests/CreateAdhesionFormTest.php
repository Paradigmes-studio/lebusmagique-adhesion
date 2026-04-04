<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAdhesionClient.php';
require_once 'db/mAdhesionType.php';
require_once 'lib/EmailHandler.php';

class CreateAdhesionFormTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;

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

        self::$conn->exec("DELETE FROM adh_adhesion_client");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_client (last_name, first_name, email, adhesion_type, date_debut, date_fin, newsletter)
             VALUES (:ln, :fn, :email, :type, :debut, :fin, :news)"
        );
        $stmt->execute([
            ':ln' => 'Dupont', ':fn' => 'Marie', ':email' => 'marie@example.com',
            ':type' => 'Standard', ':debut' => '2024-01-01', ':fin' => '2025-01-01', ':news' => 1,
        ]);
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

    public function testNewFormNewsletterUncheckedByDefault(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString('name="subscribe"', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<input\s+name="subscribe"\s+type="checkbox"\s+checked/',
            $html
        );
    }

    public function testNewFormNewsletterCheckedWhenSubscribeOn(): void
    {
        $html = $this->renderForm(['subscribe' => 'on']);

        $this->assertMatchesRegularExpression(
            '/name="subscribe".*checked/',
            $html
        );
    }

    public function testNewFormShowsCodeValidation(): void
    {
        $html = $this->renderForm();

        $this->assertStringContainsString('name="code_valid"', $html);
    }

    public function testEditFormHiddenIdInsideForm(): void
    {
        $id = self::$conn->query("SELECT id FROM adh_adhesion_client LIMIT 1")->fetchColumn();
        $html = $this->renderForm(['id' => $id]);

        $formPos = strpos($html, '<form');
        $formEnd = strpos($html, '</form>');
        $hiddenIdPos = strpos($html, 'name="id"');

        $this->assertNotFalse($formPos);
        $this->assertNotFalse($hiddenIdPos);
        $this->assertGreaterThan($formPos, $hiddenIdPos, 'Hidden id input must be inside the <form> tag');
        $this->assertLessThan($formEnd, $hiddenIdPos, 'Hidden id input must be before </form>');
    }

    public function testEditFormDoesNotShowCodeValidation(): void
    {
        $id = self::$conn->query("SELECT id FROM adh_adhesion_client LIMIT 1")->fetchColumn();
        $html = $this->renderForm(['id' => $id]);

        $this->assertStringNotContainsString('name="code_valid"', $html);
    }

    public function testEditFormNewsletterReflectsDbValue(): void
    {
        $id = self::$conn->query(
            "SELECT id FROM adh_adhesion_client WHERE newsletter = 1 LIMIT 1"
        )->fetchColumn();
        $html = $this->renderForm(['id' => $id]);

        $this->assertMatchesRegularExpression(
            '/name="subscribe".*checked/',
            $html
        );
    }
}
