<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-3                                               |
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
require_once "libs/paloSantoForm.class.php";
require_once("libs/paloSantoGrid.class.php");
require_once "libs/misc.lib.php";

if (file_exists("/var/lib/asterisk/agi-bin/phpagi-asmanager.php")) {
require_once "/var/lib/asterisk/agi-bin/phpagi-asmanager.php";
}

function _moduleContent(&$smarty, $module_name)
{
    include_once "modules/$module_name/configs/default.conf.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $sContenidoModulo = '';
    $sAccion = getParameter('action');
    switch ($sAccion) {
    case 'new':
    case 'edit':
        $sContenidoModulo = modificarArchivo($module_name, $smarty, $local_templates_dir, $arrConf['astetcdir'], $sAccion);
        break;
    case 'list':
    default:
        $sContenidoModulo = listarArchivos($module_name, $smarty, $local_templates_dir, $arrConf['astetcdir']);
        break;
    }
    return $sContenidoModulo;
}

function listarArchivos($module_name, $smarty, $local_templates_dir, $sDirectorio)
{
    // Función que rechaza los directorios punto y doble punto
    function _reject_dotdirs($s) { return !($s == '.' || $s == '..'); }
    $listaArchivos = (file_exists($sDirectorio) && is_dir($sDirectorio))
        ? array_filter(scandir($sDirectorio), '_reject_dotdirs')
        : NULL;
    if (!is_array($listaArchivos)) {
        $smarty->assign("msj_err", _tr('This is not a valid directory'));
    }

    // Filtrar por la cadena indicada en el filtro
    $sSubStrArchivo = getParameter('file');
    if ($sSubStrArchivo != '') {
        $t = array();
        foreach ($listaArchivos as $sArchivo) {
            if (strpos($sArchivo, $sSubStrArchivo) !== FALSE)
                $t[] = $sArchivo;
        }
        $listaArchivos = $t;
    }
    // Mapear de la lista de archivos al listado completo con URLs
    $arrData = array();
    foreach ($listaArchivos as $sArchivo) {
        $arrData[] = array(
            sprintf('<a href="%s">%s</a>',
                construirURL(array(
                    'menu'      =>  $module_name,
                    'action'    =>  'edit',
                    'file'      =>  $sArchivo,
		    'search'	=>  $sSubStrArchivo,
		    'nav'	=>  getParameter('nav'),
		    'page'	=>  getParameter('page'),
                )),
                htmlentities($sArchivo, ENT_COMPAT, 'UTF-8')),
            filesize($sDirectorio.$sArchivo),
        );
    }
    ////PARA EL PAGINEO
    $total = count($arrData); $limit = 25; $offset = 0;

    $oForm = new paloForm($smarty,
        array(
            "file"  => array(
                "LABEL"                  => _tr("File"),
		"REQUIRED"               => "no",
                "INPUT_TYPE"             => "TEXT",
                "INPUT_EXTRA_PARAM"      => "",
                "VALIDATION_TYPE"        => "text",
                "VALIDATION_EXTRA_PARAM" => ""
            ),
        )
    );
    $smarty->assign("Filter", _tr('Filter'));
    $smarty->assign("NEW_FILE", _tr("New File"));
    $smarty->assign('url_new', construirURL(array('menu' => $module_name, 'action' => 'new')));
    $oGrid = new paloSantoGrid($smarty);
    $arr = array("file"=>getParameter("file"),"filter"=>"Filter");
    if(empty($_POST))
	$_POST = $arr;

    $oGrid->addFilterControl(_tr("Filter applied ")._tr("File")." = ".$sSubStrArchivo, $_POST, array("file" => ""));
    $htmlFilter = $oForm->fetchForm("$local_templates_dir/new.tpl", _tr("File Editor"), $_POST);

    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);

    $offset = $oGrid->calculateOffset();

    $inicio = ($total == 0) ? 0 : $offset + 1;
    $fin = ($offset+$limit) <= $total ? $offset+$limit : $total;
    $leng = $fin - $inicio;

    $arrDatosGrid = array_slice($arrData, $inicio-1, $leng+1);
    $arrGrid = array(
        "title"    => _tr("File Editor"),
        "url"      => array('menu' => $module_name, 'file' => $sSubStrArchivo ),
        "icon"     => "/modules/$module_name/images/pbx_tools_asterisk_file_editor.png",
        "width"    => "99%",
        "start"    => $inicio,
        "end"      => $fin,
        "total"    => $total,
        "columns"  => array(
            0 => array("name"      => _tr("File List"),
                        "property1" => ""),
            1 => array("name"      => _tr("File Size"),
                        "property1" => ""),
            )
    );
    $oGrid->addNew("?menu=$module_name&action=new",_tr("New File"),true);
    $oGrid->showFilter($htmlFilter);
    return $oGrid->fetchGrid($arrGrid, $arrDatosGrid);
}

