<?php

// *****************************************************************************
// Project		: PTP135A100
// Programmer	: Sergio Bertana
// Date			: 16/10/2017
// *****************************************************************************
// Script eseguito da sistema SlimLine da FB "RESTWSvcClient". Viene ricevuta
// una richiesta in HTTP POST il messaggio di richiesta può essere un heartbeat
// in questo caso non contiene dati ma ha solo l'header. Oppure può essere un
// messaggio con dati a cui dopo l'header seguono i dati.
//
// L'header del messaggio contiene i campi:
// MID: (Message ID) Identificativo messaggio
// UID: (Unit ID) Identificativo sistema
// MV: (Message version) Versione messaggio
// RP: (REST parameters) Numero parametri ricevuti con pagina REST
//
// Il messaggio dati inizia con un campo numerico che contiene le informazioni
// relative tra cui l'epoch time relativo al momento in cui si è generato il
// dato. In questo modo si ha sempre un riferimento alla data dell'evento e non
// a quella di ricezione del messaggio. Il campo dati si compone:
// 
// +---+---+-+-+-+-+-+-+-+...+-+
// | Length|0|0| Epoch | Value |
// +---+---+-+-+-+-+-+-+-+...+-+
//
// Length: Lunghezza record (2 byte)
// Epoch: Epoch time (4 byte)
// Value: Stringa con valore (Lunghezza variabile)
// -----------------------------------------------------------------------------
// Ecco alcuni esempi di messaggi REST ricevuti.
// http://www.slimline.altervista.org/Mdp095a200/Ptp135a100/RESTSvc.php?MID=0&UID=3407887&MV=1.0&RP=0&Data=00200000564CA7A8{"DInp":"0"}

// Per visualizzare i dati:
// http://www.slimline.altervista.org/Mdp095a200/Ptp135a100/Home.htm
// -----------------------------------------------------------------------------

// *****************************************************************************
// FUNZIONI CONVERSIONE DATI RICEVUTI
// *****************************************************************************
// Funzioni per conversione dati.

function RxBYTE($Rx, $Ofs) {return(intval(substr($Rx, $Ofs, 2), 16));}
function RxWORD($Rx, $Ofs) {return(intval(substr($Rx, $Ofs, 4), 16));}
function RxDWORD($Rx, $Ofs) {return(intval(substr($Rx, $Ofs, 8), 16));}
function RxREAL($Rx, $Ofs) {$Pk=pack("L", intval(substr($Rx, $Ofs, 8), 16)); $Uk=unpack("f", $Pk); return($Uk[1]);}

// -----------------------------------------------------------------------------
// INCLUSIONE FILES
// -----------------------------------------------------------------------------
// Inclusione files.

$HomeDir=substr($_SERVER['SCRIPT_FILENAME'],0,-strlen($_SERVER['SCRIPT_NAME'])); //Rilevo Home directory "/var/www/"
require_once($HomeDir."/Mdp095a200/Ptp135a100/Include.php"); //Inclusioni generali

// -----------------------------------------------------------------------------
// CONTROLLO RICHIESTA IN ARRIVO
// -----------------------------------------------------------------------------
// La richiesta deve contenere i campi, MID, UID, MV, RP. Se errore esco.

if (!CkReqPars(array("MID", "UID", "MV", "RP"))) exit("Wrong REST parameters");
if (!is_numeric($_REQUEST['UID'])) exit("Wrong system UID");

// Per ogni sistema (Riconoscibile dal suo "UID") esiste un file dati ne eseguo
// lettura e compilazione array globale.

ReadINIFile($_REQUEST['UID']); //File dati di sistema
$GLOBALS['St']['UID']=$_REQUEST['UID']; //System unique ID
$GLOBALS['St']['MV']=$_REQUEST['MV']; //Message version

// (Opzionale) Nel messaggio è possibile ricevere il numero di parametri che
// l'FB "RESTWSvcClient" ha ricevuto in risposta alla precedente richiesta.
// Questo valore deve essere indicato alla FB "RESTWSvcClient" sull'ack della
// risposta ricevuta caricando il numero in "RPAck".

$GLOBALS['St']['RPAck']=$_REQUEST['RP']; //REST parameters acknowledged

// (Opzionale) Nel messaggio di risposta è possibile ritornare numero parametri
// ricevuti in POST. Questo valore è ritornato dalla FB "RESTWSvcClient" in
// RPCount. Può essere utilizzato dal programma per verificare se i dati inviati
// sono stati ricevuti dal server REST.

$GLOBALS['St']['RPCount']=0; //REST parameters counter

// Calcolo tempo di poll.

$GLOBALS['St']['PollTime']=sprintf("%6.3f", GetuTime()-$GLOBALS['St']['Heartbeat']); //Tempo poll sistema
$GLOBALS['St']['Heartbeat']=GetuTime(); //Data/Ora ultimo heartbeat (UTC)

