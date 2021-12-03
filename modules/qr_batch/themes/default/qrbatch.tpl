<script>
var lang={};
{foreach key=key item=item from=$LANG}
    lang["{$key}"]="{$item}";
{/foreach}
</script>

<form  method='POST' enctype='multipart/form-data' style='margin-bottom:0;' action='?menu={$MODULE_NAME}'>

<form method='POST' action='?menu={$MODULE_NAME}'>
<input type=hidden name=action value=qrlist>

<div class='container-fluid'>
<div class='row'>
<div class='panel panel-blue'>
<div class='row'>
  <div class='p0 col-md-12'>
      <h3>{$QRCODE_GENERATOR}</h3>
  </div>
</div>

<div class='row'>
  <div class='p0 col-md-4'>
    {$BRAND}
  </div>
  <div class='p0 col-md-8'>
    <select name=template id=template class='selectpicker'>
    {foreach $TEMPLATES as $template}
      <option value='{$template}'>{$template}</option>
    {/foreach}
    </select>
  </div>
</div>

<div class='row p0'>
  <div class='p0 col-md-4'>
    {$ISSABEL_HOST_IP}
  </div>
  <div class='p0 col-md-8'>
    <select name=asteriskip data-container="body" id=asteriskip class='selectpickeradd' >
    {foreach $ALL_IP as $ip}
      <option value='{$ip}'>{$ip}</option>
    {/foreach}
    <select>
  </div>
</div>
<div class='row p0'>
  <div class='p0 col-md-12'>
      <input type=submit class='btn btn-primary' name=generate value='{$GENERATE}'</input>
  </div>
</div>

</div>
</div>
</div>

</form>
