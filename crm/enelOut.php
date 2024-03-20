<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Europe/Rome');

require "/var/www/html/Know/connessione/connessione_msqli_vici.php";
require "/var/www/html/Know/connessione/connessione.php";
require "/var/www/html/Know/connessione/connessioneCrm.php";
require "/var/www/html/Know/funzione/funzioneCrm.php";

$obj = new ConnessioneVici();
$conn = $obj->apriConnessioneVici();

$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();

$objCrm = new ConnessioneCrm();
$connCrm = $objCrm->apriConnessioneCrm();

/**
 * Inizio Processo prelievo giornaliero Siscall1
 */
$dataImport = date('Y-m-d H:i:s');
$oggi = date('Y-m-d');
$ieri = date('Y-m-d', strtotime('-1 days'));
$provenienza = "enelOut";
$tipoCampagna = "";

/**
 * Recupero valore idStato
 */
$idStato = idStato($conn19);
/**
 * Query inserimento log iniziale
 */
scriviLog($conn19, $dataImport, $provenienza, 'Inizio Import da $provenienza', 0, '$idStato');
/**
 * Query ricerca sul crm2
 */
$queryRicerca = "SELECT "
        . "operatore.user_name as 'Creato da', "
        . "date_format(vtiger_eneloutcf.cf_1536,'%d-%m-%Y') as 'data', "
        . "vtiger_eneloutcf.cf_1548 as 'comodity', "
        . "'Consumer' as 'Mercato', "
        . "vtiger_eneloutcf.cf_1542 as 'Sede', "
        . "vtiger_eneloutcf.cf_1558 as 'Metodo Pagamento', "
        . "vtiger_eneloutcf.cf_1560 as 'Metodo Invio', "
        . "vtiger_eneloutcf.cf_1534 as 'Stato PDA', "
        . "vtiger_eneloutcf.cf_1550 as 'Stato Luce', "
        . "vtiger_eneloutcf.cf_1552 as 'Stato Gas', "
        . "'NO' as 'winback', "
        . "entity.createdtime AS 'dataCreazione', "
        . "vtiger_eneloutcf.cf_1576 as 'Codice Campagna', "
        . "vtiger_eneloutcf.eneloutid AS 'pratica', "
        . "'NO' as 'Sanata BO', "
        . "vtiger_eneloutcf.cf_2812 as 'assicurazione', "
        . "vtiger_eneloutcf.cf_3839 as 'fibra', "
        . "vtiger_eneloutcf.cf_3743 as 'aggiuntivi', "
        . "vtiger_eneloutcf.cf_1530 as 'idLuce', "
        . "vtiger_eneloutcf.cf_1532 as 'idGas' "
        . "FROM "
        . "vtiger_eneloutcf  "
        . "inner join vtiger_enelout on vtiger_eneloutcf.eneloutid=vtiger_enelout.eneloutid "
        . "inner join vtiger_crmentity as entity on vtiger_eneloutcf.eneloutid=entity.crmid "
        . "inner join vtiger_users as operatore on entity.smownerid=operatore.id "
        . "WHERE "
        . "vtiger_eneloutcf.cf_1536 >'2023-01-31'";

//echo $queryRicerca;
$risultato = $connCrm->query($queryRicerca);
/**
 * Se la ricerca da errore segna nei log l'errore
 */
