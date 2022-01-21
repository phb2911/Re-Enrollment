// function used to validate date
// date format: dd/mm/yyyy or d/m/yyyy
// returns true if valid, false otherwise

function isValidDate(dateString) {
    
    // First check the format
    if(!/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(dateString))
        return false;

    // Parse the date parts to integers
    var parts = dateString.split('/');
    var day = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10);
    var year = parseInt(parts[2], 10);

    // Check the ranges of month and year
    if(year < 1970 || year > 2099 || month == 0 || month > 12)
        return false;

    // Adjust for leap year on monthLength[1]
    var monthLength = [31, (year % 400 === 0 || (year % 100 !== 0 && year % 4 === 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    // Check the range of the day
    return day > 0 && day <= monthLength[month - 1];
    
}

// compares dates.
// date format: dd/mm/yyyy
// if date1 > date2 returns 1
// if date1 = date2 returns 0
// if date1 < date2 return -1
// date validation will not be made, isValidDate() must be used
// before values are submitted.

function compareDates(date1, date2){
    
    var parts1 = date1.split('/');
    var parts2 = date2.split('/');
    
    for (var i = 2; i >= 0; i--){
        if (parseInt(parts1[i], 10) > parseInt(parts2[i], 10)) return 1;
        if (parseInt(parts1[i], 10) < parseInt(parts2[i], 10)) return -1;
    }
    
    return 0;
    
}

// - This function will return true if the child dates are within
// the parent dates and false otherwise.
// - date format: dd/mm/yyyy
// - childDateEnd may be ommited
// - date validation will not be made, isValidDate() must be used
// before values are submitted.

function isRangeWithinRange(parentDateStart, parentDateEnd, childDateStart, childDateEnd){
    
    // parent start date cannot greater then parent end date
    if (compareDates(parentDateStart, parentDateEnd) === 1) return false;
    
    // check if child end date was passed
    if (typeof childDateEnd === 'undefined')
        return (compareDates(parentDateStart, childDateStart) <= 0 && compareDates(parentDateEnd, childDateStart) >= 0);
    
    // child start date cannot be greter then child end date
    if (compareDates(childDateStart, childDateEnd) === 1) return false;
    
    return (compareDates(parentDateStart, childDateStart) <= 0 && compareDates(parentDateEnd, childDateStart) >= 0 &&
            compareDates(parentDateStart, childDateEnd) <= 0 && compareDates(parentDateEnd, childDateEnd) >= 0);
    
}

/*
 * 
 * This function gets a textbox object as parameter,
 * extracts it's value, checks if the value is numbers
 * only and if it's 4 or less in length. It pads the
 * value with leading zeroes and adds a colon (:)
 * in the middle of the string, then it sets the
 * result as the textbox value.
 * 
 * E.g.: if the value of the textbox is 15,
 * it converts it to 00:15 and it sets it as the new
 * value of the textbox.
 * 
 * @param {textbox object} txtBox
 * @returns {undefined}
 */
function autoFormatTime(txtBox){
            
    var val = txtBox.value.trim();


    if (/^(\d)+$/.test(val) && val.length <= 4){

        // pad with zeros
        while (val.length < 4){
            val = '0' + val;
        }

        txtBox.value = val.substr(0, 2) + ':' + val.substr(2);

    }

}

/**
 * 
 * This function validates a time string in the format H:MM or HH:MM.
 * 
 * It returns false for MM > 59 or time = 00:00
 * 
 * @param {String} str The time string.
 * @returns {Boolean}
 */
function validateShortTimeStr(str){
    
    // trim time string
    str = String.prototype.trim ? str.trim() : str.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
    
    return (/^\d{1,2}\:\d{2}$/.test(str) && !/^0{1,2}\:0{2}$/.test(str) && str.split(':')[1] < 60);
    
}