<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL | E_STRICT);
//mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
//
//date_default_timezone_set('Europe/Rome');
//
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
$provenienza = "Vivigas";

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
        . "vivicf.cf_1812 as 'data', "
        . "vivicf.cf_1806 as 'comodity', "
        . "vivicf.cf_1848 as 'Mercato', "
        . "vivicf.cf_1810 as 'Sede', "
        . "vivicf.cf_1778 as 'Metodo Pagamento', "
        . "vivicf.cf_1708 as 'Metodo Invio', "
        . "vivicf.cf_1718 as 'Stato PDA', "
        . "vivicf.cf_1724 as 'Stato Luce', "
        . "vivicf.cf_1726 as 'Stato Gas', "
        . "vivicf.cf_1808 as 'winback', "
        . "entity.createdtime AS 'dataCreazione', "
        . "vivicf.cf_1860 as 'Codice Campagna', "
        . "vivicf.vivigasid AS 'pratica', "
        . "vivicf.cf_1896 as 'Sanata BO', "
        . "vivicf.cf_1720 as 'idRigaLuce', "
        . "vivicf.cf_1722 as 'idRigaGas', "
        . "vivicf.cf_3759 as 'codiceFornituraLuce', "
        . "vivicf.cf_3757 as 'codiceFornituraGas', "
        . "vivicf.cf_4132 as 'idGestioneLead', "
        . "vivicf.cf_2978 as 'leadId' "
        . "FROM "
        . "`vtiger_vivigascf` as vivicf "
        . "inner join vtiger_vivigas as vivi on vivicf.vivigasid=vivi.vivigasid "
        . "inner join vtiger_crmentity as entity on vivi.vivigasid=entity.crmid "
        . "inner join vtiger_users as operatore on entity.smownerid=operatore.id ";

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
    $queryTruncate = "TRUNCATE TABLE `vivigas`";
    $conn19->query($queryTruncate);
    $queryTruncate2 = "TRUNCATE TABLE `aggiuntaVivigas`";
    $conn19->query($queryTruncate2);
    $dataTruncate = date('Y-m-d H:i:s');
    $queryTruncateLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataTruncate','$provenienza','Truncate $provenienza',0,'$idStato')";
    $conn19->query($queryTruncateLog);
    while ($riga = $risultato->fetch_array()) {
        $isFormazione = false;
        $pesoTotalePagato = 0;
        $pesoFormazione = 0;

        $pezzoLordo = 0;
        $pezzoNetto = 0;
        $pezzoPagato = 0;
        $pezzoFormazione = 0;

        $user = $riga[0];
        $data = $riga[1];
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
        $codiceFornituraLuce = $riga[17];
        $codiceFornituraGas = $riga[18];
          $idGestioneLead=$riga[19];
        $leadId=$riga[20];
        /**
         * ricerca id stato PDA
         */
        $queryStatoPda = "SELECT * FROM `vivigasStatoPDA` where descrizione='$statoPDA'";
        $risultatoStatoPda = $conn19->query($queryStatoPda);
        $conteggioStatoPda = $risultatoStatoPda->num_rows;
        if ($conteggioStatoPda == 0) {
            $queryInserimentoStatoPda = "INSERT INTO `vivigasStatoPDA`(`descrizione`) VALUES ('$statoPDA')";
            $conn19->query($queryInserimentoStatoPda);
            $idStatoPda = $conn19->insert_id;
        } else {
            $rigaStatoPda = $risultatoStatoPda->fetch_array();
            $idStatoPda = $rigaStatoPda[0];
        }
        /**
         * ricerca id stato luce
         */
        $queryStatoLuce = "SELECT * FROM `vivigasStatoLuce` where descrizione='$statoLuce'";
        $risultatoStatoLuce = $conn19->query($queryStatoLuce);
        $conteggioStatoLuce = $risultatoStatoLuce->num_rows;
        if ($conteggioStatoLuce == 0) {
            $queryInserimentoStatoLuce = "INSERT INTO `vivigasStatoLuce`( `descrizione`) VALUES ('$statoLuce')";
            $conn19->query($queryInserimentoStatoLuce);
            $idStatoLuce = $conn19->insert_id;
        } else {
            $rigaStatoLuce = $risultatoStatoLuce->fetch_array();
            $idStatoLuce = $rigaStatoLuce[0];
        }
        /**
         * Ricerca id stato Gas
         */
        $queryStatoGas = "SELECT * FROM `vivigasStatoGas` where descrizione='$statoGas'";
        //echo $queryStatoGas;
        $risultatoStatoGas = $conn19->query($queryStatoGas);
        $conteggioStatoGas = $risultatoStatoGas->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioStatoGas == 0) {
            $queryInserimentoStatoGas = "INSERT INTO `vivigasStatoGas`( `descrizione`) VALUES ('$statoGas')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoStatoGas);
            $idStatoGas = $conn19->insert_id;
        } else {
            $rigaStatoGas = $risultatoStatoGas->fetch_array();
            $idStatoGas = $rigaStatoGas[0];
        }
        if ($winback == "Si") {
            $mandato = "Vivigas Energia Retention";
            $idMandato = 2;
        } else {
            $mandato = "Vivigas Energia";
            $idMandato = 1;
        }

        $queryCampagna = "SELECT * FROM `vivigasCampagna` where nome='$codiceCampagna'";
        //echo $queryStatoGas;
        $risultatoCampagna = $conn19->query($queryCampagna);
        $conteggioCampagna = $risultatoCampagna->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioCampagna == 0) {
            $queryInserimentoCampagna = "INSERT INTO `vivigasCampagna`( `nome`) VALUES ('$codiceCampagna')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoCampagna);
            $idCampagna = $conn19->insert_id;
        } else {
            $rigaCampagna = $risultatoCampagna->fetch_array();
            $idCampagna = $rigaCampagna[0];
            $tipoCampagna = $rigaCampagna[2];
        }




                $queryInserimento = "INSERT INTO `vivigas`"
                . "( `creatoDa`, `data`, `comodity`, `mercato`, `sede`, `metodoPagamento`, `statoPDA`, `statoLuce`, `statoGas`, `dataImport`, `mandato`,idStatoPda,idStatoLuce,idStatoGas,winback,idMandato,dataCreazione,idCampagna,campagna,metodoInvio,pratica,sanataBo,idGestioneLead,leadId)"
                . " VALUES ('$user','$data','$comodity','$mercato','$sede','$metodoPagamento','$statoPDA','$statoLuce','$statoGas','$dataImport','vivigas','$idStatoPda','$idStatoLuce','$idStatoGas','$winback','$idMandato','$dataCreazione','$idCampagna','$codiceCampagna','$metodoInvio','$pratica','$sanataBo','$idGestioneLead','$leadId')";
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
        //formazione

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
//        echo "++++".intval($isFormazione);
// echo "<br>";
        //Peso Commodity
        $queryPesoComodity = "SELECT peso FROM `vivigasPesiComoditi` WHERE tipoCampagna='$tipoCampagna' AND valore='$comodity' AND dataInizioValidita='$mese'";
        $risultatoPesoComodity = $conn19->query($queryPesoComodity);
        if (($risultatoPesoComodity->num_rows) > 0) {
            $rigaPesoComodity = $risultatoPesoComodity->fetch_array();
            $pesoComodity = $rigaPesoComodity[0];
        }

        $queryPesoInvio = "SELECT peso FROM `vivigasPesiInvio` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoInvio' AND dataInizioValidita='$mese'";
        $risultatoPesoInvio = $conn19->query($queryPesoInvio);
        if (($risultatoPesoInvio->num_rows) > 0) {
            $rigaPesoInvio = $risultatoPesoInvio->fetch_array();
            $pesoInvio = $rigaPesoInvio[0];
        }

        $queryPesoPagamento = "SELECT peso FROM `vivigasPesiMetodoPagamento` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoPagamento' AND dataInizioValidita='$mese'";
        $risultatoPesoPagamento = $conn19->query($queryPesoPagamento);
        if (($risultatoPesoPagamento->num_rows) > 0) {
            $rigaPesoPagamento = $risultatoPesoPagamento->fetch_array();
            $pesoPagamento = $rigaPesoPagamento[0];
        }



        $queryFaseLuce = "SELECT fase FROM `vivigasStatoLuce` WHERE id='$idStatoLuce'";
        $risultatoFaseLuce = $conn19->query($queryFaseLuce);
        $rigaFaseLuce = $risultatoFaseLuce->fetch_array();
        $faseLuce = $rigaFaseLuce[0];

        $queryFaseGas = "SELECT fase FROM `vivigasStatoGas` WHERE id='$idStatoGas'";
        $risultatoFaseGas = $conn19->query($queryFaseGas);
        $rigaFaseGas = $risultatoFaseGas->fetch_array();
        $faseGas = $rigaFaseGas[0];

        $queryFasePDA = "SELECT fase FROM `vivigasStatoPDA` WHERE id='$idStatoPda'";
        $risultatoFasePDA = $conn19->query($queryFasePDA);
        $rigaFasePDA = $risultatoFasePDA->fetch_array();
        $fasePDA = $rigaFasePDA[0];

        if ($fasePDA == "") {
            
        } else {
            $pesoTotaleLordo = $pesoComodity + $pesoInvio + $pesoPagamento;
            if ($comodity == "DUAL") {
                $pezzoLordo = 2;
            } else {
                $pezzoLordo = 1;
            }
        }



        $pesoSanataBo = 0;
        $tipoSanataBo = 0;
        if ($sanataBo == "SI") {
            $querySanataBo = "SELECT peso,tipoDetrazione FROM `vivigasPesiSanata` WHERE tipoCampagna='$tipoCampagna' AND valore='$sanataBo' AND dataInizioValidita='$mese' ";
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

        $pesoTotaleNetto = 0;
        if ($faseGas == "OK" && $faseLuce == "OK" && $comodity == "DUAL") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseLuce == "OK" && $comodity == "LUCE") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseGas == "OK" && $comodity == "GAS") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        }

        if ($fasePDA == "OK") {
            if ($isFormazione == true) {
                $pesoTotalePagato = 0;
                $pesoFormazione = $pesoTotaleNetto;
                $pezzoFormazione = $pezzoNetto;
            } else {
                $pesoFormazione = 0;
                $pesoTotalePagato = $pesoTotaleNetto;
                $pezzoPagato = $pezzoNetto;
            }
        }

        $fasePost = "KO";
        $statoPost = "";
        if ($faseGas == $faseLuce && $comodity == "DUAL") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        } elseif ($comodity == "LUCE") {
            $fasePost = $faseLuce;
            $statoPost = $statoLuce;
        } elseif ($comodity == "GAS") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        } elseif ($faseLuce <> null && $comodity == "Polizza") {
            $fasePost = $faseLuce;
            $statoPost = $statoLuce;
        } elseif ($faseGas <> null && $comodity == "Polizza") {
            $fasePost = $faseGas;
            $statoPost = $statoGas;
        }

        $queryInserimentoSecondario = "INSERT INTO `aggiuntaVivigas`(`id`, `tipoCampagna`, `pesoComodity`, `pesoInvio`, `pesoMPagamento`, `totalePesoLordo`,"
                . " `faseLuce`, `faseGas`, `totalePesoNetto`,mese,fasePDA,pesoTotalePagato,idRigaLuce,idRigaGas,"
                . "codiceFornituraLuce,codiceFornituraGas,pesoFormazione,pezzoLordo,pezzoNetto,PezzoPagato,PezzoFormazione,fasePost,statoPost) "
                . "VALUES ('$indiceContratto','$tipoCampagna','$pesoComodity','$pesoInvio','$pesoPagamento','$pesoTotaleLordo',"
                . "'$faseLuce','$faseGas','$pesoTotaleNetto','$mese','$fasePDA','$pesoTotalePagato','$idRigaLuce','$idRigaGas',"
                . "'$codiceFornituraLuce','$codiceFornituraGas','$pesoFormazione','$pezzoLordo','$pezzoNetto','$pezzoPagato','$pezzoFormazione','$fasePost','$statoPost')";
        //echo $queryInserimentoSecondario;
        $conn19->query($queryInserimentoSecondario);

//        if ($mese >= "2023-02-01") {
//            $queryUpdatePesi = "UPDATE `vtiger_vivigascf` SET cf_3761='$pesoTotaleLordo',cf_3765='$pesoTotalePagato' WHERE vivigasid='$pratica'";
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