// -------------------------------------------------------------------------
// CONTROLLO ID MESSAGGIO
// -------------------------------------------------------------------------
// Controllo se ricevuto l'acknowledge dallo SlimLine del messaggio REST
// inviato precedentemente dal server. Controllo se il  MID ricevuto è
// corretto (Successivo al MID del messaggio precedente).

if ((($_REQUEST['MID']-$GLOBALS['St']['MID'])&0xFFFF) == 1)
{
	// Ricevuto MID successivo messaggio corretto (Nessun messaggio è
	// andato perso) utilizzo MID ricevuto.

	$GLOBALS['St']['MID']=$_REQUEST['MID']; //Message ID
}
else
{
	// Errore ricezione messaggi, occorre eseguire una resincronizzazione
	// sistema, viene inviato un numero random che sarà utilizzato dal
	// sistema come prossimo MID.

	$GLOBALS['St']['MID']=rand(0, 65535); //Message ID
	$GLOBALS['St']['Resyncs']++; //REST resyncronizations
}

// -----------------------------------------------------------------------------
// CONTROLLO SE CLIENT HA RICEVUTO PARAMETRI
// -----------------------------------------------------------------------------
// Il client alla ricezione di messaggio con parametri, nel successivo messaggio
// deve indicare in RPAck il numero di parametri che ha ricevuto. In questo modo
// il server può controllare se il messaggio inviato è stato recepito.
// Questo controllo è opzionale.

if ($GLOBALS['St']['RPAck'] != $GLOBALS['St']['TxPars']) $GLOBALS['St']['RPError']++; //REST parameters error

// -----------------------------------------------------------------------------
// ACQUISIZIONE INFORMAZIONI DAL MESSAGGIO DATI
// -----------------------------------------------------------------------------
// Un messaggio dati contiene un campo "Data" composto da diversi campi, ogni
// byte occupa due caratteri ascii. I dati sono in Big endian, MSB ... LSB.
// +---+---+-+-+-+-+-+-+-+...+-+
// | Length|0|0| Epoch | Value |
// +---+---+-+-+-+-+-+-+-+...+-+
//
// Length: Lunghezza record (2 byte)
// Epoch: Epoch time (4 byte)
// Value: Stringa con valore (Lunghezza variabile)
// -----------------------------------------------------------------------------
// Se messaggio ricevuto contiene campo "Data" eseguo acquisizione dati campo.

if (!isset($_REQUEST['Data'])) goto SENDDATA;
$GLOBALS['St']['Length']=RxWORD($_REQUEST['Data'], 0); //Lunghezza record dati
$GLOBALS['St']['Epoch']=RxDWORD($_REQUEST['Data'], 8); //Epoch time relativo al record dati
$GLOBALS['St']['RxMessage']=substr($_REQUEST['Data'], 16, ($GLOBALS['St']['Length']-8)); //Messaggio ricevuto

// Nel campo "RxMessage" il sistema SlimLine invia le variabili in una stringa
// codificata JSON. Nel nostro esempio vi sarà la sola variabile "DInp".

$Vars=json_decode($GLOBALS['St']['RxMessage']);
$GLOBALS['St']['DInp']=$Vars->{"DInp"}; //Stato ingressi digitali
$GLOBALS['St']['RPCount']=1; //REST parameters counter

// -------------------------------------------------------------------------
// INVIO DATI AL SISTEMA
// -------------------------------------------------------------------------
// Inserisco la definizione dei campi da impostare, separo ogni campo con
// lo spazio per permettere nel sistema alla scanf di interrompersi sulla
// acquisizione di valori stringa. Nel nostro esempio vi è un solo campo.

SENDDATA:
$GLOBALS['St']['TxPars']=1; //Numero parametri trasmessi
$Return=array("DOut" => $GLOBALS['St']['DOut']); //Dati verso SlimLine
$GLOBALS['St']['TxMessage']=json_encode($Return);
WriteINIFile($GLOBALS['St']['UID']); //Scrittura file ini

// Compilo messaggio di risposta che inizia con il MID. Il valore ritornato
// è calcolato sommando il valore di UID. In questo modo si garantisce che
// il sistema che riceve il messaggio possa verificalo utilizzando il suo
// unique ID.

$RPage=sprintf("MID=%d", ($GLOBALS['St']['MID']+$GLOBALS['St']['UID'])&0xFFFF); //Return page
$RPage.=sprintf("&RP=%d", $GLOBALS['St']['RPCount']); //Carico numero parametri ricevuti
$RPage.="&Page={$GLOBALS['St']['TxMessage']}"; //Return page
echo $RPage;

?>
