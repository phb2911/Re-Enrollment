// this script is dependent on general.js

function hideBoxes(){
    
    element('spSelectReason').style.visibility = 'hidden';
    element('spYrCtr').style.visibility = 'hidden';
    element('imgEditLoader').style.visibility = 'hidden';
    element('spFlagErrMsg').style.visibility = 'hidden';
    element('imgFlagEditLoader').style.visibility = 'hidden';
    element('imgUnflagEditLoader').style.visibility = 'hidden';
    
    element('modifyStatusBox').style.opacity = '0';
    element('flagBox').style.opacity = '0';
    element('unflagBox').style.opacity = '0';
    element('overlay').style.opacity = '0';
    element('overlay').style.visibility = 'hidden';
    element('modifyStatusBox').style.visibility = 'hidden';
    element('flagBox').style.visibility = 'hidden';
    element('unflagBox').style.visibility = 'hidden';
    
}

function closeErrorBox(){
    element('overlay').style.opacity = '0';
    element('errorBox').style.opacity = '0';
    element('errorBox').style.visibility = 'hidden';
    element('overlay').style.visibility = 'hidden';
}

function openErrorBox(msg){
    element('spErrorMsg').innerHTML = msg;
    element('overlay').style.visibility = 'visible';
    element('errorBox').style.visibility = 'visible';
    element('overlay').style.opacity = '0.6';
    element('errorBox').style.opacity = '1';
}

function changeElementsStatus(disable){
    element('selEditStatus').disabled = disable;
    element('selEditReason').disabled = disable;
    element('txtEditNotes').disabled = disable;
    element('btnSave').disabled = disable;
    element('btnCancel').disabled = disable;
}

function changeFlagElStatus(disable){
    element('txtFlagNotes').disabled = disable;
    element('btnFlagSave').disabled = disable;
    element('btnFlagCancel').disabled = disable;
}

function showFlagBox(sid){

    // request student info through ajax
    element('overlay').style.visibility = 'visible';
    element('loaderBox').style.visibility = 'visible';
    element('overlay').style.opacity = '0.6';
    element('loaderBox').style.opacity = '1';
    element('hidFlagStdId').value = sid;
    
    var xmlhttp = xmlhttpobj();

    xmlhttp.onreadystatechange = function() {

        // request ready
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            element('loaderBox').style.opacity = '0';
            element('loaderBox').style.visibility = 'hidden';

            var obj = JSON.parse(xmlhttp.responseText);

            if (obj.Error){
                openErrorBox(atob(obj.Error));
            }
            else if (obj.Flagged == 'MQ==') { // 'MQ==' base64 encoded '1'

                // add values to edit box elements - base64 decode
                element('txtUnflagName').value = atob(obj.Name);
                element('txtUnflagTeacher').value = atob(obj.Teacher);
                element('txtUnflagClass').value = atob(obj.Class) + ' (' + atob(obj.Unit) + ')';
                // base64 decode, converts special chars in html entities and new line into <br/>
                element('divFlagDetails').innerHTML = atob(obj.FlagNotes).htmlEntities().nl2br(); 

                // enable buttons
                element('btnUnflag').disabled = false;
                element('btnUnflagClose').disabled = false;

                element('unflagBox').style.visibility = 'visible';
                element('unflagBox').style.opacity = '1';

            }
            else {
                // add values to edit box elements - base64 decode
                element('txtFlagName').value = atob(obj.Name);
                element('txtFlagTeacher').value = atob(obj.Teacher);
                element('txtFlagClass').value = atob(obj.Class) + ' (' + atob(obj.Unit) + ')';

                // clear text area content
                element('txtFlagNotes').value = '';

                // enable elements
                changeFlagElStatus(false);

                // display box
                element('flagBox').style.visibility = 'visible';
                element('flagBox').style.opacity = '1';

            }

        }

    };

    // THIS WILL NOT STOP PAGE
    xmlhttp.open("POST", "aj_getstdinfo.php?" + Math.random(), true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("flag=1&sid=" + sid);

}

function showStatusBox(sid){

    // request student info through ajax
    element('overlay').style.visibility = 'visible';
    element('loaderBox').style.visibility = 'visible';
    element('overlay').style.opacity = '0.6';
    element('loaderBox').style.opacity = '1';
    element('hidStdId').value = sid;

    var xmlhttp = xmlhttpobj();

    xmlhttp.onreadystatechange = function() {

        // request ready
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            element('loaderBox').style.opacity = '0';
            element('loaderBox').style.visibility = 'hidden';

            var obj = JSON.parse(xmlhttp.responseText);

            if (obj.Error){
                openErrorBox(atob(obj.Error));
            }
            else {
                // add values to edit box elements - base64 decode
                element('txtEditName').value = atob(obj.Name);
                element('txtEditTeacher').value = atob(obj.Teacher);
                element('txtEditClass').value = atob(obj.Class) + ' (' + atob(obj.Unit) + ')';
                element('txtEditNotes').value = atob(obj.Notes);
                
                // check yearly contract box
                var yrCtr = (atob(obj.YearlyContract) == 1);
            
                element('chkYrCtr').checked = yrCtr;
                element('spYrCtr').style.visibility = (yrCtr ? 'visible' : 'hidden');
                
                // set status
                var status = atob(obj.Status);

                for (var i = 0; i < element('selEditStatus').options.length; i++){
                    if (element('selEditStatus').options[i].value == status){
                        element('selEditStatus').options[i].selected = true;
                        break;
                    }
                }

                // set reason
                element('selEditReason').selectedIndex = 0;

                if (obj.Reason){

                    var reason = atob(obj.Reason);

                    for (var i = 0; i < element('selEditReason').options.length; i++){
                        if (element('selEditReason').options[i].value == reason){
                            element('selEditReason').options[i].selected = true;
                            break;
                        }
                    }

                }

                // enable elements
                changeElementsStatus(false);
                
                // disable status select if yearly contract
                element('selEditStatus').disabled = yrCtr;

                // style select boxes
                styleStatusSelect();
                element('selEditReason').styleOption();

                // display box
                element('modifyStatusBox').style.visibility = 'visible';
                element('modifyStatusBox').style.opacity = '1';

            }

        }

    };

    // THIS WILL NOT STOP PAGE
    xmlhttp.open("POST", "aj_getstdinfo.php?" + Math.random(), true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("sid=" + sid);

}

