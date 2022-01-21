<?php

// dependency: genreq/genreq.php
require_once __DIR__ . '/../../genreq/genreq.php';

function displayEditBoxes(&$db){
    
?>
        <div class="overlay" id="overlay"></div>
        <div class="helpBox" id="modifyStatusBox" style="width: 430px; height: 365px;">
            <img src="<?php echo IMAGE_DIR; ?>person.png"/>
            <span style="font-weight: bold;">Alterar Status</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Aluno:</td>
                    <td style="width: 100%;"><input id="txtEditName" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;"><input id="txtEditTeacher" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Contrato Anual:</td>
                    <td style="width: 100%;">
                        <input type="checkbox" id="chkYrCtr" onclick="return false;"/>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Turma:</td>
                    <td style="width: 100%;"><input id="txtEditClass" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Status:</td>
                    <td style="width: 100%;">
                        <select id="selEditStatus" name="status" style="border-width: 1px; width: 170px;" onkeyup="styleStatusSelect(); element('spSelectReason').style.visibility = 'hidden';" onchange="styleStatusSelect(); element('spSelectReason').style.visibility = 'hidden';">
                            <option value="0" style="background-color: yellow;">Não Contatado</option>
                            <option value="1" style="background-color: orange;">Contatado</option>
                            <option value="2" style="background-color: red;">Não Volta</option>
                            <option value="3" style="background-color: green;">Rematriculado</option>
                        </select>
                        &nbsp; <span id="spYrCtr" style="color: red; font-style: italic; font-size: 13px;">* Contrato anual.</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Motivo:</td>
                    <td style="width: 100%;">
                        <select id="selEditReason" name="reason" style="width: 170px;" onkeyup="this.styleOption(); element('spSelectReason').style.visibility = 'hidden';" onchange="this.styleOption(); element('spSelectReason').style.visibility = 'hidden';">
                            <option value="0" style="font-style: italic;">- Selecione -</option>
                        <?php

                            $result = $db->query("CALL sp_reasons()");

                            while ($row = $result->fetch_assoc()){
                                echo '<option value="' . $row['ID'] . '" style="font-style: normal;">' . htmlentities($row['Description'], 0, 'ISO-8859-1') . '</option>' . PHP_EOL;
                            }

                            $result->close();

                            // clear stored results after stored procedure call
                            clearStoredResults($db);

                        ?>
                        </select>
                        &nbsp; <span id="spSelectReason" style="color: red; font-style: italic; font-size: 13px; visibility: hidden;">Selecione o motivo.</span>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap; vertical-align: top;">Observações:</td>
                    <td style="width: 100%;">
                        <textarea id="txtEditNotes" name="notes" maxlength="2000" style="width: 95%; height: 80px; resize: none;"></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap; vertical-align: top;"></td>
                    <td style="width: 100%;">
                        <input id="btnSave" type="button" value="Salvar" style="width: 70px;" onclick="saveEdit();"/>
                        <input id="btnCancel" type="button" value="Cancelar" style="width: 70px;" onclick="hideBoxes();"/>
                        <img id="imgEditLoader" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="visibility: hidden;"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" id="hidStdId" name="stdid" />
        </div>
        <div class="helpBox" id="flagBox" style="width: 420px; height: 295px;">
            <img src="<?php echo IMAGE_DIR; ?>flag.png"/>
            <span style="font-weight: bold;">Marcar Aluno</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Aluno:</td>
                    <td style="width: 100%;"><input id="txtFlagName" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;"><input id="txtFlagTeacher" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Turma:</td>
                    <td style="width: 100%;"><input id="txtFlagClass" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="2">
                        Descreva o motivo abaixo:<br/>
                        <textarea id="txtFlagNotes" name="notes" maxlength="500" style="width: 95%; height: 80px; resize: none;" onkeyup="element('spFlagErrMsg').style.visibility = 'hidden';"></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="2">
                        <input id="btnFlagSave" type="button" value="Marcar" style="width: 70px;" onclick="saveFlag();"/>
                        <input id="btnFlagCancel" type="button" value="Cancelar" style="width: 70px;" onclick="hideBoxes();"/>
                        <img id="imgFlagEditLoader" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="visibility: hidden;"/>
                        <span id="spFlagErrMsg" style="color: red; font-style: italic; visibility: hidden; font-size: 14px;"></span>
                    </td>
                </tr>
            </table>
            <input type="hidden" id="hidFlagStdId" name="stdid" />
        </div>
        <div class="helpBox" id="unflagBox" style="width: 420px; height: 295px;">
            <img src="<?php echo IMAGE_DIR; ?>forbidden.png"/>
            <span style="font-weight: bold;">Desmarcar Aluno</span>
            <hr/>
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Aluno:</td>
                    <td style="width: 100%;"><input id="txtUnflagName" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Professor:</td>
                    <td style="width: 100%;"><input id="txtUnflagTeacher" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="text-align: right; white-space: nowrap;">Turma:</td>
                    <td style="width: 100%;"><input id="txtUnflagClass" type="text" readonly="readonly" value="" style="width: 95%;"/></td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="2">
                        Descrição do motivo:<br/>
                        <div id="divFlagDetails" style="width: 95%; height: 80px; border: #a9a9a9 solid 1px; padding: 5px; overflow-y: auto;"></div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%;" colspan="2">
                        <input id="btnUnflag" type="button" value="Desmarcar" onclick="unflag();"/>
                        <input id="btnUnflagClose" type="button" value="Fechar" style="width: 70px;" onclick="hideBoxes();"/>
                        <img id="imgUnflagEditLoader" src="<?php echo IMAGE_DIR; ?>circle_loader.gif" style="visibility: hidden;"/>
                    </td>
                </tr>
            </table>
        </div>
        <div class="helpBox" id="loaderBox" style="width: 80px; height: 80px; vertical-align: top; text-align: center;">
            <img src="<?php echo IMAGE_DIR; ?>circle_loader2.gif"/><br/>
            <span style="color: #61269e;">Carregando...</span>
        </div>
        <div class="helpBox" id="errorBox" style="width: 380px; height: 80px;">
            <img src="<?php echo IMAGE_DIR; ?>warning.png" style="vertical-align: middle;"/>
            <span id="spErrorMsg" style="color: #61269e; padding: 5px;"></span><br/><br/>
            <div style="text-align: right; padding: 5px;"><input type="button" value="OK" style="width: 70px;" onclick="closeErrorBox();"/></div>
        </div>
    
<?php
}

?>