// old browser do not have .trim()
if (!String.prototype.trim) {
    (function() {
        // Make sure we trim BOM and NBSP
        String.prototype.trim = function() {
            return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
        };
    })();
}

// converts some characters (&<>"') from a string into HTML entities
String.prototype.htmlEntities = function() {
    return this.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
};

// convert html entities into characters (&<>"')
String.prototype.revertHtmlEntities = function() {
    return this.replace(/&amp;/g, "&").replace(/&gt;/g, ">").replace(/&lt;/g, "<").replace(/&quot;|&#34;/g, "\"").replace(/&#039;/g, "'");
};

// converts a new line to a <BR> html element
String.prototype.nl2br = function(){
    return this.replace(/(\r\n|\n|\r)/g, "<br />");
};

// delete itself
Object.prototype.kill = function(){
    this.parentNode.removeChild(this);
};

// remove all children nodes
Object.prototype.clearChildren = function(){
    while (this.hasChildNodes()) {
        this.removeChild(this.lastChild);
    }
};

function element(id){
    return document.getElementById(id);
}

function selectedValue(el){
    // the parameter may be the select box id or the select box itself.
    
    var sel;
    
    if (typeof el == 'string') sel = element(el);
    else if (typeof el == 'object') sel = el;
    else return;
    
    if (sel.nodeName.toLowerCase() !== 'select') return; // not a select element
    
    return sel.options[sel.selectedIndex].value;
            
}

Object.prototype.selectedValue = function(){
    // returns the selected value from a select element
    
    if (this.nodeName.toLowerCase() !== 'select') return; // not a select element
    
    return this.options[this.selectedIndex].value;
    
};

Object.prototype.selectedText = function(){
    // returns the selected text from a select element
    
    if (this.nodeName.toLowerCase() !== 'select') return; // not a select element
    
    return this.options[this.selectedIndex].text;
    
};

function xmlhttpobj(){
    //                               code for IE6, IE5                        code for IE7+, Firefox, Chrome, Opera, Safari
    return (!window.XMLHttpRequest ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest());

}

function styleSelectBox(sel){
    sel.style.fontStyle = (sel.selectedIndex === 0 ? 'italic' : 'normal');
}

Object.prototype.styleOption = function (){
    if (this.nodeName.toLowerCase() === 'select') styleSelectBox(this);
};

function initializeSelect(sel){
    
    for (var i = 0; i < sel.options.length; i++){
        sel.options[i].style.fontStyle = (i === 0 ? 'italic' : 'normal');
    }
    
    sel.onchange = function(){styleSelectBox(this);};
    sel.onkeyup = function(){styleSelectBox(this);};

    styleSelectBox(sel);
    
}

Object.prototype.initializeSelect = function (){
    if (this.nodeName.toLowerCase() === 'select') initializeSelect(this);
};

function initializeAllSelect(){

    // style all select elements and add the events
    var sels = document.getElementsByTagName('select');

    for (var i = 0; i < sels.length; i++){
        initializeSelect(sels[i]);
    }

}

// converts some characters (&<>"') from a string into HTML entities
function conv(str){
    return str.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// convert html entities into characters (&<>"')
function revert(str){
    return str.replace(/&amp;/g, "&").replace(/&gt;/g, ">").replace(/&lt;/g, "<").replace(/&quot;/g, "\"").replace(/&#039;/g, "'");
}

// scroll up arrow
var myTimer;
    
function scrollUp(){

    var pos = window.pageYOffset - 15;

    if (pos < 0) pos = 0;

    window.scrollTo(0, pos);

    if (pos == 0) clearInterval(myTimer);

}

function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

// gets the absolute position of an element
function getAbsPosition(el){
            
    var el2 = el;
    var curtop = 0;
    var curleft = 0;

    if (document.getElementById || document.all) {

        do  {

            curleft += el.offsetLeft-el.scrollLeft;
            curtop += el.offsetTop-el.scrollTop;
            el = el.offsetParent;
            el2 = el2.parentNode;

            while (el2 != el) {
                curleft -= el2.scrollLeft;
                curtop -= el2.scrollTop;
                el2 = el2.parentNode;
            }

        } while (el.offsetParent);

    }
    else if (document.layers) {
        curtop += el.y;
        curleft += el.x;
    }

    return [curtop, curleft];

}
