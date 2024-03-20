<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require "/var/www/html/Know/connessione/connessione.php";
$obj19 = new Connessione();
$conn19 = $obj19->apriConnessione();
if (isset($_GET["errore"])) {
    $stato = $_GET["errore"];
} else {
    $stato = "";
}
$pagina = "";
switch ($stato) {
    case "password":
        $pagina = "<p>"
                . "La password inserita è Sbagliata!!!"
                . "</p>";
        break;
    case "logged":
        $pagina = "<p>"
                . "Non sei Connesso/a!!!!"
                . "</p>";
        break;
}
?>


<html>
    <head>
        <title>MagiPunteggi</title>
        <link href="css/style.css" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    </head>
    <body class="raccolta">
        <header>          
            <h1 class="titolo">
                <img src="images/favicon.png" class="immaggine">
                MagiPunteggi
                <img src="images/favicon.png" class="immaggine">
            </h1>
        </header>
        <form method="post" action="login/login.php">
            <fieldset class="raccoltaDati">
                <legend>Log In</legend>
                <label for="username"> Nome Utente
                    <input type="text" name="username" id="username" required>
                </label>                
                <label for="password">Password
                    <input type="password" name="password" id="password" required>
                </label>
                <?= $pagina ?>
                <input type="submit" value="login">
            </fieldset>
        </form>
    </body>
</html>


