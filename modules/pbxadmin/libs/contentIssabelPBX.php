<?php
require_once "libs/paloSantoACL.class.php";

function getContent(&$smarty, $iss_module_name, $withList)
{
    global $fc_save;
    global $arrConf;
    global $arrLang;
    global $tabindex;

    // for page.modules.php (module admin)
    global $display;
    global $type;
    global $online;

    require_once "libs/misc.lib.php";
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);

    load_language_module($iss_module_name);

    $dieafterquiet=false;

    $api_modules=array();

    $it = new RecursiveDirectoryIterator("admin/modules/");
    foreach(new RecursiveIteratorIterator($it) as $file) {
        if(preg_match("/guimodule\.php$/ui",$file)) {
            include_once $file;
        }
    }

    foreach($api_modules as $key=>$endpoint) {
        if(!file_exists('/var/www/html/newgui/'.$endpoint.'.html')) {
           unset($api_modules[$key]);
        }
    }

    $arrLangIssabelPBX = array("en" => "en_US", "bg" => "bg_BG", "cn" => "zh_CN", "de" => "de_DE", "es" => "es_ES",
                            "fr" => "fr_FR", "he" => "he_IL", "hu" => "hu_HU", "it" => "it_IT",
                            "pt" => "pt_PT", "ru" => "ru_RU", "sv" => "sv_SE", "br" => "pt_BR");

    $langIssabelPBX = isset($arrLangIssabelPBX[$lang])?$arrLangIssabelPBX[$lang]:"en_US";
    setcookie("lang",$langIssabelPBX);
    $local_templates_dir = "$base_dir/modules/$iss_module_name/themes/default";

    //set variables
    $vars = array(
        'action'             => null,
        'confirm_email'      => '',
        'confirm_password'   => '',
        'display'            => '',
        'extdisplay'         => null,
        'email_address'      => '',
        'fw_popover'         => '',
        'fw_popover_process' => '',
        'logout'             => false,
        'password'           => '',
        'quietmode'          => '',
        'restrictmods'       => false,
        'skip'               => 0,
        'skip_astman'        => false,
        'type'               => '',
        'username'           => '',
    );

    if (!isset($_REQUEST['display'])) {
        $_REQUEST['display'] = 'extensions';
        $_REQUEST['type']    = 'setup';
        $_GET['display']     = 'extensions';
        $_GET['type']        = 'setup';
    }

    foreach ($vars as $k => $v) {
        // were use config_vars instead of, say, vars, so as not to polute
        // page.<some_module>.php (which usually uses $var or $vars)
        $config_vars[$k] = $$k = isset($_REQUEST[$k]) ? $_REQUEST[$k] : $v;

        //special handeling
        switch ($k) {
                case 'extdisplay':
                    $extdisplay = (isset($extdisplay) && $extdisplay !== false)
                    ? htmlspecialchars($extdisplay, ENT_QUOTES)
                    : false;
                    $_REQUEST['extdisplay'] = $extdisplay;
                    break;

                case 'restrictmods':
                    $restrict_mods = $restrictmods
                    ? array_flip(explode('/', $restrictmods))
                    : false;
                    break;

                case 'skip_astman':
                    $bootstrap_settings['skip_astman']    = $skip_astman;
                    break;
        }
    }

    header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
    header('Cache-Control: post-check=0, pre-check=0',false);
    header('Pragma: no-cache');
    header('Content-Type: text/html; charset=utf-8');

    global $amp_conf;
    global $db;
    global $no_auth;
    global $currentcomponent;
    global $CC;
    global $dirname;
    global $bootstrap_settings;
    global $astman;
    global $extmap;
    global $module_name;
    global $module_page;
    global $reload_needed;
    global $active_modules;
    global $remove_rnav;
    global $js_content;
    global $use_popover_css;
    global $popover_mode;
    global $quietmode;
    global $recordings_save_path;
    global $path_to_dir;
    global $itemid; // required by IssabelPBX 2.11.x daynight module
    $return_HTML = "";

    // This needs to be included BEFORE the session_start or we fail so
    // we can't do it in bootstrap and thus we have to depend on the
    // __FILE__ path here.

    if (isset($_REQUEST['handler'])) {
        $restrict_mods = true;
        // I think reload is the only handler that requires astman, so skip it
        //for others
        switch ($_REQUEST['handler']) {
            case 'api':
                $restrict_mods = false;
                break;
            case 'reload';
                break;
            default:
                $bootstrap_settings['skip_astman'] = true;
                break;
        }
    }

    $bootstrap_settings['issabelpbx_auth'] = false;

    if(is_file('/etc/issabelpbx.conf')) {
        if (!@include_once(getenv('ISSABELPBX_CONF') ? getenv('ISSABELPBX_CONF') : '/etc/issabelpbx.conf')) {
            include_once('/etc/asterisk/issabelpbx.conf');
        }
    } else {
        if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
            include_once('/etc/asterisk/freepbx.conf');
        }
    }

    $username = "admin";
    $_SESSION['AMP_user'] = new ampuser($username);
    set_language();

    /* If there is an action request then some sort of update is usually being done.
    This may protect from cross site request forgeries unless disabled.
    */
    if (!isset($no_auth) && $action != '' && $amp_conf['CHECKREFERER']) {
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer   = parse_url($_SERVER['HTTP_REFERER']);
            $refererok = (trim($referer['host']) == trim($_SERVER['SERVER_NAME'])) ? true : false;
        } else {
            $refererok = false;
        }
        if (!$refererok) {
            $display = 'badrefer';
        }
    }
    if (isset($no_auth) && empty($display)) {
        $display = 'noauth';
    }
    // handle special requests
    if (!in_array($display, array('noauth', 'badrefer'))
            && isset($_REQUEST['handler'])
    ) {
        $module = isset($_REQUEST['module'])    ? $_REQUEST['module']    : '';
        $file   = isset($_REQUEST['file'])      ? $_REQUEST['file']      : '';
        fileRequestHandler($_REQUEST['handler'], $module, $file);
        exit();
    }

    if (!$quietmode) {
        module_run_notification_checks();
    }

    $allmodules = module_getinfo(false,false,true); // refresh module list to get updated embedcategory and show new installed modules automatically 

    //draw up menu
    $ipbx_menu = array();

    // pointer to current item in $ipbx_menu, if applicable
    $cur_menuitem = null;

    //  var_dump($_SESSION);
    // add module sections to $ipbx_menu
    if(is_array($active_modules)){
        foreach($active_modules as $key => $module) {

            //create an array of module sections to display
            // stored as [items][$type][$category][$name] = $displayvalue
            if (isset($module['items']) && is_array($module['items'])) {
                // loop through the types
                foreach($module['items'] as $itemKey => $item) {

                    // check access, unless module.xml defines all have access
                    //TODO: move this to bootstrap and make it work
                    //module is restricted to admin with excplicit permission
                    $needs_perms = !isset($item['access'])
                        || strtolower($item['access']) != 'all'
                        ? true : false;

                    //check if were logged in
                    $admin_auth = isset($_SESSION["AMP_user"])
                        && is_object($_SESSION["AMP_user"]);

                    //per admin access rules
                    $has_perms = $admin_auth
                        && $_SESSION["AMP_user"]->checkSection($itemKey);

                    //requies authentication
                    $needs_auth = isset($item['requires_auth'])
                        && strtolower($item['requires_auth']) == 'false'
                        ? false
                        : true;

                    //skip this module if we dont have proper access
                    //test: if we require authentication for this module
                    //            and either the user isnt authenticated
                    //            or the user is authenticated and dose require
                    //                section specifc permissions but doesnt have them
                    if ($needs_auth
                            && (!$admin_auth || ($needs_perms && !$has_perms))
                       ) {
                        //clear display if they were trying to gain unautherized
                        //access to $itemKey. If there logged in, but dont have
                        //permissions to view this specicc page - show them a message
                        //otherwise, show them the login page
                        if($display == $itemKey){
                            if ($admin_auth) {
                                $display = 'noaccess';
                            } else {
                                $display = 'noauth';
                            }
                        }
                        continue;
                    }

                    if (!isset($item['display'])) {
                        $item['display'] = $itemKey;
                    }

                    // reference to the actual module
                    $item['module'] =& $active_modules[$key];

                    // item is an assoc array, with at least
                    //array(module=> name=>, category=>, type=>, display=>)
                    $ipbx_menu[$itemKey] = $item;

                    // allow a module to replace our main index page
                    if (($item['display'] == 'index') && ($display == '')) {
                        $display = 'index';
                    }

                    // check current item
                    if ($display == $item['display']) {
                        // found current menuitem, make a reference to it
                        $cur_menuitem =& $ipbx_menu[$itemKey];
                    }
                }
            }
        }
    }

    if ($cur_menuitem === null && !in_array($display, array('noauth', 'badrefer','noaccess',''))) {
        $display = 'noaccess';
    }

    // new gui hooks
    if(!$quietmode && is_array($active_modules)){
            foreach($active_modules as $key => $module) {
                    modgettext::push_textdomain($module['rawname']);
                    if (isset($module['items']) && is_array($module['items'])) {
                            foreach($module['items'] as $itemKey => $itemName) {
                                    //list of potential _configpageinit functions
                                    $initfuncname = $key . '_' . $itemKey . '_configpageinit';
                                    if ( function_exists($initfuncname) ) {
                                            $configpageinits[] = $initfuncname;
                                    }
                            }
                    }
                    //check for module level (rather than item as above) _configpageinit function
                    $initfuncname = $key . '_configpageinit';
                    if ( function_exists($initfuncname) ) {
                            $configpageinits[] = $initfuncname;
                    }
                    modgettext::pop_textdomain();
            }
    }

    // extensions vs device/users ... this is a bad design, but hey, it works
    if (!$quietmode && isset($ipbx_menu["extensions"])) {
            if (isset($amp_conf["AMPEXTENSIONS"])
                    && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
                    unset($ipbx_menu["extensions"]);
            } else {
                    unset($ipbx_menu["devices"]);
                    unset($ipbx_menu["users"]);
            }
    }

    ob_start();
    // load the component from the loaded modules
    if (!in_array($display, array('', 'badrefer'))
            && isset($configpageinits) && is_array($configpageinits)
    ) {

            $CC = $currentcomponent = new component($display,$type);
            // call every modules _configpageinit function which should just
            // register the gui and process functions for each module, if relevant
            // for this $display
            foreach ($configpageinits as $func) {
                    $func($display);
            }

            // now run each 'process' function and 'gui' function
            $currentcomponent->processconfigpage();
            $currentcomponent->buildconfigpage();
    }
    $module_name = "";
    $module_page = "";
    $module_file = "";

    // hack to have our default display handler show the "welcome" view
    // Note: this probably isn't REALLY needed if there is no menu item for "Welcome"..
    // but it doesn't really hurt, and it provides a handler in case some page links
    // to "?display=index"
    //TODO: acount for bad refer
    if ($display == 'index' && ($cur_menuitem['module']['rawname'] == 'builtin')) {
            $display = '';
    }

    // show the appropriate page
    switch($display) {
            case 'modules':
                    // set these to avoid undefined variable warnings later
                    //
                    $module_name = 'modules';
                    $module_page = $cur_menuitem['display'];

                    $possibilites = array(
                            'userdisplay',
                            'extdisplay',
                            'id',
                            'itemid',
                            'selection'
                    );
                    $itemid = '';
                    foreach($possibilites as $possibility) {
                        if (isset($_REQUEST[$possibility]) && $_REQUEST[$possibility] != '' ) {
                            $itemid = htmlspecialchars($_REQUEST[$possibility], ENT_QUOTES);
                            $_REQUEST[$possibility] = $itemid;
                        }
                    }
                    if($itemid=='process') { $quietmode=true; $dieafterquiet=true;}
                    $display='modules'; 
                    $type='setup';
                    include $dirname.'/page.modules.php';
                    break;
            case 'noaccess':
                    show_view($amp_conf['VIEW_NOACCESS'], array('amp_conf' => &$amp_conf));
                    break;
            case 'noauth':
                    $config_vars['obe_error_msg'] = array();
                    if ($config_vars['action'] == 'setup_admin'){
                            $config_vars['obe_error_msg'] = framework_obe_intialize_validate(
                                    $config_vars['username'],
                                    $config_vars['password'],
                                    $config_vars['confirm_password'],
                                    $config_vars['email_address'],
                                    $config_vars['confirm_email']);
                    }
                    //if we have no admin users AND were trying to set one up
                    if (!count(getAmpAdminUsers())
                            && $action == 'setup_admin'
                            && !$config_vars['obe_error_msg']
                    ) {
                            //validate the inputs
                            framework_obe_intialize_admin(
                                    $config_vars['username'],
                                    $config_vars['password'],
                                    $config_vars['confirm_password'],
                                    $config_vars['email_address'],
                                    $config_vars['confirm_email']
                            );
                    }

                    //if we (still) have no admin users
                    if (!count(getAmpAdminUsers())) {
                            $login = $config_vars;
                            $login['amp_conf'] = $amp_conf;
                            $login['errors'] = $config_vars['obe_error_msg'];
                            $return_HTML .= load_view($amp_conf['VIEW_OBE'], $login);
                            unset($_SESSION['AMP_user']);
                    }

                    //prompt for a password if we have users
                    if (count(getAmpAdminUsers())) {
                            //error message
                            $login['errors'] = array();
                            if ($config_vars['username'] && $action !== 'setup_admin') {
                                    $login['errors'][] = _('Invalid Username or Password');
                            }

                            //show fop option if enabled, probobly doesnt belong on the
                            //login page
                            $login['panel'] = false;
                            if (!empty($amp_conf['FOPWEBROOT'])
                                    && is_dir($amp_conf['FOPWEBROOT'])
                            ){
                                    $login['panel'] = str_replace($amp_conf['AMPWEBROOT'] .'/admin/',
                                                    '', $amp_conf['FOPWEBROOT']);
                            }


                            $login['amp_conf'] = $amp_conf;
                            $return_HTML .= load_view($amp_conf['VIEW_LOGIN'], $login);
                    }
                    break;
            case 'badrefer':
                    $return_HTML .= load_view($amp_conf['VIEW_BAD_REFFERER'], $amp_conf);
                    break;
            case '':
                    if ($astman) {
                            show_view($amp_conf['VIEW_WELCOME'], array('AMP_CONF' => &$amp_conf));
                    } else {
                            // no manager, no connection to asterisk
                            show_view($amp_conf['VIEW_WELCOME_NOMANAGER'],
                                    array('mgruser' => $amp_conf["AMPMGRUSER"]));
                    }
                    break;
            default:
                    //display the appropriate module page
                    $module_name = $cur_menuitem['module']['rawname'];
                    $module_page = $cur_menuitem['display'];
                    $module_file = 'modules/'.$module_name.'/page.'.$module_page.'.php';

                    //TODO Determine which item is this module displaying.
                    //Currently this is over the place, we should standardize on a
                    //"itemid" request var for now, we'll just cover all possibilities :-(
                    $possibilites = array(
                            'userdisplay',
                            'extdisplay',
                            'id',
                            'itemid',
                            'selection'
                    );
                    $itemid = '';
                    foreach($possibilites as $possibility) {
                            if (isset($_REQUEST[$possibility]) && $_REQUEST[$possibility] != '' ) {
                                    $itemid = htmlspecialchars($_REQUEST[$possibility], ENT_QUOTES);
                                    $_REQUEST[$possibility] = $itemid;
                            }
                    }

                    // create a module_hook object for this module's page
                    $module_hook = new moduleHook;

                    // populate object variables
                    $module_hook->install_hooks($module_page,$module_name,$itemid);

                    // let hooking modules process the $_REQUEST
                    $module_hook->process_hooks($itemid,
                            $module_name,
                            $module_page,
                            $_REQUEST);


                    // include the module page
                    if (isset($cur_menuitem['disabled']) && $cur_menuitem['disabled']) {
                            show_view($amp_conf['VIEW_MENUITEM_DISABLED'], $cur_menuitem);
                            break; // we break here to avoid the generateconfigpage() below
                    } else if (file_exists($dirname."/".$module_file)) {
                            // load language info if available
                            modgettext::textdomain($module_name);
                            if(array_key_exists($module_file,$api_modules)) {
                                $endpoint = $api_modules[$module_file];
                                renderPpbxAPIFrame($endpoint);
                            } else {
                                include($dirname."/".$module_file);
                            }
                    } else {
                            $return_HTML .= "404 Not found (" . $module_file  . ')';
                    }

                    // global component
                    if ( isset($currentcomponent) ) {
                        $endpoint = $api_modules[$module_file];
                        if(file_exists("/var/www/html/pbxapi/controllers/".$endpoint.".php")) {
                            //$return_CONFIG_HTML = "NEW GUI ".$currentcomponent->_compname;
                        } else {
                            $return_CONFIG_HTML =  $currentcomponent->generateconfigpage();
                        }
                    }

                    break;
    }

    /* Lo siguiente es un SOBERANO HACKEO.
     *
     * El módulo ivr de IssabelPBX tiene un HTML inválido que mete un <table> dentro
     * de otro <table> lo cual es tolerado por el navegador en la interfaz no
     * embebida pero rompe el HTML dentro de Issabel. Aquí se recoge el HTML
     * inválido y se lo embebe dentro de un <tr><td>. */
    if (isset($_REQUEST['display']) && $_REQUEST['display'] == 'ivr') {
        $return_CONFIG_HTML = preg_replace(
            '|</tr>\s*(<table .*?</table><img .* id="add_entrie">)\s*<tr>|s',
            '</tr><tr><td colspan="2">$1</td></tr><tr>',
            $return_CONFIG_HTML);
    }

    if ($quietmode) {
        // send the output buffer, should be sending just the page contents
        ob_end_flush();
        if($dieafterquiet==true) { die(); }
    } elseif ($fw_popover || $fw_popover_process) {
        $admin_template = $template = array();
        //get the page contents from the buffer
        $content = ob_get_contents();
        ob_end_clean();
        $return_HTML = '';

        $use_popover_css = ($fw_popover || $fw_popover_process);
        $return_HTML .= load_view("$local_templates_dir/header.php", null);

        //if we have a module loaded, load its css
        if (isset($module_name)) {
            $return_HTML .= framework_include_css_issabelpbx();
        }

        // If processing posback (fw_popover_process) and there are errors then we
        // display again, otherwise we ignore the $content and prepare to process
        //
        $show_normal = $fw_popover_process ? fwmsg::errors() : true;
        if ($show_normal) {
            // provide beta status
            if (isset($ipbx_menu[$display]['beta']) && strtolower($ipbx_menu[$display]['beta']) == 'yes') {
                $return_HTML .= load_view($amp_conf['VIEW_BETA_NOTICE']);
            }
            $return_HTML .= $content.$return_CONFIG_HTML;
            $popover_mode = 'display';
        } else {
            $popover_mode = 'process';
        }

        $js_content     = load_view("$local_templates_dir/popover_js.php", null);
        $extmap            = framework_get_extmap(true);
        $reload_needed  = false;
        $remove_rnav    = true;
        $return_HTML   .= load_view("$local_templates_dir/footer.php", null);
        return $return_HTML;

    } else {
        $_SESSION['module_name'] = $module_name;
        $_SESSION['module_page'] = $module_page;

        $page_content = ob_get_contents();
        ob_end_clean();

        //if we have a module loaded, load its css
        if (isset($module_name)) {
            $return_HTML .= framework_include_css_issabelpbx();
        }

        // send menu
        $apply = isset($arrLang['Apply changes'])?$arrLang['Apply changes']:'Apply changes';
        $vars = array("applyconfig"=>$apply);
        $return_HTML .= load_view("$local_templates_dir/menu.php", $vars);

        //send actual page content
        $return_HTML .= $page_content.$return_CONFIG_HTML;

        //send footer
        $extmap         = framework_get_extmap(true);
        $reload_needed  = check_reload_needed();
        $return_HTML   .= load_view("$local_templates_dir/footer.php", null);

        if($withList){

            if(is_dir("/var/www/html/admin/modules/motif")) {
                $smarty->assign("has_google_voice",1);
            } else {
                $smarty->assign("has_google_voice",0);
            }

            $smarty->assign("isissabelpx", 0);
            if(is_file("/etc/issabelpbx.conf")) {
                $smarty->assign("isissabelpbx", 1);
            }

            $smarty->assign("Option", _tr('Option'));
            $smarty->assign("Unembedded_IssabelPBX", _tr('Unembedded IssabelPBX'));

            $smarty->assign("INFO", _tr("Warning: Updating IssabelPBX through its web interface will cause it to install versions that may have not yet been properly integrated with Issabel. To avoid conflicts, it is always recommended to search/install updates only through the linux command \"yum update issabelPBX\"."));

            $dsnAsterisk = generarDSNSistema('asteriskuser', 'asterisk');
            $pDB = new paloDB($dsnAsterisk);

            // run wizard if no extensions are found
            if($module_page=='extensions' && !isset($_REQUEST['Submit'])) {

                $query = "SELECT count(*) FROM `users`";
                $row = $pDB->getFirstRowQuery($query, false, array());
                if($row[0]<1) {
                    if(is_file("/var/www/html/wizard/index.php")) {
                        $return_HTML.="<script>";
                        $return_HTML.="\$(document).ready( function() { \n";
                        $return_HTML.="ShowModalPopUP('"._tr('Initial Configuration Wizard')."','900',800,'<iframe src=\"/wizard/index.php\" frameborder=\"0\" style=\"overflow:hidden;height:100%;width:100%\" height=\"100%\" width=\"100%\" ></iframe>');\n";
                        $return_HTML.="$('.neo-modal-issabel-popup-content').css('left','0');$('.neo-modal-issabel-popup-content').css('right','0');$('.neo-modal-issabel-popup-content').css('bottom','0');\n";
                        $return_HTML.="});</script>";
                    }
                }
            }

            $pDBsq = new paloDB($arrConf['issabel_dsn']['acl']);
            if (!empty($pDBsq->errMsg)) {
               return "ERROR DE DB: $pDBsq->errMsg <br>";
            }
            $pACL = new paloACL($pDBsq);
            if (!empty($pACL->errMsg)) {
                return "ERROR DE ACL: $pACL->errMsg <br>";
            }
            $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
            // Default cateogories for IssabelPBX core modules
            $itemcat     = array();
            $categories  = array();
            $categories['Basic']                = array('extensions', 'featurecodeadmin', 'routing', 'trunks');
            $categories['Inbound Call Control'] = array('did', 'dahdichandids');
            $categories['Settings']             = array('advancedsettings');

            foreach($categories as $key=>$val) {
                foreach($val as $opt) {
                    $itemcat[$opt]=$key;
                }
            }
        
            // IssabelPBX modules to exclude from menu 
            $exclude = array('users','devices','ampusers','wiki');

            // helper array for setting menu category order
            $menuorder= array('Basic','Inbound Call Control','Internal Options & Configuration','Remote Access','Advanced');

            $query = "SELECT `data` FROM `module_xml` WHERE `id` = 'mod_serialized'";
            $module_serialized = $pDB->getFirstRowQuery($query, false, array());
            $unserialized = unserialize($module_serialized[0]);

            $allprivs    = array();
            $id_resource = $pACL->getIdResource('pbxadmin');
            $id_group    = $pACL->getIdGroup('administrator');
            if($id_group === false) { $id_group=1; } 
            $privs       = $pACL->getModulePrivileges('pbxadmin');

            foreach($privs as $idx=>$priv) {
                $allprivs[]=$priv['privilege'];
            }

            // if module admin does not eixsts as privilege, insert it and grant administrator access
            if (!in_array('modules',$allprivs)) {
                $pACL->createModulePrivilege($id_resource, 'modules', $name);
                $id_privilege  = $pACL->getIdModulePrivilege($id_resource,'modules');
                $bExito        = $pACL->grantModulePrivilege2Group($id_privilege, $id_group);
            }

            // populate menu from IssabelPBX module tables
            foreach($unserialized as $modulekey=>$moduledata) {
                if(isset($moduledata['embedcategory'])) {
                    foreach($moduledata['menuitems'] as $urlkey=>$name) {
                        if(!in_array($urlkey,$exclude)) {

                            // if module does not eixsts as privilege, insert it and grant administrator access
                            if (!in_array($urlkey,$allprivs)) {
                                $pACL->createModulePrivilege($id_resource, $urlkey, $name);
                                $id_privilege  = $pACL->getIdModulePrivilege($id_resource,$urlkey);
                                $bExito        = $pACL->grantModulePrivilege2Group($id_privilege, $id_group);
                            }

                            if (!$pACL->hasModulePrivilege($user, 'pbxadmin', $urlkey)) {
                                continue;
                            }

                            if(isset($itemcat[$urlkey])) { 
                                $cate=$itemcat[$urlkey]; 
                            } else { 
                                if(preg_match('/settings$/',$urlkey) || preg_match('/admin$/',$urlkey)) {
                                    $cate='Settings';
                                } else {
                                    $cate=$moduledata['embedcategory']; 
                                }
                            }
                            $cate = trim(preg_replace('/\s\s+/', ' ', $cate)); // xml with & char is inserted with new lines between &, remove them
                            if(!in_array($cate,$menuorder)) {
                                $menuorder[]=$cate;
                            }
                            $idx = array_search($cate,$menuorder);
                            if($moduledata['status']==2) {
                                $menu[$idx][$cate][]=array('urlkey'=>$urlkey,'name'=>_tr($name),'category'=>$cate);
                            }
                        }
                    }
                }
            }
            ksort($menu);

            // sort menu items inside each category
            $menu_sorted=array();
            foreach($menu as $idx=>$datita) {
                foreach($datita as $cate=>$items) {
                     usort($items, function($a, $b) {
                    if ($a['name'] == $b['name']) { return 0; }
                        return ($a['name'] < $b['name']) ? -1 : 1;
                    });
                    $menu_sorted[_tr($cate)]=$items;
                }
            }

            // if we do not have custom privilege for module, disable output
            if (!$pACL->hasModulePrivilege($user, 'pbxadmin', $display)) {
                $return_HTML='';
            }

            $smarty->assign('leftmenu',$menu_sorted);

            $smarty->assign("htmlFPBX", $return_HTML);

            return $smarty->fetch("$local_templates_dir/main.tpl");
        }
    }
}

