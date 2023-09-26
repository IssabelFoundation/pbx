<?php
// This file should be part of issabel-pbx module verison 5, only file needed to wrap issabelPBX
// It will override standalone issabelPBX authentication/authorization using issabel one instead

require_once(dirname(__FILE__) . '/libraries/ampuser.class.php'); // must come first if we are loading the object from existing session

if (!isset($_SESSION)) {
    //start a session if we need one
    session_set_cookie_params(60 * 60 * 24 * 30);//(re)set session cookie to 30 days
    ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);//(re)set session to 30 days
    session_name("issabelSession");
    session_start();
    $user = isset($_SESSION['issabel_user'])?$_SESSION['issabel_user']:"";
}

if(is_file("../libs/misc.lib.php") ) {

    require_once "../libs/misc.lib.php";
    require_once "../configs/default.conf.php";
    require_once "../libs/paloSantoDB.class.php";
    require_once "../libs/paloSantoACL.class.php";
    $dsnAsterisk = generarDSNSistema('asteriskuser', 'asterisk', '../');

    if (!isset($_SESSION)) {
        //start a session if we need one
        session_name("issabelSession");
        session_start();
    }

    if($user!='') {
        $mylang = array('en'=>'en_US','es'=>'es_ES','br'=>'pt_BR');
        $lang = get_language('../');
        $finallang = isset($mylang[$lang])?$mylang[$lang]:$lang."_".strtoupper($lang);
        setcookie("lang", $finallang);
    }

    $pDB = new paloDB($dsnAsterisk);

    $pDBsq = new paloDB($arrConf['issabel_dsn']['acl']);
    if (!empty($pDBsq->errMsg)) {
        return "ERROR DE DB: $pDBsq->errMsg <br>";
    }
    $pACL = new paloACL($pDBsq);
    if (!empty($pACL->errMsg)) {
        return "ERROR DE ACL: $pACL->errMsg <br>";
    }

    $pDBSettings = new paloDB($arrConf['issabel_dsn']["settings"]);

    $allow_direct_access =  get_key_settings($pDBSettings,"activatedIssabelPBX");

}

