<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0                                                  |
  | http://www.elastix.com                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
*/

require_once "/var/www/html/libs/paloSantoDB.class.php";
require_once "/var/www/html/libs/misc.lib.php";
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

$DocumentRoot = (isset($_SERVER['argv'][1]))?$_SERVER['argv'][1]:"/var/www/html";
$DataBaseRoot = "/var/www/db";
$tmpDir = '/tmp/new_module/pbx';  # in this folder the load module extract the package content

if (!file_exists("$DataBaseRoot/control_panel_design.db")) {
    $cmd_mv    = "mv $tmpDir/setup/control_panel_design.db $DataBaseRoot/";
    $cmd_chown = "chown asterisk.asterisk $DataBaseRoot/control_panel_design.db";
    exec($cmd_mv);
    exec($cmd_chown);
}

createTrunkDB($DataBaseRoot);
if (!file_exists("$DataBaseRoot/trunk.db")) {
    if (file_exists("$tmpDir/setup/trunk.db")) {
        $cmd_mv    = "mv $tmpDir/setup/trunk.db $DataBaseRoot/";
        $cmd_chown = "chown asterisk.asterisk $DataBaseRoot/trunk.db";
        exec($cmd_mv);
        exec($cmd_chown);
    } else {
        $cmd_mv = "mv $DataBaseRoot/trunk-pbx.db $DataBaseRoot/trunk.db";
        exec($cmd_mv);
    }
}


// creacion de la tabla provider_account
$provider_account = existDBTable("provider_account", "trunk.db", $DataBaseRoot);
if ($provider_account['flagStatus']==0) {
    $arrConsole = $provider_account['arrConsole'];
    $exists = (isset($arrConsole) && isset($arrConsole[0]))?true:false;
// antes verificar si hay datos en proveedores configurados sino existen solo se reemplaza la base
    if (!$exists) {
        echo "Changing database trunk.db...\n";
        $pDB    = new paloDB("sqlite3:////var/www/db/trunk.db");
        $pDBNew = new paloDB("sqlite3:////var/www/db/trunk-pbx.db");
        $pDBFreePBX = new paloDB(generarDSNSistema('asteriskuser', 'asterisk', '/var/www/html/'));

        $query  = "SELECT
                        t.name        AS account_name,
                        t.username    AS username,
                        t.password    AS password,
                        a.type        AS type,
                        a.qualify     AS qualify,
                        a.insecure    AS insecure,
                        a.host        AS host,
                        a.fromuser    AS fromuser,
                        a.fromdomain  AS fromdomain,
                        a.dtmfmode    AS dtmfmode,
                        a.disallow    AS disallow,
                        a.context     AS context,
                        a.allow       AS allow,
                        a.trustrpid   AS trustrpid,
                        a.sendrpid    AS sendrpid,
                        a.canreinvite AS canreinvite,
                        p.id          AS id_provider
                   FROM 
                        trunk t, 
                        attribute a, 
                        provider p
                   WHERE 
                        t.id_provider = p.id AND 
                        a.id_trunk = t.id;";
        $result = $pDB->fetchTable($query, true);
        if (isset($result) & $result != "") {
            //recorriendo el $result
            foreach($result as $key => $value) {
                $data[0]  = $value['account_name'];
                $data[1]  = $value['username'];
                $data[2]  = $value['password'];
        		$data[3]  = "";
                $data[4]  = $value['type'];
                $data[5]  = $value['qualify'];
                $data[6]  = $value['insecure'];
                $data[7]  = $value['host'];
                $data[8]  = $value['fromuser'];
                $data[9]  = $value['fromdomain'];
                $data[10] = $value['dtmfmode'];
                $data[11] = $value['disallow'];
                $data[12] = $value['context'];
                $data[13] = $value['allow'];
                $data[14] = $value['trustrpid'];
                $data[15] = $value['sendrpid'];
                $data[16] = $value['canreinvite'];
                $tech     = getTechnology($value['account_name'], $pDBNew);
                $data[17] = $tech;
                $data[18] = $value['id_provider'];
                if ($value['username'] != "" && $value['password'] != "") {
                    if ($value["account_name"] != "NuFone IAX") {
                        echo "Inserting trunk $value[account_name]...\n";
                        insertAccount($data, $pDBNew, $pDBFreePBX);
                    }
                    if (strtolower($tech) == "sip")
                        echo "Deleting trunk $value[account_name] from file /etc/asterisk/sip_custom.conf...\n";
                    else
                        echo "Deleting trunk $value[account_name] from file /etc/asterisk/iax_custom.conf...\n";
                    deleteInFileCustom($value["account_name"],$tech);
                    if (strtolower($tech) == "sip")
                        echo "Deleting register string of trunk $value[account_name] from file /etc/asterisk/sip_register_custom.conf...\n";
                    else
                        echo "Deleting register string of trunk $value[account_name] from file /etc/asterisk/iax_register_custom.conf...\n";
                    deleteInFileRegister($value["username"],$value["password"],$value["host"],$tech);
                }
            }
        }
        // para la tabla trunk_bill
        $query  = "SELECT COUNT(*) AS size FROM trunk_bill;";
        $result = $pDB->getFirstRowQuery($query, true);
        if ($result['size'] > 0) {
            $result2 = getTrunkBills($pDB);
            foreach($result2 as $key2 => $value2) {
                $trunkName = $value2['trunk'];
                insertTrunlBill($trunkName, $pDBNew);
            }
        }
        exec("mv $DataBaseRoot/trunk.db $DataBaseRoot/trunk-old.db");
        exec("mv $DataBaseRoot/trunk-pbx.db $DataBaseRoot/trunk.db");

    	$pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    	$arrAMP = $pConfig->leer_configuracion(false);
    	$dsn_agi_manager['password'] = $arrAMP['AMPMGRPASS']['valor'];
    	$dsn_agi_manager['host'] = $arrAMP['AMPDBHOST']['valor'];
    	$dsn_agi_manager['user'] = $arrAMP['AMPMGRUSER']['valor'];
    	$pConfig2 = new paloConfig($arrAMP['ASTETCDIR']['valor'], "asterisk.conf", "=", "[[:space:]]*=[[:space:]]*");
    	$arrAST  = $pConfig2->leer_configuracion(false);
        echo "Reloading asterisk...\n";
    	do_reloadAll($dsn_agi_manager, $arrAST, $arrAMP, $pDBFreePBX);
    }
    $result = existDBField("provider", "orden", "trunk.db", $DataBaseRoot);
    if ($result['flagStatus']!=0)
      doUpdatesTrunkDB($DataBaseRoot);
}

