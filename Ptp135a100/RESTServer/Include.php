<?php

// *****************************************************************************
// Project		: PTP135A100
// Programmer	: Sergio Bertana
// Date			: 16/10/2017
// *****************************************************************************
// Inclusioni generali.
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// DEFINIZIONI GENERALI
// -----------------------------------------------------------------------------
// Definizioni generali.

$GLOBALS['DataPath']="Data/"; //Indirizzo percorso file dati

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
// FUNZIONE "IsFilePresent($FileName)"
// *****************************************************************************
// Questa funzione esegue il controllo sulla presenza di un file.
//
// Parametri funzione:
// $FileName: File da controllare
//
// La funzione ritorna:
// FALSE=File non presente.
// TRUE=File presente.
// -----------------------------------------------------------------------------

function IsFilePresent($FileName)
{
	// Controllo se file esiste, se libero in lettura, se directory, se link.

	if (!file_exists($FileName)) return(false);
	if (!is_readable($FileName)) return(false);
	if (is_dir($FileName)) return(false);
	if (is_link($FileName)) return(false);
	return(true); //File presente
}

// *****************************************************************************
// FUNZIONE "ReadINIFile($FileName)"
// *****************************************************************************
// Questa funzione esegue la lettura del file ini.
//
// Parametri funzione:
// $FileName: File da leggere 
//
// La funzione ritorna: FALSE=File non presente. TRUE=lettura eseguita.
// -----------------------------------------------------------------------------

function ReadINIFile($FileName)
{
	if (!IsFilePresent($GLOBALS['DataPath'].$FileName)) return(false);

	// Eseguo lettura file e compilazione campi.

	$DArray=parse_ini_file($GLOBALS['DataPath'].$FileName); //Data array
	foreach ($DArray as $Field => $Value) $GLOBALS['St'][$Field]=$Value;
	return(true);
}

// *****************************************************************************
// FUNZIONE "WriteINIFile($FileName)"
// *****************************************************************************
// Questa funzione esegue l ascrittura di un file *.ini.
//
// Parametri funzione:
// $FileName: File da scrivere.
//
// La funzione ritorna: false:Errore, true:Ok esecuzione.
// -----------------------------------------------------------------------------

function WriteINIFile($FileName)
{
	// Compongo contenuto file da scrivere.

	$FContent=""; //File content
	foreach ($GLOBALS['St'] as $Key=>$Element)
	{
		if (is_array($Element))
		for($i=0;$i<count($Element);$i++) $FContent.=$Key."[]=\"".$Element[$i]."\"\n";
		else if ($Element=="") $FContent.=$Key."=\n";
		else $FContent.= $Key."=\"".$Element."\"\n";
	}

	// Eseguo scrittura file.

	if (!$Handle=fopen($GLOBALS['DataPath'].$FileName, 'w')) return(false);
	$Success=fwrite($Handle, $FContent);
	fclose($Handle);
	return($Success);
}

?>
