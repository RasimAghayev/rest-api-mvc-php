<?php
/**
 * OTP Service
 *
 * Handles TOTP/G2FA operations extracted from Users controller:
 * - OTP secret generation via GoogleAuthenticator
 * - QR code URL generation for authenticator setup
 * - OTP code validation
 * - OTP secret persistence coordination with User model
 *
 * @package    rest-api-mvc-php
 * @subpackage app/Services
 * @author     rest-api-mvc-php team
 * @version    1.0
 */
class OTPService
{
    /** @var GoogleAuthenticator OTP authenticator instance */
    private $authenticator;
    /** @var object User model instance for DB operations */
    private $userModel;

    /**
     * OTPService constructor.
     *
     * @param object $userModel User model instance
     */
    public function __construct($userModel) {
        $this->userModel = $userModel;
        $this->authenticator = new GoogleAuthenticator();
    }

    /**
     * Generate or retrieve OTP setup data for user.
     *
     * Creates a new TOTP secret if none exists, persists it to the
     * database, generates a QR code URL for authenticator setup,
     * and returns composite session keys.
     *
     * @param int $userId User ID to generate OTP for
     *
     * @return array {
     *     @type string $md52Key - MD5 hash of TOTP secret + random string
     *     @type int    $jstKey   - JWT start timestamp
     *     @type int    $jetKey   - JWT end timestamp
     *     @type string|null $imgKey - Base64-encoded QR code PNG or null
     * }
     *
     * @route Internal (called by login, OTP verification)
     */
    public function setupOTP(int $userId): array
    {
        $g2faDB = $this->userModel->g2faCodeR();
        (!$g2faDB) ? die(HTTPStatus(404,0,'/',"Invalid credentials g2faCode")) : '';
        $this->userModel->g2fa = $this->authenticator->createSecret();
        if (empty($g2faDB['g2fa'])) {
            $this->userModel->g2faCodeU();
            $qr_code = $this->authenticator->getQRCodeGoogleUrl(
                $this->userModel->Email,
                $this->userModel->g2fa,
                'BSC',
                array(300, 300, 'Q')
            );
            $image = file_get_contents($qr_code);
            $imgKey = ($image !== false)
                ? 'data:image/png;base64,'.base64_encode($image)
                : null;
        } else {
            $imgKey = null;
        }
        return [
            'md52Key' => strtolower(
                (($g2faDB['g2fa']) ? $g2faDB['g2fa'] : $this->userModel->g2fa)
                . generateRandomString(16)
            ),
            'jstKey' => $g2faDB['jwt_start_time'],
            'jetKey' => $g2faDB['jwt_end_time'],
            'imgKey' => $imgKey
        ];
    }

    /**
     * Validate an OTP code against the user's secret.
     *
     * @param string $secret  The user's OTP secret
     * @param string $code    The code to validate
     * @param int    $discrepancy Allowed time drift in 30-second units
     *
     * @return bool True if code is valid, false otherwise
     */
    public function validateCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        return $this->authenticator->verifyCode($secret, $code, $discrepancy);
    }

    /**
     * Generate a new OTP secret for the user.
     *
     * @param int    $secretLength Desired secret length (16-128)
     *
     * @return string Generated base32 secret
     */
    public function generateSecret(int $secretLength = 16): string
    {
        return $this->authenticator->createSecret($secretLength);
    }

    /**
     * Generate QR code URL for OTP authenticator setup.
     *
     * @param string $name   User identifier (email)
     * @param string $secret OTP secret
     * @param string $title  Account title
     * @param array  $params QR code dimensions and error correction
     *
     * @return string QR code URL
     */
    public function getQRCodeUrl(string $name, string $secret, string $title = null, array $params = []): string
    {
        return $this->authenticator->getQRCodeGoogleUrl($name, $secret, $title, $params);
    }
}