function unflag(){

    var stdId = element('hidFlagStdId').value;

    // disable buttons
    element('btnUnflag').disabled = true;
    element('btnUnflagClose').disabled = true;

    // show loader
    element('imgUnflagEditLoader').style.visibility = 'visible';

    var xmlhttp = xmlhttpobj();

    xmlhttp.onreadystatechange = function() {

        // request ready
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            var obj = JSON.parse(xmlhttp.responseText);

            hideBoxes();

            if (obj.Error){
                openErrorBox(atob(obj.Error));
            }
            else {

               // hide flag icon
               element('imgFlag' + obj.StdId).style.visibility = 'hidden';

            }

        }

    };

    // THIS WILL NOT STOP PAGE
    xmlhttp.open("POST", "aj_saveflag.php?" + Math.random(), true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("unflag=1&sid=" + stdId);

}

function saveFlag(){

    var stdId = element('hidFlagStdId').value;
    var flagNotes = element('txtFlagNotes').value.trim();

    // validate notes
    if (flagNotes.length == 0){
        // display error message
        element('spFlagErrMsg').innerHTML = 'Por favor descreva o motivo.';
        element('spFlagErrMsg').style.visibility = 'visible';
        element('txtFlagNotes').focus();
        return;
    }
    else if (flagNotes.length > 500){
        // display error message
        element('spFlagErrMsg').innerHTML = 'Descrição com mais de 500 caracteres.';
        element('spFlagErrMsg').style.visibility = 'visible';
        element('txtFlagNotes').focus();
        return;
    }

    // encode notes
    // the base64 string contains some special characters that won't be
    // submitted correctly to the script via ajax, therefore it needs to be
    // converted to a URL safe string.
    var encNotes = encodeURIComponent(btoa(flagNotes));

    // disable elements
    changeFlagElStatus(true);

    // display loader
    element('imgFlagEditLoader').style.visibility = 'visible';

    var xmlhttp = xmlhttpobj();

    xmlhttp.onreadystatechange = function() {

        // request ready
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            var obj = JSON.parse(xmlhttp.responseText);

            hideBoxes();

            if (obj.Error){
                openErrorBox(atob(obj.Error));
            }
            else {

               // display flag icon
               element('imgFlag' + obj.StdId).style.visibility = 'visible';

            }

        }

    };

    // THIS WILL NOT STOP PAGE
    xmlhttp.open("POST", "aj_saveflag.php?" + Math.random(), true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("sid=" + stdId + "&notes=" + encNotes);

}

function saveEdit(){

    var stdId = element('hidStdId').value;
    var status = selectedValue('selEditStatus');
    var reason = "";
    var notes = element('txtEditNotes').value.trim();

    // validate notes
    if (notes.length > 2000){
        alert('As observações contém mais de 2000 caracteres.');
        return;
    }

    // the text from the text field must be base64 encoded.
    // the base64 string contains some special characters that won't be
    // submitted correctly to the script via ajax, therefore it needs to be
    // converted to a URL safe string.
    var encNotes = encodeURIComponent(btoa(notes));
    
    // validate reason
    if (status == 2){
        if (element('selEditReason').selectedIndex > 0){
            reason = selectedValue('selEditReason');
        }
        else {
            // reason not selected
            element('spSelectReason').style.visibility = 'visible';
            return;
        }
    }

    // disable elements
    changeElementsStatus(true);

    element('imgEditLoader').style.visibility = 'visible';

    var xmlhttp = xmlhttpobj();

    xmlhttp.onreadystatechange = function() {

        // request ready
        if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

            var obj = JSON.parse(xmlhttp.responseText);

            hideBoxes();

            if (obj.Error){
                openErrorBox(atob(obj.Error));
            }
            else {

               var id = obj.StdId;
               var reaVal = selectedValue('selEditStatus');
               var bg, st;

               // status
               if (reaVal == 0){
                   st = 'Não Contatado';
                   bg = 'yellow';
               }
               else if (reaVal == 1){
                   st = 'Contatado';
                   bg = 'orange';
               }
               else if (reaVal == 2){
                   st = 'Não Volta';
                   bg = 'red';
               }
               else {
                   st = 'Rematriculado';
                   bg = 'green';
               }

               element('divStatus' + id).style.backgroundColor = bg;
               element('divStatus' + id).innerHTML = st;

               // reasons
               if (reaVal != 2 || element('selEditReason').selectedIndex === 0){
                   element('spReason' + id).innerHTML = '';
               }
               else {
                   element('spReason' + id).innerHTML = element('selEditReason').selectedText().htmlEntities();
               }

               // insert notes
               element('spNotes' + id).innerHTML = element('txtEditNotes').value.trim().htmlEntities().nl2br();
               
               // if function exists, call it to update numbers
               if (typeof updateValues === 'function') updateValues();
               
            }

        }

    };

    // THIS WILL NOT STOP PAGE
    xmlhttp.open("POST", "aj_savestdinfo.php?" + Math.random(), true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("sid=" + stdId + "&status=" + status + "&reason=" + reason + "&notes=" + encNotes);

}
