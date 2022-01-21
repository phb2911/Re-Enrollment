
function userSelected(sel, cid){
    
    // selected index > 0
    if (sel.selectedIndex){
    
        var frm = document.createElement('form');
        frm.method = 'post';
        frm.action = 'ppyedit.php';

        document.body.appendChild(frm);

        var hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'uid';
        hid.value = sel.selectedValue();

        frm.appendChild(hid);

        var hid2 = document.createElement('input');
        hid2.type = 'hidden';
        hid2.name = 'cid';
        hid2.value = cid;

        frm.appendChild(hid2);
        frm.submit();
    
    }
    
}

function removeRecord(uid, cid){
    
    if (confirm('Os dados referentes ao PPY deste professor serão removidos permanentemente. Deseja continuar?')){
        
        var frm = document.createElement('form');
        frm.method = 'post';
        frm.action = 'ppyedit.php';
        
        document.body.appendChild(frm);
        
        var hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'del';
        hid.value = uid;
        
        frm.appendChild(hid);
        
        var hid2 = document.createElement('input');
        hid2.type = 'hidden';
        hid2.name = 'uid';
        hid2.value = uid;
        
        frm.appendChild(hid2);
        
        var hid3 = document.createElement('input');
        hid3.type = 'hidden';
        hid3.name = 'cid';
        hid3.value = cid;
        
        frm.appendChild(hid3);
        frm.submit();
        
    }
    
}

function checkBoxClicked(){
    
    var checked = element('chkAxis1only').checked;
    
    // disable/enable axis 2 and 3
    disableInputControls(element('tblAxis2'), checked);
    disableInputControls(element('tblAxis3'), checked);
    
    if (checked){
        element('txtAxis2Points').value = '0';
        element('txtAxis3Points').value = '0';
    }
    else {
        element('txtAxis2Points').value = element('txtEx2Total').value;
        element('txtAxis3Points').value = element('txtEx3Total').value;
    }
    
}

function disableInputControls(element, disable){
                
    var i;
    
    if (element.tagName && (element.tagName.toLowerCase() == 'input' || element.tagName.toLowerCase() == 'select')) {
        element.disabled = disable;
    }

    if (element.childNodes && element.childNodes.length > 0){

        for (i = 0; i < element.childNodes.length; i++){
            disableInputControls(element.childNodes[i], disable);
        }

    }

}

