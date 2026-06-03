<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'lib/AdhesionEdit.php';
require_once 'db/mAdhesionClient.php';
require_once 'db/mAdhesionType.php';

class AdhesionEditTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private static int $adhesionTypeId;
    private mAdhesionClient $clientManager;
    private mAdhesionType $typeManager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }
        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];

        self::$conn->exec("DELETE FROM adh_adhesion_type");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_adhesion_type (name, price, email_welcome, duration) VALUES (:n, :p, :e, :d)"
        );
        $stmt->execute([':n' => 'Annuel', ':p' => 5.00, ':e' => '', ':d' => 365]);
        self::$adhesionTypeId = (int) self::$conn->lastInsertId();
    }

    protected function setUp(): void
    {
        self::$conn->exec("DELETE FROM adh_adhesion_client");
        $this->clientManager = new mAdhesionClient(self::$conn, self::$conf);
        $this->typeManager = new mAdhesionType(self::$conn, self::$conf);
    }

    private function createAdhesion(string $email = 'test@example.com', ?string $dateFin = null): AdhesionClient
    {
        $c = new AdhesionClient();
        $c->last_name = 'Martin'; $c->first_name = 'Paul';
        $c->email = $email; $c->adhesion_type = 'Annuel';
        $c->date_debut = '2024-01-01 00:00:00';
        $c->date_fin = $dateFin ?? '2025-01-01 00:00:00';
        $c->newsletter = 0; $c->referral_source = 'passant';
        $this->clientManager->write($c);
        return $c;
    }

    public function testValidDailyCodeForCurrentDate(): void
    {
        $ts = strtotime('2026-04-20');
        $code = strrev('20') . strrev('04');
        $this->assertTrue(is_valid_daily_code($code, $ts));
    }

    public function testInvalidDailyCodeWrongDay(): void
    {
        $ts = strtotime('2026-04-20');
        $this->assertFalse(is_valid_daily_code('1040', $ts));
    }

    public function testInvalidDailyCodeWrongMonth(): void
    {
        $ts = strtotime('2026-04-20');
        $this->assertFalse(is_valid_daily_code('0250', $ts));
    }

    public function testInvalidDailyCodeWrongLength(): void
    {
        $ts = strtotime('2026-04-20');
        $this->assertFalse(is_valid_daily_code('024', $ts));
        $this->assertFalse(is_valid_daily_code('02400', $ts));
    }

    public function testEmptyDailyCodeRejected(): void
    {
        $this->assertFalse(is_valid_daily_code('', time()));
    }

    public function testComputeRenewalDatesBasic(): void
    {
        [$start, $end] = compute_renewal_dates('2026-04-20', 365);
        $this->assertEquals('2026-04-20 00:00:00', $start);
        $this->assertEquals('2027-04-20 00:00:00', $end);
    }

    public function testComputeRenewalDatesShortDuration(): void
    {
        [$start, $end] = compute_renewal_dates('2026-01-01', 30);
        $this->assertEquals('2026-01-01 00:00:00', $start);
        $this->assertEquals('2026-01-31 00:00:00', $end);
    }

    public function testComputeRenewalDatesEmptyDateReturnsNull(): void
    {
        $result = compute_renewal_dates('', 365);
        $this->assertNull($result);
    }

    public function testComputeRenewalDatesInvalidDateReturnsNull(): void
    {
        $result = compute_renewal_dates('not-a-date', 365);
        $this->assertNull($result);
    }

    // --- process_edit_post: handler logic ---

    private function validDailyCode(?int $ts = null): string
    {
        $ts = $ts ?? time();
        return strrev(date('d', $ts)) . strrev(date('m', $ts));
    }

    public function testProcessEditRequiresDailyCode(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            ['email' => 'new@example.com', 'code_valid' => ''],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('code', strtolower($result['error']));
    }

    public function testProcessEditRejectsWrongDailyCode(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            ['email' => 'new@example.com', 'code_valid' => '9999'],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('code', strtolower($result['error']));
    }

    public function testProcessEditUpdatesEmail(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            ['email' => 'new@example.com', 'code_valid' => $this->validDailyCode()],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals('new@example.com', $saved->email);
    }

    public function testProcessEditRenewSetsDatesFromToday(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renew' => 'on',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renewed']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+365 days'));
        $this->assertEquals($today . ' 00:00:00', $saved->date_debut);
        $this->assertEquals($endDate . ' 00:00:00', $saved->date_fin);
    }

    public function testProcessEditRenewRejectedWhenAdhesionStillValid(): void
    {
        $futureEnd = date('Y-m-d', strtotime('+30 days')) . ' 00:00:00';
        $c = $this->createAdhesion('active@example.com', $futureEnd);

        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renew' => 'on',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('renouvelable à partir du', strtolower($result['error']));

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals($futureEnd, $saved->date_fin);
    }

    public function testProcessEditRenewAllowedWithinRenewalWindow(): void
    {
        $endInWindow = date('Y-m-d', strtotime('+10 days')) . ' 00:00:00';
        $c = $this->createAdhesion('window@example.com', $endInWindow);

        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renew' => 'on',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renewed']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+365 days'));
        $this->assertEquals($today . ' 00:00:00', $saved->date_debut);
        $this->assertEquals($endDate . ' 00:00:00', $saved->date_fin);
    }

    public function testProcessEditRenewAllowedOnWindowBoundary(): void
    {
        $endOnBoundary = date('Y-m-d', strtotime('+' . RENEWAL_WINDOW_DAYS . ' days')) . ' 00:00:00';
        $c = $this->createAdhesion('boundary@example.com', $endOnBoundary);

        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renew' => 'on',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renewed']);
    }

    public function testProcessEditRenewAllowedOnExpiryDay(): void
    {
        $todayEnd = date('Y-m-d') . ' 00:00:00';
        $c = $this->createAdhesion('expiring@example.com', $todayEnd);

        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renew' => 'on',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertTrue($result['renewed']);
    }

    public function testProcessEditWithoutRenewKeepsDates(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            [
                'email' => 'new@example.com',
                'code_valid' => $this->validDailyCode(),
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertFalse($result['renewed']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals('2024-01-01 00:00:00', $saved->date_debut);
        $this->assertEquals('2025-01-01 00:00:00', $saved->date_fin);
    }

    public function testProcessEditIgnoresRenewalDateInputForSafety(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            [
                'email' => $c->email,
                'code_valid' => $this->validDailyCode(),
                'renewal_date' => '2030-05-01',
            ],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);
        $this->assertFalse($result['renewed']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals('2024-01-01 00:00:00', $saved->date_debut);
        $this->assertEquals('2025-01-01 00:00:00', $saved->date_fin);
    }

    public function testProcessEditIgnoresPostedIdUsesSessionId(): void
    {
        $victim = $this->createAdhesion('victim@example.com');
        $attacker = $this->createAdhesion('attacker@example.com');

        // Attacker session id, but posts victim's id in POST
        $result = process_edit_post(
            [
                'id' => $victim->id,
                'email' => 'pwned@example.com',
                'code_valid' => $this->validDailyCode(),
            ],
            $attacker->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);

        $savedVictim = new AdhesionClient();
        $this->clientManager->read($victim->id, $savedVictim);
        $this->assertEquals('victim@example.com', $savedVictim->email);

        $savedAttacker = new AdhesionClient();
        $this->clientManager->read($attacker->id, $savedAttacker);
        $this->assertEquals('pwned@example.com', $savedAttacker->email);
    }

    public function testProcessEditInvalidEmailRejected(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            ['email' => 'not-an-email', 'code_valid' => $this->validDailyCode()],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('email', strtolower($result['error']));
    }

    public function testProcessEditSetsNewsletterWhenSubscribeOn(): void
    {
        $c = $this->createAdhesion();
        $result = process_edit_post(
            ['email' => $c->email, 'code_valid' => $this->validDailyCode(), 'subscribe' => 'on'],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals(1, (int)$saved->newsletter);
    }

    public function testProcessEditClearsNewsletterWhenSubscribeAbsent(): void
    {
        $c = $this->createAdhesion();
        $c->newsletter = 1;
        $this->clientManager->write($c);

        $result = process_edit_post(
            ['email' => $c->email, 'code_valid' => $this->validDailyCode()],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );
        $this->assertTrue($result['ok']);

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals(0, (int)$saved->newsletter);
    }

    public function testProcessEditPreservesEditToken(): void
    {
        $c = $this->createAdhesion();
        $originalToken = $c->edit_token;

        process_edit_post(
            ['email' => 'keep-token@example.com', 'code_valid' => $this->validDailyCode()],
            $c->id,
            $this->clientManager,
            $this->typeManager
        );

        $saved = new AdhesionClient();
        $this->clientManager->read($c->id, $saved);
        $this->assertEquals($originalToken, $saved->edit_token);
    }
}