// Remove temporary file if not renamed
if (file_exists("$DataBaseRoot/trunk-pbx.db")) {
    unlink("$DataBaseRoot/trunk-pbx.db");
}

function createTrunkDB($DataBaseRoot)
{
  $sql = <<<TEMP
BEGIN TRANSACTION;
CREATE TABLE attribute 
(
       id                INTEGER    PRIMARY KEY,
       type              VARCHAR(20),
       qualify           VARCHAR(20),
       insecure          VARCHAR(20),
       host              VARCHAR(20),
       fromuser          VARCHAR(20),
       fromdomain        VARCHAR(20),
       dtmfmode          VARCHAR(20),
       disallow          VARCHAR(20),
       context           VARCHAR(20),
       allow             VARCHAR(20),
       trustrpid         VARCHAR(20),
       sendrpid          VARCHAR(20),
       canreinvite       VARCHAR(20),
       id_provider       INTEGER,
       FOREIGN KEY(id_provider) REFERENCES provider(id)
);
INSERT INTO "attribute" VALUES(1, 'peer', 'yes', 'very', 'ippbx.net2phone.com', '', '', '', 'all', 'from-pstn', 'alaw&ulaw', '', '', 'no', 1);
INSERT INTO "attribute" VALUES(2, 'friend', 'yes', 'very', 'sip.camundanet.com', '', 'camundanet.com', 'rfc2833', 'all', 'from-pstn', 'gsm', '', '', 'no', 2);
INSERT INTO "attribute" VALUES(3, 'peer', 'yes', '', 'outbound1.vitelity.net', '', '', '', '', 'from-trunk', '', 'yes', 'yes', 'no', 3);
INSERT INTO "attribute" VALUES(4, 'friend', 'yes', 'very', 'sip1.starvox.com', '', '', 'rfc2833', '', 'from-pstn', '', '', '', '', 4);
INSERT INTO "attribute" VALUES(6, 'peer', 'yes', 'very', 'freephonie.net', '', 'freephonie.net', 'auto', 'all', 'from-trunk', 'alaw', '', '', 'no', 6);
INSERT INTO "attribute" VALUES(7, 'peer', 'yes', 'very', 'sip.ovh.net', '', 'sip.ovh.net', 'auto', 'all', 'from-trunk', 'alaw', '', '', 'no', 7);
INSERT INTO "attribute" VALUES(8, 'peer', 'yes', '', 'sip.voipdiscount.com', '', '', 'rfc2833', 'all', 'from-trunk', 'alaw', '', '', 'no', 8);
INSERT INTO "attribute" VALUES(9, 'peer', 'yes', 'very', 'gateway.circuitid.com', NULL, NULL, 'rfc2833', 'all', 'from-pstn', 'alaw&ulaw&gsm', 'no', 'no', 'no', 9);
INSERT INTO "attribute" VALUES(10, 'friend', 'yes', 'very', 'sip.vozelia.com', NULL, NULL, 'auto', 'all', 'from-pstn', 'alaw&ulaw&gsm', 'no', 'no', 'no', 10);
CREATE TABLE provider
(
       id                INTEGER    PRIMARY KEY,
       name              VARCHAR(20),
       domain            VARCHAR(20),
       type_trunk        VARCHAR(20),
       description       VARCHAR(20)
, orden integer);
INSERT INTO "provider" VALUES(1, 'Net2Phone', '', 'SIP', 'trunk type SIP', 1);
INSERT INTO "provider" VALUES(2, 'CamundaNET', '', 'SIP', 'trunk type SIP', 2);
INSERT INTO "provider" VALUES(3, 'Vitelity', '', 'SIP', 'trunk type SIP', 7);
INSERT INTO "provider" VALUES(4, 'StarVox', '', 'SIP', 'trunk type SIP', 6);
INSERT INTO "provider" VALUES(6, 'Freephonie', 'freephonie.net', 'SIP', 'trunk type SIP', 4);
INSERT INTO "provider" VALUES(7, 'OVH', 'sip.ovh.net', 'SIP', 'trunk type SIP', 5);
INSERT INTO "provider" VALUES(8, 'VoIPDiscount', 'sip.voipdiscount.com', 'SIP', 'trunk type SIP', 8);
INSERT INTO "provider" VALUES(9, 'CircuitID', '', 'SIP', 'trunk type SIP', 3);
INSERT INTO "provider" VALUES(10, 'Vozelia', '', 'SIP', 'trunk type SIP', 9);
CREATE TABLE trunk_bill
(
       trunk             VARCHAR(50)
);
CREATE TABLE provider_account
(
       id                INTEGER       PRIMARY KEY,
       account_name      VARCHAR(40),
       username          VARCHAR(40),
       password          VARCHAR(40),
       callerID          VARCHAR(40)   DEFAULT '',
       type              VARCHAR(20),
       qualify           VARCHAR(20),
       insecure          VARCHAR(20),
       host              VARCHAR(20),
       fromuser          VARCHAR(20),
       fromdomain        VARCHAR(20),
       dtmfmode          VARCHAR(20),
       disallow          VARCHAR(20),
       context           VARCHAR(20),
       allow             VARCHAR(20),
       trustrpid         VARCHAR(20),
       sendrpid          VARCHAR(20),
       canreinvite       VARCHAR(20),
       type_trunk        VARCHAR(20),
       status            VARCHAR(20)   DEFAULT 'activate',
       id_provider       INTEGER, id_trunk INTEGER,
       FOREIGN KEY(id_provider) REFERENCES provider(id)
);
COMMIT;
TEMP;
    
    // Undo previous leftover garbage
    if (file_exists('/tmp/trunk_dump.sql')) unlink('/tmp/trunk_dump.sql');

    $filename = tempnam('/tmp', 'elastix-pbx-installer-');
    $dbname = "$DataBaseRoot/trunk-pbx.db";

    // Undo previous leftover garbage
    if (file_exists($dbname)) unlink($dbname);

    file_put_contents($filename, $sql);
    exec("sqlite3 $dbname '.read $filename'");
    chown($dbname, 'asterisk'); chgrp($dbname, 'asterisk');
}

