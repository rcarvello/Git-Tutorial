<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require "/var/www/html/Know/connessione/connessione.php";
require "/var/www/html/Know/connessione/connessioneCrm.php";

$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$objCrm = new ConnessioneCrm();
$connCrm = $objCrm->apriConnessioneCrm();

$meseCorrente = date('Y-m-1');

$mesePrecedente = date('Y-m-1', strtotime($meseCorrente . '-1 months'));

$queryTruncate = "TRUNCATE TABLE `stringhePeso`";
$connCrm->query($queryTruncate);

$queryEnelOut = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',mese,'EnelOut' as 'Mandato' "
        . "FROM `enelOut` inner join aggiuntaEnelOut on enelOut.id=aggiuntaEnelOut.id  "
        . "Where mese>='$mesePrecedente' "
        . "group by creatoDa,mese";

$risultato19 = $conn19->query($queryEnelOut);

while ($riga19 = $risultato19->fetch_array()) {
    $operatore = $riga19[0];
    $pesoLordo = $riga19[1];
    $pesoPagato = $riga19[2];
    $mese = $riga19[3];
    $mandato = $riga19[4];

    $queryInserimentoCrm = "INSERT INTO stringhePeso (operatore,pesoLordo,pesoPagato,mese,mandato) VALUES ('$operatore','$pesoLordo','$pesoPagato','$mese','$mandato')";
    $connCrm->query($queryInserimentoCrm);
}

$queryGreen = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',mese,'Greennetwork' as 'Mandato' "
        . "FROM `green` inner join aggiuntaGreen on green.id=aggiuntaGreen.id  "
        . "Where mese>='$mesePrecedente' "
        . "group by creatoDa,mese";

$risultato19 = $conn19->query($queryGreen);

while ($riga19 = $risultato19->fetch_array()) {
    $operatore = $riga19[0];
    $pesoLordo = $riga19[1];
    $pesoPagato = $riga19[2];
    $mese = $riga19[3];
    $mandato = $riga19[4];

    $queryInserimentoCrm = "INSERT INTO stringhePeso (operatore,pesoLordo,pesoPagato,mese,mandato) VALUES ('$operatore','$pesoLordo','$pesoPagato','$mese','$mandato')";
    $connCrm->query($queryInserimentoCrm);
}

$queryPlenitude = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',mese,'Plenitude' as 'Mandato' "
        . "FROM `plenitude` inner join aggiuntaPlenitude on plenitude.id=aggiuntaPlenitude.id  "
        . "Where mese>='$mesePrecedente' "
        . "group by creatoDa,mese";

$risultato19 = $conn19->query($queryPlenitude);

while ($riga19 = $risultato19->fetch_array()) {
    $operatore = $riga19[0];
    $pesoLordo = $riga19[1];
    $pesoPagato = $riga19[2];
    $mese = $riga19[3];
    $mandato = $riga19[4];

    $queryInserimentoCrm = "INSERT INTO stringhePeso (operatore,pesoLordo,pesoPagato,mese,mandato) VALUES ('$operatore','$pesoLordo','$pesoPagato','$mese','$mandato')";
    $connCrm->query($queryInserimentoCrm);
}


$queryVivigas = "SELECT creatoDa,round(sum(totalePesoLordo),5) as 'Peso Lordo' ,round(sum(pesoTotalePagato),5)as 'Peso Pagato',mese,'Vivigas' as 'Mandato' "
        . "FROM `vivigas` inner join aggiuntaVivigas on vivigas.id=aggiuntaVivigas.id  "
        . "Where mese>='$mesePrecedente' "
        . "group by creatoDa,mese";

$risultato19 = $conn19->query($queryVivigas);

while ($riga19 = $risultato19->fetch_array()) {
    $operatore = $riga19[0];
    $pesoLordo = $riga19[1];
    $pesoPagato = $riga19[2];
    $mese = $riga19[3];
    $mandato = $riga19[4];

    $queryInserimentoCrm = "INSERT INTO stringhePeso (operatore,pesoLordo,pesoPagato,mese,mandato) VALUES ('$operatore','$pesoLordo','$pesoPagato','$mese','$mandato')";
    $connCrm->query($queryInserimentoCrm);
}