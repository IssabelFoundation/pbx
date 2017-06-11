<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 1.0                                                  |
  | http://www.issabel.org                                               |
  +----------------------------------------------------------------------+
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
  $Id: index.php,v 1.1 2008/01/30 15:55:57 a_villacis Exp $ */
require_once '/var/lib/asterisk/agi-bin/phpagi-asmanager.php';

define('VOICEMAIL_CONFIG', '/etc/asterisk/voicemail.conf');

class paloSantoExtensionsBatch
{
    private $_amportal;
    private $_astconfig;
    private $_DB;
    var $errMsg;

    private $_colRequeridas = array('name', 'extension', 'secret', 'tech');
    private $_batch = array();

    /**
     * Procedimiento para reunir en un solo lugar el título a asignar a cada
     * etiqueta interna
     *
     * @return  arreglo tag=>título
     */
    function getFieldTitles()
    {
        return array(
            'name'                  =>  'Display Name',
            'extension'             =>  'User Extension',
            'directdid'             =>  'Direct DID',
            'outboundcid'           =>  'Outbound CID',
            'callwaiting'           =>  'Call Waiting',
            'secret'                =>  'Secret',
            'voicemail'             =>  'Voicemail Status',
            'vm_secret'             =>  'Voicemail Password',
            'email_address'         =>  'VM Email Address',
            'pager_email_address'   =>  'VM Pager Email Address',
            'vm_options'            =>  'VM Options',
            'email_attachment'      =>  'VM Email Attachment',
            'play_cid'              =>  'VM Play CID',
            'play_envelope'         =>  'VM Play Envelope',
            'delete_vmail'          =>  'VM Delete Vmail',
            'context'               =>  'Context',
            'tech'                  =>  'Tech',
            'callgroup'             =>  'Callgroup',
            'pickupgroup'           =>  'Pickupgroup',
            'disallow'              =>  'Disallow',
            'allow'                 =>  'Allow',
            'deny'                  =>  'Deny',
            'permit'                =>  'Permit',
            'recording_in_external' =>  'Record Incoming External',
            'recording_out_external'=>  'Record Outgoing External',
            'recording_in_internal' =>  'Record Incoming Internal',
            'recording_out_internal'=>  'Record Outgoing Internal',
            'recording_ondemand'    =>  'Record On Demand',
            'recording_priority'    =>  'Record Priority',
        );
    }

    function __construct(&$pDB, $arrAST, $arrAMP)
    {
        $this->_amportal = $arrAMP;
        $this->_astconfig = $arrAST;

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

    /**
     * Procedimiento para leer la información de todas las extensiones del
     * sistema, y devolver la lista. Esta función se usa para la descarga CSV.
     *
     * @return  NULL en caso de error, o un arreglo con la siguiente estructura:
            [extension] => 1086
            [name] => P Cuenta SIP
            [outboundcid] =>
            [tech] => sip
            [parameters] => Array
                (
                    [dtmfmode] => rfc2833
                    [canreinvite] => no
                    [context] => from-internal
                    [secret] => 1086pppp
                    [port] => 5060
                    [nat] => yes
                    [qualify] => yes
                    [callgroup] =>
                    [pickupgroup] =>
                    [disallow] =>
                    [allow] =>
                    [dial] => SIP/1086
                    [accountcode] =>
                    [type] => friend
                    [host] => dynamic
                    [mailbox] => 1086@device
                    [deny] => 0.0.0.0/0.0.0.0
                    [permit] => 0.0.0.0/0.0.0.0
                    [account] => 1086
                    [callerid] => device <1086>
                    [recording_in_external] => enabled|dontcare|disabled
                    [recording_out_external] => enabled|dontcare|disabled
                    [recording_in_internal] => enabled|dontcare|disabled
                    [recording_out_internal] => enabled|dontcare|disabled
                    [recording_ondemand] => enabled|disabled
                    [recording_priority] => 10
                )

            [callwaiting] => DISABLED
            [directdid] =>
            [voicemail] => disable
            [vm_secret] =>
            [email_address] =>
            [pager_email_address] =>
            [vm_options] =>
            [email_attachment] => no
            [play_cid] => no
            [play_envelope] => no
            [delete_vmail] => no
     *
     */
    function queryExtensions()
    {
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin())) {
            $this->errMsg = "Error connect AGI_AsteriskManager";
            return NULL;
        }