function doUpdatesTrunkDB($DataBaseRoot)
{
    $command  = "sqlite3 $DataBaseRoot/trunk.db 'ALTER TABLE provider_account ADD COLUMN id_trunk INTEGER'";
    exec($command);
    $command2 = "sqlite3 $DataBaseRoot/trunk.db 'ALTER TABLE provider ADD COLUMN orden INTEGER'";
    exec($command2);
    $command3 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 1 WHERE id=1'";
    exec($command3);
    $command4 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 2 WHERE id=2'";
    exec($command4);
    $command5 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 3 WHERE id=9'";
    exec($command5);
    $command6 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 4 WHERE id=6'";
    exec($command6);
    $command7 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 5 WHERE id=7'";
    exec($command7);
    $command8 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 6 WHERE id=4'";
    exec($command8);
    $command9 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 7 WHERE id=3'";
    exec($command9);
    $command10 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 8 WHERE id=8'";
    exec($command10);
    $command11 = "sqlite3 $DataBaseRoot/trunk.db 'UPDATE provider SET orden = 9 WHERE id=10'";
    exec($command11);
}

function existDBField($table, $field, $db_name, $DataBaseRoot)
{
    $query = "select $field from $table;";
    exec("sqlite3 $DataBaseRoot/$db_name '$query'",$arrConsole,$flagStatus);
    $result['flagStatus'] = $flagStatus;
    $result['arrConsole'] = $arrConsole;
    return $result;
}

