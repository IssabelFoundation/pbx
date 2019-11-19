<?php
if(!isset($out)) { $out=''; }

$out .= '<div id="page">';//open page

//add script warning
$out .= '<noscript><div class="attention">'
		. _('WARNING: Javascript is disabled in your browser. '
		. 'The IssabelPBX administration interface requires Javascript to run properly. '
		. 'Please enable javascript or switch to another  browser that supports it.') 
		. '</div></noscript>';
$out  = '';
$out .= '<div id="header">';
$out .= '<a id="button_reload" href="#" data-button-icon-primary="ui-icon-gear">'
		. $applyconfig .'</a>';
$out .= '</div>';
$out .= '<div id="page_body">';

echo $out;
?>
