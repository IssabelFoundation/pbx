<?php
global $amp_conf;
global $module_name;
global $module_page;
global $extmap;
global $reload_needed;
global $remove_rnav;
global $js_content;

$version     = get_framework_version();
$version_tag = '?load_version=' . urlencode($version);

$html = '';
$html .= '</div>';//page_body
//$html .= '</div><!-- div id="page" -->'; //page

//add javascript

//localized strings and other javascript values that need to be set dynamically
//TODO: this should be done via callbacks so that all modules can hook in to it
$fpbx['conf'] = $amp_conf;
$clean = array(
		'AMPASTERISKUSER',
		'AMPASTERISKGROUP',
		'AMPASTERISKWEBGROUP',
		'AMPASTERISKWEBUSER',
		'AMPDBENGINE',
		'AMPDBHOST',
		'AMPDBNAME',
		'AMPDBPASS',
		'AMPDBUSER',
		'AMPDEVGROUP',
		'AMPDEVUSER',
		'AMPMGRPASS',
		'AMPMGRUSER',
		'AMPVMUMASK',
		'ARI_ADMIN_PASSWORD',
		'ARI_ADMIN_USERNAME',
		'ASTMANAGERHOST',
		'ASTMANAGERPORT',
		'ASTMANAGERPROXYPORT',
		'CDRDBHOST',
		'CDRDBNAME',
		'CDRDBPASS',
		'CDRDBPORT',
		'CDRDBTABLENAME',
		'CDRDBTYPE',
		'CDRDBUSER',
		'FOPPASSWORD',
		'FOPSORT',
);

foreach ($clean as $var) {
	if (isset($fpbx['conf'][$var])) {
		unset($fpbx['conf'][$var]);
	}
}

$fpbx['conf']['text_dir']		= isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))
									? 'rtl' : 'ltr';
$fpbx['conf']['uniqueid']		= sql('SELECT data FROM module_xml WHERE id = "installid"', 'getOne');
$fpbx['conf']['dist']			= _module_distro_id();
$fpbx['conf']['ver']			= get_framework_version();
$fpbx['conf']['reload_needed']  = $reload_needed;
$fpbx['msg']['framework']['reload_unidentified_error'] = _(" error(s) occurred, you should view the notification log on the dashboard or main screen to check for more details.");
$fpbx['msg']['framework']['close'] = _("Close");
$fpbx['msg']['framework']['continuemsg'] = _("Continue");//continue is a resorved word!
$fpbx['msg']['framework']['cancel'] = _("Cancel");
$fpbx['msg']['framework']['retry'] = _("Retry");
$fpbx['msg']['framework']['update'] = _("Update");
$fpbx['msg']['framework']['save'] = _("Save");
$fpbx['msg']['framework']['bademail'] = _("Invalid email address");
$fpbx['msg']['framework']['updatenotifications'] = _("Update Notifications");
$fpbx['msg']['framework']['securityissue'] = _("Security Issue");
$fpbx['msg']['framework']['validation']['duplicate'] = _(" extension number already in use by: ");
$fpbx['msg']['framework']['noupdates'] = _("Are you sure you want to disable automatic update notifications? This could leave your system at risk to serious security vulnerabilities. Enabling update notifications will NOT automatically install them but will make sure you are informed as soon as they are available.");
$fpbx['msg']['framework']['noupemail'] = _("Are you sure you don't want to provide an email address where update notifications will be sent. This email will never be transmitted off the PBX. It is used to send update and security notifications when they are detected.");
$fpbx['msg']['framework']['invalid_responce'] = _("Error: Did not receive valid response from server");
$fpbx['msg']['framework']['invalid_response'] = $fpbx['msg']['framework']['invalid_responce']; // TYPO ABOVE
$fpbx['msg']['framework']['validateSingleDestination']['required'] = _('Please select a "Destination"');
$fpbx['msg']['framework']['validateSingleDestination']['error'] = _('Custom Goto contexts must contain the string "custom-".  ie: custom-app,s,1');
$fpbx['msg']['framework']['weakSecret']['length'] = _("The secret must be at minimum six characters in length.");
$fpbx['msg']['framework']['weakSecret']['types'] = _("The secret must contain at least two numbers and two letters.");

$html .= "\n" . '<script type="text/javascript">'
		. 'var fpbx='
		. json_encode($fpbx)
		. ";\n"

		. 'var extmap='
		. $extmap

		. ';$(document).click();' //TODO: this should be cleaned up eventually as right now it prevents the nav bar from not being fully displayed
 		. '</script>';

// Production versions should include the packed consolidated javascript library but if it
// is not present (useful for development, then include each individual library below
if (file_exists("/var/www/html/admin/assets/js/pbxlib.js")) {
	$pbxlibver = '.' . filectime("/var/www/html/admin/assets/js/pbxlib.js");
	$html .= '<script type="text/javascript" src="admin/assets/js/pbxlib.js'.$version_tag.$pbxlibver. '"></script>';
}

if (isset($module_name) && $module_name != '') {
	$html .= framework_include_js_freepbx($module_name, $module_page);
}

if (!empty($js_content)) {
	$html .= $js_content;
}

//add IE specifc styling polyfills
//offer google chrome frame for the richest experience
if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) {
	$html .= '<!--[if lte IE 10]>';
	$html .= '<link rel="stylesheet" href="admin/assets/css/progress-polyfill.css" type="text/css">';
	$html .= '<script type="text/javascript" src="admin/assets/js/progress-polyfill.min.js"></script>';
	$html .= '<![endif]-->';

	//offer google chrome frame for the richest experience
	$html .= <<<END
	<!--[if IE]>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/chrome-frame/1/CFInstall.min.js"></script>
		<script>
			!$.cookie('skip_cf_check') //skip check if skip_cf_check cookie is active
				&& CFInstall	//make sure CFInstall is loaded
				&& !!window.attachEvent //attachEvent is ie only, should never fire in other browsers
				&& window.attachEvent("onload", function() {
				 CFInstall.check({
					preventPrompt: true,
					onmissing: function() {
						$('<div></div>')
							.html('Unfortunately, some features may not work correctly in your '
								+ 'current browser. We suggest that you activate Chrome Frame, '
								+ 'which will offer you the richest posible experience. ')
							.dialog({
								title: 'Activate Chrome Frame',
								resizable: false,
								modal: true,
								position: ['center', 'center'],
								close: function (e) {
									$.cookie('skip_cf_check', 'true');
									$(e.target).dialog("destroy").remove();
								},
								buttons: [
									{
										text: 'Activate',
										click: function() {
												window.location = 'http://www.google.com/chromeframe/?redirect=true';
										}

									},
									{
										text: fpbx.msg.framework.cancel,
										click: function() {
												//set cookie to prevent prompting again in this session
												$.cookie('skip_cf_check', 'true');
												$(this).dialog("destroy").remove();
											}
									}
									]
							});
					}
				});

			});
	</script>
	<![endif]-->
END;
}
echo $html;
?>