function modificarArchivo($module_name, $smarty, $local_templates_dir, $sDirectorio, $sAccion)
{
    $sNombreArchivo = '';
    $sMensajeStatus = '';

    if(isset($_POST['Reload'])){
        $parameters = array('Command'=>"module reload");
        $result = AsteriskManagerAPI("Command",$parameters,true);
        if($result){
            $smarty->assign("mb_title", "MESSAGE");
            $smarty->assign("mb_message", _tr("Asterisk has been reloaded"));
        }else{
            $smarty->assign("mb_title", "ERROR");
            $smarty->assign("mb_message", _tr("Error when connecting to Asterisk Manager"));
        }
    }

    if ($sAccion == 'new') {
        $smarty->assign('LABEL_COMPLETADO', '.conf');
        if (isset($_POST['Guardar'])) {
            if (!isset($_POST['basename']) || trim($_POST['basename']) == '') {
                $sMensajeStatus .= _tr('Please write the file name').'<br/>';
            } else {
                $sNombreArchivo = basename($_POST['basename'].'.conf');
                /* Los datos del archivo se envían desde el navegador con líneas
                   separadas por CRLF que debe ser convertido a LF para estilo Unix
                 */
                if (file_put_contents($sDirectorio.$sNombreArchivo,
                        str_replace("\r\n", "\n", $_POST['content'])) === FALSE) {
                    $sMensajeStatus .= _tr("This file doesn't have permisses to write").'<br/>';
                } else {
                    $sMensajeStatus .= _tr("The changes was saved in the file").'<br/>';
                }
            }
        }
    } elseif ($sAccion == 'edit') {
        $sNombreArchivo = basename(getParameter('file'));
        if (is_null($sNombreArchivo) ||
            !file_exists($sDirectorio.$sNombreArchivo)) {
            Header("Location: ?menu=$module_name");
            return '';
        }

        if (isset($_POST['Guardar'])) {
            /* Los datos del archivo se envían desde el navegador con líneas
               separadas por CRLF que debe ser convertido a LF para estilo Unix
             */
            if (!is_writable($sDirectorio.$sNombreArchivo) ||
                file_put_contents($sDirectorio.$sNombreArchivo,
                    str_replace("\r\n", "\n", $_POST['content'])) === FALSE) {
                $sMensajeStatus .= _tr("This file doesn't have permisses to write").'<br/>';
            } else {
                $sMensajeStatus .= _tr("The changes was saved in the file").'<br/>';
            }
        } else {
            if (!is_writable($sDirectorio.$sNombreArchivo)) {
                $sMensajeStatus .= _tr("This file doesn't have permisses to write").'<br/>';
            }
        }
        $sContenido = file_get_contents($sDirectorio.$sNombreArchivo);
        if ($sContenido === FALSE) {
            $sMensajeStatus .= _tr("This file doesn't have permisses to read").'<br/>';
        }
        if (!isset($_POST['content'])) $_POST['content'] = $sContenido;
        $_POST['basename'] = basename($sNombreArchivo);
    }

    $oForm = new paloForm($smarty,
        array(
            'basename'  =>  array(
                'LABEL'                     =>  _tr('File'),
                'REQUIRED'                  =>  'yes',
                'INPUT_TYPE'                =>  'TEXT',
                'INPUT_EXTRA_PARAM'         =>  '',
                'VALIDATION_TYPE'           =>  'text',
                'VALIDATION_EXTRA_PARAM'    =>  '',
                'EDITABLE'                  =>  ($sAccion == 'new') ? 'yes' : 'no',
            ),
            'content'   =>  array(
                'LABEL'                     =>  _tr('Content'),
                'REQUIRED'                  =>  'no',
                'INPUT_TYPE'                =>  'TEXTAREA',
                'INPUT_EXTRA_PARAM'         =>  '',
                'VALIDATION_TYPE'           =>  'text',
                'VALIDATION_EXTRA_PARAM'    =>  '',
                'ROWS'                      =>  25,
                'COLS'                      =>  100,
            ),
        )
    );
    $oForm->setEditMode();

    $smarty->assign('url_edit', construirURL(array('menu' => $module_name, 'action' => $sAccion, 'file' => $sNombreArchivo)));
    $smarty->assign('url_back', construirURL(array('menu' => $module_name), array('action', 'file', 'nav'=>getParameter('nav'), 'page'=>getParameter('page'))));
    $smarty->assign('search',getParameter('search'));
    $smarty->assign('LABEL_SAVE', _tr('Save'));
    $smarty->assign('RELOAD_ASTERISK', _tr('Reload Asterisk'));
    $smarty->assign('LABEL_BACK', _tr('Back'));
    $smarty->assign('msg_status', $sMensajeStatus);
    $smarty->assign('icon',"images/user.png");

    return $oForm->fetchForm("$local_templates_dir/file_editor.tpl", _tr("File Editor"), $_POST);
}

function AsteriskManagerAPI($action, $parameters, $return_data=false)
{
    $astman_host = "127.0.0.1";
    $astman_user = 'admin';
    $astman_pwrd = obtenerClaveAMIAdmin();

    $astman = new AGI_AsteriskManager();

    if (!$astman->connect("$astman_host", "$astman_user" , "$astman_pwrd")) {
        return false;
    } else{
        $salida = $astman->send_request($action, $parameters);
        $astman->disconnect();
        if (strtoupper($salida["Response"]) != "ERROR") {
            if($return_data) return $salida;
            else return explode("\n", $salida["Response"]);
        }else return false;
    }
    return false;
}
?>
