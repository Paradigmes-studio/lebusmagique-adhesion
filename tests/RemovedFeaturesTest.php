<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

/**
 * Regression guard: ensures removed features (MailChimp sync, alerts,
 * email-template CRUD UI) stay removed. Trips a red light if someone
 * reintroduces them without updating this test on purpose.
 */
class RemovedFeaturesTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__);
    }

    // --- MailChimp removed ---

    public function testMailchimpFilesAreAbsent(): void
    {
        $paths = [
            'lib/MailChimpHandler.php',
            'db/mailchimpTag.php',
            'db/mMailchimpTag.php',
            'tagMailchimp.php',
            'mTagMailchimp.php',
        ];
        foreach ($paths as $p) {
            $this->assertFileDoesNotExist($this->root . '/' . $p, "{$p} should be gone");
        }
    }

    // --- Alerts removed ---

    public function testAlertFilesAreAbsent(): void
    {
        $paths = [
            'alertRules.php',
            'mAlertRules.php',
            'db/alertRule.php',
            'db/mAlertRule.php',
            'db/mAlertSent.php',
        ];
        foreach ($paths as $p) {
            $this->assertFileDoesNotExist($this->root . '/' . $p, "{$p} should be gone");
        }
    }

    // --- Email template CRUD UI removed ---

    public function testEmailTemplateCrudUiIsAbsent(): void
    {
        $this->assertFileDoesNotExist($this->root . '/emailTemplates.php');
        $this->assertFileDoesNotExist($this->root . '/mEmailTemplates.php');
    }

    // --- Welcome email infra kept ---

    public function testWelcomeEmailInfraKept(): void
    {
        $this->assertFileExists($this->root . '/lib/EmailHandler.php');
        $this->assertFileExists($this->root . '/db/mEmailTemplate.php');
        $this->assertFileExists($this->root . '/db/emailTemplate.php');
    }

    public function testEmailHandlerHasNoSendAlert(): void
    {
        $src = file_get_contents($this->root . '/lib/EmailHandler.php');
        $this->assertStringNotContainsString('send_alert', $src);
        $this->assertStringNotContainsString('adh_alert_', $src);
    }

    // --- Dashboard links to removed pages are gone ---

    public function testDashboardHasNoLinksToRemovedPages(): void
    {
        $src = file_get_contents($this->root . '/main.php');
        foreach (['tagMailchimp.php', 'alertRules.php', 'emailTemplates.php'] as $page) {
            $this->assertStringNotContainsString($page, $src, "main.php still links to {$page}");
        }
    }

    // --- Brevo sync remains wired in the form handler ---

    public function testBrevoSyncWiredInFormHandler(): void
    {
        $src = file_get_contents($this->root . '/mCreateAdhesionClient.php');
        $this->assertStringContainsString('BrevoHandler', $src);
        $this->assertStringContainsString('upsertContact', $src);
        // And the legacy MailChimp block is gone
        $this->assertStringNotContainsString('MailChimpHandler', $src);
    }
}
