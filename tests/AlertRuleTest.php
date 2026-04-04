<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mAlertRule.php';
require_once 'db/mAlertSent.php';
require_once 'db/mAdhesionClient.php';
require_once 'db/mAdhesionType.php';

class AlertRuleTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private mAlertRule $ruleManager;
    private mAlertSent $sentManager;
    private mAdhesionClient $clientManager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];

        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");
        self::$conn->exec("DELETE FROM adh_adhesion_type");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES (:name, :price, :email, :duration)"
        );
        $stmt->execute([':name' => 'Standard', ':price' => 5.00, ':email' => 'welcome.html', ':duration' => 365]);
    }

    protected function setUp(): void
    {
        $this->ruleManager = new mAlertRule(self::$conn, self::$conf);
        $this->sentManager = new mAlertSent(self::$conn, self::$conf);
        $this->clientManager = new mAdhesionClient(self::$conn, self::$conf);
    }

    private function createRule(array $overrides = []): AlertRule
    {
        $defaults = [
            'name' => 'Test Rule',
            'trigger_type' => 'before',
            'trigger_days' => 30,
            'email_template' => 'welcome.html',
            'active' => 1,
        ];
        $data = array_merge($defaults, $overrides);

        $rule = new AlertRule();
        $rule->name = $data['name'];
        $rule->trigger_type = $data['trigger_type'];
        $rule->trigger_days = $data['trigger_days'];
        $rule->email_template = $data['email_template'];
        $rule->active = $data['active'];

        return $rule;
    }

    private function insertClient(array $overrides = []): int
    {
        $defaults = [
            'last_name' => 'Test',
            'first_name' => 'User',
            'email' => 'test' . mt_rand(1, 99999) . '@example.com',
            'adhesion_type' => 'Standard',
            'date_debut' => date('Y-m-d H:i:s'),
            'date_fin' => date('Y-m-d H:i:s', strtotime('+365 days')),
            'newsletter' => 0,
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
        $client->referral_source = null;
        $this->clientManager->write($client);

        return $client->id;
    }

    public function testCreateAndReadAlertRule(): void
    {
        $rule = $this->createRule([
            'name' => 'Relance 30j',
            'trigger_type' => 'before',
            'trigger_days' => 30,
            'email_template' => 'tpl1',
            'active' => 1,
        ]);
        $this->ruleManager->write($rule);
        $this->assertGreaterThan(0, $rule->id);

        $saved = new AlertRule();
        $result = $this->ruleManager->read($rule->id, $saved);

        $this->assertTrue($result);
        $this->assertSame($rule->id, $saved->id);
        $this->assertSame('Relance 30j', $saved->name);
        $this->assertSame('before', $saved->trigger_type);
        $this->assertSame(30, $saved->trigger_days);
        $this->assertSame('tpl1', $saved->email_template);
        $this->assertSame(1, $saved->active);
    }

    public function testListActive(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");

        $active = $this->createRule(['name' => 'Active Rule', 'active' => 1]);
        $this->ruleManager->write($active);

        $inactive = $this->createRule(['name' => 'Inactive Rule', 'active' => 0]);
        $this->ruleManager->write($inactive);

        $results = $this->ruleManager->list_active();
        $this->assertCount(1, $results);
        $this->assertSame('Active Rule', $results[0]->name);
    }

    public function testToggleActive(): void
    {
        $rule = $this->createRule(['active' => 1]);
        $this->ruleManager->write($rule);

        $this->ruleManager->toggle_active($rule->id);

        $toggled = new AlertRule();
        $this->ruleManager->read($rule->id, $toggled);
        $this->assertSame(0, $toggled->active);
    }

    public function testDeleteRule(): void
    {
        $rule = $this->createRule();
        $this->ruleManager->write($rule);
        $id = $rule->id;

        $this->ruleManager->delete($id);

        $deleted = new AlertRule();
        $result = $this->ruleManager->read($id, $deleted);
        $this->assertFalse($result);
    }

    public function testGetEligibleClientsBeforeTrigger(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $rule = $this->createRule(['trigger_type' => 'before', 'trigger_days' => 30]);
        $this->ruleManager->write($rule);

        $this->insertClient([
            'date_fin' => date('Y-m-d H:i:s', strtotime('+15 days')),
        ]);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(1, $eligible);
    }

    public function testGetEligibleClientsNotEligible(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $rule = $this->createRule(['trigger_type' => 'before', 'trigger_days' => 30]);
        $this->ruleManager->write($rule);

        $this->insertClient([
            'date_fin' => date('Y-m-d H:i:s', strtotime('+60 days')),
        ]);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(0, $eligible);
    }

    public function testMarkSentPreventsDoubleAlert(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $rule = $this->createRule(['trigger_type' => 'before', 'trigger_days' => 30]);
        $this->ruleManager->write($rule);

        $clientId = $this->insertClient([
            'date_fin' => date('Y-m-d H:i:s', strtotime('+15 days')),
        ]);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(1, $eligible);

        $this->sentManager->mark_sent($rule->id, $clientId);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(0, $eligible);
    }

    public function testOnTriggerType(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $rule = $this->createRule(['trigger_type' => 'on', 'trigger_days' => 0]);
        $this->ruleManager->write($rule);

        $this->insertClient([
            'date_fin' => date('Y-m-d 12:00:00'),
        ]);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(1, $eligible);
    }

    public function testOnTriggerDoesNotMatchPast(): void
    {
        self::$conn->exec("DELETE FROM adh_alert_sent");
        self::$conn->exec("DELETE FROM adh_alert_rule");
        self::$conn->exec("DELETE FROM adh_adhesion_client");

        $rule = $this->createRule(['trigger_type' => 'on', 'trigger_days' => 0]);
        $this->ruleManager->write($rule);

        $this->insertClient([
            'date_fin' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ]);

        $eligible = $this->sentManager->get_eligible_clients($rule);
        $this->assertCount(0, $eligible);
    }
}
