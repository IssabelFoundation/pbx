$(document).ready(function(){
    if($("#filter_field").val() == "recordingfile"){
	document.getElementsByName("filter_value")[0].style.display="none";
	document.getElementById("filter_value_recordingfile").style.display="";
    }
    $("#filter_field").change(function(){
	if($(this).val() == "recordingfile"){
	    document.getElementsByName("filter_value")[0].style.display="none";
	    document.getElementById("filter_value_recordingfile").style.display="";
	}
	else{
	    document.getElementsByName("filter_value")[0].style.display="";
	    document.getElementById("filter_value_recordingfile").style.display="none";
	}
    });
});
