<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 1.0                                                  |
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
  $Id: index.php,v 1.1 2008/01/30 15:55:57 afigueroa Exp $ */

function _moduleContent(&$smarty, $module_name)
{
    //include elastix framework
    include_once "libs/paloSantoGrid.class.php";
    include_once "libs/paloSantoValidar.class.php";
    include_once "libs/paloSantoConfig.class.php";
    include_once "libs/misc.lib.php";
    include_once "libs/paloSantoForm.class.php";

    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoConference.php";

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);


    //folder path for custom templates
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $arrConfig = $pConfig->leer_configuracion(false);

    $dsnMeetme =  $arrConfig['AMPDBENGINE']['valor'] . "://".
                  $arrConfig['AMPDBUSER']['valor'] . ":".
                  $arrConfig['AMPDBPASS']['valor'] . "@".
                  $arrConfig['AMPDBHOST']['valor'] . "/meetme";

    $dsn_agi_manager['password'] = $arrConfig['AMPMGRPASS']['valor'];
    $dsn_agi_manager['host'] = $arrConfig['AMPDBHOST']['valor'];
    $dsn_agi_manager['user'] = 'admin';

    //solo para obtener los devices (extensiones) creadas.
    $dsnAsterisk = $arrConfig['AMPDBENGINE']['valor']."://".
                   $arrConfig['AMPDBUSER']['valor']. ":".
                   $arrConfig['AMPDBPASS']['valor']. "@".
                   $arrConfig['AMPDBHOST']['valor']."/asterisk";

    $pDB     = new paloDB($dsnMeetme);

    if(isset($_POST["new_conference"])) $accion = "new_conference";
    else if(isset($_POST["add_conference"])) $accion = "add_conference";
    else if(isset($_POST["cancel"])) $accion = "cancel";
    else if(isset($_POST["new_open"])) $accion = "new_conference";
    else if(isset($_POST["delete_conference"])) $accion = "delete_conference";
    else if(isset($_POST["caller_invite"])) $accion = "caller_invite";
    else if(isset($_POST["callers_mute"])) $accion = "callers_mute";
    else if(isset($_POST["callers_kick"])) $accion = "callers_kick";
    else if(isset($_POST["callers_kick_all"])) $accion = "callers_kick_all";
    else if(isset($_POST["update_show_callers"])) $accion = "update_show_callers";
    else if(isset($_GET["accion"]) && $_GET["accion"]=="show_callers") $accion = "show_callers";
    else if(isset($_GET["accion"]) && $_GET["accion"]=="view_conference") $accion = "view_conference";

    // Las siguientes dos opciones funcionan al integrar conferencias Web
    else if(isset($_GET["action"]) && $_GET["action"] == "list_guests") $accion = "list_guests";
    else if(isset($_GET["action"]) && $_GET["action"] == "list_chatlog") $accion = "list_chatlog";

    else $accion ="report_conference";
    $content = "";
    switch($accion)
    {
        case "new_conference":
            $content = new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig,$dsnAsterisk);
            break;
        case "add_conference":
            $content = add_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig,$dsnAsterisk);
            break;
        case "cancel":
            header("Location: ?menu=$module_name");
            break;
        case "delete_conference":
            $content = delete_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk);
            break;
        case "show_callers":
            $content = show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "callers_mute":
            $content = callers_mute($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "callers_kick":
            $content = callers_kick($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "view_conference":
            $content = view_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig,$dsnAsterisk);
            break;
        case "callers_kick_all":
            $content = callers_kick_all($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "caller_invite":
            $content = caller_invite($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case "update_show_callers":
            $room = getParameter('roomNo');
            header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
            break;
        case 'list_guests': // Para caso de conferencias web
            $content = embedded_webConf_mostrarListaInvitados($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        case 'list_chatlog': // Para caso de conferencias web
            $content = embedded_webConf_mostrarChatlog($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
        default:
            $content = report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
            break;
    }

    return $content;
}

function report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    global $arrConf;

    $bSoporteWebConf = (file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php'));
    $arrConference = array("Past_Conferences" => _tr("Past Conferences"), "Current_Conferences" => _tr("Current Conferences"), "Future_Conferences" => _tr("Future Conferences"));

    $arrFormElements = array(
                                "conference"  => array(  "LABEL"                  => _tr("State"),
                                                    "REQUIRED"               => "yes",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrConference,
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => "",
                                                    "EDITABLE"               => "no",
                                                    "SIZE"                   => "1"),

                                "filter" => array(  "LABEL"                  => _tr("Filter"),
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "TEXT",
                                                    "INPUT_EXTRA_PARAM"      => array("id" => "filter_value"),
                                                    "VALIDATION_TYPE"        => "text",
                                                    "VALIDATION_EXTRA_PARAM" => ""),
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $smarty->assign("SHOW", _tr("Show"));
   // $smarty->assign("NEW_CONFERENCE", _tr("New Conference"));

    $startDate = $endDate = date("Y-m-d H:i:s");

    $conference = getParameter("conference");
    $field_pattern = getParameter("filter");
    if($conference)
        $_POST['conference'] = $conference;
    else $_POST['conference'] = "Current_Conferences";

    $oGrid  = new paloSantoGrid($smarty);

    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("State")." = ".$arrConference[$_POST['conference']], $_POST, array("conference" => "Current_Conferences"),true);
    $oGrid->addFilterControl(_tr("Filter applied: ")._tr("Conference Name")." = $field_pattern", $_POST, array("filter" => ""));
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/conference.tpl", "", $_POST);

    $pConference = new paloSantoConference($pDB);
    $total_datos =$pConference->ObtainNumConferences($startDate, $endDate, "confDesc", $field_pattern, $conference);

    //Paginacion
    $limit  = 8;
    $total  = $total_datos[0];
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $end    = ($offset+$limit)<=$total ? $offset+$limit : $total;
    $url = array('menu' => $module_name, 'conference' => $conference, 'filter' => $field_pattern);
    //Fin Paginacion

    $arrResult =$pConference->ObtainConferences($limit, $offset, $startDate, $endDate, "confDesc", $field_pattern, $conference);

    $pConfWeb = NULL;
    if ($bSoporteWebConf) $pConfWeb = embedded_prepareWebConfLister();
    $arrData = null;
    if(is_array($arrResult) && $total>0){

        // En caso de haber soporte de conferencias web, se recoge el ID de
        // conferencia telefónica asociada a la conferencia web, y se construye
        // la lista de datos para las columnas adicionales
        $listaWebConf = array();
        if (!is_null($pConfWeb)) {
            $pACL = new paloACL($arrConf['elastix_dsn']['acl']);
            $listaWC = $pConfWeb->listarConferencias($pACL->isUserAdministratorGroup($_SESSION['elastix_user']));
            foreach ($listaWC as $tuplaConf) {
                if (!is_null($tuplaConf['id_cbmysql_conference']))
                    $listaWebConf[$tuplaConf['id_cbmysql_conference']] = $tuplaConf;
            }
        }

        foreach($arrResult as $key => $conference){
            $arrTmp[0]  = "<input type='checkbox' name='conference_{$conference['bookId']}'  />";
            $arrTmp[1] = "<a href='?menu=$module_name&accion=view_conference&conferenceId=".$conference['bookId']."'>".htmlentities($conference['confDesc'], ENT_COMPAT, "UTF-8")."</a>";
            $arrTmp[2] = $conference['roomNo'];
            $arrTmp[3] = $conference['startTime'].' - '.$conference['endTime'];
            if($_POST['conference'] == "Current_Conferences")
            {
                $arrCallers = $pConference->ObtainCallers($dsn_agi_manager, $conference['roomNo']);
                $numCallers = count($arrCallers);
                $arrTmp[4] = "<a href='?menu=$module_name&accion=show_callers&roomNo=".$conference['roomNo']."'>{$numCallers} / {$conference['maxUser']}</a>";
            }
            else
                $arrTmp[4] = $conference['maxUser'];

            if ($bSoporteWebConf) {
                $arrTmp[5] = '';
                $arrTmp[6] = '';
                $arrTmp[7] = '';
                $arrTmp[8] = '';
                if (isset($listaWebConf[$conference['bookId']])) {
                    $tuplaConf = $listaWebConf[$conference['bookId']];
                    $arrTmp[5] = htmlentities($tuplaConf['tema'], ENT_COMPAT, "UTF-8");
                    $arrTmp[6] = $tuplaConf['num_invitados'];
                    $arrTmp[7] = $tuplaConf['num_documentos'];
                    $arrTmp[8] =
                        "<a href=\"?menu=$module_name&amp;action=list_guests&amp;id_conference=$tuplaConf[id_conferencia]\">["._tr('List guests')."]</a>&nbsp;".
                        "<a href=\"?menu=$module_name&amp;action=list_chatlog&amp;id_conference=$tuplaConf[id_conferencia]\">["._tr('Chatlog')."]</a>";
                }
            }

            $arrData[] = $arrTmp;
        }
    }

    $arrGrid = array("title"    => _tr("Conference"),
                        "url"      => $url,
                        "icon"     => "/modules/$module_name/images/pbx_conference.png",
                        "width"    => "99%",
                        "start"    => ($total==0) ? 0 : $offset + 1,
                        "end"      => $end,
                        "total"    => $total,
                        'columns'   =>  array(
                            array('name' => ""),
                            array("name"      => _tr("Conference Name"),),
                            array("name"      => _tr("Conference #"),),
                            array('name'        => _tr('Period')),
                            array('name'        => _tr("Participants"),),
                        ),
                    );

    if ($bSoporteWebConf) {
        $arrGrid['columns'][] = array('name' => _tr('Topic'));
        $arrGrid['columns'][] = array('name' => _tr('# Guests'));
        $arrGrid['columns'][] = array('name' => _tr('# Docs'));
        $arrGrid['columns'][] = array('name' => _tr('Options'));
    }
    $oGrid->addNew("new_conference",_tr('New Conference'));
    $oGrid->deleteList(_tr("Are you sure you wish to delete conference (es)?"),"delete_conference",_tr("Delete"));
    $oGrid->showFilter(trim($htmlFilter));
    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrData);

    return $contenidoModulo;
}

function new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk)
{
    $bSoporteWebConf = (file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php'));

    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("Show", 1);
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("SAVE", _tr("Save"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("icon","/modules/$module_name/images/pbx_conference.png");
    $smarty->assign("announce", _tr("Announce"));
    $smarty->assign("record", _tr("Record"));
    $smarty->assign("listen_only", _tr("Listen Only"));
    $smarty->assign("wait_for_leader", _tr("Wait for Leader"));
    $smarty->assign("enable_web_conf", _tr("Enable Web Conference"));

    $pConference = new paloSantoConference($pDB);
    while(true)
    {
        $number = rand(0,99999);
        $existe = $pConference->ConferenceNumberExist($number);
        if(!$existe)
        {
            $_POST['conference_number'] = $number;
            break;
        }
    }

    if (!isset($_POST['max_participants'])) $_POST['max_participants'] = 10;
    if (!isset($_POST['duration'])) $_POST['duration'] = 1;
    if (!isset($_POST['duration_min'])) $_POST['duration_min'] = 0;


    // Si se detecta que hay soporte para crear conferencias web, se muestra
    // también la interfaz correspondiente
    $content = '';
    if ($bSoporteWebConf) {
        $content = embedded_viewFormCreateConference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk);
    }

    if (isset($_POST['enable_webconf'])) {
        $smarty->assign('WEBCONF_SELECTED', 'checked="checked"');
    }
    $smarty->assign('WEBCONF_CONTENT', $content);
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_conference.tpl", _tr("Conference"), $_POST);
    $contenidoModulo = "<form  method='post' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

function createFieldForm()
{
/*
    $arrReoccurs_period = array("Daily" => _tr("Daily"), "Weekly" => _tr("Weekly"), "Bi-weekly" => _tr("Bi-weekly"));
    $arrReoccurs_days = array("2" => "2 "._tr("days"), "3" => "3 "._tr("days"),
                              "4" => "4 "._tr("days"), "5" => "5 "._tr("days"),
                              "6" => "6 "._tr("days"), "7" => "7 "._tr("days"),
                              "8" => "8 "._tr("days"), "9" => "9 "._tr("days"),
                              "10" => "10 "._tr("days"), "11" => "11 "._tr("days"),
                              "12" => "12 "._tr("days"), "13" => "13 "._tr("days"),
                              "14" => "14 "._tr("days"));
*/

    $arrFields =       array("conference_name"  => array("LABEL"              => _tr('Conference Name'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:300px;"),
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                             "conference_owner" => array("LABEL"              => _tr('Conference Owner'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "conference_number" => array("LABEL"              => _tr('Conference Number'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_pin"     => array("LABEL"              => _tr('Moderator PIN'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_options_1" => array("LABEL"            => _tr('Moderator Options'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "moderator_options_2" => array("LABEL"            => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_pin"          => array("LABEL"              => _tr('User PIN'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_1"    => array("LABEL"              => _tr('User Options'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_2"    => array("LABEL"              => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "user_options_3"    => array("LABEL"              => "",
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "start_time"        => array("LABEL"              => _tr('Start Time'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "DATE",
                                                     "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%Y-%m-%d %H:%M","TIMEFORMAT" => "12"),
                                                     "VALIDATION_TYPE"        => "ereg",
                                                     "VALIDATION_EXTRA_PARAM" => "^(([1-2][0,9][0-9][0-9])-((0[1-9])|(1[0-2]))-((0[1-9])|([1-2][0-9])|(3[0-1]))) (([0-1][0-9]|2[0-3]):[0-5][0-9])$"),
                            "duration"          => array("LABEL"              => _tr('Duration (HH:MM)'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:20px;text-align:center","maxlength" =>"2"),
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "duration_min"      => array("LABEL"              => _tr('Duration (HH:MM)'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:20px;text-align:center","maxlength" =>"2"),
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
/*
                            "recurs"            => array("LABEL"              => _tr('Recurs'),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "CHECKBOX",
                                                     "INPUT_EXTRA_PARAM"      => "",
                                                     "VALIDATION_TYPE"        => "",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                            "reoccurs_period"   => array("LABEL"              => _tr("Reoccurs"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => $arrReoccurs_period,
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => "",
                                                     "EDITABLE"               => "no",
                                                     "SIZE"                   => "1"),
                            "reoccurs_days"     => array("LABEL"              => _tr("for"),
                                                     "REQUIRED"               => "no",
                                                     "INPUT_TYPE"             => "SELECT",
                                                     "INPUT_EXTRA_PARAM"      => $arrReoccurs_days,
                                                     "VALIDATION_TYPE"        => "text",
                                                     "VALIDATION_EXTRA_PARAM" => "",
                                                     "EDITABLE"               => "no",
                                                     "SIZE"                   => "1"),
*/
                            "max_participants"  => array("LABEL"              => _tr('Max Participants'),
                                                     "REQUIRED"               => "yes",
                                                     "INPUT_TYPE"             => "TEXT",
                                                     "INPUT_EXTRA_PARAM"      => array("style" => "width:50px;"),
                                                     "VALIDATION_TYPE"        => "numeric",
                                                     "VALIDATION_EXTRA_PARAM" => ""),
                        );
    return $arrFields;
}

function add_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk)
{
    $bSoporteWebConf = (file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php'));

    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty, $arrFormConference);

    $bandera = true;
    if(!empty($_POST['moderator_pin']) && !empty($_POST['user_pin']) &&  $_POST['moderator_pin']==$_POST['user_pin'])
        $bandera = false;

    $bValidoConfWeb = TRUE;
    if ($bSoporteWebConf && isset($_POST['enable_webconf'])) $bValidoConfWeb = validarWebConf($smarty);
    $bValidoForm = $oForm->validateForm($_POST);

    if(!$bValidoForm || !$bandera || !$bValidoConfWeb) {
        // Falla la validación básica del formulario
        if (!$bValidoForm || !$bandera) {
            $smarty->assign("mb_title", _tr("Validation Error"));
            $arrErrores = $oForm->arrErroresValidacion;
            $strErrorMsg = "<b>"._tr('The following fields contain errors').":</b><br/>";
            if(is_array($arrErrores) && count($arrErrores) > 0){
                foreach($arrErrores as $k=>$v) {
                    $strErrorMsg .= "$k : {$v['mensaje']}";
                }
            }
            if(!$bandera)
                $strErrorMsg .= _tr('Moderator and user PINs must not be equal');
            $smarty->assign("mb_message", $strErrorMsg);
        } else {
            // Variables ya están asignadas en validarWebConf
        }
        $contenidoModulo = new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk);
        return $contenidoModulo;
    }

    $pConference = new paloSantoConference($pDB);
    $id_cbmysql_conference = $pConference->CreateConference($_POST['conference_name'],
        $_POST['conference_number'], $_POST['start_time'],
        ($_POST['duration'] * 3600) + ($_POST['duration_min'] * 60),
        $_POST['max_participants'],
        array(
            'confOwner'         =>  empty($_POST['conference_owner']) ? '' : $_POST['conference_owner'],
            'silPass'           =>  empty($_POST['moderator_pin']) ? '' : $_POST['moderator_pin'],
            'moderatorAnnounce' =>  ($_POST['moderator_options_1'] == 'on'),
            'moderatorRecord'   =>  ($_POST['moderator_options_2'] == 'on'),
            'roomPass'          =>  empty($_POST['user_pin']) ? '' : $_POST['user_pin'],
            'userAnnounce'      =>  ($_POST['user_options_1'] == 'on'),
            'userListenOnly'    =>  ($_POST['user_options_2'] == 'on'),
            'userWaitLeader'    =>  ($_POST['user_options_3'] == 'on'),
        ));
    if (is_null($id_cbmysql_conference)) {
        $smarty->assign("mb_message", _tr('Unable to create conference').': '.$pConference->errMsg);
        $contenidoModulo = new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk);
        return $contenidoModulo;
    }
    if (!($bSoporteWebConf && isset($_POST['enable_webconf']))) {
        header("Location: ?menu=$module_name");
    } else {
        $_POST['id_cbmysql_conference'] = $id_cbmysql_conference;
        $result = ejecutarCreacionWebConf($smarty);
        if ($result[0]) {
            return $result[1];
        } else {
            return new_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk);
        }
    }
}

function delete_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $bSoporteWebConf = (file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php'));
    $pWebConf = NULL;
    if ($bSoporteWebConf) {
        $pWebConf = embedded_prepareWebConfCreator();
    }

    $pConference = new paloSantoConference($pDB);

    foreach($_POST as $key => $values){
        if(substr($key,0,11) == "conference_")
        {
            $tmpBookID = substr($key, 11);

            if (!is_null($pWebConf)) {
                $idWebConf = $pWebConf->getConfIDFromMeetmeID($tmpBookID);
                if (!is_null($idWebConf)) {
                    $pWebConf->deleteConference($idWebConf);
                }
            }
            $result = $pConference->DeleteConference($tmpBookID);
        }
    }
    $content = report_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk);

    return $content;
}

function show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);
    $room = getParameter("roomNo");
    $arrCallers = $pConference->ObtainCallers($dsn_agi_manager, $room);
    $arrDevices = $pConference->getDeviceFreePBX($dsnAsterisk);
    $arrData = array();

    if(is_array($arrCallers) && count($arrCallers)>0){
        foreach($arrCallers as $key => $caller){
            $arrTmp[0] = $caller['userId'];
            $arrTmp[1] = isset($arrDevices[$caller['callerId']])?$arrDevices[$caller['callerId']]:$caller['callerId'];
            $arrTmp[2] = $caller['duration'];
            $mode = strstr($caller['mode'], "Muted");
            if(!$mode)
            {
                $arrTmp[3] = _tr("UnMuted");
                $checked = 'off';
            }
            else{
                $arrTmp[3] = _tr("Muted");
                $checked = 'on';
            }
            $arrTmp[4] = checkbox("mute_".$caller['userId'], $checked);
            $arrTmp[5] = checkbox("kick_".$caller['userId']);
            $arrData[] = $arrTmp;
        }
    }
    $total = count($arrCallers);
    //Paginacion
    $limit  = 10;
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setLimit($limit);
    $oGrid->setTotal($total);
    $offset = $oGrid->calculateOffset();

    $inicio = ($total == 0) ? 0 : $offset + 1;
    $fin = ($offset+$limit) <= $total ? $offset+$limit : $total;
    $leng = $fin - $inicio;

    $arrDatosGrid = array_slice($arrData, $inicio-1, $leng+1);

    $url = array(
        'menu'      =>  $module_name,
        'accion'    =>  'show_callers',
        'roomNo'    =>  $_GET['roomNo'],
    );

    $arrGrid = array("title"    => _tr("Conference"),
                        "icon"     => "/modules/$module_name/images/pbx_conference.png",
                        "url"      => $url,
                        "width"    => "99%",
                        "start"    => $inicio,
                        "end"      => $fin,
                        "total"    => $total,
                        "columns"  => array(0 => array("name"      => _tr("Id"),
                                                    "property1" => ""),
                                            1 => array("name"      => _tr("CallerId"),
                                                    "property1" => ""),
                                            2 => array("name"      => _tr("Duration"),
                                                    "property1" => ""),
                                            3 => array("name"      => _tr("Status"),
                                                    "property1" => ""),
                                            4 => array("name"      => "<input type='submit' name='callers_mute' value='"._tr("Mute")."' class='button' onclick=\" return confirmSubmit('"._tr("Are you sure you wish to Mute caller (s)?")."');\" />",
                                                    "property1" => ""),
                                            5 => array("name"      => "<input type='submit' name='callers_kick' value='"._tr("Kick")."' class='button' onclick=\" return confirmSubmit('"._tr("Are you sure you wish to Kick caller (s)?")."');\" />",
                                                    "property1" => "")
                                        )
                    );

    $smarty->assign("INVITE_CALLER", _tr("Invite Caller"));
    $smarty->assign("KICK_ALL", _tr("Kick All"));
    $smarty->assign("UPDATE", _tr("Update"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("accion", "show_callers");

    $arrFormElements = array(
                            "device"  => array(     "LABEL"                  => "DEVICE",
                                                    "REQUIRED"               => "no",
                                                    "INPUT_TYPE"             => "SELECT",
                                                    "INPUT_EXTRA_PARAM"      => $arrDevices,
                                                    "VALIDATION_TYPE"        => "numeric",
                                                    "VALIDATION_EXTRA_PARAM" => "")
                                );

    $oFilterForm = new paloForm($smarty, $arrFormElements);
    $_POST['device']="unselected";
    $htmlFilter = $oFilterForm->fetchForm("$local_templates_dir/conference.tpl", "", $_POST);
    $oGrid->showFilter(trim($htmlFilter),true);

    $contenidoModulo = $oGrid->fetchGrid($arrGrid, $arrDatosGrid);
    return $contenidoModulo;
}

function callers_mute($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParameter('roomNo');
    foreach($_POST as $key => $values)
    {
        if(substr($key,0,5) == "mute_")
        {
            $tmpCallerId = substr($key, 5);
            $arrCallers = $pConference->MuteCaller($dsn_agi_manager, $room, $tmpCallerId, $_POST["$key"]);
        }
    }

    header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
}

function callers_kick($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParameter('roomNo');
    foreach($_POST as $key => $values)
    {
        if(substr($key,0,5) == "kick_")
        {
            $tmpCallerId = substr($key, 5);
            if($_POST["$key"] == "on")
                $arrCallers = $pConference->KickCaller($dsn_agi_manager, $room, $tmpCallerId);
        }
    }
    sleep(3);
    header("location: ?menu=$module_name&accion=show_callers&roomNo=$room");
}

function callers_kick_all($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParameter('roomNo');

    $pConference->KickAllCallers($dsn_agi_manager, $room);

    sleep(3);
    header("location: ?menu=$module_name");
}

function caller_invite($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager, $dsnAsterisk)
{
    $pConference = new paloSantoConference($pDB);

    $room = getParameter('roomNo');

    $device = getParameter("device");

    if($device != null)
    {
        if(preg_match('/^[0-9]+$/i', $device))
        {
            $callerId = _tr('Conference'). "<$room>";
            $result = $pConference->InviteCaller($dsn_agi_manager, $room, $device, $callerId);
            if(!$result)
            {
                $smarty->assign("mb_title", _tr('ERROR').":");
                $smarty->assign("mb_message", _tr("The device couldn't be added to the conference"));
            }else sleep(3);
        }else{
            $smarty->assign("mb_title", _tr('ERROR').":");
            $smarty->assign("mb_message", _tr("The device must be numeric"));
        }
    }
    else{
        $smarty->assign("mb_title", _tr('ERROR').":");
        $smarty->assign("mb_message", _tr("The device wasn't write"));
    }

    return show_callers($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk);
}

function view_conference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk)
{
    $arrFormConference = createFieldForm();
    $oForm = new paloForm($smarty,$arrFormConference);

    $smarty->assign("Show", 0);
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("CANCEL", _tr("Cancel"));
    $smarty->assign("announce", _tr("Announce"));
    $smarty->assign("record", _tr("Record"));
    $smarty->assign("listen_only", _tr("Listen Only"));
    $smarty->assign("wait_for_leader", _tr("Wait for Leader"));

    $pConference = new paloSantoConference($pDB);
    $conferenceId = isset($_GET['conferenceId'])?$_GET['conferenceId']:"";

    $conferenceData = $pConference->ObtainConferenceData($conferenceId);

    $arrData['conference_number'] = $conferenceData['roomNo'];
    $arrData['conference_owner'] = $conferenceData['confOwner'];
    $arrData['conference_name'] = $conferenceData['confDesc'];
    $arrData['moderator_pin'] = $conferenceData['silPass'];
    $arrData['user_pin'] = $conferenceData['roomPass'];
    $arrData['start_time'] = $conferenceData['startTime'];
    $arrData['max_participants'] = $conferenceData['maxUser'];
    if(strpos($conferenceData['aFlags'], 'i', 4))
        $arrData['moderator_options_1'] = 'on';
    if(strpos($conferenceData['aFlags'], 'r', 4))
        $arrData['moderator_options_2'] = 'on';

    if(strpos($conferenceData['uFlags'], 'i', 1))
        $arrData['user_options_1'] = 'on';
    if(strpos($conferenceData['uFlags'], 'm', 1))
        $arrData['user_options_2'] = 'on';
    if(strpos($conferenceData['uFlags'], 'w', 1))
        $arrData['user_options_3'] = 'on';

    $fecha_ini = strtotime($conferenceData['startTime']);
    $fecha_fin = strtotime($conferenceData['endTime']);
    $duracion = $fecha_fin - $fecha_ini;

    $arrData['duration'] = number_format($duracion/3600, 0, ",", "");
    $arrData['duration_min'] = ($duracion%3600)/60;

    $oForm->setViewMode();
    $htmlForm = $oForm->fetchForm("$local_templates_dir/new_conference.tpl", _tr("Conference"), $arrData);

    $contenidoModulo = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $contenidoModulo;
}

/******* Funciones creadas para ayudar a integración de conferencias web ********/

function embedded_prepareWebConfCreator()
{
    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";
    include_once "modules/$module_plugin/libs/paloSantoCreateConference.class.php";

    global $arrConf;
    global $arrConfModule;

    load_language_module($module_plugin);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    return new paloSantoCreateConference($pDB);
}

function embedded_viewFormCreateConference($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsnAsterisk)
{
    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";

    global $arrConf;
    global $arrConfModule;

    load_language_module($module_plugin);

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_plugin/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    // elastix-conferenceroom-2.2.0-5 requiere de $arrLang
    global $arrLang;
    return viewFormCreateConference($smarty, $module_plugin, $local_templates_dir, $pDB, $arrConf, $arrLang, TRUE);
}

function embedded_prepareWebConfLister()
{
    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";
    include_once "modules/$module_plugin/libs/paloSantoListConference.class.php";

    global $arrConf;
    global $arrConfModule;

    load_language_module($module_plugin);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);
    return new paloSantoListConference($pDB);
}

function embedded_webConf_mostrarListaInvitados($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk)
{
    if (!file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php')) {
        Header('Location: ?menu='.$module_name);
        return '';
    }

    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";

    global $arrConf;
    global $arrConfModule;

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_plugin);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_plugin/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    // elastix-conferenceroom-2.2.0-5 requiere de $arrLang
    global $arrLang;
    return mostrarListaInvitados($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
}

function embedded_webConf_mostrarChatLog($smarty, $module_name, $local_templates_dir, $pDB, $arrConfig, $dsn_agi_manager,$dsnAsterisk)
{
    if (!file_exists('modules/conferenceroom_list/libs/conferenceActions.lib.php')) {
        Header('Location: ?menu='.$module_name);
        return '';
    }

    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";

    global $arrConf;
    global $arrConfModule;

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_plugin);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_plugin/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    // elastix-conferenceroom-2.2.0-5 requiere de $arrLang
    global $arrLang;
    return mostrarChatLog($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $arrLang);
}

// Esto sólo se ejecuta si se tiene soporte de conferencia web
function validarWebConf($smarty)
{
    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";

    load_language_module($module_plugin);

    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    $listaTraduccion = array(
        array('conference_name', 'room_name'),
        array('duration', 'duration_hours'),
    );
    foreach ($listaTraduccion as $tuplaTraduccion) {
        if (isset($_POST[$tuplaTraduccion[0]])) $_POST[$tuplaTraduccion[1]] = $_POST[$tuplaTraduccion[0]];
    }

    // elastix-conferenceroom-2.2.0-5 requiere de $arrLang
    global $arrLang;
    return validate_saveNewCreateConference($smarty, $arrLang);
}

// Esto sólo se ejecuta si se tiene soporte de conferencia web
function ejecutarCreacionWebConf($smarty)
{
    $module_plugin = 'conferenceroom_list';
    include_once "modules/$module_plugin/configs/default.conf.php";
    include_once "modules/$module_plugin/libs/conferenceActions.lib.php";

    global $arrConfModule;
    global $arrConf;

    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($module_plugin);

    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_plugin/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    $listaTraduccion = array(
        array('conference_name', 'room_name'),
        array('duration', 'duration_hours'),
    );
    foreach ($listaTraduccion as $tuplaTraduccion) {
        if (isset($_POST[$tuplaTraduccion[0]])) $_POST[$tuplaTraduccion[1]] = $_POST[$tuplaTraduccion[0]];
    }

    // elastix-conferenceroom-2.2.0-5 requiere de $arrLang
    global $arrLang;
    return execute_saveNewCreateConference($smarty, $module_plugin, $local_templates_dir, $pDB, $arrConf, $arrLang, TRUE);
}
?>