function existDBTable($table, $db_name, $DataBaseRoot)
{
    exec("sqlite3 $DataBaseRoot/$db_name '.tables $table'",$arrConsole,$flagStatus);
    $result['flagStatus'] = $flagStatus;
    $result['arrConsole'] = $arrConsole;
    return $result;
}

function insertAccount($data, &$pDB, &$pDBFreePBX)
{
    $pDB->beginTransaction();
    $pDBFreePBX->beginTransaction();
    $id_trunk = getIdNextTrunk($pDBFreePBX);
    if (!saveTrunkFreePBX($data,$id_trunk,$pDBFreePBX)) {
	$pDB->rollBack();
	$pDBFreePBX->rollBack();
	echo "Error during the copy of trunks\n";
	return false;
    }
    $query = "INSERT INTO provider_account(account_name,username,password,callerID,type,qualify,insecure,host,fromuser,fromdomain,dtmfmode,disallow,context,allow,trustrpid,sendrpid,canreinvite,type_trunk,id_provider,id_trunk) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
    $result = $pDB->genQuery($query, array_merge($data,array($id_trunk)));
    if ($result==FALSE) {
	$pDB->rollBack();
	$pDBFreePBX->rollBack();
        return false;
    }
    $pDB->commit();
    $pDBFreePBX->commit();
    return true;
}

function insertTrunlBill($trunkName, &$pDB)
{
    $data = array($trunkName);
    $query = "INSERT INTO trunk_bill(trunk) VALUES(?);";
    $result = $pDB->genQuery($query, $data);
    if ($result==FALSE) {
        return false;
    }
    return true;
}

function getTrunkBills(&$pDB)
{
    $query = "SELECT * FROM trunk_bill;";
    $result = $pDB->fetchTable($query,true);
    if ($result==FALSE) {
        return false;
    }
    return $result;
}

