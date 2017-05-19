<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
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
  $Id: paloSantoCDR.class.php,v 1.1.1.1 2008/01/31 21:31:55 afigueroa Exp $ */

//ini_set("display_errors", true);
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";

class paloSantoConference {
    var $_DB;
    var $errMsg;

    function paloSantoConference(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    private function _queryBooking($sQueryFields, $date_start = '', $date_end = '',
        $field_name = '', $field_pattern = '', $conference_state = '')
    {
        // Parámetros base de la petición SQL
        $sPeticionSQL = "SELECT $sQueryFields FROM booking";
        $condicionWhere = array();
        $paramWhere = array();

        // Fecha según el estado de la conferencia
        if (!empty($date_start) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date_start)) {
            $this->errMsg = _tr('Invalid date start');
            return NULL;
        }
        if (!empty($date_end) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $date_end)) {
            $this->errMsg = _tr('Invalid date end');
            return NULL;
        }
        switch ($conference_state) {
        case 'Past_Conferences':
            $condicionWhere[] = 'endTime <= ?';
            $paramWhere[] = $date_end;
            break;
        case 'Future_Conferences':
            $condicionWhere[] = 'startTime >= ?';
            $paramWhere[] = $date_start;
            break;
        default:
            $condicionWhere[] = 'startTime <= ?';
            $paramWhere[] = $date_start;
            $condicionWhere[] = 'endTime >= ?';
            $paramWhere[] = $date_end;
            break;
        }

        // Filtrado adicional por subcadena
        if (!empty($field_name) and !empty($field_pattern)) {
            if (!in_array($field_name, array('roomNo', 'roomPass', 'silPass',
                'maxUser', 'status', 'confOwner', 'confDesc', 'aFlags', 'uFlags'))) {
                $this->errMsg = _tr('Invalid text field');
                return NULL;
            }
            $condicionWhere[] = "AND $field_name LIKE ?";
            $paramWhere[] = "%$field_pattern%";
        }

        // Armar SQL con WHERE
        if (count($condicionWhere) > 0) {
            $sPeticionSQL .= ' WHERE '.implode(' AND ', $condicionWhere);
        }

