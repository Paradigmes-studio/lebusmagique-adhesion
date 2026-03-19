<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once 'db/mUser.php';

class CheckLoginTest extends TestCase
{
    private static PDO $conn;
    private static array $conf;
    private mUser $manager;

    public static function setUpBeforeClass(): void
    {
        if ($GLOBALS['conn'] === null) {
            self::markTestSkipped('No database connection available');
        }

        self::$conf = $GLOBALS['conf'];
        self::$conn = $GLOBALS['conn'];

        self::$conn->exec("DELETE FROM adh_user");
        $stmt = self::$conn->prepare(
            "INSERT INTO adh_user (login, password) VALUES (:login, :password)"
        );
        $stmt->execute([
            ':login' => 'admin',
            ':password' => password_hash('admin', PASSWORD_DEFAULT),
        ]);
    }

    protected function setUp(): void
    {
        $this->manager = new mUser(self::$conn, self::$conf);
    }

    public function testValidCredentials(): void
    {
        $user = new User();
        $found = $this->manager->read('admin', $user);
        $this->assertTrue($found);
        $this->assertNotNull($user->password);
        $this->assertTrue(password_verify('admin', $user->password));
    }

    public function testUnknownUserReturnsFalse(): void
    {
        $user = new User();
        $found = $this->manager->read('unknown_user', $user);
        $this->assertFalse($found);
    }

    public function testUnknownUserPasswordRemainsNull(): void
    {
        $user = new User();
        $this->manager->read('unknown_user', $user);
        $this->assertNull($user->password);
    }

    public function testWrongPassword(): void
    {
        $user = new User();
        $found = $this->manager->read('admin', $user);
        $this->assertTrue($found);
        $this->assertFalse(password_verify('wrong_password', $user->password));
    }

    public function testPasswordVerifyWithNullDoesNotWarn(): void
    {
        $user = new User();
        $found = $this->manager->read('unknown_user', $user);

        $this->assertFalse($found);
        if ($found && $user->password !== null) {
            password_verify('test', $user->password);
        }
        $this->assertTrue(true);
    }
}
