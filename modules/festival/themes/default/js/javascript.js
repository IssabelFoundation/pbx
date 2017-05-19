$(document).ready(function () {

	$(":checkbox[name='chkoldstatus']").iButton({
        labelOn: "On",
        labelOff: "Off",
        change: function ($input) {
        	var festival_activate = $input.is(":checked") ? "activate" : "deactivate";
        	$("#status").val(festival_activate);
            
            var arrAction            = new Array();
		    arrAction["action"]  = "change";
		    arrAction["menu"]	 = "festival";
		    arrAction["rawmode"] = "yes";
		    arrAction["status"]  = festival_activate;
		    request("index.php",arrAction,false,
			function(arrData,statusResponse,error)
			{
			    if (arrData["mb_title"] && arrData["mb_message"]) {
			    	$("#message_error").remove();
			    	if (document.getElementById("neo-contentbox-maincolumn")) {
			    		var message= "<div class='div_msg_errors' id='message_error'>" +
						      "<div style='float:left;'>" +
							  "<b style='color:red;'>&nbsp;&nbsp;"+arrData['mb_title']+"</b>" +
						      "</div>" +
						      "<div style='text-align:right; padding:5px'>" +
							  "<input type='button' onclick='hide_message_error();' value='"+arrData['button_title']+"'/>" +
						      "</div>" +
						      "<div style='position:relative; top:-12px; padding: 0px 5px'>" +
							  arrData['mb_message'] +
						      "</div>" +
						      "</div>";

			    		$(".neo-module-content:first").prepend(message);
			    	} else if (document.getElementById("elx-blackmin-content")) {
			    		var message = "<div class='ui-state-highlight ui-corner-all'>" +
							"<p>" +
							"<span style='float: left; margin-right: 0.3em;' class='ui-icon ui-icon-info'></span>" +
							"<span id='elastix-callcenter-info-message-text'>"+ arrData['mb_title'] + arrData['mb_message'] +"</span>" +
						    "</p>" +
						    "</div>";
			    		$("#elx-blackmin-content").prepend(message);
			    	} else {
			    		var message= "<div style='background-color: rgb(255, 238, 255);' id='message_error'><table width='100%'><tr><td align='left'><b style='color:red;'>" +
						  arrData['mb_title'] + "</b>" + arrData['mb_message'] + "</td> <td align='right'><input type='button' onclick='hide_message_error();' value='" +
						  arrData['button_title']+ "'/></td></tr></table></div>";
			    		$("body > table > tbody > tr > td:last").prepend(message);
			    	}
			    }
			});
        }
	});
});
