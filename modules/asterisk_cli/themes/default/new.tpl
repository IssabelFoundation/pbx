<!--<form method="POST" enctype="multipart/form-data">

Comentario:  He agregado variables para que se muestre la misma vista de la 160

-->

<form method="POST" enctype="multipart/form-data">

<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">

		<tr>
			<td align="right">{$command}: </td>
			<td><input name="txtCommand" type="text" size="70" value="{$txtCommand}"></td>
		</tr>

		<tr>
			<td>&nbsp;</td>
			<td>
				<input type="submit" class="button" value="{$execute}">
			</td>
		</tr>

		<tr>
			<td height="8">&nbsp;</td>
			<td><hr>
<pre style="font-family: monospace;">
{$RESPUESTA_SHELL}
</pre>
			</td>
		</tr>
</table>
</form>
