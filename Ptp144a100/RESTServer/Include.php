<?php

// *****************************************************************************
// Project		: PTP144A100
// Programmer	: Sergio Bertana
// Date			: 17/10/2017
// *****************************************************************************
// Inclusioni generali.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// CREDENZIALI ACCESSO AL DATABASE
// -----------------------------------------------------------------------------
// Definire le credenziali di accesso al database.
// Occorre definire db_user, db_password, db_name, db_host:port

$DbRefs=array("Host" => "localhost", "User" => "User", "Password" => "Password", "Database" => "Database");
$GLOBALS['Db']=new ezSQL_pdo("mysql:host={$DbRefs["Host"]}; dbname={$DbRefs["Database"]}", $DbRefs["User"], $DbRefs["Password"]); //Database PDO

// Definizioni tabelle database.

define("SISTEMIDX", "Ptp144_SystemIDx"); //Tabella ID di sistema

// Ritorno elenco tabella database solo per testare la connessione.

//$DbTables=$Db->get_results("SHOW TABLES", ARRAY_N);
//$Db->debug(); //Print out last query and results

// -----------------------------------------------------------------------------
// DEFINIZIONI DATI SISTEMA
// -----------------------------------------------------------------------------
// Array stato sistema contiene i dati per gestire il REST.

$GLOBALS['St']=array
(
	// Dati ricevuti dal sistema.

	"UID" => 0, //System unique ID
	"MID" => 0, //Message ID
	"MV" => "", //Message version
	"RPAck" => 0, //REST parameters acknowledged
	"Length" => 0, //Lunghezza record dati
	"Epoch" => 0, //Epoch time relativo al record dati
	"RxMessage" => "", //Messaggio ricevuto

	// Variabili inviate dal sistema.

	"DInp" => 0x00, //Stato ingressi digitali

	// Dati inviati al sistema SlimLine in risposta.

	"DOut" => 0x00, //Comando uscite digitali
	"RPCount" => 0, //REST parameters counter 
	"TxMessage" => "", //Messaggio trasmesso

	// Dati statistici per debug.

	"RPError" => 0, //REST parameters error
	"Resyncs" => 0, //REST resyncronizations
	"PollTime" => 0, //Tempo poll sistema
	"Heartbeat" => GetuTime(), //Data/Ora ultimo heartbeat (UTC)
	"TxPars" => 0, //Numero parametri trasmessi
);

// *****************************************************************************
// FUNZIONE "GetuTime()"
// *****************************************************************************
// microtime() returns a string in the form "msec sec", where sec is the current
// time measured in the number of seconds since the Unix epoch, and msec is the
// number of microseconds that have elapsed since sec expressed in seconds.
//
// La funzione non ha parametri.
//
// La funzione ritorna
// Valore tempo in mSec da Epoch time.
// -----------------------------------------------------------------------------

function GetuTime()
{
	list($uSec, $Sec)=explode(" ", microtime()); 
	return((float)$uSec+(float)$Sec); 
}

// *****************************************************************************
// FUNZIONE "GetTimeNow($Local)"
// *****************************************************************************
// Ritorna ora in Unix epoch time.
//
// Parametri funzione:
// $Local: false, Ritorna GMT, true, Ritorna ora locale
//
// La funzione ritorna epoch time.
// -----------------------------------------------------------------------------

function GetTimeNow($Local)
{
	if (!$Local) return(time()); //Torno tempo in UTC
	return(time()+date("Z")); //Torno tempo locale
}

// *****************************************************************************
// FUNZIONE "CkReqPars($AList)"
// *****************************************************************************
// Questa funzione esegue il controllo se sono definiti i parametri "$_REQUEST".
//
// Parametri funzione:
// $AList: Lista parametri da controllare
//
// La funzione ritorna, false: Errore parametri. true: Parametri corretti.
// -----------------------------------------------------------------------------

function CkReqPars($AList)
{
	foreach ($AList as $Id => $Field) {if (!isset($_REQUEST[$Field])) return(false);} //Errore parametri
	return(true); //Parametri corretti
}

// *****************************************************************************
// FUNZIONE "ErrorTrace($Description)"
// *****************************************************************************
// Funzione di tracciatura errore.
//
// Parametri funzione:
// $Description: Descrizione errore.
//
// La funzione non prevede ritorni
// -----------------------------------------------------------------------------

function ErrorTrace($Description)
{
	echo $Description;
}

?>
