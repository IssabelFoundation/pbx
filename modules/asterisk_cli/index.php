<?php
require_once "libs/paloSantoForm.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    require_once "modules/$module_name/configs/default.conf.php";

	load_language_module($module_name);

    //global variables
    global $arrConf;
    global $arrConfModule;
    $arrConf = array_merge($arrConf,$arrConfModule);

    //folder path for custom templates
    $base_dir = dirname($_SERVER['SCRIPT_FILENAME']);
    $templates_dir = (isset($arrConf['templates_dir'])) ? $arrConf['templates_dir'] : 'themes';
    $local_templates_dir = "$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    $txtCommand = isset($_POST['txtCommand'])? trim($_POST['txtCommand']) : '';
    $oForm = new paloForm($smarty, array());
    $smarty->assign(array(
        'asterisk'  =>  _tr('Asterisk CLI'),
        'command'   =>  _tr('Command'),
        'txtCommand'=>  htmlspecialchars($txtCommand),
        'execute'   =>  _tr('Execute'),
        'icon'      =>  "modules/$module_name/images/pbx_tools_asterisk_cli.png",
    ));

    $result = "";
    if (!empty($txtCommand)) {
    	$output = $retval = NULL;
        exec("/usr/sbin/asterisk -rnx ".escapeshellarg($txtCommand), $output, $retval);
        $result = implode("\n", array_map('htmlspecialchars', $output));
    }
    if ($result == "") $result = "&nbsp;";
    $smarty->assign("RESPUESTA_SHELL", $result);

    return $oForm->fetchForm("$local_templates_dir/new.tpl", _tr('Asterisk-Cli'), $_POST);
}
?>