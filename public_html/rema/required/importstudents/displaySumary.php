<?php

function displaySumary($campName, $unitName, $isDropOuts, $counters){
    
?>
        <br/>
        <!-- DO NOT REUSE MESSAGE BOX -->
        <div id="msgBox">
            <div style="background-color: #e5e5e5; border-radius: 5px; box-shadow: 3px 3px 3px #808080; padding: 10px; position: relative;">
                <div class="closeImg" onclick="element('msgBox').style.display = 'none';"></div>
                <span style="color: blue; font-style: italic;">Operação realizada com sucesso.</span>
            </div>
            <br/>
        </div>
        <div class="panel">
            
            <span style="font-weight: bold;">Importar Alunos - Passo 4/4</span>
            <hr/>
            
            <table>
                <tr>
                    <td style="text-align: right;">Campanha:</td>
                    <td style="font-weight: bold;"><?php echo $campName; ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Unidade:</td>
                    <td style="font-weight: bold;"><?php echo htmlentities($unitName, 0, 'ISO-8859-1'); ?></td>
                </tr>
                <tr>
                    <td style="text-align: right;">Alunos evadidos:</td>
                    <td style="font-weight: bold;"><?php echo ($isDropOuts ? 'Sim' : 'Não'); ?></td>
                </tr>
            </table>
            
        </div>
        <br/>
        
        <div class="panel">
            <span style="font-weight: bold;">Resultados</span>
            <hr/>
            
            <table style="width: 100%; border: #c6c6c6 solid 1px; border-collapse: collapse;">
                <tr>
                    <td style="width: 70%; background-color: #c6c6c6; width: 100%;">Alunos adicionados</td>
                    <td style="width: 30%; background-color: #c6c6c6; text-align: right; padding-right: 20px;"><?php echo $counters['countStd']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #ffffff;">Turmas criadas</td>
                    <td style="width: 30%; background-color: #ffffff; text-align: right; padding-right: 20px;"><?php echo $counters['countCls']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #c6c6c6;">Professores criados</td>
                    <td style="width: 30%; background-color: #c6c6c6; text-align: right; padding-right: 20px;"><?php echo $counters['countTch']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #ffffff;">Professores ignorados</td>
                    <td style="width: 30%; background-color: #ffffff; text-align: right; padding-right: 20px;"><?php echo $counters['countIgnTch']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #c6c6c6;">Estágios criados</td>
                    <td style="width: 30%; background-color: #c6c6c6; text-align: right; padding-right: 20px;"><?php echo $counters['countLvl']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #ffffff;">Estágios ignorados</td>
                    <td style="width: 30%; background-color: #ffffff; text-align: right; padding-right: 20px;"><?php echo $counters['countIgnLvl']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #c6c6c6;">Alunos não criados devido à professores ou estágios ignorados</td>
                    <td style="width: 30%; background-color: #c6c6c6; text-align: right; padding-right: 20px;"><?php echo $counters['countIgnStd']; ?></td>
                </tr>
                <tr>
                    <td style="width: 70%; background-color: #ffffff;">Linhas ignoradas</td>
                    <td style="width: 30%; background-color: #ffffff; text-align: right; padding-right: 20px;"><?php echo $counters['countSkpLine']; ?></td>
                </tr>
            </table>
            
        </div>

<?php
    
}

?>