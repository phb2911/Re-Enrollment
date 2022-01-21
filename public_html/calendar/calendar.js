
// global vars

// date
var yr;
var mo;

var maxYear = 2099;
var minYear = 1970;

var isBuilt = false;
var isOpen = false;

var currTxtBox;

function getDate(year, month, day){
    
    currTxtBox.value = (day < 10 ? '0' + day : day) + '/' + (month < 10 ? '0' + month : month) + '/' + year;
    
    hideCalendar();
    
}

function showCalendar(txtBox, posTop, posLeft, year, month){
    
    // check if it is already open
    if (isOpen) return;
    
    // check if built
    if (!isBuilt) buildCalendar();
    
    // custom - select elements
    currTxtBox = txtBox;
    
    // if date passed is not valid
    // assing current date
    var re = /^([0-9])+$/;
    
    if (re.test(year) && re.test(month) && year >= minYear && year <= maxYear && month > 0 && month <= 12){
        
        yr = year;
        mo = month;
                
    }
    else {
        
        var d = new Date();
        
        yr = d.getFullYear();
        mo = d.getMonth() + 1;
        
    }
    
    setSelBoxes();
    populateCalendar();
    
    var cal = document.getElementById('calendar');
    
    cal.style.top = posTop + 'px';
    cal.style.left = posLeft + 'px';
    cal.style.visibility = 'visible';
    
    isOpen = true;
    
}

function hideCalendar(){
    document.getElementById('calendar').style.visibility = 'hidden';
    isOpen = false;
}

function CalendarIsOpen(){
    return isOpen;
}

function buildCalendar(){
    
    var cal;
    
    if (document.getElementById('calendar')){
        cal = document.getElementById('calendar')
    }
    else{
        cal = document.createElement('div');
        cal.id = 'calendar';
        cal.className = 'calendar';
        document.body.appendChild(cal);
    }
    
    //var mon = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var mon = ['01 - Jan', '02 - Fev', '03 - Mar', '04 - Abr', '05 - Mai', '06 - Jun', '07 - Jul', '08 - Ago', '09 - Set', '10 - Out', '11 - Nov', '12 - Dez'];
    var wd = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
    var sel;
    var opt;
    var cell = 0;
    var table;
    var tr;
    var td;
    var sp;
    var footer;
    
    // create header
    table = document.createElement('table');
    table.style.width = '100%';
    table.style.fontFamily = 'verdana';
    table.style.fontSize = '11px';
    tr = document.createElement('tr');
    
    // first cell
    td = document.createElement('td');
    sp = document.createElement('span');
    sp.style.fontWeight = 'bold';
    sp.style.cursor = 'pointer';
    sp.innerHTML = '&lt;';
    sp.onclick = function(){decMonth()};
    td.appendChild(sp);
    tr.appendChild(td);
    
    // second cell
    td = document.createElement('td');
    td.style.textAlign = 'right';
    td.style.width = "50%"
    
    // create month select
    sel = document.createElement('select');
    sel.id = 'calMonth';
    sel.style.fontFamily = 'verdana';
    sel.style.fontSize = '11px';
    sel.onchange = function(){changeMonth(this)};
    
    for (var s = 0; s < 12; s++){
        
        opt = document.createElement('option');
        opt.value = (s + 1);
        opt.innerHTML = mon[s];
        sel.appendChild(opt);
    
    }
    
    td.appendChild(sel);
    tr.appendChild(td);
    
    // third cell
    td = document.createElement('td');
    td.style.textAlign = 'left';
    td.style.width = '50%';
    
    // create select year
    sel = document.createElement('select');
    sel.id = 'calYear';
    sel.style.fontFamily = 'verdana';
    sel.style.fontSize = '11px';
    sel.onchange = function(){changeYear(this)};
    
    //for (var t = maxYear; t >= minYear; t--){
    for (var t = minYear; t <= maxYear; t++){
        
        opt = document.createElement('option');
        opt.value = t;
        opt.innerHTML = t;
        
        sel.appendChild(opt);
    
    }
    
    td.appendChild(sel);
    tr.appendChild(td);
    
    // fourth cell
    td = document.createElement('td');
    sp = document.createElement('span');
    sp.style.fontWeight = 'bold';
    sp.style.cursor = 'pointer';
    sp.innerHTML = '&gt;';
    sp.onclick = function(){incMonth()};
    td.appendChild(sp);
    tr.appendChild(td);
    
    table.appendChild(tr);
    cal.appendChild(table);
    
    // create body
    table = document.createElement('table');
    table.style.width = '100%';
    table.style.fontFamily = 'verdana';
    table.style.fontSize = '11px';

    for (var i = 0; i < 7; i++){

        tr = document.createElement('tr');

        for (var j = 0; j < 7; j++){
            td = document.createElement('td');
            td.style.padding = '3px';
                        
            // insert week days
            if (i == 0){
                td.innerHTML = wd[j];
                td.style.textAlign = 'center';
            }
            else {
                td.id = 'calCell' + cell;
                td.style.textAlign = 'right';
                cell++;
            }
            
            // red sunday
            if (j == 0){
                td.style.color = 'red';
            }
            
            tr.appendChild(td)
            
        }

        table.appendChild(tr);

    }
    
    cal.appendChild(table);
    
    // create footer
    footer = document.createElement('div');
    sp = document.createElement('sp');
    
    footer.style.width = '100%';
    footer.style.textAlign = 'center';
    
    sp.style.cursor = 'pointer';
    sp.style.fontSize = '11px';
    sp.style.fontFamily = 'verdana';
    sp.innerHTML = 'Close';
    sp.onclick = function(){hideCalendar()}
    
    footer.appendChild(sp);
    cal.appendChild(footer);
    
    isBuilt = true;

}