// If its called from inside an iframe or it is an ajax request from config.php
// an issabel-framework is installed, authenticate using issabel users instead of issabelpbx
//
if(is_file("../libs/misc.lib.php") && $user!='') {

    //    ( $_SERVER['HTTP_SEC_FETCH_DEST'] <> 'document' || ( preg_match("/admin\/config.php/",$_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_SEC_FETCH_SITE'] == 'same-origin') )) 
    $allow_direct_access = '1';

    if (!@include_once(getenv('ISSABELPBX_CONF') ? getenv('ISSABELPBX_CONF') : '/etc/issabelpbx.conf')) {
        include_once('/etc/asterisk/issabelpbx.conf');
    }

    $allprivs    = array();
    $id_resource = $pACL->getIdResource('pbxadmin');
    $id_group    = $pACL->getIdGroup('administrator');
    if($id_group === false) { $id_group=1; }
    $privs       = $pACL->getModulePrivileges('pbxadmin');

    foreach($privs as $idx=>$priv) {
        $allprivs[]=$priv['privilege'];
    }

    $username = "admin"; // no matter the user, login as admin, we will set permissions down bellow
    $amp_conf['AUTHTYPE']='none'; // so it does not show the logout button in menu
    $amp_conf['SHOWLANGUAGE']=false; // so it does not show lanuage selection in menu
    unset($no_auth);

    if (!defined('ISSABELPBX_IS_AUTH')) {
        define('ISSABELPBX_IS_AUTH', 'TRUE');
    }

    $_SESSION['AMP_user'] = new ampuser($username);
    $_SESSION['AMP_user']->clearSections();

    $query = "SELECT `data` FROM `module_xml` WHERE `id` = 'mod_serialized'";
    $module_serialized = $pDB->getFirstRowQuery($query, false, array());
    $unserialized = unserialize($module_serialized[0]);

    // special issabelPBX section, not in modules.xml, needed for reload bar and also device type selection on extensions section
    $adddevice = array('menuitems'=>array('999'=>'Add Devices'),'embedcategory'=>'dummy');
    $unserialized['999']=$adddevice;
    $reloadbar = array('menuitems'=>array('99'=>'Apply Changes'),'embedcategory'=>'dummy');
    $unserialized['99']=$reloadbar;

    // We do not want to show ampusers section when loaded from issabel framework
    unset($unserialized['ampusers']);
    unset($unserialized['core']['menuitems']['ampusers']);
    $unserialized['core']['menuitems']['modules1']='Module Admin';

    $noadmin=0;

    foreach($unserialized as $modulekey=>$moduledata) {
        if(isset($moduledata['embedcategory'])) {
            foreach($moduledata['menuitems'] as $urlkey=>$name) {

                // if module does not exists as privilege, insert it and grant administrator access
                if (!in_array($urlkey,$allprivs)) {
                    $pACL->createModulePrivilege($id_resource, $urlkey, $name);
                    $id_privilege  = $pACL->getIdModulePrivilege($id_resource,$urlkey);
                    $bExito        = $pACL->grantModulePrivilege2Group($id_privilege, $id_group);
                }

                if (!$pACL->hasModulePrivilege($user, 'pbxadmin', $urlkey)) {
                    //echo "salteo $user =  $urlkey<br>";
                    $noadmin=1;
                    continue;
                }

                $_SESSION['AMP_user']->allowSection($urlkey);
                //echo "permito ($modulekey) $user = $urlkey<bR>";
            }
        }
    }

    if($noadmin==0) { 
        // $_SESSION['AMP_user']->setAdmin();
    }

    set_language();

    // Audit
    
    
    $logvars = array(
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

    $action  = isset($_POST['action'])?$_POST['action'].' ':'';
    $display = isset($_POST['display'])?$_POST['display'].' ':'';

    $logMSG="issabelPBX ".$display.$action;

    if(isset($_POST['extension'])) {
        $logMSG .= "extension=".$_POST['extension']." ";
    }
    if(isset($_POST['routename'])) {
        $logMSG .= "route=".$_POST['routename']." ";
    }
    if(isset($_POST['trunk_name'])) {
        $logMSG .= "trunk=".$_POST['trunk_name']." ";
    }
    if(isset($_POST['account'])) {
        $logMSG .= $_POST['account']." ";
    }
    if(isset($_POST['displayname'])) {
        $logMSG .= "Display Name=".$_POST['displayname']." ";
    }
    if(isset($_POST['name'])) {
        $logMSG .= "Name=".$_POST['name']." ";
    }
    if(isset($_POST['extdisplay']) && !isset($_POST['extension'])) {
        $logMSG .= $_POST['extdisplay']." ";
    }
    if(isset($_POST['announcement_id'])) {
        $logMSG .= "Announcement ID=".$_POST['announcement_id']." ";
    }
    if(isset($_POST['cid_id'])) {
        $logMSG .= "Set CID ID=".$_POST['cid_id']." ";
    }
    if(isset($_POST['qlog_id'])) {
        $logMSG .= "Queue Log ID=".$_POST['qlog_id']." ";
    }
    if(isset($_POST['description'])) {
        $logMSG .= "Desc=".$_POST['description']." ";
    }
    if(isset($_POST['fc_description'])) {
        $logMSG .= "Desc=".$_POST['fc_description']." ";
    }
    if(isset($_POST['destdial'])) {
        $logMSG .= "Dial=".$_POST['destdial']." ";
    }
    if(isset($_POST['callbacknum'])) {
        $logMSG .= "Dial=".$_POST['callbacknum']." ";
    }
    if(isset($_POST['tech'])) {
        $logMSG .= "TECH=".$_POST['tech']." ";
    }
    if(isset($_POST['customcontext'])) {
        $logMSG .= "COS=".$_POST['customcontext']." ";
    }


    foreach ($logvars as $k => $v) {
        //set message for audit.log
        if ($logvars[$k] != null && $logvars[$k] != "") {
            $logMSG .= $k . "=" . $logvars[$k] . " ";
        }
    }

    $ipaddr = $_SERVER['REMOTE_ADDR'];

    if(isset($_POST['action'])) {
        writeLOG("audit.log", "PBX $user: User $user performed an action [".trim($logMSG)."] from $ipaddr.");
    }

    if(isset($_POST['handler'])) {
        if($_POST['handler']=='reload') {
            writeLOG("audit.log", "PBX $user: User $user applied changes from $ipaddr.");
        }
    }
} else {
    if($allow_direct_access==0) {
	$display='noaccess';
       // die("no direct script access allowed");
    }
}
