<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../app/bootstrap.php';

class AuthControllerTest extends TestCase
{
    private $userModel;
    private $usersController;

    protected function setUp(): void
    {
        $this->userModel = $this->createMock(User::class);
        $this->usersController = $this->getMockBuilder(Users::class)
            ->onlyMethods(['model'])
            ->disableOriginalConstructor();
    }

    public function testRegisterValidDataReturns201(): void
    {
        $this->userModel->method('login')->willReturn([]);
        $this->userModel->method('registerUser')->willReturn(1);
        $this->userModel->method('g2faCodeC')->willReturn([]);

        $data = (object)[
            'SurName' => 'Test',
            'Name' => 'User',
            'MiddleName' => 'Test',
            'Gender' => 'M',
            'UserName' => 'testuser',
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
            'UserStatus' => 'A',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $jsonInput = json_encode($data);

        $this->expectOutputRegex('/"status":1/');
        $controller->register();
    }

    public function testRegisterDuplicateEmailReturns500(): void
    {
        $this->userModel->method('login')->willReturn(['id' => 1, 'Email' => 'test@example.com']);

        $data = (object)[
            'SurName' => 'Test',
            'Name' => 'User',
            'MiddleName' => 'Test',
            'Gender' => 'M',
            'UserName' => 'testuser2',
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
            'UserStatus' => 'A',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":0/');
        $controller->register();
    }

    public function testRegisterMissingRequiredFieldsReturns500(): void
    {
        $data = (object)[];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":0/');
        $controller->register();
    }

    public function testRegisterShortSurNameReturns500(): void
    {
        $data = (object)[
            'SurName' => 'Ab',
            'Name' => 'User',
            'MiddleName' => 'Test',
            'Gender' => 'M',
            'UserName' => 'testuser',
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
            'UserStatus' => 'A',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":0/');
        $controller->register();
    }

    public function testRegisterInvalidEmailReturns500(): void
    {
        $data = (object)[
            'SurName' => 'Test',
            'Name' => 'User',
            'MiddleName' => 'Test',
            'Gender' => 'M',
            'UserName' => 'testuser',
            'Email' => 'invalid-email',
            'Password' => 'Pass123!',
            'UserStatus' => 'A',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":0/');
        $controller->register();
    }

    public function testRegisterWeakPasswordReturns500(): void
    {
        $data = (object)[
            'SurName' => 'Test',
            'Name' => 'User',
            'MiddleName' => 'Test',
            'Gender' => 'M',
            'UserName' => 'testuser',
            'Email' => 'test@example.com',
            'Password' => 'weak',
            'UserStatus' => 'A',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":0/');
        $controller->register();
    }

    public function testRegisterNonPostReturns503(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $this->expectOutputRegex('/Access denied/');
        $controller->register();
    }

    public function testLoginValidCredentialsReturns202(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'A',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);
        $this->userModel->method('loginUserStatusC')->willReturn(true);
        $this->userModel->method('loginFaildAttempsD')->willReturn(true);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":1/');
        $controller->login();
    }

    public function testLoginUserNotFoundReturns404(): void
    {
        $this->userModel->method('login')->willReturn([]);

        $data = (object)[
            'Email' => 'unknown@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Username or Email not found/');
        $controller->login();
    }

    public function testLoginDisabledUserReturns423(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'D',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/User is Disable/');
        $controller->login();
    }

    public function testLoginHoldUserReturns423(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'H',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/User is Hold/');
        $controller->login();
    }

    public function testLoginLockedUserReturns423(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'L',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/User is Lock/');
        $controller->login();
    }

    public function testLoginExpiredPasswordReturns404(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'A',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        ];

        $this->userModel->method('login')->willReturn($userData);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'Pass123!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Your password Expiration Date/');
        $controller->login();
    }

    public function testLoginInvalidPasswordReturns404(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'A',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);
        $this->userModel->method('loginUserStatusC')->willReturn(true);
        $this->userModel->method('loginFaildAttempsC')->willReturn(true);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'WrongPass!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Invalid credentials/');
        $controller->login();
    }

    public function testLoginMaxFailedAttemptsReturns404(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
            'Password' => password_hash('Pass123!', PASSWORD_DEFAULT),
            'UserStatus' => 'A',
            'SecretKey' => 'abc123secret',
            'Expiration_Date' => date('Y-m-d H:i:s', strtotime('+30 days')),
        ];

        $this->userModel->method('login')->willReturn($userData);
        $this->userModel->method('loginUserStatusC')->willReturn(true);
        $this->userModel->method('loginFaildAttempsR')->willReturn(['count' => 8]);

        $data = (object)[
            'Email' => 'test@example.com',
            'Password' => 'WrongPass!',
        ];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Failed login attempt limit/');
        $controller->login();
    }

    public function testLoginEmptyDataReturns404(): void
    {
        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];

        $this->expectOutputRegex('/All data needed/');
        $controller->login();
    }

    public function testLoginNonPostReturns503(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $this->expectOutputRegex('/Access denied/');
        $controller->login();
    }

    public function testResetPasswordValidEmailReturns201(): void
    {
        $userData = [
            'id' => 1,
            'Email' => 'test@example.com',
        ];

        $this->userModel->method('login')->willReturn($userData);
        $this->userModel->method('resetPassword')->willReturn(true);
        $this->userModel->method('loginUserStatusC')->willReturn(true);

        $data = (object)['Email' => 'test@example.com'];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/"status":1/');
        $controller->resetPassword();
    }

    public function testResetPasswordInvalidEmailReturns404(): void
    {
        $this->userModel->method('login')->willReturn([]);

        $data = (object)['Email' => 'unknown@example.com'];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Username or Email not found/');
        $controller->resetPassword();
    }

    public function testResetPasswordNonPostReturns503(): void
    {
        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputRegex('/Access denied/');
        $controller->resetPassword();
    }

    public function testCheckResetTokenValidReturns200(): void
    {
        $userData = ['id' => 1];

        $this->userModel->method('checkResetToken')->willReturn($userData);
        $this->userModel->method('loginUserStatusC')->willReturn(true);

        $data = (object)['Token' => 'valid-token-base64'];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Reset password Token in successfully/');
        $controller->checkResetToken();
    }

    public function testCheckResetTokenExpiredReturns404(): void
    {
        $this->userModel->method('checkResetToken')->willReturn(false);
        $this->userModel->method('loginUserStatusC')->willReturn(true);

        $data = (object)['Token' => 'expired-token-base64'];

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectOutputRegex('/Reset password Token in expired/');
        $controller->checkResetToken();
    }

    public function testCheckResetTokenNonPostReturns503(): void
    {
        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->expectOutputRegex('/Access denied/');
        $controller->checkResetToken();
    }

    public function testG2faCodeCReturnsArrayWithKeys(): void
    {
        $g2faDB = ['g2fa' => '', 'jwt_start_time' => 0, 'jwt_end_time' => 0];

        $this->userModel->method('g2faCodeR')->willReturn($g2faDB);
        $this->userModel->method('g2faCodeU')->willReturn(true);

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $result = $controller->g2faCodeC();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('md52Key', $result);
        $this->assertArrayHasKey('jstKey', $result);
        $this->assertArrayHasKey('jetKey', $result);
    }

    public function testG2faCodeCInvalidCredentialsReturns404(): void
    {
        $this->userModel->method('g2faCodeR')->willReturn(false);

        $controller = $this->usersController->getMock();
        $controller->userModel = $this->userModel;

        $this->expectOutputRegex('/Invalid credentials g2faCode/');
        $controller->g2faCodeC();
    }
}