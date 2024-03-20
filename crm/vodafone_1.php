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
$provenienza = "Vodafone";
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
        . "date_format(vodacf.cf_2986,'%d-%m-%Y') as 'data', "
        . "entity.createdtime AS 'dataCreazione', "
        . "vodacf.vodafoneid AS 'pratica', "
        . "vodacf.cf_2988 as 'Codice Campagna', "
        . "vodacf.cf_3146 as 'Stato PDA', "
        . "vodacf.cf_3254 as 'idEagle', "
        . "vodacf.cf_3148 as 'id Ordine Vola', "
        . "vodacf.cf_3156 as 'codice Siebel', "
        . "vodacf.cf_3536 as 'idEagle Mobile', "
        . "vodacf.cf_3506 as 'GNP', "
        . "vodacf.cf_3504 as 'CB VDF', "
        . "vodacf.cf_3222 as 'Servizio Ready', "
        . "vodacf.cf_3068 as 'Modalita Pagamento', "
        . "vodacf.cf_3122 as 'dummy2', "
        . "vodacf.cf_3226 as 'Rete Sicura', "
        . "vodacf.cf_3064 as 'Piano Telefonico', "
        . "vodacf.cf_3234 as 'tipo Vendita', "
        . "vodacf.cf_3106 as 'tipologia Offerta', "
        . "vodacf.cf_3502 as 'tipo Contratto', "
        . "vodacf.cf_3228 as 'opzione1', "
        . "vodacf.cf_3192 as 'tipo Sim', "
        . "if(vodacf.cf_3228='','no','si') as 'numero Contatto', "
        . "vodacf.cf_3534 as 'Fase Pratica', "
        . "vodacf.cf_4114 as 'opzione2', "
        . "vodacf.cf_3232 as 'opzione3', "
        . "vodacf.cf_4076 as 'idGestioneLead', "
        . "vodacf.cf_3869 as 'leadId' "
        . "FROM "
        . "vtiger_vodafonecf as vodacf "
        . "inner join vtiger_vodafone as voda on vodacf.vodafoneid=voda.vodafoneid "
        . "inner join vtiger_crmentity as entity on voda.vodafoneid=entity.crmid "
        . "inner join vtiger_users as operatore on entity.smownerid=operatore.id "
        . "WHERE "
        . "vodacf.cf_2986 >'2023-01-31'";

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
    $queryTruncate = "TRUNCATE TABLE `vodafone`";
    $conn19->query($queryTruncate);
    $queryTruncate2 = "TRUNCATE TABLE `aggiuntaVodafone`";
    $conn19->query($queryTruncate2);
    $dataTruncate = date('Y-m-d H:i:s');
    $queryTruncateLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataTruncate','$provenienza','Truncate $provenienza',0,'$idStato')";
    $conn19->query($queryTruncateLog);
    while ($riga = $risultato->fetch_array()) {
        $pesoFormazione = 0;
        $pesoPagato = 0;

        $pezzoLordo = 0;
        $pezzoNetto = 0;
        $pezzoPagato = 0;
        $pezzoFormazione = 0;

        $user = $riga[0];
        $dataVendita = date('Y-m-d', strtotime(strtr($riga[1], '/', '-')));
        $dataCreazione = date('Y-m-d h:m:s', strtotime(strtr($riga[2], '/', '-')));
        $pratica = $riga[3];
        $codiceCampagna = $riga[4];
        $statoPda = $riga[5];
        $idEagle = $riga[6];
        $idOrdineVola = $riga[7];
        $codiceSiebel = $riga[8];
        $idEagleMobile = $riga[9];
        $gnp = $riga[10];
        $cbVdf = $riga[11];
        $servizioReady = $riga[12];
        $modalitaPagamento = $riga[13];
        $dummy = $riga[14];
        $reteSicura = $riga[15];
        $pianoTelefonico = $riga[16];
        $tipoVendita = $riga[17];
        $tipologiaOfferta = $riga[18];
        $tipoContratto = $riga[19];
        $opzione1 = $riga[20];
        $tipoSim = $riga[21];
        $numeroContatto = $riga[22];
        $fasePratica = $riga[23];
        $opzione2 = $riga[24];
        $opzione3 = $riga[25];
         $idGestioneLead=$riga[26];
        $leadId=$riga[27];

        /**
         * ricerca id stato PDA
         */
        $queryStatoPda = "SELECT * FROM `vodafoneStatoPDA` where descrizione='$statoPda'";
        $risultatoStatoPda = $conn19->query($queryStatoPda);
        $conteggioStatoPda = $risultatoStatoPda->num_rows;
        if ($conteggioStatoPda == 0) {
            $queryInserimentoStatoPda = "INSERT INTO `vodafoneStatoPDA`(`descrizione`) VALUES ('$statoPda')";
            $conn19->query($queryInserimentoStatoPda);
            $idStatoPda = $conn19->insert_id;
        } else {
            $rigaStatoPda = $risultatoStatoPda->fetch_array();
            $idStatoPda = $rigaStatoPda[0];
            $fasePda=$rigaStatoPda[2];
        }

        /**
         * ricerca id fase pratica
         */
        $queryStatoPratica = "SELECT * FROM `vodafoneStatoPratica` where descrizione='$fasePratica'";
        $risultatoStatoPratica = $conn19->query($queryStatoPratica);
        $conteggioStatoPratica = $risultatoStatoPratica->num_rows;
        if ($conteggioStatoPratica == 0) {
            $queryInserimentoStatoPratica = "INSERT INTO `vodafoneStatoPratica`(`descrizione`) VALUES ('$fasePratica')";
            $conn19->query($queryInserimentoStatoPratica);
            $idStatoPratico = $conn19->insert_id;
        } else {
            $rigaStatoPratica = $risultatoStatoPratica->fetch_array();
            $idStatoPratico = $rigaStatoPratica[0];
        }


        $queryCampagna = "SELECT * FROM `vodafoneCampagna` where nome='$codiceCampagna'";
        //echo $queryStatoGas;
        $risultatoCampagna = $conn19->query($queryCampagna);
        $conteggioCampagna = $risultatoCampagna->num_rows;
        //echo $conteggioStatoGas;
        if ($conteggioCampagna == 0) {
            $queryInserimentoCampagna = "INSERT INTO `vodafoneCampagna`( `nome`) VALUES ('$codiceCampagna')";
            //echo $queryInserimentoStatoGas;
            $conn19->query($queryInserimentoCampagna);
            $idCampagna = $conn19->insert_id;
        } else {
            $rigaCampagna = $risultatoCampagna->fetch_array();
            $idCampagna = $rigaCampagna[0];
            $tipoCampagna = $rigaCampagna[2];
        }




        $queryInserimento = "INSERT INTO `vodafone`"
                . "(creatoDA,dataVendita,dataCreazione,pratica,codiceCampagna,statoPda,idEagle,idOrdineVola,codiceSiebel,idEagleMobile,gnp,"
                . "cbVdf,servizioReady,modalitaPagamento,dummy2,reteSicura,pianoTelefonico,tipoVendita,tipologiaOfferta,tipoContratto,"
                . "opzione1,tipoSim,numeroContatto,fasePratica,opzioni2,opzioni3,idGestioneLead,leadId )"
                . " VALUES ('$user','$dataVendita','$dataCreazione','$pratica','$codiceCampagna','$statoPda','$idEagle','$idOrdineVola','$codiceSiebel','$idEagleMobile','$gnp',"
                . "'$cbVdf','$servizioReady','$modalitaPagamento','$dummy','$reteSicura','$pianoTelefonico','$tipoVendita','$tipologiaOfferta','$tipoContratto',"
                . "'$opzione1','$tipoSim','$numeroContatto','$fasePratica','$opzione2','$opzione3','$idGestioneLead','$leadId')";
        //echo $queryInserimento;
        $conn19->query($queryInserimento);
//        /*
//         * Aggiunta per calcolo pesi
//         */
        $indiceContratto = $conn19->insert_id;
        $pesoBase = 0;
        $pesoOpzione = 0;
        $pesoPiano = 0;
        $pesoMobile = 0;
        $pesoPagamento = 0;
//        $pesoComodity = 0;
//        $pesoInvio = 0;


        $mese = date('Y-m-1', strtotime($dataVendita));

        $queryFormazione = "SELECT ore FROM `formazioneTotale` where nomeCompleto='$user' and giorno='$dataVendita'";
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

//Peso Base
        $queryPesoBase = "SELECT peso FROM `vodafonePesiBase` WHERE tipoCampagna='$gnp' AND valore='$tipoContratto' AND dataInizioValidita='$mese'";
        $risultatoPesoBase = $conn19->query($queryPesoBase);
        if (($risultatoPesoBase->num_rows) > 0) {
            $rigaPesoBase = $risultatoPesoBase->fetch_array();
            $pesoBase = $rigaPesoBase[0];
        } else {
            $queryInserimentoPesoBase = "INSERT INTO `vodafonePesiBase`( `dataInizioValidita`, `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$tipoContratto',0)";
            $conn19->query($queryInserimentoPesoBase);
        }
//Peso Piano
        $queryPesoPiano = "SELECT peso FROM `vodafonePesiPiano` WHERE  valore='$pianoTelefonico' AND dataInizioValidita='$mese'";
        $risultatoPesoPiano = $conn19->query($queryPesoPiano);
        if (($risultatoPesoPiano->num_rows) > 0) {
            $rigaPesoPiano = $risultatoPesoPiano->fetch_array();
            $pesoPiano = $rigaPesoPiano[0];
        } else {
            $queryPesoPiano = "INSERT INTO `vodafonePesiPiano`(`dataInizioValidita`,  `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$pianoTelefonico',0)";
            $conn19->query($queryPesoPiano);
        }

        //Peso Opzione
        if ($opzione1 <> "") {
            $queryPesoOpzione = "SELECT peso FROM `vodafonePesiOpzioni` WHERE  valore='$opzione1' AND dataInizioValidita='$mese'";
            $risultatoPesoOpzione = $conn19->query($queryPesoOpzione);
            if (($risultatoPesoOpzione->num_rows) > 0) {
                $rigaPesoOpzione = $risultatoPesoOpzione->fetch_array();
                $pesoOpzione = $rigaPesoOpzione[0];
            } else {
                $queryInserimentoPesoOpzioni = "INSERT INTO `vodafonePesiOpzioni`(`dataInizioValidita`, `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$opzione1',0)";
                $conn19->query($queryInserimentoPesoOpzioni);
            }
        }
        //Peso Opzione2
        if ($opzione2 <> "") {
            $queryPesoOpzione2 = "SELECT peso FROM `vodafonePesiOpzioni` WHERE  valore='$opzione2' AND dataInizioValidita='$mese'";
            $risultatoPesoOpzione2 = $conn19->query($queryPesoOpzione2);
            if (($risultatoPesoOpzione2->num_rows) > 0) {
                $rigaPesoOpzione2 = $risultatoPesoOpzione2->fetch_array();
                $pesoOpzione += $rigaPesoOpzione2[0];
            } else {
                $queryInserimentoPesoOpzioni = "INSERT INTO `vodafonePesiOpzioni`(`dataInizioValidita`, `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$opzione2',0)";
                $conn19->query($queryInserimentoPesoOpzioni);
            }
        }
        //Peso Opzione3
        if ($opzione3 <> "") {
            $queryPesoOpzione3 = "SELECT peso FROM `vodafonePesiOpzioni` WHERE  valore='$opzione3' AND dataInizioValidita='$mese'";

            $risultatoPesoOpzione3 = $conn19->query($queryPesoOpzione3);
            if (($risultatoPesoOpzione3->num_rows) > 0) {
                $rigaPesoOpzione3 = $risultatoPesoOpzione3->fetch_array();
                $pesoOpzione += $rigaPesoOpzione3[0];
            } else {
                $queryInserimentoPesoOpzioni = "INSERT INTO `vodafonePesiOpzioni`(`dataInizioValidita`, `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$opzione3',0)";
                $conn19->query($queryInserimentoPesoOpzioni);
            }
        }
        //Peso Mobile
        $queryPesoMobile = "SELECT peso FROM `vodafonePesiMobile` WHERE  valore='$tipoSim' AND dataInizioValidita='$mese'";
        $risultatoPesoMobile = $conn19->query($queryPesoMobile);
        if (($risultatoPesoMobile->num_rows) > 0) {
            $rigaPesoMobile = $risultatoPesoMobile->fetch_array();
            $pesoMobile = $rigaPesoMobile[0];
        } else {
            $queryInserimentoPesoMobile = "INSERT INTO `vodafonePesiMobile`(`dataInizioValidita`,`tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$tipoSim',0)";
            $conn19->query($queryInserimentoPesoMobile);
        }
        //Peso Pagamento
        $queryPesoPagamento = "SELECT peso FROM `vodafonePesiPagamento` WHERE  valore='$modalitaPagamento' AND dataInizioValidita='$mese'";
        $risultatoPesoPagamento = $conn19->query($queryPesoPagamento);
        if (($risultatoPesoPagamento->num_rows) > 0) {
            $rigaPesoPagamento = $risultatoPesoPagamento->fetch_array();
            $pesoPagamento = $rigaPesoPagamento[0];
        } else {
            $queryInserimentoPesoPagamento = "INSERT INTO `vodafonePesiPagamento`(`dataInizioValidita`, `tipoCampagna`, `valore`, `peso`) VALUES ('$mese','$gnp','$modalitaPagamento',0)";
            $conn19->query($queryInserimentoPesoPagamento);
        }

        $pesoTotaleLordo = $pesoBase + $pesoMobile + $pesoOpzione + $pesoPagamento + $pesoPiano;
        $pezzoLordo = 1;

        if ($statoPda == 'OK DEFINITIVO') {
            $pesoTotaleNetto = $pesoTotaleLordo;
            $pezzoNetto = $pezzoLordo;
        } else {
            $pesoTotaleNetto = 0;
        }



        $queryFasePratica = "SELECT fase FROM `vodafoneStatoPratica` WHERE descrizione='$fasePratica'";
        $risultatoFasePratica = $conn19->query($queryFasePratica);
        $rigaFasePratica = $risultatoFasePratica->fetch_array();
        $statoPratica = $rigaFasePratica[0];
        //echo $statoPratica;



        if ($statoPratica == "OK") {
            if ($isFormazione == true) {
                $pesoPagato = 0;
                $pesoFormazione = $pesoTotaleNetto;
                $pezzoFormazione = $pezzoNetto;
            } else {
                $pesoFormazione = 0;
                $pesoPagato = $pesoTotaleNetto;
                $pezzoPagato = $pezzoNetto;
            }
        } else {
            $pesoPagato = 0;
        }

        $queryInserimentoSecondario = "INSERT INTO `aggiuntaVodafone`(`id`, mese,pesoBase,pesoPiano,pesoOpzione,pesoMobile,PesoPagamento,pesoTotaleLordo,pesoTotaleNetto,pesoPagato,pesoFormazione,"
                . " pezzoLordo, pezzoNetto, pezzoPagato, pezzoFormazione,fasePDA) "
                . "VALUES ('$indiceContratto','$mese','$pesoBase','$pesoPiano','$pesoOpzione','$pesoMobile','$pesoPagamento','$pesoTotaleLordo','$pesoTotaleNetto','$pesoPagato',$pesoFormazione,"
                . " '$pezzoLordo','$pezzoNetto','$pezzoPagato','$pezzoFormazione','$fasePda')";
        //echo $queryInserimentoSecondario;
        $conn19->query($queryInserimentoSecondario);
//
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



