<?php

// dependency: constant IMAGE_DIR from general.php

class DropDown{
    
    private $_menus;
    
    function __construct(){
        $this->_menus = array();
    }
    
    function addMenu($menu){
        
        // check if submenu is a DropDownMenu object
        if (!is_a($menu, 'DropDownMenu')) throw new Exception('Invalid menu.');
        
        $this->_menus[] = $menu;
        
    }
    
    function createAndAddMenu($text, $link = null, $confMsg = null){
        
        $this->addMenu(new DropDownMenu($text, $link, $confMsg));
        
    }
    
    // removes menu.
    // if $arg is type 'DropDownMenu', menu is removed, if
    // it is int, index is removed.
    // returns true if menu removed.
    // note: this function removes the first occurence of the
    // object. If more than one of the same object is added
    // to the collection, the one with the lower index will be removed.
    function removeMenu($arg){
        
        if (is_a($arg, 'DropDownMenu')){
            
            foreach ($this->_menus as $key => $m){
                if ($arg === $m){
                    array_splice($this->_menus, $key, 1);
                    return true;
                }
            }
            
        }
        elseif (is_int($arg) && $arg >= 0){
            $result = array_splice($this->_menus, $arg, 1);
            return !empty($result);
        }
        
        return false;

    }
    
    function renderDropDownMenu(){
        
        echo '<ul class="dropdown">' . PHP_EOL;
        
        foreach ($this->_menus as $menu){
            $this->renderMenu($menu, true);
        }
        
        echo '</ul>' . PHP_EOL;
        
    }
    
    private function renderMenu($menu, $top){
        
        echo '<li>' . PHP_EOL;
        
        if ($menu->hasSubMenus()){
            
            // add right arrow if not a top menu and has submenus
            if (!$top) echo '<div style="float: right;"><img style="vertical-align: middle;" src="' . IMAGE_DIR . 'arrowright.png"/></div>';
            
            echo '<span>' . $menu->getText() . (!$top ? ' &nbsp;' : '');
            
            // add down arrow if it is a top menu with submenus
            if ($top) echo ' <img style="vertical-align: middle;" src="' . IMAGE_DIR . ($top ? 'arrowdown.png' : 'arrowright.png') . '"/>';
            
            echo '</span>' . PHP_EOL . '<ul style="min-width: 150px;">' . PHP_EOL;
            
            for ($i = 0; $i < $menu->subMenuCount(); $i++){
                $this->renderMenu($menu->subMenu($i), false);
            }
            
            echo '</ul>' . PHP_EOL;
            
        }
        elseif ($menu->getLink() !== null){
            echo '<a href="' . $menu->getLink() . '"' . ($menu->getConfMsg() !== null ? ' onclick="return confirm(\'' . $menu->getConfMsg() . '\');"' : '') . '>' . $menu->getText() . '</a>';
        }
        else {
            echo '<span>' . $menu->getText() . '</span>';
        }
        
        echo '</li>' . PHP_EOL;
        
    }
    
}

class DropDownMenu{
    
    private $_text;
    private $_link;
    private $_conf;
    private $_menus;
    
    function __construct($text, $link = null, $confMsg = null){
        
        $this->setText($text);
        $this->setLink($link);
        $this->setConfMsg($confMsg);
        $this->_menus = array();
        
    }
    
    function subMenu($index){
        return $this->_menus[$index];
    }
    
    function getText(){
        return $this->_text;
    }
    
    function setText($text){
        
        // trim text an check if it is not an empty string
        $text = trim($text);
        if (!strlen($text)) throw new Exception('Invalid menu text.');
        
        $this->_text = $text;
        
    }
    
    function getLink(){
        return $this->_link;
    }
    
    function setLink($link){
        
        // check if link is set and if it's not an empty string.
        if (isset($link)){
            $link = trim($link);
            if (!strlen($link)) $link = null;
        }
        
        $this->_link = $link;
        
    }
    
    function getConfMsg(){
        return $this->_conf;
    }
    
    function setConfMsg($confMsg){
        
        // check if conf. msg. is set and if it's not an empty string.
        if (isset($confMsg)){
            $confMsg = trim($confMsg);
            if (!strlen($confMsg)) $confMsg = null;
        }
        
        $this->_conf = $confMsg;
        
    }
    
    function hasSubMenus(){
        return !empty($this->_menus);
    }
    
    // adds submenu to collection
    function addSubMenu($subMenu){
        
        // check if submenu is a DropDownMenu object
        if (!is_a($subMenu, 'DropDownMenu')) throw new Exception('Invalid sub menu.');
        
        $this->_menus[] = $subMenu;
        
    }
    
    // creates and adds new submenu to collection
    function createAndAddSubMenu($text, $link = null, $confMsg = null){
        
        $this->addSubMenu(new DropDownMenu($text, $link, $confMsg));
        
    }
    
    // removes sub menu.
    // if $arg is type 'DropDownMenu', menu is removed, if
    // it is int, index is removed.
    // returns true if sub menu removed.
    // note: this function removes the first occurence of the
    // object. If more than one of the same object is added
    // to the collection, the one with the lower index will be removed.
    function removeSubMenu($arg){
        
        if (is_a($arg, 'DropDownMenu')){
            
            foreach ($this->_menus as $key => $m){
                if ($arg === $m){
                    array_splice($this->_menus, $key, 1);
                    return true;
                }
            }
            
        }
        elseif (is_int($arg) && $arg >= 0){
            $result = array_splice($this->_menus, $arg, 1);
            return !empty($result);
        }
        
        return false;

    }
    
    function subMenuCount(){
        return count($this->_menus);
    }
    
}

?>