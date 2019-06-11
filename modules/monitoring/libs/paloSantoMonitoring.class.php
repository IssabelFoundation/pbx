<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Issabel version 4.0.0-18                                               |
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
  $Id: paloSantoMonitoring.class.php,v 1.1 2010-03-22 05:03:48 Eduardo Cueva ecueva@palosanto.com Exp $ */

define ('DEFAULT_ASTERISK_RECORDING_BASEDIR', '/var/spool/asterisk/monitor');

class paloSantoMonitoring
{
    var $_DB;
    var $errMsg;

    private static $_listaExtensiones = array(
        'wav'   =>  'audio/wav',
        'gsm'   =>  'audio/gsm',
        'mp3'   =>  'audio/mpeg',
        'WAV'   =>  'audio/wav',    // audio gsm en envoltura RIFF
    );

    function paloSantoMonitoring(&$pDB)
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
  
    private function _construirWhereMonitoring($param)
    {
        $condSQL = array();
        $paramSQL = array();

        if (!is_array($param)) {
            $this->errMsg = '(internal) invalid parameter array';
            return NULL;
        }
        if (!function_exists('_construirWhereMonitoring_notempty')) {
            function _construirWhereMonitoring_notempty($x) { return ($x != ''); }
        }
        $param = array_filter($param, '_construirWhereMonitoring_notempty');

        // La columna recordingfile debe estar no-vacía
//        $condSQL[] = 'recordingfile <> ""';

        // Fecha y hora de inicio y final del rango
        $sRegFecha = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
        if (isset($param['date_start'])) {
            if (preg_match($sRegFecha, $param['date_start'])) {
//                $condSQL[] = 'calldate >= ?';
//                $paramSQL[] = $param['date_start'];
$fecha_inicio= $param['date_start'];
            } else {
                $this->errMsg = '(internal) Invalid start date, must be yyyy-mm-dd hh:mm:ss';
                return NULL;
            }
        }
        if (isset($param['date_end'])) {
            if (preg_match($sRegFecha, $param['date_end'])) {
//                $condSQL[] = 'calldate <= ?';
//                $paramSQL[] = $param['date_end'];
$fecha_fin=$param['date_end'];
            } else {
                $this->errMsg = '(internal) Invalid end date, must be yyyy-mm-dd hh:mm:ss';
                return NULL;
            }
        }
//modificado por hgmnetwork.com 13-08-2018 para mostrar las grabaciones de las extensiones indicadas sin que sea solo 1, pueden ser varias separadas por ;
//$param['extension']="5002";
//echo " campo busqueda es :".print_r($param)."<br>";


        foreach (array('src', 'dst') as $sCampo) if (isset($param[$sCampo])) {
            $listaPat = array_filter(
                array_map('trim',
                    is_array($param[$sCampo])
                        ? $param[$sCampo]
                        : explode(',', trim($param[$sCampo]))),
                '_construirWhereMonitoring_notempty');
//echo " <hr> listaPat es ".$listaPat[0]."<hr>";
            if (!function_exists('_construirWhereMonitoring_troncal2like2')) {
                function _construirWhereMonitoring_troncal2like2($s) { return '%'.$s.'%'; }
            }
  //          $paramSQL = array_merge($paramSQL, array_map('_construirWhereMonitoring_troncal2like2', $listaPat));
            $fieldSQL = array_fill(0, count($listaPat), "$sCampo LIKE \"%$listaPat[0]%\"");

            /* Caso especial: si se especifica field_pattern=src|dst, también
             * debe buscarse si el canal fuente o destino contiene el patrón
             * dentro de su especificación de canal. */
            if ($sCampo == 'src' || $sCampo == 'dst') {
                if ($sCampo == 'src') $chanexpr = "SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1)";
                if ($sCampo == 'dst') $chanexpr = "SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1)";
 //               $paramSQL = array_merge($paramSQL, array_map('_construirWhereMonitoring_troncal2like2', $listaPat));
                $fieldSQL = array_merge($fieldSQL, array_fill(0, count($listaPat), "$chanexpr LIKE \"%$listaPat[0]%\""));
            }

//            $condSQL[] = '('.implode(' OR ', $fieldSQL).')';
//sql_busqueda para usuarios
$sql_busqueda= " (".implode(' OR ', $fieldSQL).") AND " ;
//sql_busqueda para admin todas las extensiones
$sql_busqueda_admin= " AND (".implode(' OR ', $fieldSQL).") " ;
       };


        // Tipo de grabación según nombre de archivo
        $prefixByType = array(
            'outgoing'  =>  array('O', 'o'),
            'group'     =>  array('g', 'r'),
            'queue'     =>  array('q'),
            'incoming' => array('exten'),
        );

//echo " tipo de busqueda es ".$param['recordingfile']."<br>";
  if (isset($param['recordingfile']) && isset($prefixByType[$param['recordingfile']])) {
            $fieldSQL = array();
            foreach ($prefixByType[$param['recordingfile']] as $p) {
                $fieldSQL[] = 'recordingfile LIKE "'.$p.'%"';
               // $paramSQL[] = $p.'%';
                $fieldSQL[] = 'recordingfile LIKE "'. DEFAULT_ASTERISK_RECORDING_BASEDIR.'%/'.$p.'%"';
               // $paramSQL[] = DEFAULT_ASTERISK_RECORDING_BASEDIR.'%/'.$p.'%';
            }

//            $condSQL[] = '('.implode(' OR ', $fieldSQL).')';
//sql_busqueda para usuarios
$sql_busqueda="(".implode(' OR ', $fieldSQL).") AND ";
//sql_busqueda para admin todas las extensiones
$sql_busqueda_admin=" AND (".implode(' OR ', $fieldSQL).")";
        }



 if (!isset($param['extension'])) {
//es admin ve todas las extensiones
//echo " se muestran todas las extensiones";
 $condSQL[] = 'recordingfile <> "" AND calldate >="'.$fecha_inicio.'" and calldate <="'.$fecha_fin.'" '.$sql_busqueda_admin;
};


//echo "extension en libs es (".$param['extension'].") y sql busqueda es $sql_busqueda y sql_busqueda_admin es $sql_busqueda_admin<br>";
        // Extensión de fuente o destino, copiada de paloSantoCDR.class.php
        if (isset($param['extension'])) {

//hgmnetwork.com obtenemos cada extension por separado mirando por el ; por defecto si es solo 1 pues seria la 0
$array_extensiones=explode(";",$param['extension']);
//echo "<hr>array_Extension es ahora (".print_r($array_extensiones).")<hr>";
//echo " array_extension 0 es ".$array_extensiones[0]."<br>";
//echo " array_extension 1 es ".$array_extensiones[1]."<br>";

//hacemos un bucle de tantos como extensiones tenga
$total_array_extensiones=count($array_extensiones);//nos indica cuantas extensiones se muestran
for ($a=0;$a<$total_array_extensiones;$a++){
            $condSQL[] = <<<SQL_COND_EXTENSION
recordingfile <> "" AND calldate >= "$fecha_inicio" AND calldate <= "$fecha_fin" AND $sql_busqueda(
       src = ?
    OR dst = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1) = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1) = ?
)
SQL_COND_EXTENSION;

//            array_push($paramSQL, $param['extension'], $param['extension'],
//                $param['extension'], $param['extension']);
            array_push($paramSQL, $array_extensiones[$a],$array_extensiones[$a],
                $array_extensiones[$a], $array_extensiones[$a]);

};
        }