function getTechnology($account_name, &$pDB)
{
    $account_name[0] = strtoupper($account_name[0]);
    if ($account_name == "NuFone IAX")
        return "iax";
    $data   = array($account_name);
    $query  = "SELECT type_trunk FROM provider WHERE name = ?;";
    $result = $pDB->getFirstRowQuery($query,true,$data);
    if ($result==FALSE) {
        return null;
    }
    return $result['type_trunk'];
}

function saveTrunkFreePBX($data,$id,&$pDB)
{
    if (strtolower($data[17]) == "sip") {
	$tech = "sip";
	$register = "$data[1]:$data[2]@$data[7]/$data[1]";
    }
    else{
	$tech = "iax";
	$register = "$data[1]:$data[2]@$data[7]";
    }

    $arrParam = array($id,$data[0],$tech,$data[0],$data[3]);
    $query = "insert into trunks (trunkid,name,tech,keepcid,channelid,disabled,usercontext,provider,outcid) values (?,?,?,'off',?,'off','','',?)";
    $result = $pDB->genQuery($query, $arrParam);
    if ($result==FALSE) {
	echo $pDB->errMsg."\n";
	return false;
    }
    $arrDataTech = getDataTech($data);
    $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,?)";
    foreach($arrDataTech as $key => $value) {
	$arrParam = array("tr-peer-$id",$key,$value['data'],$value['flag']);
	$result = $pDB->genQuery($query, $arrParam);
	if ($result==FALSE) {
	    echo $pDB->errMsg."\n";
	    return false;
	}
    }
    $query = "insert into $tech (id,keyword,data,flags) values (?,?,?,0)";
    $arrParam = array("tr-reg-$id","register",$register);
    $result = $pDB->genQuery($query, $arrParam);
    if ($result==FALSE) {
	echo $pDB->errMsg."\n";
	return false;
    }
    return true;
}

function getIdNextTrunk(&$pDB)
{
    $query = "select max(trunkid) as id from trunks";
    $result= $pDB->getFirstRowQuery($query,true);
    if ($result==FALSE) {
	echo $pDB->errMsg."\n";
	return false;
    }
    return 1 + $result['id'];
}

function deleteInFileCustom($trunkName,$tech)
{
    if (strtolower($tech) == "sip")
        $file = "/etc/asterisk/sip_custom.conf";
    else
        $file = "/etc/asterisk/iax_custom.conf";
    if (file_exists($file)) {
        $file_sipCustom = file($file);
        $arrLines = array();
        $record = true;
        $trunkName = str_replace(" ","",$trunkName);
        foreach($file_sipCustom as $line) {
            if (preg_match("/^#include/",rtrim($line)))
                $arrLines[] = $line;
            elseif (preg_match("/^\[$trunkName\]$/",rtrim($line)))
                $record = false;
            elseif (preg_match("/^\[/",rtrim($line))) {
                $record = true;
                $arrLines[] = $line;
            }
            elseif ($record)
                $arrLines[] = $line;
        }
        file_put_contents($file,implode("",$arrLines));
    }
}

function deleteInFileRegister($user,$password,$host,$tech)
{
    if (strtolower($tech) == "sip") {
        $arrFiles = array("/etc/asterisk/sip_registrations_custom.conf","/etc/asterisk/sip_registrations.conf");
        $register = "register=$user:$password@$host/$user";
    }
    else{
        $arrFiles = array("/etc/asterisk/iax_registrations_custom.conf","/etc/asterisk/iax_registrations.conf");
        $register = "register=$user:$password@$host";
    }
    foreach($arrFiles as $file) {
        if (file_exists($file)) {
            $file_sipRegister = file($file);
            $arrLines = array();
            foreach($file_sipRegister as $line) {
                if (rtrim($line) != $register)
                    $arrLines[] = $line;
            }
            file_put_contents($file,implode("",$arrLines));
        }
    }
}

