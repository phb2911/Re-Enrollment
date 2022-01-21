<?php

date_default_timezone_set('America/Recife');

require_once '../../dbconn/dbconn.php';
require_once '../genreq/loginClass.php';

define('MAX_ROWS', 100);

$db = mysqliConnObj();

if ($db->connect_errno > 0) die("Unable to connect to database.");

// create login object
$loginObj = new Login($db);

if (!$loginObj->isLoggedIn(true)){
    $db->close();
    die('Access denied.');
}

header('Content-type: text/html; charset=iso-8859-1');
header('Content-Disposition: attachment; filename="PortalBackup_' . date('Y_m_d', time()) . '.sql"');

// record backup
$db->query('INSERT INTO backup_log (Date) VALUES (' . time() . ');');

// a three dimensional array containing
// the current database structure.
// format: array(table_name => array(column_name, 0/1))
// 0 if value doesn't need quotes and 1 if it does
$dbStructure = generateDbStructure();

echo '# Rematrícula Database Backup for MySQL' . PHP_EOL . '# Created on: ' . date('Y-m-d H:i:s', time()) . PHP_EOL . PHP_EOL . 'SET FOREIGN_KEY_CHECKS=0;' . PHP_EOL . PHP_EOL;

foreach ($dbStructure as $table => $rowInfo) {
    
    $result = $db->query("SELECT * FROM `$table`");
    
    $cf = false;
    $numRows = $result->num_rows;
    
    if ($numRows){
        
        echo '#' . PHP_EOL . '# Data for the `' . $table . '` table' . PHP_EOL . '#' . PHP_EOL . PHP_EOL;
        
        $count = 0;
                
        while ($row = $result->fetch_assoc()){
            
            if ($count == 0){
                
                echo 'INSERT INTO `' . $table . '` (';
                
                foreach ($row as $key => $value){
                    echo ($cf ? ',' : '') . "`$key`";
                    $cf = true;
                }
                
                echo ') VALUES ' . PHP_EOL;
                
                $cf = false;
                
            }
            
            echo '(';
            
            foreach ($row as $key => $value){
                echo ($cf ? ',' : '') . (($value === NULL) ? 'NULL' : (($rowInfo[$key] ? '\'' : '') . $db->real_escape_string($value) . ($rowInfo[$key] ? '\'' : '')));
                $cf = true;
            }
            
            $count++;
            
            echo ')' . (($numRows == $count || $count == MAX_ROWS) ? ';' . PHP_EOL : ',') . PHP_EOL;
            
            if ($count == MAX_ROWS) {
                $count = 0;
                $numRows -= MAX_ROWS;
            }
            
            $cf = false;
            
        }
        
    }
    
    $result->close();
    
}

if (!isset($count)) echo '# The database is empty.' . PHP_EOL;
else echo '# End of File';

$db->close();

//----------------------------------------------------------

function generateDbStructure(){
    
    global $db;
    
    // retrieve table names
    $result = $db->query("SHOW TABLES");
    
    $tables = array();
    
    while ($row = $result->fetch_row()){
        $tables[] = $row[0];
    }
    
    $result->close();
    
    $struc = array();
    
    // retrieve table rows and types
    // 1 if type needs quotes, 0 otherwise
    foreach ($tables as $table){
        
        $struc[$table] = array();
        
        $result = $db->query("DESCRIBE `$table`");
        
        while ($row = $result->fetch_assoc()){
            
            $struc[$table][$row['Field']] = (
                strpos($row['Type'], 'tinyint') === 0 ||
                strpos($row['Type'], 'smallint') === 0 ||
                strpos($row['Type'], 'mediumint') === 0 ||
                strpos($row['Type'], 'int') === 0 ||
                strpos($row['Type'], 'float') === 0 ||
                strpos($row['Type'], 'double') === 0 ||
                strpos($row['Type'], 'decimal') === 0 
                ? 0 : 1
            );
            
        }
        
        $result->close();
        
    }
    
    return $struc;
    
}
