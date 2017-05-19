{literal}
<script type="text/javascript">
<!-- Original:  Gregor (legreg@legreg.de) -->

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->

<!-- Begin
var ie4 = (document.all) ? true : false;
var ns4 = (document.layers) ? true : false;
var ns6 = (document.getElementById && !document.all) ? true : false;
function hidelayer(lay) {
if (ie4) {
    document.all[lay].style.visibility = "hidden";
    document.all[lay].style.position = "absolute";
}
if (ns4) {
    document.layers[lay].visibility = "hide";
}
if (ns6) {
    document.getElementById([lay]).style.display = "none";
    document.getElementById([lay]).style.position = "absolute";
}
}
function showlayer(lay) {
if (ie4) {
    document.all[lay].style.visibility = "visible";
    document.all[lay].style.position = "";
}
if (ns4) {
    document.layers[lay].visibility = "show";
}
if (ns6) {
    document.getElementById([lay]).style.display = "";
    document.getElementById([lay]).style.position = "";
}
}
//  End -->
</script>
{/literal}
<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td align="left">
            {if $Show}
                <input class="button" type="submit" name="add_conference" value="{$SAVE}">&nbsp;&nbsp;&nbsp;&nbsp;
            {/if}
            <input class="button" type="submit" name="cancel" value="{$CANCEL}">
        </td>

    {if $mode ne 'view'}
	<td align="right" nowrap><span class="letra12"><span  class="required">*</span> {$REQUIRED_FIELD}</span></td>
    {/if}
    </tr>
    <tr><td>
        <table width="99%" cellpadding="4" cellspacing="0" border="0" class="tabForm">
            <tr>
                <td align="left" width="20%"><b>{$conference_name.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
                <td class="required" align="left">{$conference_name.INPUT}</td>
                <td align="left"><b>{$conference_owner.LABEL}: </b></td>
                <td align="left">{$conference_owner.INPUT}</td>
            </tr>
            <tr>
                <td align="left"><b>{$moderator_pin.LABEL}: </b></td>
                <td align="left">{$moderator_pin.INPUT}</td>
                <td align="left"><b>{$moderator_options_1.LABEL}</b></td>
                <td align="left">
                    {$moderator_options_1.INPUT}{$announce}&nbsp;&nbsp;&nbsp;
                    {$moderator_options_2.INPUT}{$record}
                </td>
            </tr>
            <tr>
                <td align="left"><b>{$user_pin.LABEL}: </b></td>
                <td align="left">{$user_pin.INPUT}</td>
                <td align="left"><b>{$user_options_1.LABEL}: </b></td>
                <td align="left">
                    {$user_options_1.INPUT}{$announce}&nbsp;&nbsp;&nbsp;
                    {$user_options_2.INPUT}{$listen_only}&nbsp;&nbsp;&nbsp;
                    {$user_options_3.INPUT}{$wait_for_leader}
                </td>
            </tr>
            <tr>
                <td align="left"><b>{$start_time.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
                <td align="left">{$start_time.INPUT}</td>
                <td align="left"><b>{$duration.LABEL}: </b></td>
                <td align="left">
                    {$duration.INPUT}&nbsp;:
                    {$duration_min.INPUT}
                </td>
            </tr>
            <tr>
                <td align="left"><b>{$conference_number.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
                <td align="left">{$conference_number.INPUT}</td>
                <td align="left"><b>{$max_participants.LABEL}: {if $mode ne 'view'}<span  class="required">*</span>{/if}</b></td>
                <td align="left">{$max_participants.INPUT}</td>
            </tr>
{if $WEBCONF_CONTENT}
            <tr><td><input type="checkbox" name="enable_webconf" id="enable_webconf" {$WEBCONF_SELECTED} {literal} onclick="if (this.checked) { showlayer('webconf_options'); } else { hidelayer('webconf_options'); } " {/literal} /><b>{$enable_web_conf}</b></td><td colspan="3">&nbsp;</td></tr>
            <tr><td colspan="4"><div id="webconf_options"><hr/>{$WEBCONF_CONTENT}</div></td></tr>
{literal}
<script type="text/javascript">
<!-- 

if (document.getElementById('enable_webconf').checked) {
    showlayer('webconf_options');
} else {
    hidelayer('webconf_options');
}

//  End -->
</script>
{/literal}
{/if}
        </table>
    </td></tr>
</table>
