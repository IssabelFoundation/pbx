<table cellspacing="0" cellpadding="0" border="0" width="100%">
  <tr>
    <td valign="top" width="220">
      <div id="nav">
        <div id="nav-setup" class="tabs-container">
          <ul>
            <li class="category category-header">{$Basic}</li>
            <li><a class="current" href="/?menu=pbxconfig&amp;type=setup&amp;display=extensions"  >{$Extensions}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=featurecodeadmin"  >{$Feature_Codes}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=routing"  >{$Outbound_Routes}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=trunks"  >{$Trunks}</a></li>
            {if $has_google_voice == 1}
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=motif"  >{$Google_Voice}</a></li>
            {/if}
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=customcontexts"  >{$CoS}</a></li>
            <li>{$Inbound_Call_Control}</li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=did"  >{$Inbound_Routes}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=dahdichandids"  >{$DAHDI_Channel_DIDs}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=announcement"  >{$Announcements}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=blacklist"  >{$Blacklist}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=cidlookup"  >{$CallerID_Lookup_Sources}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=daynight"  >{$Call_Flow_Control}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=findmefollow"  >{$Follow_Me}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=ivr"  >{$IVR}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=queueprio"  >{$Queue_Priorities}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=queues"  >{$Queues}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=ringgroups"  >{$Ring_Groups}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=timeconditions"  >{$Time_Conditions}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=timegroups"  >{$Time_Groups}</a></li>
            <li>{$Internal_Options_Configuration}</li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=conferences"  >{$Conferences}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=languages"  >{$Languages}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=miscapps"  >{$Misc_Applications}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=miscdests"  >{$Misc_Destinations}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=music"  >{$Music_on_Hold}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=pinsets"  >{$PIN_Sets}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=paging"  >{$Paging_Intercom}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=parking"  >{$Parking_Lot}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=recordings"  >{$System_Recordings}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=vmblast"  >{$VoiceMail_Blasting}</a></li>
            <li>{$Remote_Access}</li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=callback"  >{$Callback}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=disa"  >{$DISA}</a></li>
            <li class="category category-header">{$Advanced}</li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=sipsettings"  >{$Asterisk_SIP_Settings}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=iaxsettings"  >{$Asterisk_IAX_Settings}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=outroutemsg"  >{$Route_Congestion_Messages}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=voicemail"  >{$Voicemail_Admin}</a></li>
            <li><a href="/?menu=pbxconfig&amp;type=setup&amp;display=asteriskinfo"  >{$Asterisk_Info}</a></li>
            <li>{$Option}</li>
            <li style="float:left;border-right:0px"><a href="/admin/" target="_blank">{$Unembedded_IssabelPBX}</a></li>
            <div style="height:0px">
                <a href="#" class="info"><span style='margin-left:0.2cm; margin-top:-1.8cm; width:303px'>{$INFO}</span></a>
            </div>

          </ul>
        </div>{* div id="nav-setup" *}
      </div>{* div id="nav" *}
      <br /> <br /> <br /> <br />
    </td>
    <td id="content_pbx" valign="top">{$htmlFPBX}</td>
  </tr>
  {if $isissabelpbx == "0"}
  <tr>
    <td></td>
    <td valign="bottom">
      <div align="center">
      </div>
    </td>
  </tr>
  {/if}
</table>
