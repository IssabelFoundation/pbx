{if $accion eq "show_callers"}
<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td width="5%" align="right"><input class="button" type="submit" name="update_show_callers" value="{$UPDATE}"></td>
        <td width="5%" align="right"><input class="button" type="submit" name="cancel" value="{$CANCEL}"></td>
        <td width="10%" align="right"><input class="button" type="submit" name="caller_invite" value="{$INVITE_CALLER}"></td>
        <td width="10%" align="left" nowrap>{$device.INPUT}</td>
        <td align="left"><input class="button" type="submit" name="callers_kick_all" value="{$KICK_ALL}"></td>
    </tr>
</table>
{else}
<table width="99%" border="0" cellspacing="0" cellpadding="4" align="center">
    <tr class="letra12">
        <td width="3%" align="right">{$conference.LABEL}: </td>
        <td width="10%" align="left" nowrap>{$conference.INPUT}</td>
        <td width="5%" align="right">{$filter.LABEL}: </td>
        <td width="10%" align="left" nowrap>{$filter.INPUT}</td>
        <td align="left"><input class="button" type="submit" name="show" value="{$SHOW}"></td>
    </tr>
</table>
{/if}