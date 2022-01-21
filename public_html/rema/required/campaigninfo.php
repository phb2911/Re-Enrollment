<?php

require_once __DIR__ . '/../../genreq/genreq.php';

// this method gets the campaign ID stored in a cookie
// and retrieves its name and if it's open or not.
// if the cookie is not found or the campaign id is not valid,
// the open campaign information is fatched and returned.
// if no campaign is open, null is returned.
// returns array('ID', 'Name', 'Open')
function getCampaignInfo(&$db, $allCamp = null){
    
    // fetch campaign id from cookie
    $cid = getCookie('curCampId');
    
    // check if campaign ID is saved in cookie and retrieve info
    if (isNum($cid)){
        
        $cid = intval($cid, 10);
        
        // check if campaign array submitted
        if (isset($allCamp) && is_array($allCamp) && isset($allCamp[$cid])){
            $cInfo = $allCamp[$cid];
        }
        else { // get from db
            $cInfo = $db->query("SELECT ID, campaignName(ID) AS Name, `Open` FROM campaigns WHERE ID = $cid")->fetch_assoc();
        }
        
    } // get open campaing info
    elseif ($cInfo = $db->query("SELECT ID, campaignName(ID) AS Name, `Open` FROM campaigns WHERE Open = 1 LIMIT 1")->fetch_assoc()) {
        // save current campaign id into cookie
        setcookie('curCampId', $cInfo['ID'], 0, '/', COOKIE_DOMAIN); 
    }
    
    
    // return campaign info
    return $cInfo;
    
}

// this method fetches all campaigns, stores into an
// array and returns the array
// format: array[ID] = array(ID, Name, Open)
function allCampaigns(&$db){
    
    // fetch all campaigns and store into array
    $result = $db->query("SELECT ID, campaignName(ID) AS Name, Open FROM campaigns ORDER BY Name DESC");

    // $allCamp[ID] = array(ID, Name, Open)
    $allCamp = array();

    while ($row = $result->fetch_assoc()){
        $allCamp[intval($row['ID'], 10)] = $row;
    }

    $result->close();
    
    return $allCamp;
    
}

?>