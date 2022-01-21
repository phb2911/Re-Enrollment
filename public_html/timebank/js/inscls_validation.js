
/* this function is dependent on dateFunctions.js */

function inscls_validate(className, selSemIndex, date1, date2, date3, date4, selModVal, checkedDays){
    
    if (!className.trim().length){
        alert('Por favor digite o nome da turma.');
        return false;
    }
    
    if (selSemIndex == 0){
        alert('Por favor selecione o semestre.');
        return false;
    }
    
    /* trim dates */
    date1 = date1.trim();
    date2 = date2.trim();
    date3 = date3.trim();
    date4 = date4.trim();
    
    if (!isValidDate(date1)){
        alert('A data inicial do per�odo pago n�o � v�lida.');
        return false;
    }

    if (!isValidDate(date2)){
        alert('A data final do per�odo pago n�o � v�lida.');
        return false;
    }

    if (compareDates(date1, date2) > 0){
        alert('A data do in�cio do per�odo pago n�o pode ser posterior � data do t�rmino.');
        return false;
    }

    if (!isValidDate(date3)){
        alert('A data inicial do per�odo de aula n�o � v�lida.');
        return false;
    }

    if (!isValidDate(date4)){
        alert('A data final do per�odo de aula n�o � v�lida.');
        return false;
    }

    if (compareDates(date3, date4) > 0){
        alert('A data do in�cio do per�odo de aula n�o pode ser posterior � data do t�rmino.');
        return false;
    }

    if (parseInt(date1.split('/')[1], 10) == 1){
        alert('A data inicial do per�odo pago n�o pode ser no m�s de janeiro.');
        return false;
    }

    var d2spl = date2.split('/');

    if (parseInt(d2spl[1], 10) == 1 && parseInt(d2spl[0], 10) != 31){
        alert('Se a data final do per�odo pago ocorrer em janeiro, o dia 31 deve ser atribu�do.');
        return false;
    }
    
    if (!isRangeWithinRange(date1, date2, date3, date4)){
        alert('A o per�odo de aula deve estar dentro do per�odo pago.');
        return false;
    }

    if (parseInt(date4.split('/')[1], 10) == 1){
        alert('O per�odo de aula n�o pode se estender ao m�s de janeiro.');
        return false;
    }
    
    if (selModVal == 0){
        alert('Por favor selecione a modalidade.');
        return false;
    }
    else {
                
        var chkCount = 0;

        for (var i = 0; i < checkedDays.length; i++){
            if (checkedDays[i].checked) chkCount++;
        }

        // first mode requires 2 days
        if (selModVal == 1 && chkCount != 2){
            alert('Dois dias da semana devem ser selecionados de acordo com a modalidade escolhida.');
            return false;
        }

        // all others require one day
        if (selModVal > 1 && chkCount != 1){
            alert('Um dia da semana deve ser selecionado de acordo com a modalidade escolhida.');
            return false;
        }

    }

    return true;
    
}