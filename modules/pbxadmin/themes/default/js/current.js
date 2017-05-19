$(function(){
    var url = window.location.href;
    var ind_def = window.location.protocol + "//" + window.location.host + "/index.php?menu=pbxconfig";
    $("#nav ul li a").each(function() {
	var href = this.href;
	if( (url == href) || (href == '') ) { 
		$(this).addClass('current');
	} else if ( url == ind_def ){
		// Dont do anything.
	} else {
		$(this).removeClass('current');
	}
    });
});