function validateInput(){
    
    var relSug = element('txtRelSug').value.trim();
    var numCls = element('txtNumCls').value.trim();
    var pedCont = element('txtPedCont').value.trim();
    var otherAct = element('txtOtherAct').value.trim();
    var misMeet = element('txtMissedMeeting').value.trim();
    var partMeet = element('txtPartMeeting').value.trim();
    var lateRoll = element('txtLateRollCall').value.trim();
    var lateRpt = element('txtLateRptCard').value.trim();
    var msgFail = element('txtMsgFail').value.trim();
    var missClass = element('txtMissClass').value.trim();
    
    if (relSug.length && (isNaN(relSug) || relSug > 6 || relSug < 0.5)){
        element('txtRelSug').style.backgroundColor = '#ffb5a8';
        element('txtRelSug').focus();
        alert('O valor da sugestão relevante implementada pela escola deve ser entre 0.5 e 6.');
        return false;
    }
    
    if (!/^([0-9])+$/.test(numCls)){
        element('txtNumCls').style.backgroundColor = '#ffb5a8';
        element('txtNumCls').focus();
        alert('O número de turmas é inválido.');
        return false;
    }
    
    if (pedCont.length && (isNaN(pedCont) || pedCont > 5 || pedCont < 2)){
        element('txtPedCont').style.backgroundColor = '#ffb5a8';
        element('txtPedCont').focus();
        alert('O valor da contribuição formal em reuniões pedagógicas deve ser entre 2 e 5.');
        return false;
    }
    
    if (otherAct.length && (isNaN(otherAct) || otherAct > 4 || otherAct < 0.5)){
        element('txtOtherAct').style.backgroundColor = '#ffb5a8';
        element('txtOtherAct').focus();
        alert('O valor do campo \'Outras atividades que caracterizam envolvimento\' deve ser entre 0.5 e 4.');
        return false;
    }
    
    if (misMeet.length && !/^([0-9])+$/.test(misMeet)){
        element('txtMissedMeeting').style.backgroundColor = '#ffb5a8';
        element('txtMissedMeeting').focus();
        alert('O número de faltas às reuniões pedagógicas não é válido.');
        return false;
    }
    
    if (partMeet.length && !/^([0-9])+$/.test(partMeet)){
        element('txtPartMeeting').style.backgroundColor = '#ffb5a8';
        element('txtPartMeeting').focus();
        alert('O número de atrasos/saídas cedo das reuniões pedagógicas não é válido.');
        return false;
    }
    
    if (lateRoll.length && !/^([0-9])+$/.test(lateRoll)){
        element('txtLateRollCall').style.backgroundColor = '#ffb5a8';
        element('txtLateRollCall').focus();
        alert('O número de caternetas do SGY preenchidas com atraso não é válido.');
        return false;
    }
    
    if (lateRpt.length && !/^([0-9])+$/.test(lateRpt)){
        element('txtLateRptCard').style.backgroundColor = '#ffb5a8';
        element('txtLateRptCard').focus();
        alert('O número de SIRs entregue com atraso não é válido.');
        return false;
    }
    
    if (msgFail.length && !/^([0-9])+$/.test(msgFail)){
        element('txtMsgFail').style.backgroundColor = '#ffb5a8';
        element('txtMsgFail').focus();
        alert('O número mensagens não é válido.');
        return false;
    }
    
    if (missClass.length && !/^([0-9])+$/.test(missClass)){
        element('txtMissClass').style.backgroundColor = '#ffb5a8';
        element('txtMissClass').focus();
        alert('O número de faltas à aula não é válido.');
        return false;
    }
    
    return true;
    
}

function calculateAxis1(){
            
    // get values from select objects
    var grad = selectedValue('selGrad');
    var intExp = selectedValue('selIntExp');
    var prof = selectedValue('selProf');
    var total = 0;

    if (grad == 1 || grad == 3){
        total += 15;
        element('txtGradPoints').value = '15';
    }
    else if (grad == 2){
        total += 5;
        element('txtGradPoints').value = '5';
    }
    else {
        element('txtGradPoints').value = '';
    }

    if (intExp == 1){
        total += 5;
        element('txtIntExpPoints').value = '5';
    }
    else if (intExp == 2){
        total += 10;
        element('txtIntExpPoints').value = '10';
    }
    else {
        element('txtIntExpPoints').value = '';
    }

    if (prof == 1){
        total += 5;
        element('txtProfPoints').value = '5';
    }
    else if (prof == 2){
        total += 10;
        element('txtProfPoints').value = '10';
    }
    else {
        element('txtProfPoints').value = '';
    }

    // ---------------
    if (total > 30) total = 30;
    
    element('txtEx1Total').value = total;
    element('txtAxis1Points').value = total;
    
}

