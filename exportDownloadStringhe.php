<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$meseSelezionato = $_GET["meseSelezionato"];

$query = "SELECT * FROM `stringheSiscall`  where mese='$meseSelezionato'";
echo $query;
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
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}

$query = "SELECT * FROM `stringheSiscall2`  where mese='$meseSelezionato'";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}
$query = "SELECT * FROM `stringheSiscall4`  where mese='$meseSelezionato'";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}
$query = "SELECT * FROM `stringheSiscallGT`  where mese='$meseSelezionato'";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}
$query = "SELECT * FROM `stringheSiscallDigital`  where mese='$meseSelezionato'";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}
$query = "SELECT * FROM `stringheSiscall4TC`  where mese='$meseSelezionato'";
$risultato = $conn19->query($query);
$conteggio = $risultato->num_rows;

while ($lista = $risultato->fetch_array()) {
    for ($i = 0; $i < $el; $i++) {
        $intestazioneChiamate .= $lista[$i];
        $intestazioneChiamate .= ";";
    }
    $intestazioneChiamate .= "\n";    
    
}



file_put_contents($file, $intestazioneChiamate);
header('Content-Description: File Transfer');
header('Content-type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Type: " . mime_content_type($file));
header("Content-type: text/csv");
header('Content-Disposition: attachment; filename=stringhe.csv' );
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);

