<script>
var lang={};
{foreach key=key item=item from=$LANG}
    lang["{$key}"]="{$item}";
{/foreach}
</script>


{foreach $names as $extension=>$name}
    <b>{$extension}</b>: {$name}<br/>
    <img src='data:image/png;base64,{$codes[$extension]}' alt='qrcode' />
    <hr/>
{/foreach}

<form method=post>
<input type=hidden name=action value=form>
<input type=submit class='btn btn-primary' name=close value='{$CLOSE}'</input>
</form>
