<?php
/**
 * Users Controller
 *
 * Handles user authentication and management:
 * - Registration with validation and OTP setup
 * - Login with password verification, status checks, and failed attempt tracking
 * - TOTP/G2FA code generation and validation
 * - Password reset with token-based flow
 *
 * @package    rest-api-mvc-php
 * @subpackage app/mvc/controllers
 * @author     rest-api-mvc-php team
 * @version    1.0
 */
class Users extends Controller
{
    /** @var object User model instance */
    private $data;
    /** @var string OTP verification code */
    private $code;
    /** @var OTPService OTP service instance */
    private $otpService;

    /**
     * Users constructor.
     *
     * Loads the User model for database operations.
     */
    public function __construct() {
        $this->userModel = $this->model('User');
        $this->otpService = new OTPService($this->userModel);
    }

    /**
     * Default index action.
     *
     * @return void
     */
    public function index(){}

    /**
     * Register a new user.
     *
     * POST endpoint — creates a new user account with validation:
     * 1. POST method check (503 if not POST)
     * 2. JSON decode request body
     * 3. Validate fields: SurName, Name, MiddleName, Gender, UserName, Email, Password, UserStatus
     * 4. Hash password with PASSWORD_DEFAULT (bcrypt)
     * 5. Generate SecretKey (32-char random string)
     * 6. Check email uniqueness (returns 500 if duplicate)
     * 7. Register user in database
     * 8. Create OTP entry via g2faCodeC()
     *
     * @return void JSON response (201/424/417/500)
     *
     * @route POST /users
     * @validation See $rules in method body
     * @flow Documentation.txt section 0.register
     */
    public function register(){
        !($_SERVER['REQUEST_METHOD'] === "POST")?die(HTTPStatus(503,0,'/',"Access denied",$_SERVER['REQUEST_METHOD'])):'';
        $data = json_decode(file_get_contents("php://input"));
        $rules = [
            'SurName' => 'req|min:3|max:16|alpha',
            'Name' => 'req|min:3|max:16|alpha',
            'MiddleName' => 'req|min:3|max:16|alpha',
            'Gender' => 'req|min:1|alpha',
            'UserName' => 'req|min:5',
            'Email' => 'req|email',
            'Password' => 'req|min:6|strength:uppercase,lowercase,number,specialChars',
            'UserStatus' => 'req|min:1|alpha'
        ];
        $validator = new ValidField($data , $rules);
        ($validator->error())?die(HTTPStatus(500,0,'/',$validator->error())):'';
        try {
            $this->userModel->Email = $data->Email;
            $this->userModel->UserName = $data->UserName;
            $this->userModel->SurName = $data->SurName;
            $this->userModel->Name = $data->Name;
            $this->userModel->MiddleName = $data->MiddleName;
            $this->userModel->Gender = $data->Gender;
            $this->userModel->Password = password_hash($data->Password, PASSWORD_DEFAULT);
            $this->userModel->SecretKey = strtolower(generateRandomString(32));
            $this->userModel->UserStatus = $data->UserStatus;
            $this->userModel->Expiration_Date = $data->Expiration_Date;
            $check_email = $this->userModel->login();
            (!empty($check_email))?die(HTTPStatus(500,0,'/',"User already exists, try another email address")):'';
            $regUser=$this->userModel->registerUser();
            ($regUser)?
                HTTPStatus(201,1,'/users',"Project has been created",$data):
                HTTPStatus(424,0,'/',"Failed to create project",'');
            $this->userModel->user_id=$regUser;
            $this->userModel->g2faCodeC();

        } catch (Exception $ex) {
            HTTPStatus(417,0,$ex->getMessage(),'');
        }
    }

    /**
     * Authenticate user login.
     *
     * POST endpoint — validates credentials and initiates session:
     * 1. POST method check (503 if not POST)
     * 2. JSON decode request body
     * 3. Validate Email and Password are not empty (404 if missing)
     * 4. Lookup user by Email or UserName
     * 5. Check password expiration date (404 if expired)
     * 6. Check user status: Disable(423), Hold(423), Reset(423), Lock(423)
     * 7. Verify password with password_verify()
     * 8a. On success: log PT status, clear failed attempts, return TOTP data (202)
     * 8b. On failure: log PF status, track failed attempts (max 8), return 404
     *
     * @return void JSON response (202/404/423/503)
     *
     * @route POST /users/login
     * @flow Documentation.txt section 1.login
     */
    public function login(){
        !($_SERVER['REQUEST_METHOD'] === "POST")?die(HTTPStatus(503,0,'/',"Access denied",$_SERVER['REQUEST_METHOD'])):'';
        $data = json_decode(file_get_contents("php://input"));
        (empty($data->Email) && empty($data->Password))?die(HTTPStatus(404,0,'/',"All data needed")):'';
        $this->userModel->Email = $data->Email;
        $user_data = $this->userModel->login();
        empty($user_data)?die(HTTPStatus(404,0,'/',"Username or Email not found")):'';
        !($user_data['Expiration_Date']>date('Y-m-d H:i:s', time()))?
            die(HTTPStatus(404,0,'/',"Your password Expiration Date")):'';
        $this->userModel->user_id = $user_data['id'];
        $user_status=[
            'D'=>'Disable',
            'H'=>'Hold',
            'R'=>'Reset',
            'L'=>'Lock'
        ];
        (array_key_exists($user_data['UserStatus'],$user_status))?
            die(HTTPStatus(423,0,'/',"User is {$user_status[$user_data['UserStatus']]}")):'';
        if(password_verify($data->Password, $user_data['Password']))
        {
            $this->userModel->loginUserStatusC('PT');
            $this->userModel->loginFaildAttempsD();
            $this->userModel->SecretKey = $user_data['SecretKey'];
            $data=[
                'user_id'=> $this->userModel->user_id,
                'md51Key'=>$this->userModel->SecretKey,
                'OtherKey'=>$this->g2faCodeC()
            ];
            return HTTPStatus(202,1,'/TOTP',"User logged in successfully",$data);
        }
        else
        {
            $this->userModel->loginUserStatusC('PF');
            $user_faild_attempt = $this->userModel->loginFaildAttempsR();
            ($user_faild_attempt['count']>=8)?
                die(HTTPStatus(404,0,'/',"Failed login attempt limit")):
                ((!$user_faild_attempt)?
                    $this->userModel->loginFaildAttempsC():
                    $this->userModel->loginFaildAttempsU()
                );
            writeLog(404,$data);
            return HTTPStatus(404,0,'/',"Invalid credentials");
        }
    }

