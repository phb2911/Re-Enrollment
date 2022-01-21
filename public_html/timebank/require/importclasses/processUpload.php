<?php
/**
 * 
 * This function reads the uploaded file and fetches only
 * the important information and saves into a different file.
 * 
 * @return boolean Returns true if the operation is successful and false otherwise.
 * 
 * @param type $fileName Name of file to be processed
 * @param type $msg Returns error message (ByRef).
 */
function processUpload($fileName, &$msg){
    
    $colCount = 0;
    
    try {

        // disable xml parser errors
        libxml_use_internal_errors(true);

        // load file
        $html = file_get_contents(UPLOAD_DIR . $fileName . '.tmp');

        // create a new dom object 
        $dom = new domDocument;

        // discard white spaces
        $dom->preserveWhiteSpace = false; 

        // set encoding to latin-1
        $dom->encoding = 'iso-8859-1';

        // load html into dom object
        if ($html !== false && $dom->loadHTML($html)){

            // get table from object
            $table = $dom->getElementsByTagName('table')->item(0);

            // get rows from table
            $rows = $table->getElementsByTagName('tr');
            
            // open destination file
            if (!$fileToWrite = fopen(UPLOAD_DIR . $fileName, "w")){
                $msg = 'N&atilde;o foi poss&iacute;vel criar arquivo.';
                return false;
            }
            
            // open html document
            fwrite($fileToWrite, '<html><meta http-equiv=Content-Type content="text/html; charset=utf-8"><body><table>' . PHP_EOL);

            // loop over the table rows
            foreach ($rows as $row){

                // get columns from row
                $cols = $row->getElementsByTagName('td');
                
                // open row
                fwrite($fileToWrite, '<tr>' . PHP_EOL);

                // loop over the columns
                foreach ($cols as $col) {
                    // write column
                    fwrite($fileToWrite, '<td>' . sTrim($col->nodeValue) . '</td>' . PHP_EOL);
                    // increment count
                    $colCount++;
                }
                
                // close row
                fwrite($fileToWrite, '</tr>' . PHP_EOL);

            }
            
            // close html document
            fwrite($fileToWrite, '</table></body></html>' . PHP_EOL);
            
            // close file
            fclose($fileToWrite);

        }
    
    } catch (Exception $ex) {
        $msg = 'Ocorreu um erro inesperado. Certifique-se que o arquivo enviado &eacute; v&aacute;lido. Clique <a href="importclasses.php">aqui</a> para tentar novamente.';
        return false;
    }
    
    if (!$colCount){
        $msg = 'O arquivo enviado est&aacute; vazio.';
        return false;
    }
    
    return true;
    
}

?>