<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mEmailTemplate.php';
require_once 'lib/EmailHandler.php';

class EmailTemplateTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private mEmailTemplate $manager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];

        self::$conn->exec("DELETE FROM adh_email_template");
    }

    protected function setUp(): void
    {
        $this->manager = new mEmailTemplate(self::$conn, self::$conf);
    }

    private function createTemplate(array $overrides = []): EmailTemplate
    {
        $defaults = [
            'name' => 'Test Template ' . mt_rand(1, 99999),
            'subject' => 'Sujet de test',
            'body' => '<p>Corps du template</p>',
        ];
        $data = array_merge($defaults, $overrides);

        $tpl = new EmailTemplate();
        $tpl->name = $data['name'];
        $tpl->subject = $data['subject'];
        $tpl->body = $data['body'];

        return $tpl;
    }

    public function testCreateAndReadTemplate(): void
    {
        $tpl = $this->createTemplate([
            'name' => 'Bienvenue',
            'subject' => 'Bienvenue {prenom}',
            'body' => '<p>Bonjour {prenom} {nom}</p>',
        ]);
        $this->manager->write($tpl);
        $this->assertGreaterThan(0, $tpl->id);

        $saved = new EmailTemplate();
        $result = $this->manager->read($tpl->id, $saved);

        $this->assertTrue($result);
        $this->assertSame('Bienvenue', $saved->name);
        $this->assertSame('Bienvenue {prenom}', $saved->subject);
        $this->assertSame('<p>Bonjour {prenom} {nom}</p>', $saved->body);
    }

    public function testListAll(): void
    {
        self::$conn->exec("DELETE FROM adh_email_template");

        $tpl1 = $this->createTemplate(['name' => 'Template A']);
        $this->manager->write($tpl1);

        $tpl2 = $this->createTemplate(['name' => 'Template B']);
        $this->manager->write($tpl2);

        $results = $this->manager->list_all();
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testListNames(): void
    {
        self::$conn->exec("DELETE FROM adh_email_template");

        $tpl = $this->createTemplate(['name' => 'Mon modele']);
        $this->manager->write($tpl);

        $names = $this->manager->list_names();

        $this->assertIsArray($names);
        $this->assertArrayHasKey($tpl->id, $names);
        $this->assertSame('Mon modele', $names[$tpl->id]);
    }

    public function testUpdateTemplate(): void
    {
        $tpl = $this->createTemplate(['subject' => 'Ancien sujet']);
        $this->manager->write($tpl);

        $saved = new EmailTemplate();
        $this->manager->read($tpl->id, $saved);
        $saved->subject = 'Nouveau sujet';
        $this->manager->write($saved);

        $updated = new EmailTemplate();
        $this->manager->read($tpl->id, $updated);
        $this->assertSame('Nouveau sujet', $updated->subject);
    }

    public function testDeleteTemplate(): void
    {
        $tpl = $this->createTemplate();
        $this->manager->write($tpl);
        $id = $tpl->id;

        $this->manager->delete($id);

        $deleted = new EmailTemplate();
        $result = $this->manager->read($id, $deleted);
        $this->assertFalse($result);
    }

    public function testGetModelsReturnsDbTemplates(): void
    {
        self::$conn->exec("DELETE FROM adh_email_template");

        $tpl = $this->createTemplate(['name' => 'Adhesion Email']);
        $this->manager->write($tpl);

        $handler = new EmailHandler(self::$conn, self::$conf);
        $models = $handler->get_models();

        $this->assertIsArray($models);
        $this->assertArrayHasKey($tpl->id, $models);
        $this->assertSame('Adhesion Email', $models[$tpl->id]);
    }

    public function testReplaceVariablesViaTemplate(): void
    {
        self::$conn->exec("DELETE FROM adh_email_template");

        $tpl = $this->createTemplate([
            'name' => 'Test Variables',
            'subject' => 'Bienvenue {prenom}',
            'body' => '<p>Bonjour {prenom} {nom}, votre adhesion #{id} est active.</p>',
        ]);
        $this->manager->write($tpl);

        $handler = new EmailHandler(self::$conn, self::$conf);
        $models = $handler->get_models();

        $this->assertArrayHasKey($tpl->id, $models);
        $this->assertSame('Test Variables', $models[$tpl->id]);

        $saved = new EmailTemplate();
        $this->manager->read($tpl->id, $saved);
        $this->assertStringContainsString('{prenom}', $saved->subject);
        $this->assertStringContainsString('{prenom}', $saved->body);
        $this->assertStringContainsString('{nom}', $saved->body);
        $this->assertStringContainsString('{id}', $saved->body);
    }
}
