<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2021 Issabel Foundation                                |
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
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
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: paloSantoRecordings.class.php, Thu 20 May 2021 08:47:25 AM EDT, nicolas@issabel.com
*/

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

class paloSantoRecordings {
    var $errMsg;

    function __construct()
    {
    }

    function Obtain_Extension_Current_User($arrConf)
    {
        $pDB_acl = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pDB_acl);
        $username = $_SESSION["issabel_user"];
        $extension = $pACL->getUserExtension($username);
        if(is_null($extension))
            return false;
        else return $extension;
    }

    function Obtain_Protocol_Current_User($arrConf)
    {
        $pDB_acl = new paloDB($arrConf['issabel_dsn']['acl']);
        $pACL = new paloACL($pDB_acl);
        $username = $_SESSION["issabel_user"];
        $extension = $pACL->getUserExtension($username);

        if($extension)
        {
            $dsnAsterisk = generarDSNSistema('asteriskuser', 'asterisk');
            $pDB = new paloDB($dsnAsterisk);

            $query = "SELECT dial, description, id FROM devices WHERE id = ?";
            $result = $pDB->getFirstRowQuery($query, TRUE, array($extension));
            if($result != FALSE)
                return $result;
            else return FALSE;
        }else return FALSE;
    }

    function Call2Phone($data_connection, $origen, $destino, $channel, $description)
    {
        $command_data['origen'] = $origen;
        $command_data['destino'] = $destino;
        $command_data['channel'] = $channel;
        $command_data['description'] = $description;
        return $this->AsteriskManager_Originate($data_connection['host'], $data_connection['user'], $data_connection['password'], $command_data);
    }

    function AsteriskManager_Originate($host, $user, $password, $command_data) {
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            $parameters = $this->Originate($command_data['origen'], $command_data['destino'], $command_data['channel'], $command_data['description']);

            $salida = $astman->send_request('Originate', $parameters);

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    function Originate($origen, $destino, $channel="", $description="")
    {
        $parameters = array();
        $parameters['Channel']      = $channel;
        $parameters['CallerID']     = "$description <$origen>";
        $parameters['Exten']        = $destino;
        $parameters['Context']      = "from-internal";
        $parameters['Priority']     = 1;
        $parameters['Application']  = "";
        $parameters['Data']         = "";

        return $parameters;
    }

    function Obtain_Protocol_from_Ext($dsn, $id)
    {
        $pDB = new paloDB($dsn);

        $query = "SELECT dial, description FROM devices WHERE id = ?";
        $result = $pDB->getFirstRowQuery($query, TRUE, array($id));
        if($result != FALSE)
            return $result;
        else{
            $this->errMsg = $pDB->errMsg;
            return FALSE;
        }
    }
}
?>
