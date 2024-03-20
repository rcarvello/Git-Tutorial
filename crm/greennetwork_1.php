<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL | E_STRICT);
//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
//
//date_default_timezone_set('Europe/Rome');

//require "/var/www/html/Know/connessione/connessione_msqli_vici.php";
//require "/var/www/html/Know/connessione/connessione.php";
//require "/var/www/html/Know/connessione/connessioneCrm.php";
//
//$obj = new ConnessioneVici();
//$conn = $obj->apriConnessioneVici();
//
//$obj19 = new Connessione();
//$conn19 = $obj19->apriConnessione();
//
//$objCrm = new ConnessioneCrm();
//$connCrm = $objCrm->apriConnessioneCrm();
/**
 * Inizio Processo prelievo giornaliero Siscall1
 */
$dataImport = date('Y-m-d H:i:s');
$oggi = date('Y-m-d');
$ieri = date('Y-m-d', strtotime('-1 days'));
$provenienza = "GreenNetwork";
$tipoCampagna = "";

/**
 * Recupero valore idStato
 */
$queryIdStato = "SELECT max(idStato) FROM `logImport`";
$risultatoIdStato = $conn19->query($queryIdStato);
$rigaStato = $risultatoIdStato->fetch_array();
$idStato = $rigaStato[0] + 1;
/**
 * Query inserimento log iniziale
 */
$queryInzioLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataImport','$provenienza','Inizio Import da $provenienza',0,'$idStato')";
$conn19->query($queryInzioLog);
/**
 * Query ricerca sul crm2
 */
$queryRicerca = "SELECT "
        . "operatore.user_name as 'Creato da', "
        . "date_format(greencf.cf_1276,'%d-%m-%Y') as 'data', "
        . "greencf.cf_1272 as 'comodity', "
        . "greencf.cf_1274 as 'Mercato', "
        . "greencf.cf_1278 as 'Sede', "
        . "greencf.cf_1384 as 'Metodo Pagamento', "
        . "greencf.cf_1394 as 'Metodo Invio', "
        . "greencf.cf_1428 as 'Stato PDA', "
        . "greencf.cf_1442 as 'Stato Luce', "
        . "greencf.cf_1444 as 'Stato Gas', "
        . "greencf.cf_1280 as 'winback', "
        . "entity.createdtime AS 'dataCreazione', "
        . "greencf.cf_1286 as 'Codice Campagna', "
        . "greencf.greennetworkid AS 'pratica', "
        . "greencf.cf_1454 as 'Sanata BO', "
        . "greencf.cf_1432 as 'idRiga Luce', "
        . "greencf.cf_1438 as 'idRiga Gas' "
        . "FROM "
        . "vtiger_greennetworkcf as greencf "
        . "inner join vtiger_greennetwork as green on greencf.greennetworkid=green.greennetworkid "
        . "inner join vtiger_crmentity as entity on green.greennetworkid=entity.crmid "
        . "inner join vtiger_users as operatore on entity.smownerid=operatore.id "
        . "WHERE "
        . "greencf.cf_1276 >'2023-01-31'";

//echo $queryRicerca;
$risultato = $connCrm->query($queryRicerca);
/**
 * Se la ricerca da errore segna nei log l'errore
 */
