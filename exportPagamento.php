<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$meseSelezionato = $_GET["meseSelezionato"];

//$dataInizio = date("Y-m-d", strtotime("2023-" . $meseSelezionato . "-01"));
//$dataFine = date("Y-m-d", strtotime(date("Y-m-t", strtotime($dataInizio))));

$query = "select "
        . "nomeCompleto, "
        . "mese, "
        . "livello, "
        . "round(numero,2)as 'ore Lavorato',"
        . "round(vodafonePesoLordo,2) as 'vodafone Peso Lordo',"
        . "round(vodafonePesoPagato,2) as 'vodafone Peso Pagato',"
        . "round(vodafonePesoFormazione,2) as 'vodafone Peso Formazione',"
        . "round(vivigasPesoLordo,2) as 'vivigas Peso Lordo',"
        . "round(vivigasPesoPagato,2) as 'vivigas Peso Pagato',"
        . "round(vivigasPesoFormazione,2) as 'vivigas Peso Formazione',"
        . "round(plenitudePesoLordo,2) as 'plenitude Peso Lordo',"
        . "round(plenitudePesoPagato,2) as 'plenitude Peso Pagato',"
        . "round(plenitudePesoFormazione,2) as 'plenitude Peso Formazione',"
        . "round(greenPesoLordo,2) as 'green Peso Lordo',"
        . "round(greenPesoPagato,2) as 'green Peso Pagato',"
        . "round(greenPesoFormazione,2) as 'green Peso Formazione',"
        . "round(enelOutPesoLordo,2) as 'enelOut Peso Lordo',"
        . "round(enelOutPesoPagato,2) as 'enelOut Peso Pagato',"
        . "round(enelOutPesoFormazione,2) as 'enelOut Peso Formazione',"
        . "round(formazione,2) as 'ore formazione', "
        . "round(orePolizze,2) as 'ore Polizze'"
        . "from pagamentoMese "
        . "where mese='$meseSelezionato' "
        . "group by nomeCompleto";
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
    $intestazioneChiamate .= $lista[2];
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[3]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[4]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[5]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[6]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[7]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[8]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[9]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[10]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[11]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[12]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[13]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[14]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[15]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[16]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[17]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[18]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= str_replace(".", ",", $lista[19]);
    $intestazioneChiamate .= ";";
     $intestazioneChiamate .= str_replace(".", ",", $lista[20]);
    $intestazioneChiamate .= ";";
    $intestazioneChiamate .= "\n";
}






file_put_contents($file, $intestazioneChiamate);
header('Content-Description: File Transfer');
header('Content-type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Type: " . mime_content_type($file));
header("Content-type: text/csv");
header('Content-Disposition: attachment; filename=pagamentoMese.csv');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);