        return array($sPeticionSQL, $paramWhere);
    }

    function ObtainConferences($limit, $offset, $date_start = '', $date_end = '',
        $field_name = '', $field_pattern = '', $conference_state = '')
    {
        list($sPeticionSQL, $paramWhere) = $this->_queryBooking(
            'roomNo, confDesc, startTime, endTime, maxUser, bookId, roomPass, '.
            'confOwner, silPass, aFlags, uFlags', $date_start, $date_end,
            $field_name, $field_pattern, $conference_state);
        $sPeticionSQL .= ' ORDER BY startTime';
        if (!empty($limit)) {
        	$sPeticionSQL .= ' LIMIT ? OFFSET ?';
            $paramWhere[] = $limit; $paramWhere[] = $offset;
        }
        $result = $this->_DB->fetchTable($sPeticionSQL, TRUE, $paramWhere);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
        	$result = NULL;
        }
        return $result;
    }

    function ObtainConferenceData($bookId)
    {
    	$sPeticionSQL =
            'SELECT roomNo, confDesc, startTime, endTime, maxUser, bookId, '.
                'roomPass, confOwner, silPass, aFlags, uFlags FROM booking '.
            'WHERE bookId = ?';
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, TRUE, array($bookId));
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            $result = NULL;
        }
        return $result;
    }

    function ObtainNumConferences($date_start = '', $date_end = '',
        $field_name = '', $field_pattern = '', $conference_state = '')
    {
        list($sPeticionSQL, $paramWhere) = $this->_queryBooking(
            'COUNT(*)', $date_start, $date_end, $field_name, $field_pattern,
            $conference_state);
        $result = $this->_DB->getFirstRowQuery($sPeticionSQL, FALSE, $paramWhere);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            $result = NULL;
        }
        return $result;
    }

    function CreateConference($sNombreConf, $sNumeroConf, $sFechaInicio,
        $iSegundosDuracion, $iMaxUsuarios, $opciones = NULL)
    {
    	if (!is_array($opciones)) $opciones = array();
        $sPeticionSQL =
            'INSERT INTO booking (confDesc, confOwner, roomNo, silPass, aFlags, '.
                'roomPass, uFlags, startTime, endTime, maxUser, clientId, '.
                'status, sequenceNo, recurInterval) '.
            'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
        $paramSQL = array(
            $sNombreConf,
            isset($opciones['confOwner']) ? $opciones['confOwner'] : '',
            $sNumeroConf,
            isset($opciones['silPass']) ? $opciones['silPass'] : '',
            'asdA'.
                ((isset($opciones['moderatorAnnounce']) && $opciones['moderatorAnnounce']) ? 'i' : '').
                ((isset($opciones['moderatorRecord']) && $opciones['moderatorRecord']) ? 'r' : ''),
            isset($opciones['roomPass']) ? $opciones['roomPass'] : '',
            'd'.
                ((isset($opciones['userAnnounce']) && $opciones['userAnnounce']) ? 'i' : '').
                ((isset($opciones['userListenOnly']) && $opciones['userListenOnly']) ? 'm' : '').
                ((isset($opciones['userWaitLeader']) && $opciones['userWaitLeader']) ? 'w' : ''),
            $sFechaInicio,
            date('Y-m-d H:i:s', strtotime($sFechaInicio) + $iSegundosDuracion),
            $iMaxUsuarios,
            0,      // clientId
            'A',    // status
            0,      // sequenceNo: Si se usa recurrencia debe autoincrementar
            0,      // recurInterval: Si se usa recurrencia debe calcularse el tiempo
        );
        $result = $this->_DB->genQuery($sPeticionSQL, $paramSQL);
        if (!$result) {
            $this->errMsg = $this->_DB->errMsg;
        	return NULL;
        }
        return $this->_DB->getLastInsertId();
    }

    function ConferenceNumberExist($number)
    {
        $result = $this->_DB->getFirstRowQuery(
            'SELECT COUNT(*) FROM booking WHERE roomNo = ?', FALSE,
            array($number));
        return (is_array($result) && $result[0] > 0);
    }

    function DeleteConference($BookId)
    {
        return $this->_DB->genQuery(
            'DELETE FROM booking WHERE bookId = ?',
            array($BookId));
    }

    function ObtainCallers($data_connection, $room)
    {
        $arrCallers = array();
        if (!ctype_digit($room)) return $arrCallers;

        // User #: 01         1064 device               Channel: SIP/1064-00000001     (unmonitored) 00:00:11
        $regexp = '!^User #:\s*(\d+)\s*(\d+).*Channel: (\S+)\s*(\(.*\))\s*([[:digit:]|\:]+)$!i';

        $command = "meetme list $room";
        $arrResult = $this->AsteriskManager_Command($data_connection['host'],
            $data_connection['user'], $data_connection['password'], $command);

        if(is_array($arrResult) && count($arrResult)>0) {
            foreach($arrResult as $Key => $linea) {
                if (preg_match($regexp, $linea, $arrReg)) {
                    $arrCallers[] = array(
                        'userId'    => $arrReg[1],
                        'callerId'  => $arrReg[2],
                        'mode'      => $arrReg[4],
                        'duration'  => $arrReg[5]
                    );
                }
            }
        }
        return $arrCallers;
    }

    function MuteCaller($data_connection, $room, $userId, $mute)
    {
        if (!ctype_digit($room)) return FALSE;
        if (count(preg_split("/[\r\n]+/", $userId)) > 1) return FALSE;

        if($mute=='on')
            $action = 'mute';
        else
            $action = 'unmute';
        $command = "meetme $action $room $userId";
        $arrResult = $this->AsteriskManager_Command($data_connection['host'],
            $data_connection['user'], $data_connection['password'], $command);
    }

    function KickCaller($data_connection, $room, $userId)
    {
        if (!ctype_digit($room)) return FALSE;
        if (count(preg_split("/[\r\n]+/", $userId)) > 1) return FALSE;

        $action = 'kick';
        $command = "meetme $action $room $userId";
        $arrResult = $this->AsteriskManager_Command($data_connection['host'],
            $data_connection['user'], $data_connection['password'], $command);
    }

    function KickAllCallers($data_connection, $room)
    {
        if (!ctype_digit($room)) return FALSE;

        $command = "meetme kick $room all";
        $arrResult = $this->AsteriskManager_Command($data_connection['host'],
            $data_connection['user'], $data_connection['password'], $command);
    }

    function InviteCaller($data_connection, $room, $device, $callerId)
    {
        if (!ctype_digit($device)) return FALSE;
        if (!ctype_digit($room)) return FALSE;
        if (count(preg_split("/[\r\n]+/", $callerId)) > 1) return FALSE;

        $command_data['device'] = $device;
        $command_data['room'] = $room;
        $command_data['callerid'] = $callerId;
        return $this->AsteriskManager_Originate($data_connection['host'],
            $data_connection['user'], $data_connection['password'], $command_data);
    }

    private function AsteriskManager_Command($host, $user, $password, $command) {
        $astman = new AGI_AsteriskManager( );
        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            $salida = $astman->Command("$command");
            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["data"]);
            }
        }
        return false;
    }

    private function AsteriskManager_Originate($host, $user, $password, $command_data) {
        $astman = new AGI_AsteriskManager();

        if (!$astman->connect("$host", "$user" , "$password")) {
            $this->errMsg = _tr("Error when connecting to Asterisk Manager");
        } else{
            /* Para poder pasar el parámetro Data correctamente, se debe usar
             * un valor separado por barra vertical (|) en Asterisk 1.4.x y una
             * coma en Asterisk 1.6.x. Para es se requiere detectar la versión
             * de Asterisk.
             */
            // CoreSettings sólo está disponible en asterisk 1.6.x
            $r = $astman->send_request('CoreSettings');
            $asteriskVersion = array(1, 4, 0, 0);
            if ($r['Response'] == 'Success' && isset($r['AsteriskVersion'])) {
                $asteriskVersion = explode('.', $r['AsteriskVersion']);
                // CoreSettings reporta la versión de Asterisk
            } else {
                // no hay soporte CoreSettings en Asterisk Manager, se asume Asterisk 1.4.x.
            }
            $versionMinima = array(1, 6, 0);
            while (count($versionMinima) < count($asteriskVersion))
                array_push($versionMinima, 0);
            while (count($versionMinima) > count($asteriskVersion))
                array_push($asteriskVersion, 0);
            $sSeparador = ($asteriskVersion >= $versionMinima) ? ',' : '|';

            $parameters = $this->Originate($command_data['device'],
                $command_data['callerid'], $command_data['room'], $sSeparador);
            $salida = $astman->send_request('Originate', $parameters);

            $astman->disconnect();
            if (strtoupper($salida["Response"]) != "ERROR") {
                return explode("\n", $salida["Response"]);
            }else return false;
        }
        return false;
    }

    private function Originate($channel, $callerid, $data, $sSep)
    {
        $parameters = array();
        $parameters['Channel'] = "Local/" . $channel;
        $parameters['CallerID'] = $callerid;
        $parameters['Data'] = $data . $sSep . "d";
        $parameters['Application'] = "MeetMe";

        return $parameters;
    }

    function getDeviceFreePBX($dsn)
    {
        $pDB = new paloDB($dsn);
        if($pDB->connStatus)
            return false;
        $sqlPeticion = "select id, concat(description,' <',user,'>') label FROM devices ORDER BY id ASC;";
        $result = $pDB->fetchTable($sqlPeticion,true); //se consulta a la base asterisk
        $pDB->disconnect();
        $arrDevices = array();
        if(is_array($result) && count($result)>0){
                $arrDevices['unselected'] = "-- "._tr('Unselected')." --";
            foreach($result as $key => $device){
                $arrDevices[$device['id']] = $device['label'];
            }
        }
        else{
            $arrDevices['no_device'] = "-- "._tr('No Extensions')." --";
        }
	return $arrDevices;
    }

    //CB for fix issue - Phone number active
    function ConferenceNumberExistDateRange($number,$fecha_ini)
    {
        $query = "SELECT COUNT(*) as NUM  FROM booking WHERE roomNo = ? AND endTime > ?";
        $result = $this->_DB->getFirstRowQuery($query, FALSE, array($number, $fecha_ini));
        if($result[0] > 0)
            return true;
        else
          return false;
    }
    //CB - END
}
?>