function framework_include_css_issabelpbx() {
    global $active_modules, $module_name, $module_page, $amp_conf;

    $version            = get_framework_version();
    $version_tag        = '?load_version=' . urlencode($version);
    if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
      $this_time_append    = '.' . time();
      $version_tag         .= $this_time_append;
    } else {
        $this_time_append = '';
    }

    $html = '';
    $view_module_version    = isset($active_modules[$module_name]['version'])
                            ? $active_modules[$module_name]['version']
                            : $version_tag;
    $mod_version_tag        = '&load_version=' . urlencode($view_module_version);
    if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
        $mod_version_tag    .= $this_time_append;
    }

    // DEPECRATED but still supported for a while, the assets directory is the new preferred mode
    if (is_file('admin/modules/' . $module_name . '/' . $module_name . '.css')) {
        $html .= '<link href="' . $_SERVER['PHP_SELF']
                . '?handler=file&amp;module=' . $module_name
                . '&amp;file=' . $module_name . '.css' . $mod_version_tag
                . '" rel="stylesheet" type="text/css" />';
    }
    if (isset($module_page)
        && ($module_page != $module_name)
        && is_file('admin/modules/' . $module_name . '/' . $module_page . '.css')
    ) {
            $html .= '<link href="' . $_SERVER['PHP_SELF']
                    . '?handler=file&amp;module=' . $module_name
                    . '&amp;file=' . $module_page . '.css' . $mod_version_tag
                    . '" rel="stylesheet" type="text/css" />';
    }


    // Check assets/css and then assets/css/page_name for any css files which will have been symlinked to
    // assets/module_name/css/*
    $css_dir = 'admin/modules/' . $module_name . '/assets/css';
    if (is_dir($css_dir)) {
        $d = opendir($css_dir);
        $file_list = array();
        while ($file = readdir($d)) {
            $file_list[] = $file;
        }
        sort($file_list);
        foreach ($file_list as $file) {
            if (substr($file,-4) == '.css' && is_file($css_dir . '/' . $file)) {
              $html .= '<link href="admin/assets/' . $module_name . '/css/' . $file
                        . '" rel="stylesheet" type="text/css" />';
            }
        }
        unset($file_list);
        $css_subdir = $css_dir . '/' . $module_page;
        if ($module_page != '' && is_dir($css_subdir)) {
            $sd = opendir($css_subdir);

            $file_list = array();
            while ($p_file = readdir($sd)) {
                $file_list[] = $p_file;
            }
            sort($file_list);
            foreach ($file_list as $p_file) {
                if (substr($p_file,-4) == '.css' && is_file($css_subdir . '/' . $p_file)) {
                $html .= '<link href="admin/assets/$module_name/css/' . $module_page . '/' . $p_file
                        . '" rel="stylesheet" type="text/css" />';
                }
            }
        }
    }

    return $html;
}

