<?php
class Users extends Controller
{
    private $data;
    private $code;

    public function __construct() {
        $this->userModel = $this->model('User');
    }
    public function index(){}
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
//            writeLog(417,$ex->getMessage());
            HTTPStatus(417,0,$ex->getMessage(),'');
        }
    }
    public function login(){
        !($_SERVER['REQUEST_METHOD'] === "POST")?die(HTTPStatus(503,0,'/',"Access denied",$_SERVER['REQUEST_METHOD'])):'';
        $data = json_decode(file_get_contents("php://input"));
        (empty($data->Email) && empty($data->Password))?die(HTTPStatus(404,0,'/',"All data needed")):'';
        $this->userModel->Email = $data->Email;
        $user_data = $this->userModel->login();
        empty($user_data)?die(HTTPStatus(404,0,'/',"Username or Email not found")):'';//
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
    public function g2faCodeC(): array
    {
        $g2faDB=$this->userModel->g2faCodeR();
        (!$g2faDB)?die(HTTPStatus(404,0,'/',"Invalid credentials g2faCode")):'';
        $pga = new GoogleAuthenticator();
        $this->userModel->g2fa=$pga->createSecret();
        if(empty($g2faDB['g2fa'])){
            $this->userModel->g2faCodeU();
            $qr_code =  $pga->getQRCodeGoogleUrl($this->userModel->Email, $this->userModel->g2fa,'BSC',array(300,300,'Q'));
            $image = file_get_contents($qr_code);
            if ($image !== false){
                $imgKey= 'data:image/png;base64,'.base64_encode($image);
            }
        }
        $data=[
            'md52Key'=>strtolower((($g2faDB['g2fa'])?$g2faDB['g2fa']:$this->userModel->g2fa).generateRandomString(16)),
            'jstKey'=>$g2faDB['jwt_start_time'],
            'jetKey'=>$g2faDB['jwt_end_time'],
            'imgKey'=>$imgKey
        ];
        return $data;
    }
    public function g2faCodeV(){
        $this->g2faCodeC();
    }
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
//            writeLog(404,$ex->getMessage());
            HTTPStatus(417, 0, $ex->getMessage(), '');
        }
    }
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
    public function logout()
    {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_email']);
        unset($_SESSION['user_fname']);
        unset($_SESSION['time']);
        session_destroy();
        redirect('users/login');
    }
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