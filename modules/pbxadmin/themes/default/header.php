<?php
global $use_popover_css;
$html  = '<html> <head>'; 
$html .= '<link href="admin/assets/css/jquery-ui.css" rel="stylesheet" type="text/css">';
$html .= '<link href="admin/assets/css/mainstyle.css" rel="stylesheet" type="text/css">';
$html .= '<link href="admin/assets/css/progress-polyfill.css" rel="stylesheet" type="text/css">';

//add the popover.css stylesheet if we are displaying a popover to override mainstyle.css styling
if ($use_popover_css) {
	$html .= '<link href="admin/assets/css/popover.css" rel="stylesheet" type="text/css">';
}

//include rtl stylesheet if using a rtl langauge
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))) {
	$html .= '<link href="admin/assets/css/mainstyle-rtl.css" rel="stylesheet" type="text/css" />';
}

$html .= '<script type="text/javascript" src="admin/assets/js/jquery-1.7.1.min.js"></script>';
$html .= '<script type="text/javascript" src="admin/assets/js/jquery-ui-1.8.9.min.js"></script>';


$html .= '</head>  <div id="page">';//open page

//add script warning
$html .= '<noscript><div class="attention">'
		. _('WARNING: Javascript is disabled in your browser. '
		. 'The FreePBX administration interface requires Javascript to run properly. '
		. 'Please enable javascript or switch to another  browser that supports it.') 
		. '</div></noscript>';
$html .= '<body>';
echo $html;
