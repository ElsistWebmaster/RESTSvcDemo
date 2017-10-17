<?php

// *****************************************************************************
// Project		: PTP135A100
// Programmer	: Sergio Bertana
// Date			: 16/10/2017
// *****************************************************************************
// Script eseguito da pagina web "Home" su richiesta ajax.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// INCLUSIONE FILES
// -----------------------------------------------------------------------------
// Inclusione files.

$HomeDir=substr($_SERVER['SCRIPT_FILENAME'],0,-strlen($_SERVER['SCRIPT_NAME'])); //Rilevo Home directory "/var/www/"
require_once($HomeDir."/Mdp095a200/Ptp135a100/Include.php"); //Inclusioni generali

// -----------------------------------------------------------------------------
// CONTROLLO RICHIESTA IN ARRIVO
// -----------------------------------------------------------------------------
// La richiesta deve contenere i campi, UID, DOut. Se errore esco.

if (!CkReqPars(array("UID", "DOut"))) exit("Wrong REST parameters");
if (!is_numeric($_REQUEST['UID'])) exit("Wrong system UID");

// -----------------------------------------------------------------------------
// ESEGUO LETTURA FILE DATI DI SISTEMA
// -----------------------------------------------------------------------------
// Per ogni sistema (Riconoscibile dal suo "UID") esiste un file dati ne eseguo
// lettura e compilazione array globale.

if (!ReadINIFile($_REQUEST['UID'])) exit("System UID not found");
$GLOBALS['St']['UID']=$_REQUEST['UID']; //System unique ID

// Salvo byte comando uscite digitali.

$GLOBALS['St']['DOut']=$_REQUEST['DOut']; //Comando uscite digitali

// Eseguo scrittura file.

WriteINIFile($GLOBALS['St']['UID']);

// -----------------------------------------------------------------------------
// RITORNO DATI A PAGINA WEB
// -----------------------------------------------------------------------------
// Creo array dati di ritorno a pagina web.

$Return=array
(
	"MID" => $GLOBALS['St']['MID'], //Message ID
	"PollTime" => sprintf("%6.3f (S)", $GLOBALS['St']['PollTime']), //Tempo poll sistema
	"Resyncs" => $GLOBALS['St']['Resyncs'], //REST resyncronizations
	"RxMessage" => $GLOBALS['St']['RxMessage'], //Messaggio ricevuto
	"TxMessage" => $GLOBALS['St']['TxMessage'], //Messaggio trasmesso
	"DInp" => $GLOBALS['St']['DInp'], //Stato ingressi digitali
	"DOut=" => $GLOBALS['St']['DOut'], //Comando uscite digitali
);

echo json_encode($Return);

?>
