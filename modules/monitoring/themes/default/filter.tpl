<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
	<td align="right">{$date_start.LABEL}:</td>
	<td align="left" nowrap>{$date_start.INPUT}</td>
	<td align="right">{$date_end.LABEL}:</td>
	<td align="left" nowrap>{$date_end.INPUT}</td>
	<td align="right">{$filter_field.LABEL}:</td>
	<td align="left">{$filter_field.INPUT}&nbsp;{$filter_value.INPUT}
	  <select id="filter_value_recordingfile" name="filter_value_recordingfile" size="1" style="display:none">
                <option value="incoming" {$SELECTED_1} >{$INCOMING}</option>
                <option value="outgoing" {$SELECTED_2} >{$OUTGOING}</option>
                <option value="queue" {$SELECTED_3} >{$QUEUE}</option>
		<option value="group" {$SELECTED_4} >{$GROUP}</option>
           </select>
    </td>
	<td align="right"><input class="button" type="submit" name="show" value="{$SHOW}" /></td>
    </tr>
</table>
<a href="javascript:seleccionar_checkbox(1)">Marcar todos</a> | <a href="javascript:seleccionar_checkbox(0)">Desmarcar Todos</a>
