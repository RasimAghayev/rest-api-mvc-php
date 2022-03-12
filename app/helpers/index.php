<?php
use JWT\JWT;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
function flash($name = '', $message = '', $class = 'alert alert-success')
{
    if (!empty($name)) {
        //No message, create it
        if (!empty($message) && empty($_SESSION[$name])) {
            if (!empty($_SESSION[$name])) {
                unset($_SESSION[$name]);
            }
            if (!empty($_SESSION[$name . '_class'])) {
                unset($_SESSION[$name . '_class']);
            }
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } //Message exists, display it
        elseif (!empty($_SESSION[$name]) && empty($message)) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : 'success';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}
function redirect($page){
    header('location: ' . URLROOT . '/' . $page);
}
function getIPAddress() {
    $ipaddress = '';
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if(getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if(getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if(getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if(getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if(getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';

    return $ipaddress;
}
function microTimeSet(){
    list($usec, $sec) = explode(' ', microtime());
    $usec = substr(str_replace("0.", ".", $usec), 0, -2);
    return date('Ymd H:i:s', $sec) . $usec;
}
function writeLog(int $code,$data){
    $log  = microTimeSet().
        '  {"User":{"Code":'.$code.
                ',"IP":"'.getIPAddress().
                '","Agent":"'.$_SERVER['HTTP_USER_AGENT'].
                '","Data":'.json_encode($data).
        '}}'.
        PHP_EOL;
    file_put_contents('./logs/'.date("Ymd").'.lg', $log, FILE_APPEND);
}
function HTTPStatus($code=0, $sts='',$forwardURL='/',$msg='',$data=null){
    http_response_code($code);
    if($data==null){
        $json_data=[
            "status" => $sts,
            "url"=>getUrl(),
            "forwardURL"=>$forwardURL,
            "message" => $msg
        ];
    }else{
        $json_data=[
            "status" => $sts,
            "url"=>getUrl(),
            "forwardURL"=>$forwardURL,
            "message" => $msg,
            "data"=>$data
        ];
    }
    writeLog($code,$json_data);
    echo json_encode($json_data);
}
function getUrl(){
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}
function jwtEncode($jwt_start_time,$jwt_end_time,$aud,array $user_arr_data){
    //JWT_SECCRET_KEY=>,$secret_key
    $payload_info = array(
        "iss"=> getUrl(),
        "iat"=> time(),
        "nbf"=> time()+$jwt_start_time,
        "exp"=> time()+$jwt_end_time,
        "aud"=> $aud,
        "data"=> $user_arr_data
    );
    return JWT::encode($payload_info, JWT_SECCRET_KEY, 'HS512');
}
function jwtDecode($jwt){
    return JWT::decode($jwt, JWT_SECCRET_KEY, array('HS512'));
}
function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}
function generateRandomString($length = 20) {
    $key = '';
    $keys = array_merge(range('0','9'),range('a','z'),range('A','Z'));
    for ($i = 0; $i < $length; $i++) {
        $key .= $keys[array_rand($keys)];
    }
    return $key;
}


















function sendMail(){
    $mail = new PHPMailer;

//Tell PHPMailer to use SMTP
    $mail->isSMTP();

//Enable SMTP debugging
// SMTP::DEBUG_OFF = off (for production use)
// SMTP::DEBUG_CLIENT = client messages
// SMTP::DEBUG_SERVER = client and server messages
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;

//Set the hostname of the mail server
    $mail->Host = 'smtp.gmail.com';
// use
// $mail->Host = gethostbyname('smtp.gmail.com');
// if your network does not support SMTP over IPv6

//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    $mail->Port = 465;

//Set the encryption mechanism to use - STARTTLS or SMTPS
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

//Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = "tls";

//Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = 'tehzeh159@gmail.com';

//Password to use for SMTP authentication
    $mail->Password = 'esfemgwrtpfdkgnu';

//Set who the message is to be sent from
    $mail->setFrom('rasimaqayev@gmail.com', 'First Last');

//Set an alternative reply-to address
    $mail->addReplyTo('rasimaqayev@gmail.com', 'First Last');

//Set who the message is to be sent to
    $mail->addAddress('b061287@gmail.com', 'John Doe');

//Set the subject line
    $mail->Subject = 'PHPMailer GMail SMTP test';

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body
    $mail->msgHTML(file_get_contents('http://bsc-rest.rst/app/libraries/OtherClass/PHPMailer/examples/contents.html'), __DIR__);

//Replace the plain text body with one created manually
    $mail->AltBody = 'This is a plain-text message body';

//Attach an image file
    $mail->addAttachment('http://bsc-rest.rst/app/libraries/OtherClass/PHPMailer/examples/images/phpmailer_mini.png');

//send the message, check for errors
    if (!$mail->send()) {
        echo 'Mailer Error: '. $mail->ErrorInfo;
    } else {
        echo 'Message sent!';
        //Section 2: IMAP
        //Uncomment these to save your message in the 'Sent Mail' folder.
        if (save_mail($mail)) {
            echo "Message saved!";
        }
    }
}
function save_mail($mail)
{
    //You can change 'Sent Mail' to any other folder or tag
    $path = '{imap.gmail.com:993/imap/ssl}[Gmail]/Sent Mail';

    //Tell your server to open an IMAP connection using the same username and password as you used for SMTP
    $imapStream = imap_open($path, $mail->Username, $mail->Password);

    $result = imap_append($imapStream, $path, $mail->getSentMIMEMessage());
    imap_close($imapStream);

    return $result;
}