function getDataTech($data)
{
    $dataTech['account']['data'] = $data[0];
    $dataTech['account']['flag'] = 2;
    $dataTech['host']['data'] = $data[7];
    $dataTech['host']['flag'] = 3;
    $dataTech['username']['data'] = $data[1];
    $dataTech['username']['flag'] = 4;
    $dataTech['secret']['data'] = $data[2];
    $dataTech['secret']['flag'] = 5;
    $dataTech['type']['data'] = $data[4];
    $dataTech['type']['flag'] = 6;
    if ($data[5] != "") {
	$dataTech['qualify']['data'] = $data[5];
	$dataTech['qualify']['flag'] = 7;
    }
    if ($data[6] != "") {
	$dataTech['insecure']['data'] = $data[6];
	$dataTech['insecure']['flag'] = 8;
    }
    if ($data[8] != "") {
	$dataTech['fromuser']['data'] = $data[8];
	$dataTech['fromuser']['flag'] = 9;
    }
    if ($data[9] != "") {
	$dataTech['fromdomain']['data'] = $data[9];
	$dataTech['fromdomain']['flag'] = 10;
    }
    if ($data[10] != "") {
	$dataTech['dtmfmode']['data'] = $data[10];
	$dataTech['dtmfmode']['flag'] = 11;
    }
    if ($data[11] != "") {
	$dataTech['disallow']['data'] = $data[11];
	$dataTech['disallow']['flag'] = 12;
    }
    if ($data[12] != "") {
	$dataTech['context']['data'] = $data[12];
	$dataTech['context']['flag'] = 13;
    }
    if ($data[13] != "") {
	$dataTech['allow']['data'] = $data[13];
	$dataTech['allow']['flag'] = 14;
    }
    if ($data[14] != "") {
	$dataTech['trustrpid']['data'] = $data[14];
	$dataTech['trustrpid']['flag'] = 15;
    }
    if ($data[15] != "") {
	$dataTech['sendrpid']['data'] = $data[15];
	$dataTech['sendrpid']['flag'] = 16;
    }
    if ($data[16] != "") {
	$dataTech['canreinvite']['data'] = $data[16];
	$dataTech['canreinvite']['flag'] = 17;
    }
    return $dataTech;
}

function do_reloadAll($data_connection, $arrAST, $arrAMP, &$pDB) 
{
    $bandera = true;

    if (isset($arrAMP["PRE_RELOAD"]['valor']) && !empty($arrAMP['PRE_RELOAD']['valor'])) {
	exec( $arrAMP["PRE_RELOAD"]['valor']);
    }

    //para crear los archivos de configuracion en /etc/asterisk
    $retrieve = $arrAMP['AMPBIN']['valor'].'/retrieve_conf';
    exec($retrieve);

    //reload MOH to get around 'reload' not actually doing that, reload asterisk
    $command_data = array("moh reload", "reload");
    $arrResult = AsteriskManager_Command($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);

    if (isset($arrAMP['FOPRUN']['valor'])) {
	//bounce op_server.pl
	$wOpBounce = $arrAMP['AMPBIN']['valor'].'/bounce_op.sh';
	exec($wOpBounce.' &>'.$arrAST['astlogdir']['valor'].'/freepbx-bounce_op.log');
    }

    //store asterisk reloaded status
    $sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'";
    if (!$pDB->genQuery($sql))
    {
	echo $pDB->errMsg."\n";
	$bandera = false;
    }

    if (isset($arrAMP["POST_RELOAD"]['valor']) && !empty($arrAMP['POST_RELOAD']['valor']))  {
	exec( $arrAMP["POST_RELOAD"]['valor']);
    }

    if (!$bandera) return false;
    else return true;
}

function AsteriskManager_Command($host, $user, $password, $command_data) 
{
    $salida = array();
    $astman = new AGI_AsteriskManager();
    //$salida = array();

    if (!$astman->connect("$host", "$user" , "$password")) {
	echo "Error when connecting to Asterisk Manager\n";
    } else{
	foreach($command_data as $key => $valor)
	    $salida = $astman->send_request('Command', array('Command'=>"$valor"));

	$astman->disconnect();
	$salida["Response"] = isset($salida["Response"])?$salida["Response"]:"";
	if (strtoupper($salida["Response"]) != "ERROR") {
	    return explode("\n", $salida["Response"]);
	}else return false;
    }
    return false;
}
?>
