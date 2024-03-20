<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$meseSelezionato = $_GET["meseSelezionato"];


$dataInizio=date("Y-m-d", strtotime("2024-".$meseSelezionato."-01") );
$dataFine= date("Y-m-d", strtotime(date("Y-m-t", strtotime($dataInizio))));

$query = "select user,"
        . "nomeCompleto,"
        . "round(lavorato,2)as lavorato,"
        . "round(Min(ore),2) as 'ore primo turno',"
        . "Min(giorno) as 'primo turno',"
        . "round(max(ore),2)as 'ore ultimo turno',"
        . "MAX(giorno) as 'ultimo turno',"
        . "IF(min(ore)<45,"
        . "if(max(ore)<45,round(max(ore)- min(ore)+lavorato,2),round(45-min(ore)+lavorato,2))"
        . ",0)as differenza,"
        . "dataAssunzione "
        . "from formazioneTotale "
        . "where giorno>='$dataInizio'and giorno<='$dataFine' "
        . "group by user";
//echo $query;
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;
$intestazione = $risultato->fetch_fields();
$el = 0;

$directory = "../elementi/";
$file = "/var/www/html/Know/elementi/file.csv";
$intestazioneChiamate = "";

foreach ($intestazione as $info) {
    $intestazioneChiamate .= $info->name;
    $intestazioneChiamate .= ";";
    $el++;
}
$intestazioneChiamate .= "\n";
while ($lista = $risultato->fetch_array()) {
        
        $intestazioneChiamate .= $lista[0];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[1];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
         $intestazioneChiamate .= str_replace(".",",",$lista[5]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[6];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[7]);
        $intestazioneChiamate .= ";";
          $intestazioneChiamate .= $lista[8];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
    }
       





file_put_contents($file, $intestazioneChiamate);
header('Content-Description: File Transfer');
header('Content-type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Type: " . mime_content_type($file));
header("Content-type: text/csv");
header('Content-Disposition: attachment; filename=FormazioneTotale.csv' );
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);

