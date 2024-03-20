<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

date_default_timezone_set('Europe/Rome');

require "/var/www/html/Know/connessione/connessione_msqli_vici.php";
require "/var/www/html/Know/connessione/connessione.php";
require "/var/www/html/Know/connessione/connessioneCrm.php";

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
$confrontoCRM = date('Y-m-1', strtotime('-3 months'));
$provenienza = "plenitude";
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
        . "replace(operatore.user_name,'enel','') as 'Creato da', "
        . "date_format(plenicf.cf_3563,'%d-%m-%Y') as 'data', "
        . "plenicf.cf_3565 as 'comodity', "
        . "plenicf.cf_3567 as 'Mercato', "
        . "plenicf.cf_3569 as 'Sede', "
        . "plenicf.cf_3659 as 'Metodo Pagamento', "
        . "plenicf.cf_3609 as 'Metodo Invio', "
        . "plenicf.cf_3673 as 'Stato PDA', "
        . "plenicf.cf_3681 as 'Stato Luce', "
        . "plenicf.cf_3683 as 'Stato Gas', "
        . "entity.createdtime AS 'dataCreazione', "
        . "plenicf.cf_3571 as 'Codice Campagna', "
        . "plenicf.plenitudeid AS 'pratica', "
        . "plenicf.cf_3677 AS 'codicePlicoLuce', "
        . "plenicf.cf_3679 AS 'codicePlicoGas', "
        . "plenicf.cf_3739 AS 'tipo acquisizione', "
        . "plenicf.cf_4070 AS 'id gestione lead', "
        . "plenicf.cf_4072 AS 'id leadId' "
        . "FROM "
        . "vtiger_plenitudecf as plenicf "
        . "inner join vtiger_plenitude as pleni on plenicf.plenitudeid=pleni.plenitudeid "
        . "inner join vtiger_crmentity as entity on pleni.plenitudeid=entity.crmid "
        . "inner join vtiger_users as operatore on entity.smownerid=operatore.id "
        . "WHERE "
        . "plenicf.cf_3563 >'2023-01-31'";

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
    $queryTruncate = "TRUNCATE TABLE `plenitude`";
    $conn19->query($queryTruncate);
    $queryTruncate2 = "TRUNCATE TABLE `aggiuntaPlenitude`";
    $conn19->query($queryTruncate2);
    $dataTruncate = date('Y-m-d H:i:s');
    $queryTruncateLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataTruncate','$provenienza','Truncate $provenienza',0,'$idStato')";
    $conn19->query($queryTruncateLog);
    while ($riga = $risultato->fetch_array()) {
        $pesoFormazione = 0;
        $isFormazione = false;
        $pesoTotalePagato = 0;

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
        $winback = "no";
        $dataCreazione = $riga[10];
        $codiceCampagna = $riga[11];
        $pratica = $riga[12];
        $codicePlicoLuce = $riga[13];
        $codicePlicoGas = $riga[14];
        $sanataBo = "no";
        $tipoAcquisizione = $riga[15];
        $idGestioneLead=$riga[16];
        $leadId=$riga[17];
        /**
         * ricerca id stato PDA
         */
        $queryStatoPda = "SELECT * FROM `plenitudeStatoPDA` where descrizione='$statoPDA'";
        $risultatoStatoPda = $conn19->query($queryStatoPda);
        $conteggioStatoPda = $risultatoStatoPda->num_rows;
        if ($conteggioStatoPda == 0) {
            $queryInserimentoStatoPda = "INSERT INTO `plenitudeStatoPDA`(`descrizione`) VALUES ('$statoPDA')";
            $conn19->query($queryInserimentoStatoPda);
            $idStatoPda = $conn19->insert_id;
        } else {
            $rigaStatoPda = $risultatoStatoPda->fetch_array();
            $idStatoPda = $rigaStatoPda[0];
        }
        /**
         * ricerca id stato luce
         */
        $queryStatoLuce = "SELECT * FROM `plenitudeStatoLuce` where descrizione='$statoLuce'";
        $risultatoStatoLuce = $conn19->query($queryStatoLuce);
        $conteggioStatoLuce = $risultatoStatoLuce->num_rows;
        if ($conteggioStatoLuce == 0) {
            $queryInserimentoStatoLuce = "INSERT INTO `plenitudeStatoLuce`( `descrizione`) VALUES ('$statoLuce')";
            $conn19->query($queryInserimentoStatoLuce);
            $idStatoLuce = $conn19->insert_id;
        } else {
            $rigaStatoLuce = $risultatoStatoLuce->fetch_array();
            $idStatoLuce = $rigaStatoLuce[0];
        }
        /**
         * Ricerca id stato Gas
         */
        $queryStatoGas = "SELECT * FROM `plenitudeStatoGas` where descrizione='$statoGas'";
        //echo $queryStatoGas;
        $risultatoStatoGas = $conn19->query($queryStatoGas);
        $conteggioStatoGas = $risultatoStatoGas->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioStatoGas == 0) {
            $queryInserimentoStatoGas = "INSERT INTO `plenitudeStatoGas`( `descrizione`) VALUES ('$statoGas')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoStatoGas);
            $idStatoGas = $conn19->insert_id;
        } else {
            $rigaStatoGas = $risultatoStatoGas->fetch_array();
            $idStatoGas = $rigaStatoGas[0];
        }
        if ($winback == "Si") {
            $mandato = "Plenitude Retention";
            $idMandato = 5;
        } else {
            $mandato = "Plenitude";
            $idMandato = 4;
        }

        $queryCampagna = "SELECT * FROM `plenitudeCampagna` where nome='$codiceCampagna'";
        //echo $queryStatoGas;
        $risultatoCampagna = $conn19->query($queryCampagna);
        $conteggioCampagna = $risultatoCampagna->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioCampagna == 0) {
            $queryInserimentoCampagna = "INSERT INTO `plenitudeCampagna`( `nome`) VALUES ('$codiceCampagna')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoCampagna);
            $idCampagna = $conn19->insert_id;
        } else {
            $rigaCampagna = $risultatoCampagna->fetch_array();
            $idCampagna = $rigaCampagna[0];
            $tipoCampagna = $rigaCampagna[2];
        }




        $queryInserimento = "INSERT INTO `plenitude`"
                . "( `creatoDa`, `data`, `comodity`, `mercato`, `sede`, `metodoPagamento`, `statoPDA`, `statoLuce`, `statoGas`, `dataImport`, `mandato`,idStatoPda,idStatoLuce,idStatoGas,winback,idMandato,dataCreazione,idCampagna,campagna,metodoInvio,pratica,sanataBo,tipoAcquisizione,idGestioneLead,leadId)"
                . " VALUES ('$user','$data','$comodity','$mercato','$sede','$metodoPagamento','$statoPDA','$statoLuce','$statoGas','$dataImport','$mandato','$idStatoPda','$idStatoLuce','$idStatoGas','$winback','$idMandato','$dataCreazione','$idCampagna','$codiceCampagna','$metodoInvio','$pratica','$sanataBo','$tipoAcquisizione','$idGestioneLead','$leadId')";
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
        $mese = date('Y-m-01', strtotime($data));

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


        /**
         * peso comodity
         * aggiornamento del 5/12/2023 aggiunto il tipo di acquisizione
         */
        if ($data < "2023-12-01") {
            $queryPesoComodity = "SELECT peso FROM `plenitudePesiComoditi` WHERE tipoCampagna='$tipoCampagna' AND valore='$comodity' AND dataInizioValidita='$mese'";
            $risultatoPesoComodity = $conn19->query($queryPesoComodity);
            if (($risultatoPesoComodity->num_rows) > 0) {
                $rigaPesoComodity = $risultatoPesoComodity->fetch_array();
                $pesoComodity = $rigaPesoComodity[0];
            }
        } else {
            $queryPesoComodity = "SELECT peso FROM `plenitudePesiComoditi` WHERE tipoCampagna='$tipoCampagna' AND valore='$comodity' AND dataInizioValidita='$mese' AND tipoAcquisizione='$tipoAcquisizione'";
            //echo $queryPesoComodity;
            $risultatoPesoComodity = $conn19->query($queryPesoComodity);
            if (($risultatoPesoComodity->num_rows) > 0) {
                $rigaPesoComodity = $risultatoPesoComodity->fetch_array();
                $pesoComodity = $rigaPesoComodity[0];
            }
        }





        if ($comodity == "Polizza") {
            $pesoInvio = 0;
        } else {
            $queryPesoInvio = "SELECT peso FROM `plenitudePesiInvio` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoInvio' AND dataInizioValidita='$mese'";
            $risultatoPesoInvio = $conn19->query($queryPesoInvio);
            if (($risultatoPesoInvio->num_rows) > 0) {
                $rigaPesoInvio = $risultatoPesoInvio->fetch_array();
                $pesoInvio = $rigaPesoInvio[0];
            }
        }

        if ($comodity == "Polizza") {
            $pesoPagamento = 0;
        } else {
            $queryPesoPagamento = "SELECT peso FROM `plenitudePesiMetodoPagamento` WHERE tipoCampagna='$tipoCampagna' AND valore='$metodoPagamento' AND dataInizioValidita='$mese'";
            $risultatoPesoPagamento = $conn19->query($queryPesoPagamento);
            if (($risultatoPesoPagamento->num_rows) > 0) {
                $rigaPesoPagamento = $risultatoPesoPagamento->fetch_array();
                $pesoPagamento = $rigaPesoPagamento[0];
            }
        }


        $queryFaseLuce = "SELECT fase FROM `plenitudeStatoLuce` WHERE id='$idStatoLuce'";
        $risultatoFaseLuce = $conn19->query($queryFaseLuce);
        $rigaFaseLuce = $risultatoFaseLuce->fetch_array();
        $faseLuce = $rigaFaseLuce[0];

        $queryFaseGas = "SELECT fase FROM `plenitudeStatoGas` WHERE id='$idStatoGas'";
        $risultatoFaseGas = $conn19->query($queryFaseGas);
        $rigaFaseGas = $risultatoFaseGas->fetch_array();
        $faseGas = $rigaFaseGas[0];

        $queryFasePDA = "SELECT fase FROM `plenitudeStatoPDA` WHERE id='$idStatoPda'";
        $risultatoFasePDA = $conn19->query($queryFasePDA);
        $rigaFasePDA = $risultatoFasePDA->fetch_array();
        $fasePDA = $rigaFasePDA[0];

        if ($fasePDA == "") {
            
        } else {
            $pesoTotaleLordo = $pesoComodity + $pesoInvio + $pesoPagamento;
            if ($comodity == "Dual") {
                $pezzoLordo = 2;
            } else {
                $pezzoLordo = 1;
            }
        }

        $pesoSanataBo = 0;
        $tipoSanataBo = 0;
        if ($sanataBo == "SI") {
            $querySanataBo = "SELECT peso,tipoDetrazione FROM `plenitudePesiSanata` WHERE tipoCampagna='$tipoCampagna' AND valore='$sanataBo' AND dataInizioValidita='$mese' ";
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
        if ($faseGas == "OK" && $faseLuce == "OK" && $comodity == "Dual") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseLuce == "OK" && $comodity == "Luce") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseGas == "OK" && $comodity == "Gas") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseLuce == "OK" && $comodity == "Polizza") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } elseif ($faseGas == "OK" && $comodity == "Polizza") {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
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
        } else {
            $pesoFormazione = 0;
            $pesoTotalePagato = 0;
        }





        $queryInserimentoSecondario = "INSERT INTO `aggiuntaPlenitude"
                . "`(`id`, `tipoCampagna`, `pesoComodity`, `pesoInvio`, `pesoMPagamento`, `totalePesoLordo`, `faseLuce`, `faseGas`, `totalePesoNetto`,"
                . "mese,fasePDA,pesoTotalePagato,codicePlicoLuce,codicePlicoGas,pesoFormazione, pezzoLordo, pezzoNetto, pezzoPagato, pezzoFormazione,fasePost,statoPost) "
                . "VALUES ('$indiceContratto','$tipoCampagna','$pesoComodity','$pesoInvio','$pesoPagamento','$pesoTotaleLordo','$faseLuce','$faseGas','$pesoTotaleNetto',"
                . "'$mese','$fasePDA','$pesoTotalePagato','$codicePlicoLuce','$codicePlicoGas','$pesoFormazione', '$pezzoLordo', '$pezzoNetto', '$pezzoPagato','$pezzoFormazione','$fasePost','$statoPost')";
        //echo $queryInserimentoSecondario;
        $conn19->query($queryInserimentoSecondario);

//        if ($mese >= $confrontoCRM) {
//            $queryUpdatePesi = "UPDATE `vtiger_plenitudecf` SET cf_3771='$pesoTotaleLordo',cf_3773='$pesoTotalePagato' WHERE plenitudeid='$pratica'";
//            //echo $queryUpdatePesi;
//            $connCrm->query($queryUpdatePesi);
//        }
    }
    $dataFine = date('Y-m-d H:i:s');
    $queryFineLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataFine','$provenienza','Fine Import da $provenienza',1,'$idStato')";
    $conn19->query($queryFineLog);
    header("location:../pannello.php");
}

/**
 * Inizio prelievo dati da crm2 tabella Vivigas
 */