function calculateAxis2(){
    
    var total = 0;
    var pedPrac = parseInt(selectedValue('selPedPrac')) * 2; // 2 points each
    var relSug = element('txtRelSug').value.trim();
    var numCls = element('txtNumCls').value.trim();
    var refStd = parseInt(selectedValue('selRefStd')) * 3; // 3 points each
    var pedCont = element('txtPedCont').value.trim();
    var otherAct = element('txtOtherAct').value.trim();
    
    if (element('chkYilts').checked){
        total += 10;
        element('txtYiltsPoints').value = '10';
    }
    else element('txtYiltsPoints').value = '';
    
    // pedagogical practice
    if (pedPrac){
        element('txtPedPracPoints').value = pedPrac;
        total += pedPrac;
    }
    else element('txtPedPracPoints').value = '';
    
    // relevant sugestion
    if (relSug.length){
        
        if (isNaN(relSug) || relSug > 6 || relSug < 0.5){
            element('txtRelSug').style.backgroundColor = '#ffb5a8';
            element('txtRelSugPoints').value = '';
        }
        else {
            total += parseFloat(relSug);
            element('txtRelSugPoints').value = parseFloat(relSug);
        }
        
    }
    else {
        element('txtRelSug').style.backgroundColor = '';
        element('txtRelSugPoints').value = '';
    }
    
    // citizenship campaign
    if (element('chkCitzCamp').checked){
        total += 5;
        element('txtCitzCampPoints').value = '5';
    }
    else element('txtCitzCampPoints').value = '';
    
    // cultural/pedagogical events
    if (element('chkCultEvents').checked){
        total += 5;
        element('txtCultEventsPoints').value = '5';
    }
    else element('txtCultEventsPoints').value = '';
    
    // number of classes
    if (numCls.length && /^([0-9])+$/.test(numCls)){
        
        var iNumCls = parseInt(numCls, 10);

        if (iNumCls == 5) {
            total += 6;
            element('txtNumClsPoints').value = '6';
        }
        else if (iNumCls == 6 || iNumCls == 7) {
            total += 10;
            element('txtNumClsPoints').value = '10';
        }
        else if (iNumCls >= 8) {
            total += 15;
            element('txtNumClsPoints').value = '15';
        }
        else {
            element('txtNumClsPoints').value = '';
        }

        element('txtNumCls2').value = iNumCls;
        element('txtClasses').value = iNumCls;
            
    }
    else {
        element('txtNumCls').style.backgroundColor = '#ffb5a8';
        element('txtNumClsPoints').value = '';
        element('txtNumCls2').value = '';
        element('txtClasses').value = '';
    }
    
    // refered student
    if (refStd){
        element('txtRefStdPoints').value = refStd;
        total += refStd;
    }
    else element('txtRefStdPoints').value = '';
    
    // pedagogical meeting contribution
    if (pedCont.length){
        
        if (isNaN(pedCont) || pedCont > 5 || pedCont < 2){
            element('txtPedCont').style.backgroundColor = '#ffb5a8';
            element('txtPedContPoints').value = '';
        }
        else {
            total += parseFloat(pedCont);
            element('txtPedContPoints').value = parseFloat(pedCont);
        }
        
    }
    else {
        element('txtPedCont').style.backgroundColor = '';
        element('txtPedContPoints').value = '';
    }
    
    // other activities
    if (otherAct.length){
        
        if (isNaN(otherAct) || otherAct > 4 || otherAct < 0.5){
            element('txtOtherAct').style.backgroundColor = '#ffb5a8';
            element('txtOtherActPoints').value = '';
        }
        else {
            total += parseFloat(otherAct);
            element('txtOtherActPoints').value = parseFloat(otherAct);
        }
        
    }
    else {
        element('txtOtherAct').style.backgroundColor = '';
        element('txtOtherActPoints').value = '';
    }
    
    // -------------- 
    if (total > 30) total = 30;
    element('txtEx2Total').value = total;
    element('txtAxis2Points').value = total;
    
}

function calculateAxis3(){
    
    var total = 0;
    var rema = parseInt(selectedValue('selRema'));
    var avStd = parseInt(selectedValue('selAverageStds'));
    
    switch (rema){
        case 1:
            element('txtRemaPoints').value = '5';
            total += 5;
            break;
        case 2:
            element('txtRemaPoints').value = '10';
            total += 10;
            break;
        case 3:
            element('txtRemaPoints').value = '20';
            total += 20;
            break;
        case 4:
            element('txtRemaPoints').value = '30';
            total += 30;
            break;
        default:
            element('txtRemaPoints').value = '';
    }
    
    switch (avStd){
        case 1:
            element('txtAverageStdsPoints').value = '5';
            total += 5;
            break;
        case 2:
            element('txtAverageStdsPoints').value = '15';
            total += 15;
            break;
        case 3:
            element('txtAverageStdsPoints').value = '20';
            total += 20;
            break;
        default:
            element('txtAverageStdsPoints').value = '';
    }
    
    // -------------- 
    if (total > 40) total = 40;
    element('txtEx3Total').value = total;
    element('txtAxis3Points').value = total;
    
}

