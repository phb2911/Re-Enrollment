<?php

// constants
define('LOGIN_PAGE', 'login.php' . (isset($_SERVER['REQUEST_URI']) ? '?redir=' . urlencode($_SERVER['REQUEST_URI']) : ''));
define('COOKIE_DOMAIN', (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), 'domain') !== false ? 'domain.com' : ''));
define('ROOT_DIR', (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), 'domain') !== false ? 'http://domain.com/' : 'http://localhost:88/'));
define('IMAGE_DIR', ROOT_DIR . 'images/');

// required scripts
require_once __DIR__ . '/../../dbconn/dbconn.php';
require_once __DIR__ . '/loginClass.php';
require_once __DIR__ . '/DropDownClass.php';

// --------------------------------------------------

// functions used to create a full domain according to the server
function getDomain($file){
    
    if (isset($_SERVER['SERVER_NAME']) && strpos(strtolower($_SERVER['SERVER_NAME']), 'domain') !== false){
        return 'http://domain.com/' . $file;
    }
    
    return 'http://localhost:88/' . $file;
    
}

function getImagePath($imgFile){
    return getDomain('images/' . $imgFile);
}

// --------------------------------------------------

// using the functions below prevents notice from PHP interpreter
function getPost($index){
    return isset($_POST[$index]) ? $_POST[$index] : null;
}

function getGet($index){
    return isset($_GET[$index]) ? $_GET[$index] : null;
}

function getCookie($index){
    return isset($_COOKIE[$index]) ? $_COOKIE[$index] : null;
}

function getSession($index){
    return isset($_SESSION[$index]) ? $_SESSION[$index] : null;
}

// --------------------------------------------------

// this function closes the database and redirects to an specific page
function closeDbAndGoTo(&$db, $path){
    
    $db->close();
    
    header("Location: " . $path);
    die();
    
}

// --------------------------------------------------

// this function validates the path to the page the script will redirect to
// note: the path is expected to have a slash as the first character
function validateRedir($path){
    
    // check if path exists
    if (!isset($path)) return false;
    
    // clear trailling spaces
    $path = trim($path);
    
    // check if path is empty
    if (!strlen($path)) return false;
    
    // get the root directory
    $root = $_SERVER['DOCUMENT_ROOT'];
    
    // remove forward slash from end of root dir
    if (substr($root, strlen($root) - 1) == '/'){
        $root = substr($root, 0, strlen($root) - 1);
    }
    
    // remove anything that comes after the first ?
    if (strpos($path, '?') !== false){
        $path = substr($path, 0, strpos($path, '?'));
    }
    
    // check if file or directory exists
    return file_exists($root . $path);
    
}

// --------------------------------------------------

// use this to clear stored results after a stored
// procedure or a multi query is executed.
function clearStoredResults(&$db){
    
    do {
        
        if ($res = $db->store_result()) {
            $res->free();
        }
        
    } while ($db->more_results() && $db->next_result());        

}

// --------------------------------------------------

// checks if string has numbers only
function isNum($str){
    return preg_match('/^([0-9])+$/', $str);
}

// --------------------------------------------------

// checks if email format is valid
function isValidEmail($email){
    $re = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';
    return preg_match($re, $email);
}

// --------------------------------------------------

// removes white spaces from the beginning and
// ending of a string and also replaces multiple
// spaces between words with a single space.
// 
// note: this procedure will also replace TABs and
// new lines with a single white space.
//
function sTrim($s){
    return preg_replace('/\s+/', ' ', trim($s));
}

// --------------------------------------------------

// this function replaces some special characters from string
// whith regular roman letters [a-z]
function replaceSpecialChars($str){
    
    $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ');
    $b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y');
  
    return str_replace($a, $b, $str);
    
}

// --------------------------------------------------

// this function creates a string containing random
// characters. the characters can be numbers (0-9),
// appercase letters (A-Z) or lowercase letters (a-z).
function createToken($size){
    
    $token = '';

    for ($i = 0; $i < $size; $i++){

        $rd = mt_rand(48, 109);     // generate random ascii code
        if ($rd > 57) $rd += 7;     // add seven to upper chars
        if ($rd > 90) $rd += 6;     // add six to lower chars

        $token .= chr($rd); // convert ascii code to character

    }

    return $token;

}

?>