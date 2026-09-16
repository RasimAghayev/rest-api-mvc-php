<?php
/**
 * User Model
 *
 * Handles all user-related database operations:
 * - Registration and authentication
 * - User status management
 * - Password reset flow
 * - TOTP/G2FA code handling
 * - Login history tracking
 *
 * @package    rest-api-mvc-php
 * @subpackage app/mvc/models
 * @author     rest-api-mvc-php team
 * @version    1.0
 */
class User
{
    /** @var int|null Primary key */
    public $id;
    /** @var string Last name */
    public $SurName;
    /** @var string First name */
    public $Name;
    /** @var string Middle name */
    public $MiddleName;
    /** @var string Gender code (M/F/etc) */
    public $Gender;
    /** @var string Unique username */
    public $UserName;
    /** @var string Unique email */
    public $Email;
    /** @var string BCRYPT password hash */
    public $Password;
    /** @var string User secret key (32 chars) */
    public $SecretKey;
    /** @var string Status: D=Disable, H=Hold, R=Reset, L=Lock */
    public $UserStatus;
    /** @var string Password reset token */
    public $Token;
    /** @var string Password expiration date */
    public $Expiration_Date;
    /** @var string Registration IP */
    public $user_ip;
    /** @var string|null Creation timestamp */
    public $created_at;
    /** @var int|null User ID (internal use) */
    public $user_id;
    /** @var string|null OTP status */
    public $status;
    /** @var string|null Google Authenticator secret */
    public $g2fa;

    /** @var Database PDO connection */
    private $conn;
    /** @var string users table name */
    private $users_tbl;
    /** @var string users_password_reset table */
    private $users_preset_tbl;
    /** @var string users_password_failed table */
    private $users_pfailed_tbl;
    /** @var string users_login_history table */
    private $users_lhistory_tbl;
    /** @var string users_action_history table */
    private $users_ahistory_tbl;
    /** @var string users_list_code (OTP) table */
    private $users_lcod_tble;

    /**
     * User constructor.
     *
     * Initializes database connection and table names.
     */
    public function __construct() {
        $this->conn = new Database;
        $this->users_tbl = "users";
        $this->users_preset_tbl = "users_password_reset";
        $this->users_pfailed_tbl = "users_password_failed";
        $this->users_lhistory_tbl = "users_login_history";
        $this->users_ahistory_tbl = "users_action_history";
        $this->users_lcod_tble = "users_list_code";
    }

    /**
     * Register a new user.
     *
     * Inserts user record into users table with hashed password
     * and generated secret key. Returns last insert ID on success.
     *
     * @return int|false Last insert ID on success, false on failure
     *
     * @flow Documentation.txt section 0.register -> step 7
     */
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

    /**
     * Lookup user by email or username.
     *
     * @return array|false User row as associative array, or false if not found
     *
     * @flow Documentation.txt section 0.register -> step 6
     */
    public function login()
    {
        $this->conn->query("SELECT * FROM ". $this->users_tbl ." WHERE email = :Email or UserName=:UserName;");
        $this->conn->bind(':Email', $this->Email);
        $this->conn->bind(':UserName', $this->Email);
        return $this->conn->singleASS();
    }

    /**
     * Record a failed login attempt (create).
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 1.login -> step 9
     */
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

    /**
     * Get failed login attempts record.
     *
     * @return array|false Failed attempt row, or false if none
     *
     * @flow Documentation.txt section 1.login -> step 9
     */
    public function loginFaildAttempsR()
    {
        $this->conn->query("SELECT * FROM ". $this->users_pfailed_tbl." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }

    /**
     * Increment failed login attempt count.
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 1.login -> step 9
     */
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

    /**
     * Clear failed login attempts for user.
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 1.login -> step 7 (on success)
     */
    public function loginFaildAttempsD()
    {
        $this->conn->query("DELETE FROM ". $this->users_pfailed_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Record login user status change.
     *
     * @param string $notify_status Status code: PT=passed, PF=failed, R=reset, T=token-verified
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 1.login -> steps 7,8,12,13
     */
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

    /**
     * Get user login history (most recent first).
     *
     * @return array|false Login history row, or false if none
     *
     * @flow Documentation.txt section 1.login
     */
    public function loginUserStatusR()
    {
        $this->conn->query("SELECT * FROM ".$this->users_lhistory_tbl." WHERE user_id = :user_id order by create_date desc;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }

    /**
     * Clear user login history.
     *
     * @return bool True on success, false on failure
     */
    public function loginUserStatusD()
    {
        $this->conn->query("DELETE FROM ".$this->users_lhistory_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Create OTP entry for user.
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 0.register -> step 8
     */
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

    /**
     * Get OTP record for user.
     *
     * @return array|false OTP row, or false if none
     */
    public function g2faCodeR()
    {
        $this->conn->query("SELECT * FROM ".$this->users_lcod_tble." WHERE SecretKey=:SecretKey and user_id = :user_id;");
        $this->conn->bind(':SecretKey', $this->SecretKey);
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }

    /**
     * Update OTP secret for user.
     *
     * @return bool True on success, false on failure
     */
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

    /**
     * Delete OTP entry for user.
     *
     * @return bool True on success, false on failure
     */
    public function g2faCodeD()
    {
        $this->conn->query("DELETE FROM ".$this->users_lcod_tble ." WHERE user_id= :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Record user action in history.
     *
     * @param string $action_status Action type code
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 1.login -> step 13
     */
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

    /**
     * Get user action history (most recent first).
     *
     * @return array|false Action history row, or false if none
     */
    public function userActionHistoryR()
    {
        $this->conn->query("SELECT * FROM ". $this->users_ahistory_tbl." WHERE user_id = :user_id order by create_date desc;");
        $this->conn->bind(':user_id', $this->user_id);
        return $this->conn->singleASS();
    }

    /**
     * Clear user action history.
     *
     * @return bool True on success, false on failure
     */
    public function userActionHistoryD()
    {
        $this->conn->query("DELETE FROM ". $this->users_ahistory_tbl ." WHERE user_id = :user_id;");
        $this->conn->bind(':user_id', $this->user_id);
        if ($this->conn->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Initiate password reset.
     *
     * Generates reset token and stores in users_password_reset table.
     * Token expires in 10 minutes.
     *
     * @return bool True on success, false on failure
     *
     * @flow Documentation.txt section 2.resetPassword -> step 5
     */
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

    /**
     * Check if reset token is valid and not expired.
     *
     * @return bool True if valid token exists, false otherwise
     *
     * @flow Documentation.txt section 3.checkResetToken -> step 4
     */
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

    /**
     * Get user by ID.
     *
     * @param int $id User primary key
     *
     * @return array|false User row, or false if not found
     *
     * @note Uses $this->db (may need $this->conn for consistency)
     */
    public function getUserById($id)
    {
        $this->db->query("SELECT * FROM users WHERE id = :id");
        $this->db->bind(':id', $id);
        $row = $this->db->singleASS();
        return $row;
    }
}