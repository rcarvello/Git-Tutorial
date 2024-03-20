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
$provenienza = "gestioneLead";
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
        . "gl.gestioneleadno as idSponsorizzate, "
        . "glCF.cf_4044 as nome, "
        . "glCF.cf_4046 as cognome, "
        . "glCF.cf_4048 as mail, "
        . "glCF.cf_4050 as UTMc, "
        . "glCF.cf_4052 as UTMm, "
        . "glCF.cf_4054 as UTMs, "
        . "glCF.cf_4056 as ip, "
        . "glCF.cf_4058 as 'data import', "
        . "glCF.cf_4060 as origine, "
        . "glCF.cf_4062 as brand, "
        . "glCF.cf_4066 as leadId, "
        . "glCF.cf_4068 as source, "
        . "glCF.cf_4152 as categoraiaEsitoPrima, "
        . "glCF.cf_4156 as categoriaEsitoUltima "
        . "FROM "
        . "vtiger_gestionelead as gl "
        . "inner join vtiger_gestioneleadcf as glCF on glCF.gestioneleadid=gl.gestioneleadid "
        . "inner join vtiger_crmentity as e on glCF.gestioneleadid=e.crmid "
        . "where "
        . " e.deleted=0";

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
    $queryTruncate = "TRUNCATE TABLE `gestioneLead`";
    $conn19->query($queryTruncate);

    $dataTruncate = date('Y-m-d H:i:s');
    $queryTruncateLog = "INSERT INTO `logImport`(`datImport`, `provenienza`, `descrizione`,stato,idStato) VALUES ('$dataTruncate','$provenienza','Truncate $provenienza',0,'$idStato')";
    $conn19->query($queryTruncateLog);

    while ($riga = $risultato->fetch_array()) {
        $idSponsorizzata = $riga[0];
        $nome = $conn19->real_escape_string($riga[1]);
        $cognome = $conn19->real_escape_string($riga[2]);
        $mail = $riga[3];
        $utmC = $riga[4];
        $utmM = $riga[5];
        $utmS = $riga[6];
        $ip = $riga[7];
        $important = $riga[8];
        $origine = $riga[9];
        $brand = $riga[10];
        $leadId = $riga[11];
        $source = $riga[12];
        $categoriaEsitoPrima = $riga[13];
        //$categoriaEsitoUltima = $riga[14];

        $categoriaEsitoUltima = ($riga[14] == "") ? "BACKLOG" : $riga[14];

        $meseRicerca = date('Y-m-01', strtotime($important));
        echo $meseRicerca . "<br>";

        switch ($origine) {
            case "Energy":
            case "Telco":
                switch ($source) {
                    case "Sito":
                        $agenzia = "ChiediAZero.it";
                        $utmC = "Sito";
                        $utmM = "Sito";
                        $utmS = "Sito";
                        break;
                    default:
                        $agenzia = "Muza";
                        break;
                }
                switch ($utmC) {
                    case "MODULO VODAFONE-copy":
                    case "vuoto":
                        $utmC = "LG VODAFONE - MOD";
                        break;
                    case "Nuova campagna Contatti":
                        $utmC = "LG VODAFONE";
                        break;
                    case "":
                    case "MODULO PLENITUDE":
                        $utmC = "LG PLENITUDE 2 - MOD";
                        break;
                    case "block":
                        $utmC = "LG VODAFONE";
                        break;
                }
                break;
            case "Sito":
                $agenzia = "Risparmiami.it";
                break;
            case "Store":
                $agenzia = "VodafoneStore.it";
//                $utmC = "Sito";
//                $utmM = "Sito";
//                $utmS = "Sito";
                $source = $brand;
                break;
            case "bolletta_luce/gas":
                switch ($utmC) {
                    case "offerta_internet":
                        $utmC = "Search_Fibra_Ita_23022024";
                        $agenzia = "Arkys";
                        break;
                    case "offerta_fibra":
                        $utmC = "Search_Fibra_Ita_23022024";
                        $agenzia = "Arkys";
                        break;
                    case "lucegas_offerte":
                    case "lucegas_tariffe":
                    case "lucegas_comparatore":
                        $utmC = "Search_LuceGas_Ita_23022024";
                        $agenzia = "Arkys";
                        break;
                    case "promo":
                        $utmC = "LG_General_29.11.23";
                        $agenzia = "Arkys";
                        break;
                    default :
                        $agenzia = "Arkys";
                        break;
                }
                break;
            default :
                switch ($source) {
                    case "Sito":
                        $agenzia = "Risparmiami.it";
                        $utmC = "Sito";
                        $utmM = "Sito";
                        $utmS = "Sito";
                        break;
                    default:
                        $agenzia = "Arkys";
                        break;
                }
        }

        switch ($source) {
            case "fb":
            case "ig":
            case "nn":
            case "block":
            case "Facebook":
            case "":
            case "vuoto":
                $source = "Meta";
                break;
        }


        $queryCrm = "SELECT "
                . "sum(pezzoLordo),sum(if(fasePDA='OK',pezzoLordo,0)),sum(if(fasePDA='KO',pezzoLordo,0)), sum(if(fasePDA='BKL',pezzoLordo,0)), sum(if(fasePDA='BKLP',pezzoLordo,0)) "
                . "FROM "
                . "plenitude "
                . "inner JOIN aggiuntaPlenitude on plenitude.id=aggiuntaPlenitude.id "
                . "WHERE "
                . "statoPda<>'bozza' and statoPda<>'annullata' and statoPda<>'pratica doppia' and statoPda<>'In attesa Sblocco' and idGestioneLead='$idSponsorizzata'";
//        echo $queryCrm;
//        echo "<br>";
        $risultatoPleni = $conn19->query($queryCrm);
        $queryMediaP = "SELECT media FROM `mediaPraticaMese` where mandato='Plenitude' and mese='$meseRicerca'";
        //echo $queryMedia;
        $risultatoMediaP = $conn19->query($queryMediaP);
        $conteggioP = $risultatoMediaP->num_rows;
        if ($conteggioP > 0) {
            $rigaMediaP = $risultatoMediaP->fetch_array();
            $mediaPleni = $rigaMediaP[0];
        } else {
            $mediaPleni = 0;
        }

        $queryMediaV = "SELECT media FROM `mediaPraticaMese` where mandato='Vivigas' and mese='$meseRicerca'";
        $risultatoMediaV = $conn19->query($queryMediaV);
        $conteggioV = $risultatoMediaV->num_rows;
        if ($conteggioV > 0) {
            $rigaMediaV = $risultatoMediaV->fetch_array();
            $mediaVivi = $rigaMediaV[0];
        } else {
            $mediaVivi = 0;
        }

        $queryMediaVo = "SELECT media FROM `mediaPraticaMese` where mandato='Vodafone' and mese='$meseRicerca'";
        $risultatoMediaVo = $conn19->query($queryMediaVo);
        $conteggioVo = $risultatoMediaVo->num_rows;
        if ($conteggioVo > 0) {
            $rigaMediaVo = $risultatoMediaVo->fetch_array();
            $mediaVoda = $rigaMediaVo[0];
        } else {
            $mediaVoda = 0;
        }

        while ($rigaPleni = $risultatoPleni->fetch_array()) {
            $pleniTot = (($rigaPleni[0] == null) ? 0 : $rigaPleni[0]);
            $pleniOk = (($rigaPleni[1] == null) ? 0 : $rigaPleni[1]);
            $pleniKo = (($rigaPleni[2] == null) ? 0 : $rigaPleni[2]);
            $valoreMedioPleni = $pleniOk * $mediaPleni;
        }

        $queryCrmVodafone = "SELECT "
                . "sum(pezzoLordo),sum(if(fasePDA='OK',pezzoLordo,0)),sum(if(fasePDA='KO',pezzoLordo,0)), sum(if(fasePDA='BKL',pezzoLordo,0)), sum(if(fasePDA='BKLP',pezzoLordo,0)) "
                . "FROM "
                . "vodafone "
                . "inner JOIN aggiuntaVodafone on vodafone.id=aggiuntaVodafone.id "
                . "WHERE "
                . "statoPda<>'bozza' and statoPda<>'annullata' and statoPda<>'pratica doppia' and statoPda<>'In attesa Sblocco' and idGestioneLead='$idSponsorizzata'";
        //echo $queryCrmVodafone;
        $risultatoVodafone = $conn19->query($queryCrmVodafone);
        while ($rigaVodafone = $risultatoVodafone->fetch_array()) {
            $vodaTot = (($rigaVodafone[0] == null) ? 0 : $rigaVodafone[0]);
            $vodaOk = (($rigaVodafone[1] == null) ? 0 : $rigaVodafone[1]);
            $vodaKo = (($rigaVodafone[2] == null) ? 0 : $rigaVodafone[2]);
            $valoreMedioVoda = $vodaOk * $mediaVoda;
        }

        $queryCrmVivigas = "SELECT "
                . "sum(pezzoLordo),sum(if(fasePDA='OK',pezzoLordo,0)),sum(if(fasePDA='KO',pezzoLordo,0)), sum(if(fasePDA='BKL',pezzoLordo,0)), sum(if(fasePDA='BKLP',pezzoLordo,0)) "
                . "FROM "
                . "vivigas "
                . "inner JOIN aggiuntaVivigas on vivigas.id=aggiuntaVivigas.id "
                . "WHERE "
                . "statoPda<>'bozza' and statoPda<>'annullata' and statoPda<>'pratica doppia' and statoPda<>'In attesa Sblocco' and idGestioneLead='$idSponsorizzata'";
        //echo $queryCrmVodafone;
        $risultatoVivigas = $conn19->query($queryCrmVivigas);
        while ($rigaVivigas = $risultatoVivigas->fetch_array()) {
            $viviTot = (($rigaVivigas[0] == null) ? 0 : $rigaVivigas[0]);
            $viviOk = (($rigaVivigas[1] == null) ? 0 : $rigaVivigas[1]);
            $viviKo = (($rigaVivigas[2] == null) ? 0 : $rigaVivigas[2]);
            $valoreMediaVivi = $viviOk * $mediaVivi;
        }


        $queryInserimento = "INSERT INTO `gestioneLead`"
                . "( `idSponsorizzata`, `nome`, `cognome`, `mail`, `utmCampagna`, `utmMedium`, `utmSource`, `ip`, `dataImport`, `origine`, `brand`, `leadId`, `source`,agenzia,pleniTot,pleniOk,pleniKo,vodaTot,vodaOk,vodaKo,viviTot,ViviOk,viviKo,valoreMediaPleni,valoreMediaVivi,valoreMedioVoda,categoriaPrima,CategoriaUltima) "
                . "VALUES "
                . "('$idSponsorizzata','$nome','$cognome','$mail','$utmC','$utmM','$utmS','$ip','$important','$origine','$brand','$leadId','$source','$agenzia','$pleniTot','$pleniOk','$pleniKo','$vodaTot','$vodaOk','$vodaKo','$viviTot','$viviOk','$viviKo','$valoreMedioPleni','$valoreMediaVivi','$valoreMedioVoda','$categoriaEsitoPrima','$categoriaEsitoUltima')";
        //echo $queryInserimento;
        $conn19->query($queryInserimento);
    }
}
?>