$(document).ready(function(){
    var content = "<input type='text' class='bss-input' onKeyDown='event.stopPropagation();' onKeyPress='addSelectInpKeyPress(this,event)' onClick='event.stopPropagation()' placeholder='"+_tr('Add Hostname')+"'> <span class='glyphicon glyphicon-plus addnewicon' onClick='addSelectItem(this,event,1);'></span>";
    var divider = $('<option/>').addClass('divider').data('divider', true);
    var addoption = $('<option/>', {class: 'addItem'}).data('content', content);
    var hostoption = $('<option/>').data('content', window.location.host).val(window.location.host);
    $('.selectpicker').selectpicker();
    if($('.selectpickeradd option[value="'+window.location.host+'"]').length==0) {
        $('.selectpickeradd').append(hostoption);
    }
    $('.selectpickeradd').append(divider).append(addoption).selectpicker();
});

function addSelectItem(t,ev) {
   ev.stopPropagation();
   var bs = $(t).closest('.bootstrap-select')

   var txt=bs.find('.bss-input').val().replace(/[|]/g,"");
   var txt=$(t).prev().val().replace(/[|]/g,"");
   if ($.trim(txt)=='') return;
   
   var p = $('#asteriskip');
   var o=$('option', p).eq(-2);
   o.before( $("<option>", { "selected": true, "text": txt}) );
   p.selectpicker('refresh');

}

function addSelectInpKeyPress(t,ev) {
   ev.stopPropagation();

   // do not allow pipe character
   if (ev.which==124) ev.preventDefault();

   // enter character adds the option
   if (ev.which==13)
   {
      ev.preventDefault();
      addSelectItem($(t).next(),ev);
   }
}

function _tr(texto) {
   if(typeof(lang[texto])=='undefined') {
       return texto;
   } else {
       return lang[texto];
   }
}
