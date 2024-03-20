<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();


//session_start();

//$logged=$_SESSION["login"];
//
//if ($logged==false){
//    header("location:index_new.php?errore=logged");
//}

$queryMese = "SELECT mese FROM `stringheSiscall` group by mese order by giorno";
$risultatoMese = $conn19->query($queryMese);
$i = 0;
while ($mesi = $risultatoMese->fetch_Array()) {
    $elencoMesi[] = $mesi[0];
    $i++;
}

$queryMese = "SELECT mese FROM `aggiuntaPlenitude` group by mese";
$risultatoMese = $conn19->query($queryMese);
$i = 0;
while ($mesi = $risultatoMese->fetch_Array()) {
    $elencoMesiCrm[] = $mesi[0];
    $i++;
}

$queryMese = "SELECT month(giorno) FROM `formazioneTotale` group by month(giorno)";
$risultatoMese = $conn19->query($queryMese);
$i = 0;
while ($mesi = $risultatoMese->fetch_Array()) {
    $elencoMesiFormazione[] = $mesi[0];
    $i++;
}

$queryMese = "SELECT mese FROM `pagamentoMese` group by mese";
$risultatoMesePagamento = $conn19->query($queryMese);
$i = 0;
while ($mesi = $risultatoMesePagamento->fetch_Array()) {
    $elencoMesiPagamento[] = $mesi[0];
    $i++;
}


if (isset($_GET["meseSelezionato"])) {
    $meseSelezionato = $_GET["meseSelezionato"];
} else {
    $meseSelezionato = $elencoMesi[0];
}
?>

<html>
    <head>
        <title>Prova</title>
        <link href="elementi/sidebar.css" rel="stylesheet">
    </head>
    <body>
        <header>
            <h1 class="titolo">Home:</h1>
        </header>
        <?php include '/var/www/html/Know/elementi/sidebar.html' ?>
        <div class="pagina">
            <button onclick="aggiornaGreen()">Aggiorna Green Network</button>
            <button onclick="aggiornaVivigas()">Aggiorna Vivigas</button>
            <button onclick="aggiornaPlenitude()">Aggiorna Plenitude</button>
            <button onclick="aggiornaEnelOut()">Aggiorna Enel Out</button>
            <button onclick="aggiornaVodafone()">Aggiorna Vodafone</button>
            <br>
            <div>

                <label for="mesi">Selezione mese Stringhe</label>
                <select id="mesi"  >

                    <?php
                    foreach ($elencoMesi as $valoreMese) {
                        echo "<option value=" . $valoreMese;
                        if ($valoreMese == $meseSelezionato) {
                            echo " selected";
                        }
                        echo " >" . $valoreMese . "</option>";
                    }
                    ?>
                </select>
                <button onclick="scaricaCSV()">Scarica CSV Stringhe</button>
            </div>
            <div>
                <label>Seleziona Mese per Pesi
                    <select id="mesiPeso">
                        <?php
                        foreach ($elencoMesiCrm as $valoreMese) {
                            echo "<option value=" . $valoreMese;
                            if ($valoreMese == $meseSelezionato) {
                                echo " selected";
                            }
                            echo " >" . $valoreMese . "</option>";
                        }
                        ?>
                    </select>

                </label>
                <button onclick="scaricaPesiCSV()" >Scarica CSV Pesi</button>
            </div>
            <div>
                <label>Seleziona Mese per Formazione
                    <select id="mesiFormazione">
                        <?php
                        foreach ($elencoMesiFormazione as $valoreMese) {
                            echo "<option value=" . $valoreMese;
                            if ($valoreMese == $meseSelezionato) {
                                echo " selected";
                            }
                            echo " >" . $valoreMese . "</option>";
                        }
                        ?>
                    </select>

                </label>
                <button onclick="scaricaformazioneCSV()" >Scarica CSV Formazione</button>
            </div>
            
            <div>
                <label>Seleziona Mese Pagamento
                    <select id="mesiPagamento">
                        <?php
                        foreach ($elencoMesiPagamento as $valoreMesePagamento) {
                            echo "<option value=" . $valoreMesePagamento;
                            if ($valoreMesePagamento == $meseSelezionato) {
                                echo " selected";
                            }
                            echo " >" . $valoreMesePagamento . "</option>";
                        }
                        ?>
                    </select>

                </label>
                <button onclick="scaricaPagamento()">Scarica CSV Pagamento</button>
            </div>
        </div>
    </body>
    <script>
        function aggiornaGreen() {
            window.location.href = "./crm/greennetwork.php";
            //window.location.href = "./index.php";
        }
        function aggiornaVivigas() {
            window.location.href = "./crm/vivigas.php";
        }
        function aggiornaPlenitude() {
            window.location.href = "./crm/plenitude.php";
        }
        function aggiornaEnelOut() {
            window.location.href = "./crm/enelOut.php";
        }
         function aggiornaVodafone() {
            window.location.href = "./crm/vodafone.php";
        }
        function scaricaCSV() {
            var mesi = document.getElementById("mesi");
            var valore = mesi.options[mesi.selectedIndex].value;
            window.location.href = "exportDownloadStringhe.php?meseSelezionato=" + valore;
        }
        function scaricaPesiCSV() {
            var mesi = document.getElementById("mesiPeso");
            var valore = mesi.options[mesi.selectedIndex].value;
            window.location.href = "exportDownloadPesi.php?meseSelezionato=" + valore;
        }
         function scaricaformazioneCSV() {
            var mesi = document.getElementById("mesiFormazione");
            var valore = mesi.options[mesi.selectedIndex].value;
            window.location.href = "exportFormazione.php?meseSelezionato=" + valore;
        }
        function scaricaPagamento() {
            var mesi = document.getElementById("mesiPagamento");
            var valore = mesi.options[mesi.selectedIndex].value;
            window.location.href = "exportPagamento.php?meseSelezionato=" + valore;
        }
    </script>

</html>


