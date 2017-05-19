<!--Comentario:  He agregado variables para que se muestre la misma vista de la 160-->
<table width="99%" border="0" cellspacing="0" cellpadding="2" align="center" >
    <tr>
        <td width="8%" align="right">{$file.LABEL}: </td>
        <td width="12%" align="left" nowrap>{$file.INPUT}</td>
        <td width="80%" align="left"><input class="button" type="submit" name="filter" value="{$Filter}" /></td>
    </tr>
    <tr width="99%" border="0" cellspacing="0" cellpadding="0" >
        <!--Mensaje de error si no es un directorio válido-->
        <td class="mb_message"><b>{$msj_err}</b></td>
        </tr>
</table>