if (!$risultato) {
    $dataErrore = date('Y-m-d H:i:s');
    $errore = $connCrm->real_escape_string($connCrm->error);
    $queryErroreLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataErrore','$provenienza','$errore',0,'$idStato')";
    $conn19->query($queryErroreLog);
}
/**
 * se non da errore svuota la tabella, segnando nei log l'operazione, e la ricarica riga per riga da zero
 */ else {
    $queryTruncate = "TRUNCATE TABLE `green`";
    $conn19->query($queryTruncate);
    $queryTruncate2 = "TRUNCATE TABLE `aggiuntaGreen`";
    $conn19->query($queryTruncate2);
    $dataTruncate = date('Y-m-d H:i:s');
    $queryTruncateLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataTruncate','$provenienza','Truncate $provenienza',0,'$idStato')";
    $conn19->query($queryTruncateLog);
    while ($riga = $risultato->fetch_array()) {
        $pesoFormazione = 0;
        $isFormazione = false;

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
        $idRigaLuce = $riga[15];
        $idRigaGas = $riga[16];
        /**
         * ricerca id stato PDA
         */
        $queryStatoPda = "SELECT * FROM `greenStatoPDA` where descrizione='$statoPDA'";
        $risultatoStatoPda = $conn19->query($queryStatoPda);
        $conteggioStatoPda = $risultatoStatoPda->num_rows;
        if ($conteggioStatoPda == 0) {
            $queryInserimentoStatoPda = "INSERT INTO `greenStatoPDA`(`descrizione`) VALUES ('$statoPDA')";
            $conn19->query($queryInserimentoStatoPda);
            $idStatoPda = $conn19->insert_id;
        } else {
            $rigaStatoPda = $risultatoStatoPda->fetch_array();
            $idStatoPda = $rigaStatoPda[0];
        }
        /**
         * ricerca id stato luce
         */
        $queryStatoLuce = "SELECT * FROM `greenStatoLuce` where descrizione='$statoLuce'";
        $risultatoStatoLuce = $conn19->query($queryStatoLuce);
        $conteggioStatoLuce = $risultatoStatoLuce->num_rows;
        if ($conteggioStatoLuce == 0) {
            $queryInserimentoStatoLuce = "INSERT INTO `greenStatoLuce`( `descrizione`) VALUES ('$statoLuce')";
            $conn19->query($queryInserimentoStatoLuce);
            $idStatoLuce = $conn19->insert_id;
        } else {
            $rigaStatoLuce = $risultatoStatoLuce->fetch_array();
            $idStatoLuce = $rigaStatoLuce[0];
        }
        /**
         * Ricerca id stato Gas
         */
        $queryStatoGas = "SELECT * FROM `greenStatoGas` where descrizione='$statoGas'";
        //echo $queryStatoGas;
        $risultatoStatoGas = $conn19->query($queryStatoGas);
        $conteggioStatoGas = $risultatoStatoGas->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioStatoGas == 0) {
            $queryInserimentoStatoGas = "INSERT INTO `greenStatoGas`( `descrizione`) VALUES ('$statoGas')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoStatoGas);
            $idStatoGas = $conn19->insert_id;
        } else {
            $rigaStatoGas = $risultatoStatoGas->fetch_array();
            $idStatoGas = $rigaStatoGas[0];
        }
        if ($winback == "Si") {
            $mandato = "GreenNetwork Retention";
            $idMandato = 4;
        } else {
            $mandato = "GreenNetwork";
            $idMandato = 3;
        }

        $queryCampagna = "SELECT * FROM `greenCampagna` where nome='$codiceCampagna'";
        //echo $queryStatoGas;
        $risultatoCampagna = $conn19->query($queryCampagna);
        $conteggioCampagna = $risultatoCampagna->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioCampagna == 0) {
            $queryInserimentoCampagna = "INSERT INTO `greenCampagna`( `nome`) VALUES ('$codiceCampagna')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoCampagna);
            $idCampagna = $conn19->insert_id;
        } else {
            $rigaCampagna = $risultatoCampagna->fetch_array();
            $idCampagna = $rigaCampagna[0];
            $tipoCampagna = $rigaCampagna[2];
        }




        $queryInserimento = "INSERT INTO `green`"
                . "( `creatoDa`, `data`, `comodity`, `mercato`, `sede`, `metodoPagamento`, `statoPDA`, `statoLuce`, `statoGas`, `dataImport`, `mandato`,idStatoPda,idStatoLuce,idStatoGas,winback,idMandato,dataCreazione,idCampagna,campagna,metodoInvio,pratica,sanataBo)"
                . " VALUES ('$user','$data','$comodity','$mercato','$sede','$metodoPagamento','$statoPDA','$statoLuce','$statoGas','$dataImport','$mandato','$idStatoPda','$idStatoLuce','$idStatoGas','$winback','$idMandato','$dataCreazione','$idCampagna','$codiceCampagna','$metodoInvio','$pratica','$sanataBo')";
        //echo $queryInserimento;
        $conn19->query($queryInserimento);
        /*
         * Aggiunta per calcolo pesi
         */
        $indiceContratto = $conn19->insert_id;
        $pesoComodity = 0;
        $pesoInvio = 0;
        $pesoPagamento = 0;
        $pesoTotaleLordo = 0;
        $mese = date('Y-m-1', strtotime($data));
        //Peso Commodity

        $queryFormazione = "SELECT ore FROM `formazioneTotale` where nomeCompleto='$user' and giorno='$data'";
        $risultatoFormazione = $conn19->query($queryFormazione);
        if (($risultatoFormazione->num_rows) > 0) {
            $rigaFormazione = $risultatoFormazione->fetch_array();
            $ore = $rigaFormazione[0];
            if ($ore < 45) {
                $isFormazione = true;
            } else {
                $isFormazione = false;
            }
        } else {
            $isFormazione = false;
        }


        $queryPesoComodity = "SELECT peso FROM `greenPesiComoditi` WHERE tipoCampagna='$tipoCampagna' AND valore='$comodity' AND dataInizioValidita='$mese'";
        $risultatoPesoComodity = $conn19->query($queryPesoComodity);
        if (($risultatoPesoComodity->num_rows) > 0) {
            $rigaPesoComodity = $risultatoPesoComodity->fetch_array();
            $pesoComodity = $rigaPesoComodity[0];
        }

        $queryPesoInvio = "SELECT peso FROM `greenPesiInvio` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoInvio' AND dataInizioValidita='$mese'";
        $risultatoPesoInvio = $conn19->query($queryPesoInvio);
        if (($risultatoPesoInvio->num_rows) > 0) {
            $rigaPesoInvio = $risultatoPesoInvio->fetch_array();
            $pesoInvio = $rigaPesoInvio[0];
        }

        $queryPesoPagamento = "SELECT peso FROM `greenPesiMetodoPagamento` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoPagamento' AND dataInizioValidita='$mese'";
        $risultatoPesoPagamento = $conn19->query($queryPesoPagamento);
        if (($risultatoPesoPagamento->num_rows) > 0) {
            $rigaPesoPagamento = $risultatoPesoPagamento->fetch_array();
            $pesoPagamento = $rigaPesoPagamento[0];
        }



        $queryFaseLuce = "SELECT fase FROM `greenStatoLuce` WHERE id='$idStatoLuce'";
        $risultatoFaseLuce = $conn19->query($queryFaseLuce);
        $rigaFaseLuce = $risultatoFaseLuce->fetch_array();
        $faseLuce = $rigaFaseLuce[0];

        $queryFaseGas = "SELECT fase FROM `greenStatoGas` WHERE id='$idStatoGas'";
        $risultatoFaseGas = $conn19->query($queryFaseGas);
        $rigaFaseGas = $risultatoFaseGas->fetch_array();
        $faseGas = $rigaFaseGas[0];

        $queryFasePDA = "SELECT fase FROM `greenStatoPDA` WHERE id='$idStatoPda'";
        $risultatoFasePDA = $conn19->query($queryFasePDA);
        $rigaFasePDA = $risultatoFasePDA->fetch_array();
        $fasePDA = $rigaFasePDA[0];

        if ($fasePDA == "") {
            
        } else {
            $pesoTotaleLordo = $pesoComodity + $pesoInvio + $pesoPagamento;
            if($comodity=="Dual"){
                $pezzoLordo=2;
            }else{
                $pezzoLordo=1;
            }
        }


        $pesoSanataBo = 0;
        $tipoSanataBo = 0;
        if ($sanataBo == "Si") {
            $querySanataBo = "SELECT peso,tipoDetrazione FROM `greenPesiSanata` WHERE tipoCampagna='$tipoCampagna' AND valore='$sanataBo' AND dataInizioValidita='$mese' ";
            //echo $querySanataBo;
            $risultatoPesoSanato = $conn19->query($querySanataBo);
            if (($risultatoPesoSanato->num_rows) > 0) {
                $rigaPesoSanato = $risultatoPesoSanato->fetch_array();
                $pesoSanataBo = $rigaPesoSanato[0];
                $tipoSanataBo = $rigaPesoSanato[1];
//                echo $pesoSanataBo;
//                echo "<br>";
            }
        }

        if ($tipoSanataBo == 1) {
            $pesoTotaleLordo = $pesoTotaleLordo - $pesoSanataBo;
        } elseif ($tipoSanataBo == 2) {
            $pesoTotaleLordo = $pesoTotaleLordo - ($pesoTotaleLordo * $pesoSanataBo);
        }
        
          $fasePost = "KO";
        $statoPost="";
        if ($faseGas == $faseLuce && $comodity == "Dual") {
            $fasePost = $faseGas;
            $statoPost=$statoGas;
        } elseif ($comodity == "Luce") {
            $fasePost = $faseLuce;
            $statoPost=$statoLuce;
        } elseif ($comodity == "Gas") {
            $fasePost = $faseGas;
            $statoPost=$statoGas;
        } elseif ($faseLuce <> null && $comodity == "Polizza") {
            $fasePost = $faseLuce;
            $statoPost=$statoLuce;
        } elseif ($faseGas <> null && $comodity == "Polizza") {
            $fasePost = $faseGas;
            $statoPost=$statoGas;
        }

        $pesoTotaleNetto = 0;
        if ($faseGas == "OK" && $faseLuce == "OK" && $comodity == "Dual") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto=$pezzoLordo;
        } elseif ($faseLuce == "OK" && $comodity == "Luce") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto=$pezzoLordo;
        } elseif ($faseGas == "OK" && $comodity == "Gas") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto=$pezzoLordo;
        }
        $pesoTotalePagato = 0;
        if ($fasePDA == "OK") {
            if ($isFormazione == true) {
                $pesoTotalePagato = 0;
                $pesoFormazione = $pesoTotaleNetto;
                $pezzoFormazione=$pezzoNetto;
            } else {
                $pesoFormazione = 0;
                $pesoTotalePagato = $pesoTotaleNetto;
                $pezzoPagato=$pezzoNetto;
            }
        }



        $queryInserimentoSecondario = "INSERT INTO `aggiuntaGreen`(`id`, `tipoCampagna`, `pesoComodity`, `pesoInvio`, `pesoMPagamento`, `totalePesoLordo`, `faseLuce`, `faseGas`,"
                . " `totalePesoNetto`,mese,fasePDA,pesoTotalePagato,idRigaLuce,idRigaGas,pesoFormazione,pezzoLordo, PezzoNetto, PezzoPagato, PezzoFormazione,fasePost,statoPost) "
                . "VALUES ('$indiceContratto','$tipoCampagna','$pesoComodity','$pesoInvio','$pesoPagamento','$pesoTotaleLordo','$faseLuce','$faseGas',"
                . "'$pesoTotaleNetto','$mese','$fasePDA','$pesoTotalePagato','$idRigaLuce','$idRigaGas','$pesoFormazione', '$pezzoLordo','$pezzoNetto', '$pezzoPagato', '$pezzoFormazione','$fasePost','$statoPost')";
        //echo $queryInserimentoSecondario;
        $conn19->query($queryInserimentoSecondario);

//        if ($mese >= "2023-02-01") {
//            $queryUpdatePesi = "UPDATE `vtiger_greennetworkcf` SET cf_3767='$pesoTotaleLordo',cf_3769='$pesoTotalePagato' WHERE greennetworkid='$pratica'";
//            //echo $queryUpdatePesi;
//            $connCrm->query($queryUpdatePesi);
//        }
    }
    $dataFine = date('Y-m-d H:i:s');
    $queryFineLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataFine','$provenienza','Fine Import da $provenienza',1,'$idStato')";
    $conn19->query($queryFineLog);
//    header("location:../pannello.php");
}

/**
 * Inizio prelievo dati da crm2 tabella Vivigas
 */



