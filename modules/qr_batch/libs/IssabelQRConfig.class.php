<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
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
*/

$document_root = $_SERVER["DOCUMENT_ROOT"];
require_once("$document_root/libs/paloSantoDB.class.php");
require_once("$document_root/libs/paloSantoConfig.class.php");
require_once("phpqrcode/phpqrcode.php");

class IssabelQRConfig {

    var $_DB;
    var $_allips;

    function __construct()
    {
        global $arrConf;

        $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
        $arrConfig = $pConfig->leer_configuracion(false);

        $dsnAsterisk = $arrConfig['AMPDBENGINE']['valor']."://".
            $arrConfig['AMPDBUSER']['valor']. ":".
            $arrConfig['AMPDBPASS']['valor']. "@".
            $arrConfig['AMPDBHOST']['valor']."/asterisk";

        $pDB = new paloDB($dsnAsterisk);

        if(!empty($pDB->errMsg)) {
            echo "$this->rutaDB: $pDB->errMsg <br>";
        } else{
            $this->_DB = $pDB;
            $sPeticionSQL = 'SELECT data FROM sipsettings WHERE keyword="externip_val"';
            $result = $this->_DB->getFirstRowQuery($sPeticionSQL, TRUE, array());
            if(count($result)>0) {
                $this->_allips[$result['data']]=1;
            }

            $allipsipaddr = $this->listIpAddresses();
            foreach($allipsipaddr as $ip) {
                $this->_allips[$ip]=1;
            }

            if (!$this->tableExists()) {
                if (!$this->createTable()) {
                    $this->errMsg = "The table qrcode_templates does not exist and could not be created";
                    return false;
                } else {
                    $this->populateTable();
                }
            }
        }
    }

    public function getIPs() {
        $ips = array();
        foreach($this->_allips as $key=>$val) {
            $ips[]=$key;
        }
        return $ips;
    }

    function listIpAddresses()
    {
        $iflist = array(); $if = NULL;
        $output = NULL;
        exec('/sbin/ip addr show', $output);
        $bIsEther = FALSE;
        $ip = NULL;
        foreach ($output as $s) {
            $regs = NULL;
            if (preg_match('/^\d+:\s+([\w\.-]+)(@\w+)?:\s*<(.*)>/', $s, $regs)) {
                $if = $regs[1];
                $bIsEther = FALSE;
            } elseif (strpos($s, 'link/ether') !== FALSE) {
                $bIsEther = TRUE;
            } elseif (preg_match('|\s*inet (\d+\.\d+\.\d+.\d+)/(\d+) brd (\d+\.\d+\.\d+.\d+).+\s(([\w\.-]+)(:(\d+)?)?)\s*$|', trim($s), $regs)) {
                if (!is_null($if) && !isset($iflist[$if])) {
                    $iflist[$if] = array('ipaddr' => $regs[1]);
                }
            }
        }
        $allips=array();
        foreach($iflist as $key=>$ip) {
            $allips[]=$ip['ipaddr'];
        }
        return $allips;
    }

    private function tableExists() {
        $query = "SELECT * FROM qrcode_templates";
        $result = $this->_DB->genQuery($query);
        if ($result === false) {
            if (preg_match("/doesn't exist/i", $this->_DB->errMsg)) {
                return false;
            }
            else {
                return true;
            }
        }
        else {
            return true;
        }
    }

    private function createTable() {
        $query = "CREATE TABLE qrcode_templates (id int(11) auto_increment primary key not null, name varchar(50), template text)";
        return $this->_DB->genExec($query);
    }

    private function populateTable() {
        $query = "INSERT INTO qrcode_templates (name,template) values (?,?)";
        $templates['GSWave']="
            <?xml version='1.0' encoding='utf-8'?>
            <AccountConfig version='1'>
              <Account>
                  <RegisterServer>{SERVER}</RegisterServer>
                  <OutboundServer></OutboundServer>
                  <UserID>{EXTENSION}</UserID>
                  <AuthID>{EXTENSION}</AuthID>
                  <AuthPass>{SECRET}</AuthPass>
                  <AccountName>{EXTENSION}</AccountName>
                  <DisplayName>{NAME}</DisplayName>
                  <Dialplan>{x+|*x+|*++}</Dialplan>
                  <RandomPort>0</RandomPort>
                  <SecOutboundServer></SecOutboundServer>
                  <Voicemail>*97</Voicemail>
                </Account>
            </AccountConfig>
        ";
        foreach($templates as $name=>$template) {
            $this->_DB->genQuery($query,array($name,$template));
        }
    }

    public function getAvailableTemplates() {
        $sPeticionSQL = 'SELECT name FROM qrcode_templates ORDER BY name';
        $results = $this->_DB->fetchTable($sPeticionSQL, true, array());
        $templates = array();
        foreach($results as $result) {
            $templates[] = $result['name'];
        }
        return $templates;
    }

    public function getTemplate($name) {
        $sPeticionSQL = 'SELECT template FROM qrcode_templates WHERE name=?';
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, TRUE, array($name));
        if(count($result)>0) {
            return $result['template'];
        } else {
            return '';
        }
    }

    public function generateQr($extension,$name,$secret,$server_ip,$template) {
        $xml = trim(preg_replace('/\s+/', ' ', $template));
        $xml = preg_replace("/\{SERVER\}/",$server_ip,$xml);
        $xml = preg_replace("/{EXTENSION}/",$extension,$xml);
        $xml = preg_replace("/{SECRET}/",$secret,$xml);
        $xml = preg_replace("/{NAME}/",$name,$xml);
        $tmpfname = tempnam("/tmp", "QR");
        QRcode::png($xml,$tmpfname);
        $imgdata = base64_encode(file_get_contents($tmpfname));
        unlink($tmpfname);
        return $imgdata;
    }

}
