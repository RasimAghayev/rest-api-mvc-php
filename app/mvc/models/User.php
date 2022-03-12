<?php
class User
{
    public $SurName,$Name,$MiddleName,$Gender,$UserName,$Email,$Password,$SecretKey,$UserStatus,
        $Token,$user_id,$status,$g2fa;
    private $conn,$users_tbl,$users_preset_tbl,$users_pfailed_tbl,$users_lhistory_tbl,$users_ahistory_tbl,$users_lcod_tble;

    public function __construct() {
        $this->conn = new Database;
        $this->users_tbl = "users";
        $this->users_preset_tbl = "users_password_reset";
        $this->users_pfailed_tbl = "users_password_failed";
        $this->users_lhistory_tbl = "users_login_history";
        $this->users_ahistory_tbl = "users_action_history";
        $this->users_lcod_tble = "users_list_code";
    }
    public function registerUser() {
        $user_query = "INSERT INTO "
            . $this->users_tbl .
            " SET SurName = :SurName, Name = :Name, MiddleName = :MiddleName, Gender = :Gender, 
            UserName = :UserName, Email = :Email, Password = :Password,SecretKey = :SecretKey, UserStatus = :UserStatus, 
            Expiration_Date = :Expiration_Date, user_ip = :user_ip;";
        $user_obj = $this->conn->query($user_query);
        $this->conn->bind(':SurName', $this->SurName);
        $this->conn->bind(':Name', $this->Name);
        $this->conn->bind(':MiddleName', $this->MiddleName);
        $this->conn->bind(':Gender', $this->Gender);
        $this->conn->bind(':UserName', $this->UserName);
        $this->conn->bind(':Email', $this->Email);
        $this->conn->bind(':Password', $this->Password);
        $this->conn->bind(':SecretKey', $this->SecretKey);
        $this->conn->bind(':UserStatus', $this->UserStatus);
        $this->conn->bind(':Expiration_Date', date('Y-m-d H:i:s', strtotime("+30 day", time())));
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    public function login()
    {
        $this->conn->query("SELECT * FROM ". $this->users_tbl ." WHERE email = :Email or UserName=:UserName;");
        $this->conn->bind(':Email', $this->Email);
        $this->conn->bind(':UserName', $this->Email);
        return $this->conn->singleASS();
    }
    public function loginFaildAttempsC()
    {
        $this->conn->query("INSERT INTO ". $this->users_pfailed_tbl ." SET user_id = :user_id,user_ip = concat(:user_ip,'-',now());");
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function loginFaildAttempsR()
    {
        $this->conn->query("SELECT * FROM ". $this->users_pfailed_tbl." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }
    public function loginFaildAttempsU()
    {
        $this->conn->query("UPDATE ". $this->users_pfailed_tbl ." SET count = count+1,user_ip=concat(:user_ip,'-',now(),'|',user_ip) WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function loginFaildAttempsD()
    {
        $this->conn->query("DELETE FROM ". $this->users_pfailed_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function loginUserStatusC($notify_status)
    {
        $this->conn->query("INSERT INTO ". $this->users_lhistory_tbl ." SET user_id = :user_id,status = :status,user_ip = :user_ip;");
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':status', $notify_status);
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function loginUserStatusR()
    {
        $this->conn->query("SELECT * FROM ".$this->users_lhistory_tbl." WHERE user_id = :user_id order by create_date desc;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }
    public function loginUserStatusD()
    {
        $this->conn->query("DELETE FROM ".$this->users_lhistory_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function g2faCodeC()
    {
        $this->conn->query("INSERT INTO ". $this->users_lcod_tble ." SET user_id = :user_id,SecretKey = :SecretKey,user_ip = :user_ip;");
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':SecretKey', $this->SecretKey);
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function g2faCodeR()
    {
        $this->conn->query("SELECT * FROM ".$this->users_lcod_tble." WHERE SecretKey=:SecretKey and user_id = :user_id;");
        $this->conn->bind(':SecretKey', $this->SecretKey);
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }
    public function g2faCodeU()
    {
        $this->conn->query("UPDATE ".$this->users_lcod_tble ." SET g2fa=:g2fa WHERE user_id = :user_id;");
        $this->conn->bind(':g2fa', $this->g2fa);
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function g2faCodeD()
    {
        $this->conn->query("DELETE FROM ".$this->users_lcod_tble ." WHERE user_id= :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }




    //Users Action History
    public function userActionHistoryC($action_status)
    {
        $this->conn->query("INSERT INTO ". $this->users_ahistory_tbl ." SET user_id = :user_id,`action` = :action,get_url = :get_url,user_ip = :user_ip;");
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':action', $action_status);
        $this->conn->bind(':get_url', getUrl());
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }
    public function userActionHistoryR()
    {
        $this->conn->query("SELECT * FROM ". $this->users_ahistory_tbl." WHERE user_id = :user_id order by create_date desc;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }
    public function userActionHistoryD()
    {
        $this->conn->query("DELETE FROM ". $this->users_ahistory_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }





    public function resetPassword()
    {
        $user_query = "INSERT INTO "
            . $this->users_preset_tbl .
            " SET user_id = :user_id, Token = :Token, UserStatus = :UserStatus, Expired = :Expired, user_ip = :user_ip;";
        $user_obj = $this->conn->query($user_query);
        $this->conn->bind(':user_id', $this->user_id);
        $this->conn->bind(':Token', $this->Token);
        $this->conn->bind(':UserStatus', 'E');
        $this->conn->bind(':Expired', strtotime("+10 minutes"));
        $this->conn->bind(':user_ip', getIPAddress());
        if ($this->conn->execute()) {
            return true;
        }
        return false;

    }
    public function checkResetToken(){
        $this->conn->query("SELECT * FROM ". $this->users_preset_tbl ." WHERE Token = :Token and UserStatus=:UserStatus and Expired>:Expired;");
        $this->conn->bind(':Token', $this->Token);
        $this->conn->bind(':UserStatus', 'E');
        $this->conn->bind(':Expired', time());
        if ($this->conn->singleASS()) {
            return true;
        }
        return false;
    }
    public function getUserById($id)
    {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        $row = $this->db->singleASS();
        return $row;
    }
}