if (!$risultato) {
    $dataErrore = date('Y-m-d H:i:s');
    $errore = $connCrm->real_escape_string($connCrm->error);
    scriviLog($conn19, $dataErrore, $provenienza, $errore, 0, $idStato);
}
/**
 * se non da errore svuota la tabella, segnando nei log l'operazione, e la ricarica riga per riga da zero
 */ else {
    $dataTruncate = truncateEnelOut($conn19);
    scriviLog($conn19, $dataTruncate, $provenienza, 'Truncate', 0, $idStato);
    while ($riga = $risultato->fetch_array()) {
        $pesoFormazione = 0;

        $pezzoLordo = 0;
        $pezzoNetto = 0;
        $pezzoPagato = 0;
        $pezzoFormazione = 0;

        $user = $riga[0];
        $data = date('Y-m-d', strtotime(strtr($riga[1], '/', '-')));
        $comodity = $riga[2];
        $mercato = $riga[3];
        $sede = $riga[4];
        $metodoPagamento = $riga[5];
        $metodoInvio = $riga[6];
        $statoPDA = $riga[7];
        $statoLuce = $conn19->real_escape_string($riga[8]);
        $statoGas = $conn19->real_escape_string($riga[9]);
        $winback = $riga[10];
        $dataCreazione = $riga[11];
        $codiceCampagna = $riga[12];
        $pratica = $riga[13];
        $sanataBo = $riga[14];
        $assicurazione = $riga[15];
        $fibra = $riga[16];
        $aggiuntivi = $riga[17];
        $idLuce = $riga[18];
        $idGas = $riga[19];
        /**
         * ricerca id stato PDA
         */
        $idStatoPda = idStatoPdaEnelOut($conn19, $statoPDA);

        /**
         * ricerca id stato luce
         */
        $idStatoLuce = idStatoLuceEnelOut($conn19, $statoLuce);
        /**
         * Ricerca id stato Gas
         */
        $idStatoGas= idStatoGasEnelOut($conn19, $statoGas);
        
        
        if ($winback == "Si") {
            $mandato = "EnelOut Retention";
            $idMandato = 14;
        } else {
            $mandato = "enel_out";
            $idMandato = 14;
        }

        $queryCampagna = "SELECT * FROM `enelOutCampagna` where nome='$codiceCampagna'";
        $risultatoCampagna = $conn19->query($queryCampagna);
        $conteggioCampagna = $risultatoCampagna->num_rows;
        if ($conteggioCampagna == 0) {
            $queryInserimentoCampagna = "INSERT INTO `enelOutCampagna`( `nome`) VALUES ('$codiceCampagna')";
            $conn19->query($queryInserimentoCampagna);
            $idCampagna = $conn19->insert_id;
        } else {
            $rigaCampagna = $risultatoCampagna->fetch_array();
            $idCampagna = $rigaCampagna[0];
            $tipoCampagna = $rigaCampagna[2];
        }




        $queryInserimento = "INSERT INTO `enelOut`"
                . "( `creatoDa`, `data`, `comodity`, `mercato`, `sede`, `metodoPagamento`, `statoPDA`, `statoLuce`, `statoGas`, `dataImport`, `mandato`,idStatoPda,idStatoLuce,idStatoGas,winback,idMandato,dataCreazione,idCampagna,campagna,metodoInvio,pratica,sanataBo,assicurazione,fibra,aggiuntivi)"
                . " VALUES ('$user','$data','$comodity','$mercato','$sede','$metodoPagamento','$statoPDA','$statoLuce','$statoGas','$dataImport','$mandato','$idStatoPda','$idStatoLuce','$idStatoGas','$winback','$idMandato','$dataCreazione','$idCampagna','$codiceCampagna','$metodoInvio','$pratica','$sanataBo','$assicurazione','$fibra','$aggiuntivi')";
//echo $queryInserimento;
        $conn19->query($queryInserimento);
        /*
         * Aggiunta per calcolo pesi
         */
        $indiceContratto = $conn19->insert_id;
        $pesoComodity = 0;
        $pesoInvio = 0;
        $pesoPagamento = 0;
//$pesoTotaleLordo = 0;
        $pesoAssicurazione = 0;
        $pesoFibra = 0;
        $pesoAggiuntivi = 0;
        $mese = date('Y-m-1', strtotime($data));

        $queryFormazione = "SELECT ore FROM `formazioneTotale` where nomeCompleto='$user' and giorno='$data'";
//        echo $queryFormazione;

        $risultatoFormazione = $conn19->query($queryFormazione);
        if (($risultatoFormazione->num_rows) > 0) {
            $rigaFormazione = $risultatoFormazione->fetch_array();
            $ore = $rigaFormazione[0];
//            echo "----".$ore;

            if ($ore < 45) {
                $isFormazione = true;
            } else {
                $isFormazione = false;
            }
        } else {
            $isFormazione = false;
        }

//Peso Commodity
        $queryPesoComodity = "SELECT peso FROM `enelOutPesiComoditi` WHERE tipoCampagna='$tipoCampagna' AND valore='$comodity' AND dataInizioValidita='$mese'";
        $risultatoPesoComodity = $conn19->query($queryPesoComodity);
        if (($risultatoPesoComodity->num_rows) > 0) {
            $rigaPesoComodity = $risultatoPesoComodity->fetch_array();
            $pesoComodity = $rigaPesoComodity[0];
        }

        $queryPesoInvio = "SELECT peso FROM `enelOutPesiInvio` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoInvio' AND dataInizioValidita='$mese'";
        $risultatoPesoInvio = $conn19->query($queryPesoInvio);
        if (($risultatoPesoInvio->num_rows) > 0) {
            $rigaPesoInvio = $risultatoPesoInvio->fetch_array();
            $pesoInvio = $rigaPesoInvio[0];
        }

        $queryPesoPagamento = "SELECT peso FROM `enelOutPesiMetodoPagamento` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoPagamento' AND dataInizioValidita='$mese'";
        $risultatoPesoPagamento = $conn19->query($queryPesoPagamento);
        if (($risultatoPesoPagamento->num_rows) > 0) {
            $rigaPesoPagamento = $risultatoPesoPagamento->fetch_array();
            $pesoPagamento = $rigaPesoPagamento[0];
        }

        $queryPesoAssicurazione = "SELECT peso FROM `enelOutPesiAssicurazione` WHERE tipoCampagna='$tipoCampagna' AND valore='$assicurazione' AND dataInizioValidita='$mese'";
        $risultatoPesoAssicurazione = $conn19->query($queryPesoAssicurazione);
        if (($risultatoPesoAssicurazione->num_rows) > 0) {
            $rigaPesoAssicurazione = $risultatoPesoAssicurazione->fetch_array();
            $pesoAssicurazione = $rigaPesoAssicurazione[0];
        }

        $queryPesoFibra = "SELECT peso FROM `enelOutPesiFibra` WHERE tipoCampagna='$tipoCampagna' AND valore='$fibra' AND dataInizioValidita='$mese'";
        $risultatoPesoFibra = $conn19->query($queryPesoFibra);
        if (($risultatoPesoFibra->num_rows) > 0) {
            $rigaPesoFibra = $risultatoPesoFibra->fetch_array();
            $pesoFibra = $rigaPesoFibra[0];
        }

        $queryPesoAggiuntivi = "SELECT peso FROM `enelOutPesiAggiuntivi` WHERE tipoCampagna='$tipoCampagna' AND valore='$aggiuntivi' AND dataInizioValidita='$mese'";
        $risultatoPesoAggiuntivi = $conn19->query($queryPesoAggiuntivi);
        if (($risultatoPesoAggiuntivi->num_rows) > 0) {
            $rigaPesoAggiuntivi = $risultatoPesoAggiuntivi->fetch_array();
            $pesoAggiuntivi = $rigaPesoAggiuntivi[0];
        }

        $pesoTotaleLordo = $pesoComodity + $pesoInvio + $pesoPagamento + $pesoAssicurazione + $pesoFibra + $pesoAggiuntivi;
        if ($comodity == "Dual") {
            $pezzoLordo = 2;
        } else {
            $pezzoLordo = 1;
        }

        $queryFaseLuce = "SELECT fase FROM `enelOutStatoLuce` WHERE id='$idStatoLuce'";
        $risultatoFaseLuce = $conn19->query($queryFaseLuce);
        $rigaFaseLuce = $risultatoFaseLuce->fetch_array();
        $faseLuce = $rigaFaseLuce[0];

        $queryFaseGas = "SELECT fase FROM `enelOutStatoGas` WHERE id='$idStatoGas'";
        $risultatoFaseGas = $conn19->query($queryFaseGas);
        $rigaFaseGas = $risultatoFaseGas->fetch_array();
        $faseGas = $rigaFaseGas[0];

        $queryFasePDA = "SELECT fase FROM `enelOutStatoPDA` WHERE id='$idStatoPda'";
        $risultatoFasePDA = $conn19->query($queryFasePDA);
        $rigaFasePDA = $risultatoFasePDA->fetch_array();
        $fasePDA = $rigaFasePDA[0];

        $pesoTotaleNetto = 0;
        if ($faseGas == "OK" && $faseLuce == "OK" && $comodity == "Dual") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseLuce == "OK" && $comodity == "Luce") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseGas == "OK" && $comodity == "Gas") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        }
        if ($comodity == "Enel X") {
            if ($faseGas == "OK" && $faseLuce == "OK") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            } elseif ($faseGas == "OK" && $faseLuce == "") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            } elseif ($faseGas == "" && $faseLuce == "OK") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            }
        }if ($comodity == "Fibra Enel") {
            if ($faseGas == "OK" && $faseLuce == "OK") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            } elseif ($faseGas == "OK" && $faseLuce == "") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            } elseif ($faseGas == "" && $faseLuce == "OK") {
                $pesoTotaleNetto = $pesoTotaleLordo;
                $pezzoNetto = $pezzoLordo;
            }
        }

        if ($isFormazione == true) {
            $pesoTotalePagato = 0;
            $pesoFormazione = $pesoTotaleNetto;
            $pezzoFormazione = $pezzoNetto;
        } else {
            $pesoFormazione = 0;
            $pesoTotalePagato = $pesoTotaleNetto;
            $pezzoPagato = $pezzoNetto;
        }

        $fasePost = "KO";
        $statoPost = "";
        if ($faseGas == $faseLuce && $comodity == "Dual") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        } elseif ($comodity == "Luce") {
            $fasePost = $faseLuce;
            $statoPost = $statoLuce;
        } elseif ($comodity == "Gas") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        } elseif ($faseLuce <> null && $comodity == "Polizza") {
            $fasePost = $faseLuce;
            $statoPost = $statoLuce;
        } elseif ($faseGas <> null && $comodity == "Polizza") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        }


        $queryInserimentoSecondario = "INSERT INTO `aggiuntaEnelOut`(`id`, `tipoCampagna`, `pesoComodity`, `pesoInvio`, `pesoMPagamento`, `totalePesoLordo`,"
                . " `faseLuce`, `faseGas`, `totalePesoNetto`,mese,fasePDA,pesoTotalePagato,pesoAssicurazione,pesoFibra,pesoAggiuntivi,idLuce,idGas,pesoFormazione,"
                . " pezzoLordo, pezzoNetto, pezzoPagato, pezzoFormazione,fasePost,statoPost) "
                . "VALUES ('$indiceContratto','$tipoCampagna','$pesoComodity','$pesoInvio','$pesoPagamento','$pesoTotaleLordo',"
                . "'$faseLuce','$faseGas','$pesoTotaleNetto','$mese','$fasePDA','$pesoTotalePagato','$pesoAssicurazione','$pesoFibra','$pesoAggiuntivi','$idLuce','$idGas','$pesoFormazione',"
                . " '$pezzoLordo', '$pezzoNetto', '$pezzoPagato', '$pezzoFormazione','$fasePost','$statoPost')";
//echo $queryInserimentoSecondario;
        $conn19->query($queryInserimentoSecondario);

        aggiornaPesiCrmEnelOut($connCrm, $mese, $pesoTotaleLordo, $pesoTotalePagato, $pratica);
    }
    $dataFine = date('Y-m-d H:i:s');
    scriviLog($conn19, $dataFine, $provenienza, 'Fine Import da $provenienza', 1, $idStato);

    header("location:../pannello.php");
}

/**
 * Inizio prelievo dati da crm2 tabella Vivigas
 */