/*
//        foreach (array('src', 'dst') as $sCampo) if (isset($param[$sCampo])) {
*/
        // Tipo de grabación según nombre de archivo

        // Construir fragmento completo de sentencia SQL
//el primero siempre debe ser and luego si hay mas seria or

//echo "<hr> el condSQL es: $condSQL[3]<hr>";
$where = array(implode(' OR ', $condSQL), $paramSQL);




        if ($where[0] != '') $where[0] = 'WHERE '.$where[0];
//modificado por hgmnetwork.com para obtener el sql de diferentes extensiones por ; 13-08-2018
//echo "<hr> el sql es ".print_r($where)."<hr>";
        return $where;

    }

    function getNumMonitoring($param)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereMonitoring($param);
        if (is_null($sWhere)) return NULL;

        $query = 'SELECT COUNT(*) FROM cdr '.$sWhere;
        $r = $this->_DB->getFirstRowQuery($query, FALSE, $paramSQL);
        if (!is_array($r)){
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
              }
        return $r[0];
    }

    function getMonitoring($param, $limit = NULL, $offset = 0)
    {
        list($sWhere, $paramSQL) = $this->_construirWhereMonitoring($param);
        if (is_null($sWhere)) return NULL;

        // TODO: paloSantoCDR ordena por calldate DESC. ¿Debería ser concordante?
        $query = 'SELECT * FROM cdr '.$sWhere.' ORDER BY uniqueid DESC';
        if (!empty($limit)) {
            $query .= " LIMIT ? OFFSET ?";
            array_push($paramSQL, $limit, $offset);
        }
echo " query es $query";
        $r = $this->_DB->fetchTable($query, TRUE, $paramSQL);
        if (!is_array($r)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
//echo "<hr> esto es en monitoring/libs : el query es $query <hr>r es ".print_r($r)."<hr>";
        return $r;
    }

    function deleteRecordFile($id, $namefile)
    {
        $recinfo = $this->getAudioByUniqueId($id, $namefile);
        if (is_null($recinfo)) return FALSE;

        $r = $this->_DB->genQuery(
            'UPDATE cdr SET recordingfile = ? WHERE uniqueid = ? AND recordingfile = ?',
            array('deleted', $id, $recinfo['recordingfile']));
        if (!$r) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        if (!is_null($recinfo['fullpath']))
            return unlink($recinfo['fullpath']);
        return TRUE;
    }

    function getAudioByUniqueId($id, $namefile = NULL)
    {
        $query = 'SELECT recordingfile FROM cdr WHERE uniqueid = ?';
        $parame = array($id);
        if (!is_null($namefile)) {
            $query .= ' AND recordingfile LIKE ?';
            $parame[] = '%'.$namefile.'%';
        }
        $result = $this->_DB->getFirstRowQuery($query, TRUE, $parame);
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return NULL;
        }
    if (count($result) <= 0) {
            $this->errMsg = '(internal) CDR not found by specified id/namefile';
            return NULL;
        }

        $result = array_merge($result, $this->resolveRecordingPath($result['recordingfile']));
        return $result;
    }

    function resolveRecordingPath($recordingfile)
    {
        $result['fullpath'] = NULL;
        $result['mimetype'] = NULL;
        $result['deleted'] = ($recordingfile == 'deleted');
        if (!$result['deleted']) {
            $result['fullpath'] = $this->_rutaAbsolutaGrabacion($recordingfile);
        }
        if (!is_null($result['fullpath'])) {
            $regs = NULL;
            if (preg_match('/\.(\S{3})$/', $result['fullpath'], $regs)) {
                if (in_array($regs[1], array_keys(self::$_listaExtensiones))) {
                    $result['mimetype'] = self::$_listaExtensiones[$regs[1]];
                }
            }
        }
        return $result;
    }

    private function _rutaAbsolutaGrabacion($file)
    {
        $basedir = DEFAULT_ASTERISK_RECORDING_BASEDIR.'/';

        /* Si la ruta almacenada en recordingfile es absoluta, sólo se acepta
         * si luego de canonicalizar inicia en /var/spool/asterisk/monitor */
        if ($file{0} == '/') {
            $dir = realpath(dirname($file)); // FALSE si el directorio no existe
            if ($dir === FALSE || strpos($dir.'/', $basedir) !== 0)
                return NULL;
            $file = substr($dir.'/'.basename($file), strlen($basedir));
        }

        if (file_exists($basedir.$file)) return $basedir.$file;

        /* ¿Por qué no existe la ruta indicada? */

        /* Algunas rutas a través del dialplan almacenan el archivo en
         * el directorio clasificado por año/mes/día, pero no almacenan
         * esta ruta en recordingfile. Se analiza el nombre de archivo
         * para detectar si tiene posible información de fecha, y se
         * adjunta esta fecha como camino si no está ya adjuntada. Este
         * análisis sólo se hace si el recordingfile no tiene componentes
         * de directorio. */
        $datedir = '';
         if (strpos($file, '/') === FALSE) {
            /* FreePBX acostumbra construir el nombre de archivo con
             * componentes separados por guiones. Si uno de los
             * componentes representa una fecha, se compondrá de
             * exactamente 8 dígitos y empezará con 2. */
            foreach (explode('-', $file) as $test_token) {
                if (strlen($test_token) == 8 && ctype_digit($test_token) &&
                    $test_token{0} == '2') {
                    // /var/spool/asterisk/monitor/2010/12/31/
                    $testdir = substr($test_token, 0, 4).'/'.
                        substr($test_token, 4, 2).'/'.
                        substr($test_token, 6, 2).'/';
                    if (is_dir($basedir.$testdir)) {
                        $datedir = $testdir;
                        break;
                    }
                }
            }

            if (file_exists($basedir.$datedir.$file)) return $basedir.$datedir.$file;
        }

        /* Algunas rutas a través del dialplan guardan un nombre de archivo SIN
         * EXTENSIÓN. Se verifica si un glob con la ruta parcial lista algún
         * conjunto de archivos. ESTO TOMA TIEMPO. */
        $bFaltaExtension = TRUE;
        $regs = NULL;
        if (preg_match('/\.(\S{3})$/', $file, $regs)) {
            /* Si la extensión es conocida, ni me molesto en hacer el glob. */
            $bFaltaExtension = !(in_array($regs[1], array_keys(self::$_listaExtensiones)));
        }
        if ($bFaltaExtension) {
            // $datedir puede ser "" si no se resolvió una fecha
            if ($datedir != '') {
                $r = glob($basedir.$datedir.$file.'*');
                if (count($r) > 0) return $r[0];
            }
            $r = glob($basedir.$file.'*');
            if (count($r) > 0) return $r[0];
        }

        // ...me doy
        return NULL;
    }
     function recordBelongsToUser($uniqueid, $extension)
    {
        $sql = <<<RECORD_BELONGS_TO_EXTENSION
SELECT COUNT(*) FROM cdr
WHERE uniqueid = ? AND (
       src = ?
    OR dst = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(channel,'-',1),'/',-1) = ?
    OR SUBSTRING_INDEX(SUBSTRING_INDEX(dstchannel,'-',1),'/',-1) = ?
)
RECORD_BELONGS_TO_EXTENSION;
        $result = $this->_DB->getFirstRowQuery($sql, FALSE,
            array($uniqueid, $extension, $extension, $extension, $extension));
        if (!is_array($result)) {
            $this->errMsg = $this->_DB->errMsg;
            return FALSE;
        }
        return ($result[0] > 0);
    }
}





