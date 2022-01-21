<?php

// dependency: genreq/date_functions.php

function validateData($newName, $semester, $tbStartDate, $tbEndDate, $date1, $date2, $date3, $date4, $mode, $daysCount, &$msg){
    
    if (!strlen($newName)){
        $msg = 'O nome da turma n�o � v�lido.';
        return false;
    }
    
    if ($semester != 1 && $semester != 2){
        $msg = 'O semestre selecionado n�o � v�lido.';
        return false;
    }
    
    if (!isValidDate($date1)){
        $msg = 'A data inicial do per�odo pago n�o � v�lida.';
        return false;
    }
    
    if (!isValidDate($date2)){
        $msg = 'A data final do per�odo pago n�o � v�lida.';
        return false;
    }
    
    if (compareDates($date1, $date2) > 0){
        $msg = 'A data inicial do per�odo pago n�o pode ser posterior � data final.';
        return false;
    }
    
    if (!isValidDate($date3)){
        $msg = 'A data inicial do per�odo de aula n�o � v�lida.';
        return false;
    }
    
    if (!isValidDate($date4)){
        $msg = 'A data final do per�odo de aula n�o � v�lida.';
        return false;
    }
    
    if (compareDates($date3, $date4) > 0){
        $msg = 'A data inicial do per�odo de aula n�o pode ser posterior � data final.';
        return false;
    }
    
    if (intval(explode('/', $date1)[1], 10) == 1){
        $msg = 'A data inicial do per�odo pago n�o pode ser no m�s de janeiro.';
        return false;
    }
    
    $d2exp = explode('/', $date2);
    
    if (intval($d2exp[1], 10) == 1 && intval($d2exp[0], 10) != 31){
        $msg = 'Quando o per�odo pago for referente ao m�s de janeiro, o dia 31 deve ser atribu�do.';
        return false;
    }
    
    if (!isWithinPeriod($date1, $date2, $date3, $date4)){
        $msg = 'As datas do per�odo de aula devem estar dentro do per�odo pago.';
    }
    
    if (intval(explode('/', $date4)[1], 10) == 1){
        $msg = 'O per�odo pago n�o pode se estender ao m�s de janeiro.';
        return false;
    }
    
    if (!isWithinPeriod($tbStartDate, $tbEndDate, $date1, $date2)){
        $msg = 'As datas do per�odo pago devem estar entre \'' . $tbStartDate . '\' e \'' . $tbEndDate . '\'.';
        return false;
    }
    
    if (!isNum($mode) || $mode < 1 || $mode > 3){
        $msg = 'Por favor selecione a modalidade.';
        return false;
    }
    
    if ($mode == 1 && $daysCount != 2){
        $msg = 'Dois dias da semana devem ser selecionados de acordo com a modalidade escolhida.';
        return false;
    }
    
    if ($mode > 1 && $daysCount != 1){
        $msg = 'Um dia da semana deve ser selecionado de acordo com a modalidade escolhida.';
        return false;
    }
    
    return true;
    
}

?>