    /**
     * Generate/validate Google Authenticator (TOTP) code.
     *
     * Generates a new TOTP secret if none exists for the user,
     * returns QR code URL for Google Authenticator setup,
     * and returns composite keys for session authentication.
     *
     * @return array {
     *     @type string $md52Key - MD5 hash of TOTP secret + random string
     *     @type int    $jstKey   - JWT start timestamp
     *     @type int    $jetKey   - JWT end timestamp
     *     @type string|null $imgKey - Base64-encoded QR code PNG or null
     * }
     *
     * @route Internal (called by login, g2faCodeV)
     * @flow Documentation.txt section FR->T_OTP
     */
    public function g2faCodeC(): array
    {
        return $this->otpService->setupOTP($this->userModel->user_id);
    }

    /**
     * Validate Google Authenticator code.
     *
     * Alias for g2faCodeC() — performs the same TOTP validation flow.
     *
     * @return array See g2faCodeC()
     *
     * @route Internal (called by OTP verification)
     * @see Users::g2faCodeC()
     */
    public function g2faCodeV(){
        return $this->otpService->setupOTP($this->userModel->user_id);
    }

    /**
     * Initiate password reset.
     *
     * POST endpoint — generates a reset token:
     * 1. POST method check (503 if not POST)
     * 2. JSON decode request body
     * 3. Validate Email is not empty (404 if missing)
     * 4. Lookup user by email (404 if not found)
     * 5. Set user status to 'R' (Reset)
     * 6. Generate base64-encoded reset token (30 bytes random)
     * 7. Save reset token to database
     *
     * @return void JSON response (201/404/417/424/503)
     *
     * @route POST /users/reset-password
     * @flow Documentation.txt section 2.resetPassword
     */
    public function resetPassword()
    {
        !($_SERVER['REQUEST_METHOD'] === "POST")?die(HTTPStatus(503,0,'/',"Access denied",$_SERVER['REQUEST_METHOD'])):'';
        $data = json_decode(file_get_contents("php://input"));
        empty($data->Email)?die(HTTPStatus(404,0,'/',"All data needed")):'';
        $this->userModel->Email = $data->Email;
        $user_data = $this->userModel->login();
        !empty($user_data)?die(HTTPStatus(404, 0, '/',"Username or Email not found")):'';
        try {
            $this->userModel->loginUserStatusC('R');
            $this->userModel->Token = base64_encode(bin2hex(random_bytes(30)));
            $this->userModel->user_id = $user_data['id'];
            ($this->userModel->resetPassword())?
                HTTPStatus(201, 1, '/',"Users has been reset", $data):
                HTTPStatus(424, 0, '/',"Failed to create project");
        } catch (Exception $ex) {
            HTTPStatus(417, 0, $ex->getMessage(), '');
        }
    }

    /**
     * Validate reset token and allow password change.
     *
     * POST endpoint — verifies the reset token:
     * 1. POST method check (503 if not POST)
     * 2. JSON decode request body
     * 3. Validate Token is not empty (404 if missing)
     * 4. Check reset token validity and expiry (404 if expired)
     * 5. Set user status to 'T' (Token verified)
     *
     * @return void JSON response (200/404/503)
     *
     * @route POST /users/check-reset-token
     * @flow Documentation.txt section 3.checkResetToken
     */
    public function checkResetToken(){
        !($_SERVER['REQUEST_METHOD'] === "POST")?die(HTTPStatus(503,0,'/',"Access denied",$_SERVER['REQUEST_METHOD'])):'';
        $data = json_decode(file_get_contents("php://input"));
        empty($data->Token)?die(HTTPStatus(404,0,'/',"All data needed")):'';
        $this->userModel->Token = $data->Token;
        $user_data = $this->userModel->checkResetToken();
        $this->userModel->loginUserStatusC('T');
        $user_data?
            HTTPStatus(200,1,'/',"Reset password Token in successfully",''):
            HTTPStatus(404,0,'/',"Reset password Token in expired");
    }

    /**
     * Logout the current user.
     *
     * Destroys user session and redirects to login page.
     *
     * @return void Redirects to /users/login
     *
     * @route GET /users/logout
     */
    public function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_fname']);
        unset($_SESSION['user_lname']);
        unset($_SESSION['time']);
        session_destroy();
        redirect('users/login');
    }

    /**
     * Create user session after successful authentication.
     *
     * Stores user data in session and redirects to dashboard.
     *
     * @param object $user User data object with id, email, fname, lname
     *
     * @return void Redirects to /dashboards
     *
     * @route Internal (called after authentication)
     */
    public function createUserSession($user)
    {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_fname'] = $user->fname;
        $_SESSION['user_lname'] = $user->lname;
        $_SESSION['time'] = time();
        redirect('dashboards');
    }
}