function calculateNegative(){
    
    var total = 0;
    var misMeet = element('txtMissedMeeting').value.trim();
    var partMeet = element('txtPartMeeting').value.trim();
    var lateRoll = element('txtLateRollCall').value.trim();
    var lateRpt = element('txtLateRptCard').value.trim();
    var msgFail = element('txtMsgFail').value.trim();
    var missClass = element('txtMissClass').value.trim();
    
    // missed meeting
    if (misMeet.length){
        
        if (/^([0-9])+$/.test(misMeet)){
            total += parseInt(misMeet) * 2; // 2 points each
            element('txtMissedMeetingPoints').value = parseInt(misMeet) * 2;
        }
        else {
            element('txtMissedMeeting').style.backgroundColor = '#ffb5a8';
            element('txtMissedMeetingPoints').value = '';
            
        }
        
    }
    else {
        element('txtMissedMeeting').style.backgroundColor = '';
        element('txtMissedMeetingPoints').value = '';
    }
    
    // partial meeting
    if (partMeet.length){
        
        if (/^([0-9])+$/.test(partMeet)){
            total += parseInt(partMeet) * 0.5; // 0.5 points each
            element('txtPartMeetingPoints').value = parseInt(partMeet) * 0.5;
        }
        else {
            element('txtPartMeeting').style.backgroundColor = '#ffb5a8';
            element('txtPartMeetingPoints').value = '';
            
        }
        
    }
    else {
        element('txtPartMeeting').style.backgroundColor = '';
        element('txtPartMeetingPoints').value = '';
    }
    
    // late roll call
    if (lateRoll.length){
        
        if (/^([0-9])+$/.test(lateRoll)){
            total += parseInt(lateRoll) * 0.5; // 0.5 points each
            element('txtLateRollCallPoints').value = parseInt(lateRoll) * 0.5;
        }
        else {
            element('txtLateRollCall').style.backgroundColor = '#ffb5a8';
            element('txtLateRollCallPoints').value = '';
            
        }
        
    }
    else {
        element('txtLateRollCall').style.backgroundColor = '';
        element('txtLateRollCallPoints').value = '';
    }
    
    // late report cards (SIR)
    if (lateRpt.length){
        
        if (/^([0-9])+$/.test(lateRpt)){
            total += parseInt(lateRpt) * 0.5; // 0.5 points each
            element('txtLateRptCardPoints').value = parseInt(lateRpt) * 0.5;
        }
        else {
            element('txtLateRptCard').style.backgroundColor = '#ffb5a8';
            element('txtLateRptCardPoints').value = '';
            
        }
        
    }
    else {
        element('txtLateRptCard').style.backgroundColor = '';
        element('txtLateRptCardPoints').value = '';
    }
    
    // failure to submit messages
    if (msgFail.length){
        
        if (/^([0-9])+$/.test(msgFail)){
            total += parseInt(msgFail) * 0.5; // 0.5 points each
            element('txtMsgFailPoints').value = parseInt(msgFail) * 0.5;
        }
        else {
            element('txtMsgFail').style.backgroundColor = '#ffb5a8';
            element('txtMsgFailPoints').value = '';
            
        }
        
    }
    else {
        element('txtMsgFail').style.backgroundColor = '';
        element('txtMsgFailPoints').value = '';
    }
    
    // classes missed
    if (missClass.length){
        
        if (/^([0-9])+$/.test(missClass)){
            total += parseInt(missClass) * 2; // 2 points each
            element('txtMissClassPoints').value = parseInt(missClass) * 2;
        }
        else {
            element('txtMissClass').style.backgroundColor = '#ffb5a8';
            element('txtMissClassPoints').value = '';
            
        }
        
    }
    else {
        element('txtMissClass').style.backgroundColor = '';
        element('txtMissClassPoints').value = '';
    }
    
    // -------------- 
    element('txtNegativeTotal').value = total;
    element('txtNegativePoints').value = total;
    
}


