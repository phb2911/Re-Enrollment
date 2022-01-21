<?php

require_once __DIR__ . '/../../../genreq/genreq.php';

function displayStudentTable(&$stdArr, $situation){
    
    if ($situation == 0) $title = 'Alunos';
    elseif ($situation == 1) $title = 'Evadidos';
    elseif ($situation == 2) $title = 'Cancelados';
    elseif ($situation == 3) $title = 'Concluintes';
    
?>    
            <br/>
            <table class="list">
                <tr style="background-color: #61269e; color: #ffffff;">
                    <td>&nbsp;</td>
                    <td style="width: 25%;"><?php echo $title; ?></td>
                    <td style="width: 15%;">Turma</td>
                    <td style="width: 9%;">Unidade</td>
                    <td style="width: 12%; text-align: center;">Status</td>
                    <td style="width: 15%;">Motivo</td>
                    <td style="width: 24%;">Observações</td>
                    <td><img src="<?php echo IMAGE_DIR; ?>cal1.png" style="width: 16px; height: 16px;" title="Contrato Anual"></td>
                    <td><img src="<?php echo IMAGE_DIR; ?>flag.png" style="width: 16px; height: 16px;"></td>
                </tr>
<?php 

            $bgcolor = '';

            foreach ($stdArr[$situation] as $stdInfo){
                
                if ($stdInfo['Situation'] == $situation) {
                    switch ($stdInfo['Status']) {
                        case '0':
                            $st = 'Não Contatado';
                            $stBg = 'yellow';
                            break;
                        case '1':
                            $st = 'Contatado';
                            $stBg = 'orange';
                            break;
                        case '2':
                            $st = 'Não Volta';
                            $stBg = 'red';
                            break;
                        default:
                            $st = 'Rematriculado';
                            $stBg = 'green';
                    }

                    $bgcolor = ($bgcolor == '#c6c6c6') ? '#ffffff' : '#c6c6c6';

                    echo '<tr style="background-color: ' . $bgcolor . ';"><td>'; 
                    
                    printMenu($stdInfo['ID'], true);
                    
                    echo '</td>' . PHP_EOL . '<td>' . htmlentities($stdInfo['Name'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                        '<td><a href="class.php?clsid=' . $stdInfo['ClassID'] . '">' . htmlentities($stdInfo['ClassName'], 0, 'ISO-8859-1') . '</a></td>' . PHP_EOL .
                        '<td>' . htmlentities($stdInfo['School'], 0, 'ISO-8859-1') . '</td>' . PHP_EOL .
                        '<td style="text-align: center;"><div id="divStatus' . $stdInfo['ID'] . '" style="background-color: ' . $stBg . ';">' . $st . '</div></td>' . PHP_EOL .
                        '<td><span id="spReason' . $stdInfo['ID'] . '">' . htmlentities($stdInfo['ReasonDescription'], 0, 'ISO-8859-1') . '</span></td>' . PHP_EOL .
                        '<td><span id="spNotes' . $stdInfo['ID'] . '">' . nl2br(htmlentities($stdInfo['Notes'], 0, 'ISO-8859-1')) . '</span></td>' . PHP_EOL .
                        '<td>' . (!!$stdInfo['YearlyContract'] ? '<img src="' . IMAGE_DIR . 'check3.png"/>' : '') . '</td>' . PHP_EOL .
                        '<td><img id="imgFlag' . $stdInfo['ID'] . '" src="' . IMAGE_DIR . 'flag.png" style="cursor: pointer; visibility: ' . (!!$stdInfo['Flagged'] ? 'visible' : 'hidden') . ';" title="Detalhes" onclick="showFlagBox(' . $stdInfo['ID'] . ');"/></td></tr>' . PHP_EOL;
                }
                
            }
                                    
?>
            </table>  
<?php
    
}

?>