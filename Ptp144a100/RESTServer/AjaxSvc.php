<?php

// *****************************************************************************
// Project		: PTP144A100
// Programmer	: Sergio Bertana
// Date			: 17/10/2017
// *****************************************************************************
// Script eseguito da pagina web "Home" su richiesta ajax.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// INCLUSIONE FILES
// -----------------------------------------------------------------------------
// Inclusione files.

$HomeDir=substr($_SERVER['SCRIPT_FILENAME'], 0, strrpos($_SERVER['SCRIPT_FILENAME'], "/")); //Rilevo Home directory
require_once $HomeDir."/ezSQL/ez_sql_core.php"; //Include ezSQL core
require_once $HomeDir."/ezSQL/ez_sql_pdo.php"; //Database PDO
require_once $HomeDir."/Include.php"; //Inclusioni generali

// -----------------------------------------------------------------------------
// CONTROLLO RICHIESTA IN ARRIVO
// -----------------------------------------------------------------------------
// La richiesta deve contenere i campi, UID, DOut. Se errore esco.

if (!CkReqPars(array("UID", "DOut"))) exit("Wrong REST parameters");
if (!is_numeric($_REQUEST['UID'])) exit("Wrong system UID");
$GLOBALS['St']['UID']=$_REQUEST['UID']; //System unique ID

// -----------------------------------------------------------------------------
// ESEGUO LETTURA FILE DATI DI SISTEMA
// -----------------------------------------------------------------------------
// Per ogni sistema (Riconoscibile dal suo "UID") esiste un record nel database.

$DbRes=$GLOBALS['Db']->get_row("SELECT * FROM ".SISTEMIDX." WHERE UID = {$GLOBALS['St']['UID']}");
if ($DbRes == NULL) exit("System UID not found");

// Salvo byte comando uscite digitali.

$GLOBALS['St']['DOut']=$_REQUEST['DOut']; //Comando uscite digitali
$GLOBALS['Db']->query("UPDATE ".SISTEMIDX." SET DOut={$GLOBALS['St']['DOut']} WHERE UID = {$GLOBALS['St']['UID']}");

// -----------------------------------------------------------------------------
// RITORNO DATI A PAGINA WEB
// -----------------------------------------------------------------------------
// Creo array dati di ritorno a pagina web.

$Return=array
(
	"MID" => $DbRes->MID, //Message ID
	"PollTime" => sprintf("%6.3f (S)", $DbRes->PollTime), //Tempo poll sistema
	"Resyncs" => $DbRes->Resyncs, //REST resyncronizations
	"RxMessage" => $DbRes->RxMessage, //Messaggio ricevuto
	"TxMessage" => $DbRes->TxMessage, //Messaggio trasmesso
	"DInp" => $DbRes->DInp, //Stato ingressi digitali
	"DOut=" => $_REQUEST['DOut'], //Comando uscite digitali
);

echo json_encode($Return);

?>
