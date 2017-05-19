<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.0.4-5                                               |
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
  $Id: index.php,v 1.1 2011-04-14 11:04:34 Alberto Santos asantos@palosanto.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoFestival.class.php";
    include_once "libs/paloSantoJSON.class.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];


    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "change":
            $content = changeStatusFestival();
            break;
        default: // view_form
            $content = viewFormFestival($smarty, $module_name, $local_templates_dir, $arrConf);
            break;
    }
    return $content;
}

function viewFormFestival($smarty, $module_name, $local_templates_dir, $arrConf)
{
    $pFestival = new paloSantoFestival();
    $arrFormFestival = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormFestival);
    $_DATA = $_POST;
    if($pFestival->isFestivalActivated())
        $_DATA["status"] = "on";
    else
        $_DATA["status"] = "off";
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("icon", "modules/$module_name/images/pbx_tools_festival.png");
    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Festival"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function changeStatusFestival()
{
    $pFestival  = new paloSantoFestival();
    $jsonObject = new PaloSantoJSON();
    $status    = getParameter("status");
    $message   = "";
    $arrMessage["button_title"] = _tr("Dismiss");
    if($status=="activate"){
        $arrMessage["mb_message"] = '';
        $arrMessage["mb_title"] = _tr("Message").":<br/>";
        switch ($pFestival->activateFestival()) {
        case 1:     // Servicio iniciado, se modificó archivo
            $arrMessage["mb_message"] = _tr("");
        // cae al siguiente caso
        case 0:     // Servicio iniciado, sin modificación
            $arrMessage["mb_message"] .= _tr("Festival has been successfully activated");
            break;
        case -1:    // Error al iniciar servicio
            $arrMessage["mb_title"] = _tr("ERROR").":<br/>";
            $arrMessage["mb_message"] = $pFestival->getError();
            break;
        }
    }
    elseif($status=="deactivate"){
        if(!$pFestival->isFestivalActivated()){
	    $arrMessage["mb_title"] = _tr("ERROR").":<br/>";
	    $arrMessage["mb_message"] = _tr("Festival is already deactivated");
            $jsonObject->set_message($arrMessage);
	    return $jsonObject->createJSON();
        }
        if($pFestival->deactivateFestival()){
	    $arrMessage["mb_title"] = _tr("Message").":<br/>";
	    $arrMessage["mb_message"] = _tr("Festival has been successfully deactivated");
        }
        else{
	    $arrMessage["mb_title"] = _tr("ERROR").":<br/>";
	    $arrMessage["mb_message"] = _tr("Festival could not be deactivated");
        }
    }
    $jsonObject->set_message($arrMessage);
    return $jsonObject->createJSON();
}

function createFieldForm()
{
    $arrFields = array(
            "status"   => array(            "LABEL"                  => _tr("Status"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "CHECKBOX",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            ),

            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("action") == "change") //Get parameter by POST (submit)
        return "change";
    else
        return "report";
}
?>
