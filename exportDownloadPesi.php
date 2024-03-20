<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$meseSelezionato = $_GET["meseSelezionato"];

$query = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',round(sum(pesoFormazione),5)as 'Peso Formazione', 'GreenNetwork' as 'Mandato' FROM `green` inner join aggiuntaGreen on green.id=aggiuntaGreen.id where mese='$meseSelezionato' group by creatoDa";
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
        $intestazioneChiamate .= str_replace(".",",",$lista[1]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
    }
       
    


$query = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',round(sum(pesoFormazione),5)as 'Peso Formazione', 'Plenitude' as 'Mandato' FROM `plenitude` inner join aggiuntaPlenitude on plenitude.id=aggiuntaPlenitude.id where mese='$meseSelezionato' group by creatoDa";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    
        $intestazioneChiamate .= $lista[0];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[1]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
}
$query = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',round(sum(pesoFormazione),5)as 'Peso Formazione', 'Vivigas' as 'Mandato' FROM `vivigas` inner join aggiuntaVivigas on vivigas.id=aggiuntaVivigas.id where mese='$meseSelezionato' group by creatoDa";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    
        $intestazioneChiamate .= $lista[0];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[1]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
}
$query = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',round(sum(pesoFormazione),5)as 'Peso Formazione', 'EnelOut' as 'Mandato' FROM `enelOut` inner join aggiuntaEnelOut on enelOut.id=aggiuntaEnelOut.id where mese='$meseSelezionato' group by creatoDa";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    
        $intestazioneChiamate .= $lista[0];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[1]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
}
   

$query = "SELECT creatoDa,round(sum(pesoTotaleLordo),5) as 'Peso Lordo' ,round(sum(pesoPagato),5)as 'Peso Pagato',round(sum(pesoFormazione),5)as 'Peso Formazione', 'Vodafone' as 'Mandato' FROM `vodafone` inner join aggiuntaVodafone on vodafone.id=aggiuntaVodafone.id where mese='$meseSelezionato' group by creatoDa";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    
        $intestazioneChiamate .= $lista[0];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[1]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[2]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= str_replace(".",",",$lista[3]);
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= $lista[4];
        $intestazioneChiamate .= ";";
        $intestazioneChiamate .= "\n"; 
}
//echo $intestazioneChiamate;




file_put_contents($file, $intestazioneChiamate);
header('Content-Description: File Transfer');
header('Content-type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Type: " . mime_content_type($file));
header("Content-type: text/csv");
header('Content-Disposition: attachment; filename=pesiTotale.csv' );
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);

