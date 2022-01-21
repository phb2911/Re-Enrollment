<?php

function printMenu($sid, $isAdmin){
    
?>
<ul class="dropdownMenu">
    <li>
        <span><img src="<?php echo IMAGE_DIR; ?>list.png"></span>
        <ul style="min-width: 150px;">
            <li><a href="#" onclick="menuClicked(1, <?php echo $sid; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>person.png"/> &nbsp; Alterar Status</a></li>
            <li><a href="#" onclick="menuClicked(2, <?php echo $sid; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>info.png"/> &nbsp; Detalhes</a></li>
<?php if ($isAdmin) { ?>
            <li><a href="#" onclick="menuClicked(3, <?php echo $sid; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>pencil1.png"/> &nbsp; Editar</a></li>
<?php } ?>
            <li><a href="#" onclick="menuClicked(4, <?php echo $sid; ?>); return false;"><img src="<?php echo IMAGE_DIR; ?>flag.png"/> &nbsp; Marcar/Desmarcar</a></li>
        </ul>
    </li>
</ul>
<?php
    
}

?>