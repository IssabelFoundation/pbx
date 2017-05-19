<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.2-4                                               |
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
  $Id: default.conf.php,v 1.1 2008-09-23 11:09:23 aflores@palosanto.com Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoTexttoWav.class.php";

    load_language_module($module_name);

    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $accion = getAction();

    $content = "";
    switch($accion){
        case "generate":
            $content = generateWav($smarty, $module_name, $local_templates_dir);
            break;
        case "back":
            header("Location: ?menu=$module_name");
        default:
            $content = form_TexttoWav($smarty, $module_name, $local_templates_dir);
            break;
    }
    return $content;
}

function generateWav($smarty, $module_name, $local_templates_dir)
{
    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormConference);

    if(!$oForm->validateForm($_POST)) {
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v) {
                $strErrorMsg .= "$k, ";
            }
        }
        $smarty->assign("mb_message", $strErrorMsg);
        $contenidoModulo =  form_TexttoWav($smarty, $module_name, $local_templates_dir);
    } else {
        $smarty->assign("GENERATE", _tr("Generate"));
        $smarty->assign("BACK", _tr("Back"));
        $smarty->assign("icon", "modules/$module_name/images/pbx_tools_text_to_wav.png");
        $smarty->assign("FORMATO", getParameter('format'));
        $smarty->assign("DOWNLOAD",_tr("Download File"));
        $path = "var";
        $smarty->assign("PATH",$path);

        $format = getParameter('format');
        $message = stripslashes(trim(getParameter('message')));
        $message = substr($message, 0, 1024);

        $oTextToWap = new paloSantoTexttoWav();
        $execute = $oTextToWap->outputTextWave($format, $message);
        if ($execute) {
        	/* Cortar la salida en este lugar. Evita lidiar con rawmode sólo
             * para caso de éxito */
            die();
        } else {
            $smarty->assign("mb_title", _tr("Error"));
            $smarty->assign("mb_message", $oTextToWap->errMsg);
        }

        $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("Text to Wav"), $_POST);
        $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";
    }
    return $contenidoModulo;
}


function form_TexttoWav($smarty, $module_name, $local_templates_dir)
{
    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("GENERATE", _tr("Generate"));
    $smarty->assign("icon", "modules/$module_name/images/pbx_tools_text_to_wav.png");
    $arrData['format'] = (getParameter("format"))?getParameter("format"):"wav";

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl", _tr("Text to Wav"), $arrData);
    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}


function createFieldForm()
{
     $arrOptions = array('wav' => "wav", 'gsm' => "gsm");

    $arrFields = array(
            "message"          => array(   "LABEL"                  => _tr("Message"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "TEXTAREA",
                                            "INPUT_EXTRA_PARAM"      => "",
                                            "VALIDATION_TYPE"        => "text",
                                            "EDITABLE"               => "si",
                                            "COLS"                   => "50",
                                            "ROWS"                   => "4",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                ),
            "format"             => array(   "LABEL"                  => _tr("Format"),
                                            "REQUIRED"               => "yes",
                                            "INPUT_TYPE"             => "RADIO",
                                            "INPUT_EXTRA_PARAM"      => $arrOptions,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                ),
            );
    return $arrFields;
}

function getAction()
{
    if(getParameter("show")) //Get parameter by POST (submit)
        return "show";
    if(getParameter("generate"))
        return "generate";
	if(getParameter("back"))
        return "back";
    else if(getParameter("new"))
        return "new";
    else if(getParameter("action")=="show") //Get parameter by GET (command pattern, links)
        return "show";
    else
        return "report";
}?>
