<?php

function createFileName(){
    
    // creates a random file name based on current unix timestamp
    // and checks if file exists in upload folder
    do {
        $res = 'file' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT) . '_' . time();
    }
    while (file_exists(UPLOAD_DIR . $res));
    
    return $res;
    
}

//--------------------------------------------------

function getMimeType($filePath){
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $res = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    return $res;
    
}

//--------------------------------------------------

function removeOldFiles(){
    
    if ($handle = opendir(UPLOAD_DIR)) {

        while ($fileName = readdir($handle)) {
            if ($fileName != "." && $fileName != ".." && !is_dir(UPLOAD_DIR . $fileName)) {
                
                // remove extension
                $tempName = str_replace('.tmp', '', $fileName);
                
                // split file and get time of creation
                $fileParts = explode('_', $tempName);
                
                // check if $time is numeric and if 3 hours have passed (3h = 10800sec)
                if (isset($fileParts[1]) && isNum($fileParts[1]) && (intval($fileParts[1], 10) + 10800) < time()){
                    // delete old file
                    unlink(UPLOAD_DIR . $fileName);
                }
                
            }
        }

        closedir($handle);
        
    }
    
}

//--------------------------------------------------

function validateCategories($cats){
    
    // the category array must have
    // one element equals to 1 and 
    // one equals to 2
    
    if (!is_array($cats)) return false; // $cats must be an array
        
    $flag1 = 0;
    $flag2 = 0;

    foreach ($cats as $c){
        if ($c === '1') $flag1++;
        if ($c === '2') $flag2++;
    }

    return ($flag1 === 1 && $flag2 === 1);

}

//--------------------------------------------------

function HTML_Safe($str){
    
    $a = array('&', '<', '>', '"', "'");
    $b = array('&amp;', '&lt', '&gt;', '&quot;', '&#039;');
    
    return str_replace($a, $b, $str);
    
}

//--------------------------------------------------

function buildRedirPage($file, $tbId, $line, $cats){
    
    $redirPage = 'importclasses.php?step=3&f=' . $file . '&tb=' . $tbId . '&line=' . $line;
    
    if (is_array($cats)){
    
        foreach ($cats as $key => $val){
            if ($val == 1 || $val == 2){
                // brackets [] are url encoded
                $redirPage .= '&cats%5B' . $key . '%5D=' . $val;
            }
        }
    
    }
    
    return $redirPage;
    
}

?>