        // Lista básica de extensiones
    	$sql = 'SELECT u.extension, u.name, u.outboundcid, d.tech '.
            'FROM users u, devices d WHERE u.extension = d.id';
        $recordset = $this->_DB->fetchTable($sql, TRUE);
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
        	return NULL;
        }

        // Parámetros de las extensiones
        $sql =  '(SELECT "sip" AS tech, id, keyword, data FROM sip) UNION '.
                '(SELECT "iax2" AS tech, id, keyword, data FROM iax)';
        $r = $this->_DB->fetchTable($sql, TRUE);
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
        $prop = array();
        foreach ($r as $tupla) {
        	$prop[$tupla['tech']][$tupla['id']][$tupla['keyword']] = $tupla['data'];
        }
        unset($r);

        // Parámetros de llamada en espera
        $callwait = array();
        foreach ($astman->database_show('CW') as $cw_key => $status) {
        	// [/CW/5011] => ENABLED
            $regs = NULL;
            if (preg_match('|^/CW/(\w+)|', $cw_key, $regs)) $callwait[$regs[1]] = $status;
        }

        $recording = array();
        foreach ($astman->database_show('AMPUSER') as $ampuser_key => $status) {
        	$regs = NULL;
            if (trim($status) != '' &&
                preg_match('|^/AMPUSER/(\w+)/recording(/(\w+)(/(\w+))?)?|', $ampuser_key, $regs)) {
            	if (!isset($recording[$regs[1]])) {
            		// Valores por omisión para extensión
                    $recording[$regs[1]] = array(
                        'recording_in_external'     =>  'dontcare',
                        'recording_out_external'    =>  'dontcare',
                        'recording_in_internal'     =>  'dontcare',
                        'recording_out_internal'    =>  'dontcare',
                        'recording_ondemand'        =>  'disabled',
                        'recording_priority'        =>  10,
                    );
            	}
                if (!isset($regs[2])) {
                    // out=Adhoc|in=Adhoc
                    // TODO: implementar mapeo no trivial
                } else {
                    $key = 'recording_'.$regs[3].(isset($regs[5]) ? '_'.$regs[5] : '');
                    if (isset($recording[$regs[1]][$key])) {
                        $recording[$regs[1]][$key] = $status;
                    }
                }
            }
        }

        // Se carga la totalidad de voicemail.conf
        $voicemailData = array();
        foreach (file(VOICEMAIL_CONFIG) as $s) {
           $regs = NULL;
           if (preg_match('/^\s*(\d+)\s*=>\s*(.+)/', trim($s), $regs)) {
               $vmext = $regs[1];
               $fields = array_map('trim', explode(',', $regs[2]));
               $properties = array(
                   'vm_secret'              => $fields[0],
                   'email_address'          => $fields[2],
                   'pager_email_address'    => $fields[3],
               );
               $fields = array_map('trim', explode('|', $fields[4]));
               foreach ($fields as $propval) {
                   $regs = NULL;
                   if (preg_match('/^(.+)=(.+)$/', $propval, $regs))
                       $properties[$regs[1]] = $regs[2];
               }
               $voicemailData[$vmext] = $properties;
           }
        }

        // Combinar todo
        for ($i = 0; $i < count($recordset); $i++) {
            $tech = $recordset[$i]['tech'];
            $ext = $recordset[$i]['extension'];
            $recordset[$i] = array_merge($recordset[$i], array(
                'callwaiting'           =>  (isset($callwait[$ext])
                                            ? $callwait[$ext]
                                            : 'DISABLED'),
                'directdid'             =>  $this->_queryDIDByExt($ext),
                'voicemail'             => 'disable',
                'vm_secret'             => '',
                'email_address'         => '',
                'pager_email_address'   => '',
                'vm_options'            =>  '',
                'email_attachment'      => 'no',
                'play_cid'              => 'no',
                'play_envelope'         => 'no',
                'delete_vmail'          => 'no',
                'parameters'            =>  array(),
            ));
            if (isset($prop[$tech][$ext]))  $recordset[$i]['parameters'] = $prop[$tech][$ext];
            if (isset($recording[$ext]))
                $recordset[$i]['parameters'] = array_merge($recordset[$i]['parameters'], $recording[$ext]);

            if (isset($voicemailData[$ext])) {
                $recordset[$i]['voicemail'] = 'enabled';
                $vmprop = $voicemailData[$ext];
                foreach (array(
                        'vm_secret'             => 'vm_secret',
                        'email_address'         => 'email_address',
                        'pager_email_address'   => 'pager_email_address',
                        'attach'                => 'email_attachment',
                        'saycid'                => 'play_cid',
                        'envelope'              => 'play_envelope',
                        'delete'                => 'delete_vmail',
                    ) as $k1 => $k2) {
                    if (isset($vmprop[$k1])) {
                        $recordset[$i][$k2] = $vmprop[$k1];
                        unset($vmprop[$k1]);
                    }
                }
                $vmoptions = array();
                foreach ($vmprop as $k => $v) $vmoptions[] = "$k=$v";
                $recordset[$i]['vm_options'] = implode('|', $vmoptions);
            }
        }
        $astman->disconnect();
        return $recordset;
    }

    private function _queryDIDByExt($extension)
    {
        $sql = 'SELECT description, extension FROM incoming WHERE destination LIKE ?';
        $tupla = $this->_DB->getFirstRowQuery($sql, TRUE, array("%$extension%"));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return "";
        }
        if (!empty($tupla['extension'])) return $tupla['extension'];
        if (!empty($tupla['description'])) return $tupla['description'];
        return '';
    }

    /**
     * Procedimiento para cargar extensiones desde un archivo CSV. Las claves de
     * las opciones de la extensión se deducen a partir de las cabeceras de la
     * primera fila. La mayoría de las validaciones sobre los valores de las
     * propiedades se delegan a addExtension()
     *
     * @param   string  $sFilePath  Ruta al archivo CSV a procesar
     *
     * @return  bool    VERDADERO en carga exitosa, FALSO en error
     */
    function loadExtensionsCSV($sFilePath)
    {
        $hArchivo = fopen($sFilePath, 'r');
        if (!$hArchivo) {
            $this->errMsg = _tr("The file is incorrect or empty") .": $sFilePath";
        	return FALSE;
        }

    	// Lista de cabeceras y el campo correspondiente de propiedad
        $fieldTags = array_flip($this->getFieldTitles());

        // Construir mapa de posición de columna a clave correspondiente
        $mapaCol = array();
        $tupla = fgetcsv($hArchivo, 4096, ',');
        foreach ($tupla as $i => $h) {
        	if (isset($fieldTags[$h])) $mapaCol[$i] = $fieldTags[$h];
        }

        $exito = TRUE;

        // Verificación de columnas requeridas
        if ($exito) {
            if (count(array_intersect($this->_colRequeridas, $mapaCol)) < 4) {
                $this->errMsg = _tr("Verify the header") ." - ".
                    _tr("At minimum there must be the columns").
                    ": \"Display Name\", \"User Extension\", \"Secret\", \"Tech\"";
            	$exito = FALSE;
            }
        }

        // Cargar el resto del archivo
        if ($exito) {
            $numLinea = 2;
            while ($exito && $tupla = fgetcsv($hArchivo, 4096, ',')) {
            	$extension = array('line_number' => $numLinea);
                foreach ($tupla as $i => $v) if (trim($v) != '') $extension[$mapaCol[$i]] = trim($v);

                if (!$this->addExtension($extension)) {
                	$this->errMsg = _tr('Line')." $numLinea: ".$this->errMsg;
                    $exito = FALSE;
                }

                $numLinea++;
            }
        }

        fclose($hArchivo);
        return $exito;
    }

    function getNumBatch() { return count($this->_batch); }

    /**
     * Procedimiento para agregar una nueva extensión a la lista de extensiones
     * a procesar, luego de validar que sus propiedades sean consistentes.
     *
     * @param   array   $extension  Tupla de las propiedades de la extensión
     *
     * @return  bool    VERDADERO si la extensión nueva se acepta, FALSO si no.
     */
    function addExtension($extension)
    {
    	$numLinea = isset($extension['line_number']) ? $extension['line_number'] : _tr('(unavailable)');

        // Valores por omisión
        if (!isset($extension['deny'])) $extension['deny'] = '0.0.0.0/0.0.0.0';
        if (!isset($extension['permit'])) $extension['permit'] = '0.0.0.0/0.0.0.0';
        if (!isset($extension['context'])) $extension['context'] = 'from-internal';

        foreach (array('recording_in_external', 'recording_out_external',
            'recording_in_internal', 'recording_out_internal') as $k) {
            if (!isset($extension[$k])) $extension[$k] = 'dontcare';
            $extension[$k] = strtolower($extension[$k]);
            if ($extension[$k] == 'enabled') $extension[$k] = 'always';
            if ($extension[$k] == 'disabled') $extension[$k] = 'never';
            if (!in_array($extension[$k], array('always', 'dontcare', 'never')))
                $extension[$k] = 'dontcare';
        }
        if (!isset($extension['recording_ondemand'])) $extension['recording_ondemand'] = 'disabled';
        $extension['recording_ondemand'] = strtolower($extension['recording_ondemand']);
        if (!in_array($extension['recording_ondemand'], array('enabled', 'disabled')))
            $extension[$k] = 'disabled';
        if (!isset($extension['recording_priority']) ||
            !ctype_digit($extension['recording_priority']))
            $extension['recording_priority'] = 10;
        if ($extension['recording_priority'] < 0 || $extension['recording_priority'] > 20)
            $extension['recording_priority'] = 10;

        // TODO: mapear parámetros antiguos 'record_in', 'record_out' Adhoc|Always|Never

        $extension['voicemail'] = (isset($extension['voicemail']) &&
            stripos($extension['voicemail'], 'enable') === 0)
            ? 'enable' : 'disable';
        $extension['callwaiting'] = (isset($extension['callwaiting']) &&
            stripos($extension['callwaiting'], 'ENABLE') === 0)
            ? 'ENABLED' : 'DISABLED';

        // Esta transformación estaba presente en implementación anterior
        if (isset($extension['outboundcid'])) {
        	$extension['outboundcid'] = str_replace('“', '"', $extension['outboundcid']);
            $extension['outboundcid'] = str_replace('”', '"', $extension['outboundcid']);
        }

        if (!isset($extension['extension'])) {
            $this->errMsg = _tr("Can't exist a extension empty. Line").": $numLinea";
        	return FALSE;
        }
        if (!isset($extension['name'])) {
            $this->errMsg = _tr("Can't exist a display name empty. Line").": $numLinea";
            return FALSE;
        }
        if (!isset($extension['tech'])) {
            $this->errMsg = _tr("Can't exist a technology empty. Line").": $numLinea";
            return FALSE;
        }
        if (!isset($extension['secret']) || !$this->_valida_password($extension['secret'])) {
            $this->errMsg = _tr("Secret weak. Line").": $numLinea";
            return FALSE;
        }
        if (!in_array($extension['tech'], array('sip', 'iax', 'iax2'))) {
            $this->errMsg = _tr("Error, extension")." ".$extension['extension'].
                " "._tr("has a wrong tech in line")." $numLinea "._tr("Tech must be sip or iax");
            return FALSE;
        }
        if ($extension['tech'] == 'iax') $extension['tech'] = 'iax2';

        // Ninguno de los valores admite saltos de línea
        foreach ($extension as $k => $v) {
        	if (strpbrk($v, "\r\n") !== FALSE) {
                $this->errMsg = _tr('Newlines not allowed for field').": $k";
        		return FALSE;
        	}
        }

        // El número de extensión debe ser numérico
        if (!ctype_digit($extension['extension'])) {
            $this->errMsg = _tr('Invalid extension, must be numeric');
        	return FALSE;
        }

        // Si el password de voicemail está definido, debe ser numérico (Issabel bug #1238)
        if (isset($extension['vm_secret']) && !ctype_digit($extension['vm_secret'])) {
            $this->errMsg = _tr('Voicemail password must be numeric');
        	return FALSE;
        }

        // Los valores de deny y permit deben ser IPs válidas
        $pattern = "/^(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}\/(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)(?:[.](?:25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)){3}$/";
        foreach (array('deny', 'permit') as $k) {
            $ipmasklist = explode('&', $extension[$k]);
            foreach ($ipmasklist as $ipmask) {
                if ($ipmask != '0.0.0.0/0.0.0.0' && !preg_match($pattern, $ipmask)) {
                    $this->errMsg = _tr('Invalid IP/mask for field').": $k";
                    return FALSE;
                }
            }
        }

        // No se admiten extensiones repetidas
        if (isset($this->_batch[$extension['extension']])) {
            $n2 = isset($this->_batch[$extension['extension']]['line_number'])
                ? $this->_batch[$extension['extension']]['line_number']
                : _tr('(unavailable)');
            $this->errMsg = _tr("Error, extension")." ".$extension['extension'].
                " "._tr("repeat in lines")." $n2";
        	return FALSE;
        }

        $this->_batch[$extension['extension']] = $extension;

        return TRUE;
    }

    /**
     * Procedimiento para validar un Secret de cuenta de SIP/IAX. La regla usada
     * por IssabelPBX es: el secreto debe ser de al menos 6 caracteres, y debe de
     * tener al menos dos dígitos y dos letras. Pero se admiten caracteres
     * arbitrarios fuera de estas restricciones.
     *
     * @param   string  $Secret Secreto a validar
     *
     * @return  VERDADERO si el secreto es válido, FALSO si no.
     */
    private function _valida_password($Secret)
    {
        if(strlen($Secret) <= 5)
            return false;

        return (preg_match('/[[:alpha:]].*[[:alpha:]]/', $Secret)
            && preg_match('/[[:digit:]].*[[:digit:]]/', $Secret));
    }

    /**
     * Procedimiento para realizar todos los cambios correspondientes a las
     * extensiones agregadas con addExtension().
     *
     * @return  bool    VERDADERO en caso de éxito, FALSO si fallo.
     */
    function applyExtensions()
    {
        ksort($this->_batch);

        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin())) {
            $this->errMsg = "Error connect AGI_AsteriskManager";
            return FALSE;
        }

        $exito = TRUE;
        $this->_DB->beginTransaction();

        if ($exito) foreach ($this->_batch as $extension) {
        	$exito = $this->_updateTechDevices($extension); if (!$exito) break;
            $exito = $this->_updateUsers($extension); if (!$exito) break;
            $exito = $this->_updateDevices($extension); if (!$exito) break;
            $exito = $this->_updateDirectDID($extension); if (!$exito) break;
            $exito = $this->_updateCallWaiting($extension, $astman); if (!$exito) break;
            $exito = $this->_updateAsteriskDB($extension, $astman); if (!$exito) break;
        }

        if ($exito) $exito = $this->_updateVoicemailConf();

        // Aplicar cambios a la base de datos
        if (!$exito) {
            $this->_DB->rollBack();
            return FALSE;
        }
        $this->_DB->commit();

        $exito = $this->_recargarAsterisk($astman);
        $astman->disconnect();
        return $exito;
    }

    private function _updateTechDevices($extension)
    {
    	/* Para la tecnología indicada, se borra la información de la misma
         * extensión que esté presente en la otra tecnología */
        if ($extension['tech'] == 'sip') $sqlborrar = 'DELETE FROM iax WHERE id = ?';
        if ($extension['tech'] == 'iax2') $sqlborrar = 'DELETE FROM sip WHERE id = ?';
        if (!$this->_DB->genQuery($sqlborrar, array($extension['extension']))) {
            $this->errMsg = $this->_DB->errMsg;
        	return FALSE;
        }

        // Sentencias a usar para probar y actualizar cada propiedad
        if ($extension['tech'] == 'sip') $tabla = 'sip';
        if ($extension['tech'] == 'iax2') $tabla = 'iax';
        $sqlleer = "SELECT COUNT(*) AS n FROM $tabla WHERE id = ? AND keyword = ?";
        $sqlupdate = "UPDATE $tabla SET data = ? WHERE id = ? AND keyword = ?";
        $sqlinsert = "INSERT INTO $tabla (data, id, keyword) VALUES (?, ?, ?)";

        // Las propiedades a insertar o actualizar para la extensión
        $prop = array(
            'callerid'      =>  'device <'.$extension['extension'].'>',
            'account'       =>  $extension['extension'],
            'mailbox'       =>  $extension['extension'].(($extension['voicemail'] == 'enable') ? '@default' : '@device'),
            'accountcode'   =>  '',
            'allow'         =>  isset($extension['allow']) ? $extension['allow'] : '',
            'disallow'      =>  isset($extension['disallow']) ? $extension['disallow'] : '',
            'qualify'       =>  'yes',
            'type'          =>  'friend',
            'host'          =>  'dynamic',
            'context'       =>  $extension['context'],
            'secret'        =>  $extension['secret'],
            'deny'          =>  $extension['deny'],
            'permit'        =>  $extension['permit'],

            //IssabelPBX 2.11: recording ya no se usa en mysql sino en astdb
            //'record_out'    =>  $extension['record_out'],
            //'record_in'     =>  $extension['record_in'],
        );
        if ($extension['tech'] == 'iax2') {
        	$prop = array_merge($prop, array(
                'dial'              =>  'IAX2/'.$extension['extension'],
                'port'              =>  4569,
                'requirecalltoken'  =>  'yes',
                'setvar'            =>  'REALCALLERIDNUM='.$extension['extension'],

                // IssabelPBX 2.11: parámetro ya no aparece
                //'notransfer'        =>  'yes',

                // IssabelPBX 2.11: nuevos parámetros IAX2
                'transfer'  =>  'yes',
            ));
        } elseif ($extension['tech'] == 'sip') {
            $prop = array_merge($prop, array(
                'dial'              =>  'SIP/'.$extension['extension'],
                'port'              =>  5060,
                'pickupgroup'       =>  isset($extension['pickupgroup']) ? $extension['pickupgroup'] : '',
                'callgroup'         =>  isset($extension['callgroup']) ? $extension['callgroup'] : '',
                'nat'               =>  'yes',
                'canreinvite'       =>  'no',
                'dtmfmode'          =>  'rfc2833',

                // IssabelPBX 2.11: nuevos parámetros SIP
                'encryption'        =>  'no',
                'qualifyfreq'       =>  '60',
                'transport'         =>  'udp',
                'trustrpid'         =>  'yes',
                'avpf'              =>  'no',
                'icesupport'        =>  'no',
                'sendrpid'          =>  'no',
            ));
        }

        // Insertar o modificar todas las propiedades
        foreach ($prop as $k => $v) {
        	$tupla = $this->_DB->getFirstRowQuery($sqlleer, TRUE, array($extension['extension'], $k));
            if (!is_array($tupla)) {
                $this->errMsg = $this->_DB->errMsg;
            	return FALSE;
            }
            $r = $this->_DB->genQuery(
                (($tupla['n'] > 0) ? $sqlupdate : $sqlinsert),
                array($v, $extension['extension'], $k));
            if (!$r) {
                $this->errMsg = "Ext: {$extension['extension']} - "._tr('Error updating Tech').': '.$this->_DB->errMsg;
                return FALSE;
            }
        }
        return TRUE;
    }

    private function _updateUsers($extension)
    {
    	$tupla = $this->_DB->getFirstRowQuery(
            'SELECT COUNT(*) AS n FROM users WHERE extension = ?',
            TRUE, array($extension['extension']));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        $sql = ($tupla['n'] > 0)
            ? 'UPDATE users SET name = ?, voicemail = ?, recording = ?, outboundcid = ? '.
              'WHERE extension = ?'
            : 'INSERT INTO users (password, ringtimer, noanswer, mohclass, sipname, '.
                    'name, voicemail, recording, outboundcid, extension) '.
              'VALUES ("", 0, "", "default", "", ?, ?, ?, ?, ?)';
        $params = array(
            $extension['name'],
            ($extension['voicemail'] == 'enable') ? 'default' : 'novm',
            //IssabelPBX 2.11: recording ya no se usa en mysql sino en astdb
            //'out='.$extension['record_out'].'|in='.$extension['record_in'],
            '',
            isset($extension['outboundcid']) ? $extension['outboundcid'] : '',
            $extension['extension']);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = "Ext: {$extension['extension']} - "._tr('Error updating Users').': '.$this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function _updateDevices($extension)
    {
    	if ($extension['tech'] == 'sip') $dial = 'SIP/'.$extension['extension'];
        if ($extension['tech'] == 'iax2') $dial = 'IAX2/'.$extension['extension'];

        $tupla = $this->_DB->getFirstRowQuery(
            'SELECT COUNT(*) AS n FROM devices WHERE id = ?',
            TRUE, array($extension['extension']));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        $sql = ($tupla['n'] > 0)
            ? 'UPDATE devices SET tech = ?, dial = ?, description = ?, user = ? WHERE id = ?'
            : 'INSERT INTO devices (devicetype, emergency_cid, tech, dial, description, user, id) '.
              'VALUES ("fixed", "", ?, ?, ?, ?, ?)';
        $params = array(
            $extension['tech'],
            $dial,
            $extension['name'],
            $extension['extension'],
            $extension['extension']);
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = "Ext: {$extension['extension']} - "._tr('Error updating Devices').': '.$this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function _updateDirectDID($extension)
    {
    	if (!isset($extension['directdid'])) return TRUE;

        $tupla = $this->_DB->getFirstRowQuery(
            'SELECT COUNT(*) AS n FROM incoming WHERE destination LIKE ?',
            TRUE, array('%'.$extension['extension'].'%'));
        if (!is_array($tupla)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        if ($tupla['n'] > 0) {
        	$sql = 'UPDATE incoming SET extension = ?, description = ?, destination = ? '.
                   'WHERE destination LIKE ? LIMIT 1';
            $params = array(
                $extension['directdid'],
                $extension['directdid'],
                'from-did-direct,'.$extension['extension'].',1',
                '%'.$extension['extension'].'%');
        } else {
        	$sql = 'INSERT INTO incoming (cidnum, extension, description, destination, '.
                        'privacyman, alertinfo, ringing, grppre, delay_answer, '.
                        'pricid, pmmaxretries, pmminlength) '.
                   'VALUES ("", ?, ?, ?, 0, "", "", "", 0, "", "", "")';
            $params = array(
                $extension['directdid'],
                $extension['directdid'],
                'from-did-direct,'.$extension['extension'].',1');
        }
        if (!$this->_DB->genQuery($sql, $params)) {
            $this->errMsg = "Ext: {$extension['extension']} - "._tr('Error to insert or update Direct DID').': '.$this->_DB->errMsg;
            return FALSE;
        }
        return TRUE;
    }

    private function _updateCallWaiting($extension, $astman)
    {
        return ($extension['callwaiting'] == 'ENABLED')
            ? $astman->database_put('CW', $extension['extension'], 'ENABLED')
            : $astman->database_del('CW', $extension['extension']);
    }

    private function _updateAsteriskDB($extension, $astman)
    {
        if ($extension['tech'] == 'sip') $dial = 'SIP/'.$extension['extension'];
        if ($extension['tech'] == 'iax2') $dial = 'IAX2/'.$extension['extension'];

        $dbprops = array(
            'AMPUSER'   => array(
                'cidname'       =>  '"'.$extension['name'].'"',
                'cidnum'        =>  $extension['extension'],
                'device'        =>  $extension['extension'],
                'noanswer'      =>  '',
                'outboundcid'   =>  str_replace(
                                        array('"', ' '), array("'", ''),
                                        isset($extension['outboundcid']) ? $extension['outboundcid'] : ''),
                'password'      =>  '',
                'ringtimer'     =>  0,
                'voicemail'     =>  ($extension['voicemail'] == 'enable') ? 'default' : 'novm',

                // Parámetros obsoletos en IssabelPBX 2.11
                //'recording'     =>  'out='.$extension['record_out'].'|in='.$extension['record_in'],
                'recording'     =>  '',

                // IssabelPBX 2.11: nuevos parámetros (dontcare|always|never)
                'recording/in/external' =>  $extension['recording_in_external'],
                'recording/in/internal' =>  $extension['recording_in_internal'],
                'recording/out/external'=>  $extension['recording_out_external'],
                'recording/out/internal'=>  $extension['recording_out_internal'],
                'recording/ondemand'    =>  $extension['recording_ondemand'],
                'recording/priority'    =>  $extension['recording_priority'], // 1..20 default 10

                // IssabelPBX 2.11: por ahora las siguientes propiedades no se setean desde archivo
                'answermode'            =>  'disabled',
                'cfringtimer'           =>  0,
                'concurrency_limit'     =>  0,
                'language'              =>  '',
                'queues/qnostate'       =>  'usestate',
            ),
            'DEVICE'    => array(
                'default_user'  =>  $extension['extension'],
                'dial'          =>  $dial,
                'type'          =>  'fixed',
                'user'          =>  $extension['extension'],
            ),
        );
        foreach ($dbprops as $family => $keyvals) foreach ($keyvals as $key => $value) {
            if (!$astman->database_put($family, $extension['extension'].'/'.$key, $value)) {
                $this->errMsg = "Ext: {$extension['extension']} - "._tr('Error processing Database Family');
                return FALSE;
            }
        }
        return TRUE;
    }

    private function _updateVoicemailConf()
    {
    	$lineas = file(VOICEMAIL_CONFIG);
        if ($lineas === FALSE) {
            $this->errMsg = _tr('Failed to open voicemail config');
        	return FALSE;
        }

        // Quitar referencias anteriores a extensiones nuevas
        $l2 = array();
        for ($i = 0; $i < count($lineas); $i++) {
            $remover = FALSE;
        	$regs = NULL;
            if (preg_match('/^(\d+)\s+=>/', $lineas[$i], $regs)) {
            	if (isset($this->_batch[$regs[1]])) $remover = TRUE;
            }

            if (!$remover) $l2[] = $lineas[$i];
        }
        $lineas = $l2;

        // Agregar líneas de voicemail
        foreach ($this->_batch as $extension) if ($extension['voicemail'] == 'enable') {
            foreach (array('email_attachment', 'play_cid', 'play_envelope', 'delete_vmail') as $k)
                if (!isset($extension[$k]) || $extension[$k] != 'yes') $extension[$k] = 'no';
            $lineas[] = $extension['extension'].' => '.implode(',', array(
                isset($extension['vm_secret']) ? $extension['vm_secret'] : '',
                $extension['name'],
                isset($extension['email_address']) ? $extension['email_address'] : '',
                isset($extension['pager_email_address']) ? $extension['pager_email_address'] : '',
                (isset($extension['vm_options']) ? $extension['vm_options'].'|' : '').
                    implode('|', array(
                        'attach='.$extension['email_attachment'],
                        'saycid='.$extension['play_cid'],
                        'envelope='.$extension['play_envelope'],
                        'delete='.$extension['delete_vmail']))
            ))."\n";
        }

        return (file_put_contents(VOICEMAIL_CONFIG, $lineas) !== FALSE);
    }

    function deleteExtensions()
    {
        $astman = new AGI_AsteriskManager();
        if (!$astman->connect("127.0.0.1", 'admin' , obtenerClaveAMIAdmin())) {
            $this->errMsg = "Error connect AGI_AsteriskManager";
            return FALSE;
        }

        $exito = TRUE;
        $this->_DB->beginTransaction();

        // Lista de extensiones a borrar
        $sql = "SELECT id FROM devices WHERE tech = 'sip' OR tech = 'iax2'";
        $recordset = $this->_DB->fetchTable($sql);
        if (!is_array($recordset)) {
            $this->errMsg = $this->_DB->errMsg;
        	$exito = FALSE;
        }
        $extlist = array();
        foreach ($recordset as $tupla) $extlist[] = $tupla[0];
        unset($recordset);

        foreach ($extlist as $ext) {
        	// Borrar propiedades en base de datos de Asterisk
            foreach (array('AMPUSER', 'DEVICE', 'CW', 'CF', 'CFB', 'CFU') as $family) {
                $astman->database_deltree($family.'/'.$ext);
            }
            if (!$exito) break;
        }

        if ($exito) {
        	foreach (array(
                "DELETE s FROM sip s INNER JOIN devices d ON s.id=d.id and d.tech='sip'",
                "DELETE i FROM iax i INNER JOIN devices d ON i.id=d.id and d.tech='iax2'",
                "DELETE u FROM users u INNER JOIN devices d ON u.extension=d.id and (d.tech='sip' or d.tech='iax2')",
                "DELETE FROM devices WHERE tech='sip' or tech='iax2'",
                ) as $sql) {
            	if (!$this->_DB->genQuery($sql)) {
                    $this->errMsg = $this->_DB->errMsg;
            		$exito = FALSE; break;
            	}
            }
        }

        // Aplicar cambios a la base de datos
        if (!$exito) {
            $this->_DB->rollBack();
            return FALSE;
        }
        $this->_DB->commit();

        $exito = $this->_recargarAsterisk($astman);
        $astman->disconnect();
        return $exito;
    }

    private function _recargarAsterisk($astman)
    {
        $bandera = true;

        if (isset($this->_amportal["PRE_RELOAD"]['valor']) &&
            !empty($this->_amportal['PRE_RELOAD']['valor'])) {
            exec( $this->_amportal["PRE_RELOAD"]['valor']);
        }

        //para crear los archivos de configuracion en /etc/asterisk
        $retrieve = $this->_amportal['AMPBIN']['valor'].'/retrieve_conf';
        exec($retrieve);

        //reload MOH to get around 'reload' not actually doing that, reload asterisk
        foreach (array("moh reload", "reload") as $c)
            $astman->command($c);

        if (isset($this->_amportal['FOPRUN']['valor'])) {
            //bounce op_server.pl
            $wOpBounce = $this->_amportal['AMPBIN']['valor'].'/bounce_op.sh';
            exec($wOpBounce.' &>'.$this->_astconfig['astlogdir']['valor'].'/issabelpbx-bounce_op.log');
        }

        //store asterisk reloaded status
        $sql = "UPDATE admin SET value = 'false' WHERE variable = 'need_reload'";
        if (!$this->_DB->genQuery($sql))
        {
            $this->errMsg = $this->_DB->errMsg;
            $bandera = false;
        }

        if (isset($this->_amportal["POST_RELOAD"]['valor']) &&
            !empty($this->_amportal['POST_RELOAD']['valor'])) {
            exec( $this->_amportal["POST_RELOAD"]['valor']);
        }

        return $bandera;
    }
}
?>