function framework_include_js_issabelpbx($module_name, $module_page) {
    global $amp_conf, $active_modules;
    $version            = get_framework_version();
    $version_tag        = '?load_version=' . urlencode($version);
    if ($amp_conf['FORCE_JS_CSS_IMG_DOWNLOAD']) {
      $this_time_append    = '.' . time();
      $version_tag         .= $this_time_append;
    } else {
        $this_time_append = '';
    }

    $html = '';

    if (is_file('admin/modules/' . $module_name . '/' . $module_name . '.js')) {
        $html .= '<script type="text/javascript" src="'
                . $_SERVER['PHP_SELF'] . '?handler=file&amp;module='
                . $module_name . '&amp;file=' . $module_name . '.js'
                . $mod_version_tag . '"></script>';
    }
    if (isset($module_page)
        && ($module_page != $module_name)
        && is_file('admin/modules/' . $module_name . '/' . $module_page . '.js')
    ) {
        $html .= '<script type="text/javascript" src="'
                . $_SERVER['PHP_SELF'] . '?handler=file&amp;module='
                . $module_name . '&amp;file=' . $module_page . '.js'
                . $mod_version_tag . '"></script>';
    }

    // Check assets/js and then assets/js/page_name for any js files which will have been symlinked to
    // assets/module_name/js/*
    //
    $js_dir = 'admin/modules/' . $module_name . '/assets/js';
    if (is_dir($js_dir)) {
        $file_list = scandir($js_dir);
        foreach ($file_list as $file) {
            if (substr($file,-3) == '.js' && is_file("$js_dir/$file")) {
                $html .= '<script type="text/javascript"'
                        . ' src="admin/assets/' . $module_name . '/js/' . $file . '"></script>';
            }
        }
        unset($file_list);
        $js_subdir ="$js_dir/$module_page";
        if ($module_page != '' && is_dir($js_subdir)) {
            $file_list = scandir($js_subdir);
            foreach ($file_list as $p_file) {
                if (substr($p_file,-3) == '.js' && is_file("$js_subdir/$p_file")) {
                  $html .= '<script type="text/javascript" '
                            . ' src="admin/assets/' . $module_name . '/js/'
                            . $module_page . '/' . $p_file
                            . '"></script>';
                }
            }
        }
  }

    // DEPCRETATED but still supported:
    // Note - include all the module js files first, then the page specific files,
    //in case a page specific file requires a module level file
    $js_dir = "admin/modules/$module_name/js";
    if (is_dir($js_dir)) {
        $file_list = scandir($js_dir);
        foreach ($file_list as $file) {
            if (substr($file,-3) == '.js' && is_file("$js_dir/$file")) {
                $html .= '<script type="text/javascript"'
                . ' src="' . $_SERVER['PHP_SELF'] . '?handler=file&module='
                . $module_name . '&file='
                . $js_dir . '/' . $file . $mod_version_tag
                . '"></script>';
            }
        }
        unset($file_list);
        $js_subdir ="$js_dir/$module_page";
        if ($module_page != '' && is_dir($js_subdir)) {
            $sd = opendir($js_subdir);

            $file_list = array();
            while ($p_file = readdir($sd)) {
                $file_list[] = $p_file;
            }
            sort($file_list);
            foreach ($file_list as $p_file) {
                if (substr($p_file,-3) == '.js' && is_file("$js_subdir/$p_file")) {
                    $html .= '<script type="text/javascript" src="'
                            . $_SERVER['PHP_SELF'] . '?handler=file&module='
                            . $module_name . '&file='
                            . $js_subdir . '/' . $p_file . $mod_version_tag
                            . '"></script>';
                }
            }
        }
    }

    return $html;
}

function renderPpbxAPIFrame($endpoint) {
    echo "<iframe src='/newgui/$endpoint.html'frameborder=0 style='width:100%; height:100px;' id='iFrame1' ></iframe>";
    echo "<script>";
    echo "

    \$('#page_body').css('width','100%');
    \$('#page_body').css('padding','0');


    function setIframeHeight(iframe,element) {
        if (iframe) {
            var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;
            if (iframeWin.document.body) {
                if(element=='grid') {
                    alto = iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
                } else {
                    alto = iframeWin.document.documentElement.offsetHeight || iframeWin.document.body.offsetHeight;
                }
                if(alto<600) { alto=window.innerHeight-100; }
                $(iframe).css('height',alto+'px');
            }
        }
    };

    $(window).on('message', function(e) {
        var data = e.originalEvent.data;  // Should work.
        if(data.hasOwnProperty('action')) {
        if(data.action=='resizeme') {
           setIframeHeight($('#iFrame1')[0],data.element);
        }
        }
    });


</script>";

}
?>
