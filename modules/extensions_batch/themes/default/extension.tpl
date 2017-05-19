<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu={$MODULE_NAME}'>

<!--  align="center" width="99%" -->
<table border="0" cellspacing="0" cellpadding="4" >
    <tr class="letra12">
    <td><input class="button" type="submit" name="csvupload" value="{$LABEL_UPLOAD}" /></td>
    <td><input class='button' type='submit' name='delete_all' value='{$LABEL_DELETE}' onClick="return confirmSubmit('{$CONFIRM_DELETE}');" /></td>
    </tr>
</table>
<table class="tabForm" width="100%">
<tbody>
<tr>
    <td align="right" width="15%"><b>{$LABEL_FILE}:</b></td>
    <td><input type='file' id='csvfile' name='csvfile' /></td>
</tr>
<tr>
    <td colspan="2"><a class="link1" href="?menu={$MODULE_NAME}&amp;action=csvdownload&amp;rawmode=yes">{$LABEL_DOWNLOAD}</a></td>
</tr>
<tr><td colspan="3">{$HeaderFile}</td></tr>
<tr><td colspan="3">{$AboutUpdate}</td></tr>
</tbody>
</table>
</form>