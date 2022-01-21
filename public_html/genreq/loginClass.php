<?php

/*
 * Login Class
 * 
 * Creating a Login object:
 * 
 * $obj = new Login($db);
 *      $db = live mysqli database connection object
 * 
 * Methods:
 * 
 * doLogin($loginId, $password, [$adminLogin = false])
 * - Performs a login using info submitted by user
 * - Returns true if login is successful and false otherwise
 * - If login not successful, public variable $error is set with error info
 *      $loginId = user's login ID
 *      $password = user's password
 *      $adminLogin = if set to true, performs a login only if user is an admin
 * 
 * isLoggedIn([$adminOnly = false])
 * - Returns true if user is logged in, false otherwise
 *      $adminOnly = validate login only if user is an admin
 * 
 * doLogOut()
 * - Performs a logout
 * 
 * isAdmin()
 * - Returns true if user is an admin, false otherwise
 * - This method is dependent on the user status which is set only 
 *   after doLogin() or isLoggedIn() is called, before that, false is returned.
 * 
 * Accessible variables:
 * 
 * $userId
 * - Stores the user id.
 * - The user ID is set only after doLogin() or isLoggedIn() is called, before
 *   that, a null value is set.
 * 
 * $status
 * - stores the user status as an integer value.
 * - The user status is set only after doLogin() or isLoggedIn() is called, before
 *   that, a null value is returned.
 * 
 * $error
 * - Stores information about faild login after method doLogin() is called.
 * 
 */

define('LOGIN_SESSION_LENGTH', 120); // in minutes
define('LOGIN_TOKEN_SIZE', 26);

class Login {
    
    private $_db;
    private $userId;
    private $status;
    private $error;
    
    // constructor must receive a live mysqli connection
    function __construct($db){
        $this->_db = $db;
    }
    
    // use the magic method __get() to allow access
    // to private variables without allowing them to
    // be modified
    function __get($name) {
        return $this->$name;
    }
    
    public function isAdmin(){
        return (isset($this->status) && $this->status > 0);
    }
    
    public function doLogin($loginId, $password, $adminLogin = false){
        
        // check if user id and password have characters
        if (!isset($loginId) || strlen($loginId) === 0 || !isset($password) || strlen($password) === 0){
            $this->error = 'Login ID ou Senha invalida.';
            return false;
        }
        
        // build querey string
        $q = "SELECT ID, Password, Salt, Status, TimeLocked, Blocked FROM users WHERE LoginID = '" . $this->_db->real_escape_string($loginId) . "'";
        
        // check if admin login only
        if ($adminLogin){
            // only user where status > 0 (admin)
            $q .= " AND Status > 0";
        }
        
        // retrieve info from db
        if ($row = $this->_db->query($q)->fetch_assoc()){
            $uid = $row['ID'];
            $db_pwd = $row['Password'];
            $salt = $row['Salt'];
            $status = $row['Status'];
            $timeLocked = intval($row['TimeLocked'], 10);
            $blocked = !!$row['Blocked'];
        }
        else {
            // user id not found on DB
            $this->error = 'Login ID ou Senha invalida.';
            return false;
        }
        
        // verify if student is blocked
        if ($blocked){
            $this->error = "Login ID ou Senha invalida.";
            return false;
        }
        
        // verify if student is locked
        if ($timeLocked != 0 && $timeLocked > time()){
            $this->error = "Este usuário que você está tentando utilizar está bloqueada por 5 minutos.";
            return false;
        }
        elseif ($timeLocked != 0){
            // time locked has subsided.
            $this->_db->query("UPDATE users SET TimeLocked = 0 WHERE ID = $uid");
        }
        
        // use salt from db to create hash
        $hash = hash('sha512', $password . $salt);
        
        // if passwords don't match, register failed attempt.
        // this will create one cookie for each user based on the sutudent id.
        if($db_pwd != $hash){

            $this->error = "Login ID ou Senha invalida.";

            // verify number of failed attempts
            if (isset($_COOKIE['TimeLocked' . $uid])){

                $tries = intval($_COOKIE['TimeLocked' . $uid], 10);

                // if 5 faild attempts, lock user for 5 min
                if ($tries >= 4){

                    // clear faild attempts count
                    setcookie('TimeLocked' . $uid, 0, 0, "/", COOKIE_DOMAIN);

                    // register lock on DB
                    $this->_db->query("UPDATE users SET TimeLocked = " . (time() + 300) . " WHERE ID = $uid");

                    $this->error = "Este usuário que você está tentando utilizar está bloqueada por 5 minutos.";

                }
                else {
                    // increment number of faild attempts
                    setcookie('TimeLocked' . $uid, ($tries + 1), 0, "/", COOKIE_DOMAIN);
                }

            }
            else {
                // create first faild attempt cookie
                setcookie('TimeLocked' . $uid, 1, 0, "/", COOKIE_DOMAIN);
            }

            return false;

        }
        
        // create random token
        $token = $this->createToken();
        
        // set expiration value
        $expiration = $this->tokenExpiration();
        
        // write token into db
        if (!$this->_db->query("INSERT INTO tokens (Token, UserID, IP_Address, Expiration) VALUES ('$token', $uid, '" . $this->_db->real_escape_string($_SERVER['REMOTE_ADDR']) . "', $expiration)")) {
            // error writing token into DB
            $this->error = 'Error: ' . $this->_db->error;
            return false;
        }
        
        // record access into db
        if (!$this->_db->query("INSERT INTO access_log (UserID, DateTime) VALUES ($uid, " . time() . ")")){
            // error recording access into DB
            $this->error = 'Error: ' . $this->_db->error;
            return false;
        }
        
        // clear failed login attempts count
        setcookie('TimeLocked' . $uid, 0, 0, "/", COOKIE_DOMAIN);

        // create session cookies
        setcookie('token', $token, 0, "/", COOKIE_DOMAIN);
        setcookie('userId', $uid, 0, "/", COOKIE_DOMAIN);
        
        // set global variables
        $this->userId = intval($uid, 10);
        $this->status = intval($status, 10);
        
        return true;
        
    }
    