function changeYear(sel){
    yr = sel.options[sel.selectedIndex].value;
    populateCalendar();
    
}

function changeMonth(sel){
    mo = sel.options[sel.selectedIndex].value;
    populateCalendar();
}

function decMonth(){
    
    if (mo == 1 && yr > minYear){
        
        mo = 12;
        yr--;
        
        setSelBoxes();
        populateCalendar();
        
    }
    else if (mo > 1) {
        
        mo--;
        
        document.getElementById('calMonth').selectedIndex = mo - 1;
        
        populateCalendar();
        
    }
    
}

function incMonth(){
    
    if (mo == 12 && yr < maxYear){
        
        mo = 1;
        yr++;
        
        setSelBoxes();
        populateCalendar();
        
    }
    else if (mo < 12){
        
        mo++;
        
        document.getElementById('calMonth').selectedIndex = mo - 1;
        
        populateCalendar();
        
    }
    
}

function populateCalendar(){
    
    var today = new Date();
    var d = new Date(yr, mo - 1, 1, 0, 0, 0, 0);
    var monthLength = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
    // Adjust for leap years
    if(yr % 400 == 0 || (yr % 100 !== 0 && yr % 4 == 0))
        monthLength[1] = 29;
    
    var fc = d.getDay();
    var lc = fc + monthLength[mo - 1] - 1;
    var cday = 1;
    var cell;
    
    for (var i = 0; i < 42; i++){
        
        cell = document.getElementById('calCell' + i);
        
        cell.style.backgroundColor = '';
        
        if (i >= fc && i <= lc){
            
            // clear cell
            cell.innerHTML = '';
            
            // set class name
            cell.className = 'tdCalCel';
            
            // changes background color for today
            if (yr == today.getFullYear() && mo == today.getMonth() + 1 && cday == today.getDate()) cell.style.backgroundColor = '#e0e7f8';
            
            cell.innerHTML = cday;
            cell.onclick = function(){getDate(yr, mo, this.innerHTML)};
            
            cday++;
            
        }
        else {
            cell.className = '';
            cell.innerHTML = '';
        }
    }
    
}

function setSelBoxes(){
    
    document.getElementById('calMonth').selectedIndex = (mo - 1);
    var cyopt = document.getElementById('calYear').options;

    for (var i = 0; i < cyopt.length; i++){
        if (cyopt[i].value == yr){
            cyopt[i].selected = true;
            continue;
        }
    }
    
}

