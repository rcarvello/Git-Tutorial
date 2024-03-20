<?php

class connessioneSiscall4 {

    protected $link;
    private $_stato = false;
    private $_host = 'siscall-04';
    private $_port = '3306';
    private $_pwd = 'pass';
    private $_userDB = 'root';
    private $_nomedb = 'asterisk';

    public function apriConnessioneSiscall4() {
        $this->link = mysqli_connect($this->_host, $this->_userDB, $this->_pwd);
        if (mysqli_connect_errno()) {
            die("Errore nella connessione:" . mysqli_connect_errno());
            exit();
        } else {
            $this->_stato = true;
            mysqli_select_db($this->link, $this->_nomedb) or die("Errore nella selezione del database");
            return $this->link;
        }
    }

    function chiudiConnessioneSiscall4() {
        $this->_stato = false;
        mysqli_close($this->link);
    }

    public function getLink() {
        return $this->link;
    }

    public function getStato() {
        return $this->_stato;
    }

    public function __toString() {
        return $this->_stato;
    }

}

?>