    public function isLoggedIn($adminOnly = false){
        
        // delete expired tokens
        $this->_db->query("DELETE FROM tokens WHERE Expiration < " . time());
        
        // retrieve info from cookies
        $token = isset($_COOKIE['token']) ? $_COOKIE['token'] : null;
        $uid = isset($_COOKIE['userId']) ? $_COOKIE['userId'] : null;
        
        // validate cookies
        if (strlen($token) == LOGIN_TOKEN_SIZE && preg_match('/^([0-9A-Za-z])+$/', $token) && preg_match('/^([0-9])+$/', $uid)){
        
            // build query string
            $q = "SELECT users.Blocked, users.Status FROM users JOIN tokens ON users.ID = tokens.UserID WHERE users.ID = $uid AND " .
                    "tokens.Token = '" . $this->_db->real_escape_string($token) . "'";
            
            // concatenating the line below to the query string will include IP address in the validation
            //$q .= " AND tokens.IP_Address = '" . $this->_db->real_escape_string($_SERVER['REMOTE_ADDR']) . "'";
            
            // admin only
            if ($adminOnly){
                $q .= " AND users.Status > 0";
            }
            
            // fetch info from DB
            if ($row = $this->_db->query($q)->fetch_assoc()){
                
                //check if user is blocked
                if (!$row['Blocked']){
                
                    // set expiration value
                    $expiration = $this->tokenExpiration();
                    
                    // reset token expiration
                    $this->_db->query("UPDATE tokens SET Expiration = $expiration WHERE Token = '" . $this->_db->real_escape_string($token) . "'");

                    // set global variables
                    $this->userId = intval($uid, 10);
                    $this->status = intval($row['Status'], 10);
                    
                    return true;
                
                }
                
            }
            
        }

        // erase cookies
        setcookie("token", "", time() - 3600, "/", COOKIE_DOMAIN);
        setcookie("userId", "", time() - 3600, "/", COOKIE_DOMAIN);
        
        return false;
        
    }
    
    public function doLogOut(){
    
        if (isset($_COOKIE['token'])){

            // get token from cookie
            $token = $_COOKIE['token'];

            // remove from db
            $this->_db->query("DELETE FROM tokens WHERE Token = '" . $this->_db->real_escape_string($token) . "'");
            
            // erase login cookies
            setcookie("token", "", time() - 3600, "/", COOKIE_DOMAIN);
            setcookie("userId", "", time() - 3600, "/", COOKIE_DOMAIN);
            
            // erase campaign cookie from rema
            setcookie('curCampId', '', time() - 3600, '/', COOKIE_DOMAIN);
            
            // erase semester cookie from talk session
            setcookie('curSemId', '', time() - 3600, '/', COOKIE_DOMAIN);

        }

    }
    
    // this function creates a string containing random
    // characters. the characters can be numbers (0-9),
    // appercase letters (A-Z) or lowercase letters (a-z).
    private function createToken(){
        
        // loop back if tooken found and recreate it
        do {
            
            $token = '';
            
            for ($i = 0; $i < LOGIN_TOKEN_SIZE; $i++){
                
                $rd = mt_rand(48, 109);     // generate random ascii code
                if ($rd > 57) $rd += 7;     // add seven to upper chars
                if ($rd > 90) $rd += 6;     // add six to lower chars
                
                $token .= chr($rd); // convert ascii code to character
                
            }
            
        } while (!!$this->_db->query("SELECT COUNT(*) FROM tokens WHERE Token = '" . $token . "'")->fetch_row()[0]);

        return $token;
        
    }
    
    private function tokenExpiration(){
        // convert length from minuts to sencds by multiplying it by 60
        // then add the result to current time.
        return time() + (LOGIN_SESSION_LENGTH * 60);
    }
    
}

?>