<?php
/*
Change Log
2007  - Creation
*/

// VDS 5.0
// 10/29/07 - L.Taylor
//    - Changed all references from DisplayColumns to DisplayedSearchResultsColumns. Added
//      additional references to AllSearchResultsColumns, and HiddenSearchResultsColumns
//    - Added support for additional SearchResultsColumns 
//    - Removed all references to CustomerNotesYN
// 10/30/07 - L.Taylor
//    - Added support for MeetingUniverseID, BallotUniverseID, FilterAgendaYN, 
//      AggregateMeetingsYN, DisplayNotesTypeID, DisplayNotesPlacementID, FundLinksYN 
// 10/31/07 - L.Taylor
//    - Changed SearchResultsColumn checkboxes to a picklist that allows the user
//      to specify the order in which the search results columns are displayed
// 11/02/07 - L.Taylor
//    - Added logic to determine what Search Results Columns we do/don't want to display
//      in order to pre-populate the select boxes with the appropriate column choices 
//      Displayed and Non-displayed columns are saved in the customer class
// 11/28/07 - L.Taylor
//    - Added logic to determine what Vote Detail Columns we do/don't want to display
//      in order to pre-populate the select boxes with the appropriate column choices 
//      Displayed and Non-displayed columns are saved in the customer class
// 01/08/08 - L.Taylor
//    - Added javascript to: select name="f_nBallotUniverseID" in order to disable 
//      f_nFilterAgendaYN when f_nBallotUniverseID = All.  Also added javascript to 
//      form onsubmit in order to re-enable f_nFilterAgendaYN to pass a value and work  
//      around the mandatory f_nFilterAgendaYN validation process and submission to the database 
// 01/16/08 - L.Taylor
//    - Modified code for Meeting Universe to include pop-up window for selections based on
//      what type Meeting Universe the user selected.  The pop-up window is a new php file
//      called selectMeetingUniverseOptions.
//    - Added javascript file, clientside_scripts/validateForms.js, to validate new form entries 
//      upon submission.  This is different than the original design.  The original design 
//      validates and saves state through php calls.  Most of the form is still validated this way.
//    - Added javascript file, clientside_scripts/openWindows.js, to handle opening the 
//       popup windows and transferring variable assignments.
//
// VDS 5.5
// 04/14/08 - L.Taylor
//    - Added fields for Website Start and End dates to differentiate between the data displayed 
//      on the Client Site and the data displayed in the Toolbox
// 04/30/08 - J.Kraus
//    - Added support for display of non-U.S. tickers.
//
// VDS 5.8
// 01/05/09 - J.Kraus
//    - Added support for navigation type (records vs. pages).
//    - Changed Meeting Date + 30 days option to Meeting Date + N days;
//      added field for storing # days (for real time and staging).
//    - Enforce sanity checks between :
//        - Update Frequency, Future Meetings, and Future Votes.
//        - Aggregate Meetings and Hyperlink Fund Names.
//        - Ballot Universe and Filter Agenda Items.
// 02/11/09 - J.Kraus
//    - Added support for allow/disallow XML requests.
//
// VDS 6.0
// 03/27/09 - J.Kraus
//    - Added support to distinguish TNA from DNV.
// 04/03/09 - J.Kraus
//    - Added functionality to modify the order of a list box without having to
//      delete and re-add all selections.
// 05/10/10 - L.Wang
//    - Added the save button on the top of the form.
// 06/17/14 - Vishal T
//    - set supressRegYN default to 'Yes' for action=create (VDS-3)

// 09/13/17 - Neeta M
// - VDS- - Adding workflowtag functionality to the toolbox 
//        - Adding Workflow Tag Logic & Workflow Tag fields
//
include("includeFiles.php");

getEntitlementsCheck(20);

$sExtraStyle = '';
$sExtraJS = '';
$sExtraBodyCode = '';

// Load calendar JS
$bLoadCalendar = true;

$sPageTitle = 'Manage Customer Details';

$nCustomerID = $_GET['nCustomerID'];
$action = $_GET['action'];
$bCreate = (strcasecmp($_GET['action'], 'create') == 0) ? true : false;;

$arrWorkFlowTagLogic = array();
$arrWorkFlowTagLogic = db_getWorkFlowTagLogicOptions();
	
// Check for an invalid object - if true, get it out of the session instead of the DB

if( strcmp(''.$nCustomerID, KEYWORD_INVALID_OBJECT) == 0 )
{
	$customer = $_SESSION[KEYWORD_INVALID_OBJECT];
	$arrInvalids = $_SESSION[KEYWORD_INVALIDS];
	unset($_SESSION[KEYWORD_INVALID_OBJECT]);
	unset($_SESSION[KEYWORD_INVALIDS]);
}
else
{
	$arrUserWorkFlowTagData = array();
	
	// If editing then get object from DB, else just create a new one
	switch($_GET['action'])
	{
		case "edit":

		    $nCustomerID = $_GET['nCustomerID'];
		    $customerCriteria = new Customer();
		    $customerCriteria->nCustomerID = $nCustomerID;
		    $customerCriteria->bChildrenYN = true;
		    $arrCustomers = db_getCustomers($customerCriteria);
		    $customer = $arrCustomers[0];			
            $arrUserWorkFlowTagData = db_getUserWorkFlowTagData($nCustomerID);
            $arrUserSignificantMeetings = db_getUserSignificantMeetingData($nCustomerID);		   
	
			     
		break;

		case "create":

			$customer = new Customer();
			
			// Setup default values
			$customer->nDefaultSort = 1;
		
			$customer->nDisplayFundSearchText = 0;
			$customer->nUpdateFrequencyID = 2;
			$customer->supressRegYN = 1;	//set default value to 'Yes' for action = create
      $customer->nEnableAuthLive = 0;
      $customer->nEnableAuthStaging = 1;
			
			// Rollover date is 08/31 of the future from today's date
			$dNow = getdate(time());
			$nYear = $dNow['year'];
			if( $dNow['month'] > 8 )
			{
				$nYear++;
			}
			
			$customer->dRolloverDate = mktime(0, 0, 0, 8, 31, $nYear);
					
			$customer->nMeetingUniverseID = 0;
			$customer->nBallotUniverseID = 0;
			$customer->nFilterAgendaYN = 0;
			$customer->nTNAYN = 0;
			$customer->nAggregateMeetingsYN = 0;
			$customer->nDisplayNotesTypeID = 0;
			$customer->nDisplayNotesPlacementID = 0;
			$customer->nRationaleDisclosureTypeID = 'all';
			$customer->nFundLinksYN = 0;
      $customer->nWebsiteFiltersID = ' ';
			$customer->nFutureMeetingYN = 0;
			$customer->nFutureVotesYN = 0;
			
			$arrAllowableSearchTypes = array();
			$arrAllowableSearchTypes[] = 1;
			$arrAllowableSearchTypes[] = 16;
			$arrAllowableSearchTypes[] = 2;
			$arrAllowableSearchTypes[] = 8;
			$arrAllowableSearchTypes[] = 4;
			$customer->arrAllowableSearchTypes = $arrAllowableSearchTypes;
			
			$customer->nStagingUpdateFrequencyID = 2;
			$customer->arrStagingAllowableSearchTypes = $arrAllowableSearchTypes;
			$customer->nNavTypeID = 1;
			$customer->nRestrictLegacySiteYN = 1;
			$customer->nRestrictJavaSiteYN = 1;

			break;
	}
	

	
	

	

}

include("adminHeader.php");
?>

<div class="dataSection">
  	<form name="processCustomer" method="POST" action="<?php echo LINK_PROCESS_CUSTOMER; ?>" onSubmit="return validateProcessCustomerForm(this);" >
     	<input type="hidden" name="f_action" value="<?php echo $action; ?>">
     	<table class="formTable" cellspacing="0" cellpadding="0">
        <tr class="formRow">
    	  <td class="formLabel">&nbsp;</td>
     		<td class="formFieldButtons">
     			<input type="submit" name="" value="Save Changes" onClick="selectAll(this.form.f_arrSearchResultsColumnsTypesAdd); selectAll(this.form.f_arrVoteDetailColumnsTypesAdd);selectAll(this.form.f_arrGraphicalMetricsTypesAdd);selectAll(this.form.f_arrMeetingListColumnsTypesAdd);selectAll(this.form.f_arrStagingMeetingListColumnsTypesAdd);selectAll(this.form.f_arrOrganizationIDAdd);">
     			<input type="button" name="" value="Cancel" onClick="window.history.back();">
     		</td>
  		</tr>
        <tr class="formDataTitleRow">
      		<td colspan="2" class="formDataTitle">Website Details</td>
      	</tr>
      
        <tr class="dataHeaderRow">
        	<th colspan="2" class="dataHeaderCell">General Information</th>
        </tr>
    
      	<tr class="formRow">
        	<td class="formLabel">Customer ID</td>
    		<td class="formField">

<?php
if($bCreate)
{
?>
		<input type="text" name="f_nCustomerID" value="<?php echo $customer->nCustomerID; ?>">
<?php
}
else
{
?>
		<?php echo $customer->nCustomerID; ?>
		<input type="hidden" name="f_nCustomerID" value="<?php echo $customer->nCustomerID; ?>">
<?php
}	// end if($bCreate)
?>
			
		</td>
  	</tr>
    <tr class="formRow">
        <td class="formLabel">Customer Name</td>
        <td class="formField">
            <input type="text" size="60" name="f_sCustomerName" value="<?php echo $customer->sCustomerName; ?>">
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Customer Domain</td>
        <td class="formField">
            <input type="text" size="60" name="f_sCustomerDomain" value="<?php echo $customer->sCustomerDomain; ?>">
        </td>
    </tr>
    
    <tr class="formRow">
        <td class="formLabel">VDS URL</td>
        <td class="formField">
            <input type="text" size="60" name="f_sVDSSiteURL" value="<?php echo $customer->sVDSSiteURL; ?>">
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">VDS Staging URL</td>
        <td class="formField">
            <input type="text" size="60" name="f_sVDSSiteStagingURL" value="<?php 

            if((!empty($customer->sVDSSiteURL)) && empty($customer->sVDSSiteStagingURL) )
            {
                $sVDSSiteURL = preg_replace('/vds/', 'vds-staging', $customer->sVDSSiteURL, 1);
                echo $sVDSSiteURL.$customer->sStagingURL;
            }else if(!empty($customer->sVDSSiteStagingURL)){
            echo $customer->sVDSSiteStagingURL;
            }

		 ?>">
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">VDS Dashboard URL</td>
        <td class="formField">
            <input type="text" size="60" name="f_sVDSDashboardURL" value="<?php 
            $isDashboardSite = db_checkDashboardSite($customer->nCustomerID);	
            if(!empty($customer->sVDSDashboardURL)){
            echo $customer->sVDSDashboardURL;
            }else if($isDashboardSite == true){
                $vdsDashboardBaseURL = 'https://vds.issgovernance.com/vds/#/';	
                echo $vdsDashboardBaseURL.base64_encode($customer->nCustomerID);
            }

		 ?>">
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">VDS Dashboard Staging URL</td>
        <td class="formField">
            <input type="text" size="60" name="f_sVDSDashboardStagingURL" value="<?php 


           if(!empty($customer->sVDSDashboardStagingURL)){
            
            echo $customer->sVDSDashboardStagingURL;
            }else if($isDashboardSite == true){
                $vdsDashboardStagingBaseURL = 'https://vds.issgovernance.com/vds-staging/#/';	
                echo $vdsDashboardStagingBaseURL.base64_encode($customer->nCustomerID);	
            }


	 ?>">
        </td>
    </tr>



    <tr class="formRow">
        <td class="formLabel">Domain Redirect</td>
        <td class="formField">
            <input type="text" size="60" name="f_sInvalidDomainRedirect" value="<?php echo $customer->sInvalidDomainRedirect; ?>">
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Update Frequency</td>
        <td class="formField">
            <select name="f_nUpdateFrequencyID" onChange="checkUpdateFrequency(this, processCustomer.f_nMeetingLagDays);">
            <?php
            $arrOptions = getOptionListForTypes($gbl_arrUpdateFrequencyTypes, $customer->nUpdateFrequencyID, false);
            foreach ($arrOptions as $option) {
                echo $option;
            }
            ?>
            </select>
        </td>
    </tr>
            
    <tr class="formRow">
      <td class="formLabel">Pre/Post Meeting Date Disclosure</td>
      <td class="formField">
          <input type="text" size="3" name="f_nMeetingLagDays" value="<?php echo $customer->nMeetingLagDays; ?>" <?php if(!($customer->nUpdateFrequencyID == 4 || $customer->nUpdateFrequencyID == 6)) {print(" disabled ");} ?>>
          &nbsp;&nbsp;&nbsp;(only valid for update frequency "Daily: N days after meeting or Daily: N days pre meeting")
      </td>
    </tr>
  

    <tr class="formRow">
       <td class="formLabel">Significant Meetings/Votes</td>
       <td class="formField">
           <select name="f_nSignificantMeetingTypeID">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrSignificantMeetingsVotesTypes, $customer->nSignificantMeetingTypeID, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
       </td>
    </tr>

     <tr class="formRow" >
      <td class="formLabel">Significant Meetings Workflow Tags</td>
      <td class="formField">
          <textarea rows="4" cols="45" name="f_sWorkFlowSignMeeting" id="f_sWorkFlowSignMeeting"><?php if(!empty($arrUserSignificantMeetings)) {
                                         if(!empty($arrUserSignificantMeetings->WorkflowSignificantMeetingTags))
                                                 {
                                                       echo $arrUserSignificantMeetings->WorkflowSignificantMeetingTags;
                                                 }else echo "";
                        }else echo "";
                        ?></textarea>
      </td>
    </tr>


        
    <tr class="formRow">
      <td class="formLabel">Workflow Tag Logic</td>
      <td class="formField">
         <select name="f_WorkFlowTagLogic" id="f_WorkFlowTagLogic" onchange="javascript:changeInWFTagLogic();"> 
         	<?php
         	
         		while(($arrWorkFlowTagLogic) && $row = sqlsrv_fetch_object($arrWorkFlowTagLogic))
				{
					echo '<option value="'.$row->WorkflowTagLogicID.'"';
					if((!empty($row->WorkflowTagLogicID)) && ($row->WorkflowTagLogicID == $arrUserWorkFlowTagData->WorkflowTagLogicID))
					{
						echo ' selected';
					} 
					echo '>'.$row->WorkflowTagsLogic.'</option>'; 
				}
			?>            
         </select>
      </td>
    </tr>

    <tr class="formRow">
      <td class="formLabel">Workflow Tags</td>
      <td class="formField">
          <textarea rows="4" cols="45" name="f_sWorkFlowTags" id="f_sWorkFlowTags"
		  <?php 
			if((!empty($arrUserWorkFlowTagData)) && isset($arrUserWorkFlowTagData) && isset($arrUserWorkFlowTagData->WorkflowTagLogicID))
			{
				if($arrUserWorkFlowTagData->WorkflowTagLogicID == 1)
				{
					echo ' class = "dropdown-disabled" readOnly="readOnly"';
				}
			}else{
				echo ' class = "dropdown-disabled"  readOnly="readOnly"';
			}
			
		  ?>		  
		  ><?php if(!empty($arrUserWorkFlowTagData)) {
		    			if(!empty($arrUserWorkFlowTagData->WorkflowTags))
						{
							echo $arrUserWorkFlowTagData->WorkflowTags;
						}else echo "";
		    	}else echo "";
		    	?></textarea>   
      </td>
    </tr>

    <tr class="formRow">
    	<td class="formLabel">Workflow Tags Logic Start Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dWorkFlowTagStartDate"  id="f_dWorkFlowTagStartDate"
			    
			    value="<?php if(!empty($arrUserWorkFlowTagData->WorkflowTagStartDate)){ echo date("m/d/Y", strtotime($arrUserWorkFlowTagData->WorkflowTagStartDate)); } else echo ""; ?>"
			    <?php 
					if((!empty($arrUserWorkFlowTagData)) && isset($arrUserWorkFlowTagData) && isset($arrUserWorkFlowTagData->WorkflowTagLogicID))
					{
						if($arrUserWorkFlowTagData->WorkflowTagLogicID == 1)
						{
							echo ' class = "dropdown-disabled" readOnly="readOnly"';
						}
					}else{
						echo ' class = "dropdown-disabled" readOnly="readOnly"';
					}			
		  		?>
			    > 
			    <a id="f_dWorkFlowTagStartDateCal" href="javascript:void(0);" 
			    onclick="if(document.getElementById('f_WorkFlowTagLogic').value != 1){ displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dWorkFlowTagStartDate'); }else { alert('The WorkFlowTag logic is set to \'Not Applied\'. Please change the WorkFlowTag Logic to set the date.'); } " onblur="document.processCustomer.f_dWorkFlowTagStartDate.focus();"
			    tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>
      
  	<tr class="formRow">
    	<td class="formLabel">Website Start Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dWebStartDate" value="<?php echo formatDateCalendar($customer->dWebStartDate); ?>"> 
			    <a id="f_dWebStartDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dWebStartDate');" onblur="document.processCustomer.f_dWebStartDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>

  	<tr class="formRow">
    	<td class="formLabel">Website End Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dWebEndDate" value="<?php echo formatDateCalendar($customer->dWebEndDate); ?>">
			    <a id="f_dWebEndDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dWebEndDate');" onblur="document.processCustomer.f_dWebEndDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>

  	<tr class="formRow">
    	<td class="formLabel">Toolbox Start Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dStartDate" value="<?php echo formatDateCalendar($customer->dStartDate); ?>"> 
			    <a id="f_dStartDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dStartDate');" onblur="document.processCustomer.f_dStartDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>

  	<tr class="formRow">
    	<td class="formLabel">Toolbox End Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dEndDate" value="<?php echo formatDateCalendar($customer->dEndDate); ?>">
			    <a id="f_dEndDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dEndDate');" onblur="document.processCustomer.f_dEndDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>
  	<tr class="formRow">
    	  <td class="formLabel">Rollover Date</td>
		    <td class="formField">
			      <input type="text" size="10" name="f_dRolloverDate" value="<?php echo formatDateCalendar($customer->dRolloverDate); ?>">
			      <a id="f_dRolloverDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dRolloverDate');" onblur="document.processCustomer.f_dRolloverDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			  </td>
      </tr>
      <tr class="formRow">
    	  <td class="formLabel">Rollover Time (U.S. Eastern)</td>
		    <td class="formField">
            <input type="text" size="10" name="f_dRolloverTime" pattern="([01]?[0-9]{1}|2[0-3]{1}):[0-5]{1}[0-9]{1}:[0-5]{1}[0-9]{1}" title="HH:mm:ss" value="<?php echo $customer->dRolloverTime; ?>"> (HH:mm:ss)  
			  </td>
  	  </tr>
        <tr class="formRow">
        <td class="formLabel">Refresh Start Date</td>
        <td class="formField">
            <input type="text" size="10" name="f_dRefreshStartDate" value="<?php echo formatDateCalendar($customer->dRefreshStartDate); ?>">
            <a id="f_dRefreshStartDate" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dRefreshStartDate');" onblur="document.processCustomer.f_dRefreshStartDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
        </td>
    </tr>
    
  	<tr class="formRow">
    	  <td class="formLabel">Lock in data end date</td>
		    <td class="formField">
			      <input type="text" size="10" name="f_LockInEnddate" value="<?php echo formatDateCalendar($customer->LockInEnddate); ?>">
			      <a id="f_LockInEnddate" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_LockInEnddate');" onblur="document.processCustomer.f_LockInEnddate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			  </td>
  	</tr>
    <tr class="formRow">
    <td class="formLabel">Custom Min Date</td>
			<td class="formField">
			    <input type="text" size="10" name="f_dCustomMinDate" value="<?php echo formatDateCalendar($customer->dCustomMinDate); ?>"> 
			    <a id="f_dCustomMinDate" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dCustomMinDate');" onblur="document.processCustomer.f_dCustomMinDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
			</td>
  	</tr>
 

    <tr class="formRow">
        <td class="formLabel">Ticker Country</td>
        <td class="formField">
            <?php
                // For now, U.S. and Canada are the only options so we can afford to hard-code.
                // Eventually we may need to have these options read from a table.
            ?>
            <select name="f_nTickerCountryID">
              <option value="9"<?php print($customer->nTickerCountryID == 9 ? " selected" : ""); ?>>USA</option>
              <option value="11"<?php print($customer->nTickerCountryID == 11 ? " selected" : ""); ?>>Canada</option>
            </select>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">NULL Ticker Override</td>
        <td class="formField">
            <input type="text" size="10" name="f_sNullTickerOverride" value="<?php echo $customer->sNullTickerOverride; ?>">
        </td>
    </tr>
  	<tr class="formRow">
    	  <td class="formLabel">Suppress Re-reg Meetings?</td>
		    <td class="formField">
  			    <select name="f_supressRegYN">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->supressRegYN, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
  	<tr class="formRow">
    	  <td class="formLabel">Allow XML Requests?</td>
		    <td class="formField">
  			    <select name="f_nAllowXmlYN">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nAllowXmlYN, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
   
	<tr class="formRow">
    	  <td class="formLabel">N-PX Note - Funds with no Voting Data</td>
		    <td class="formField">
  			     <input type="text" size="60" name="f_npxNoVoteData" value="<?php echo $customer->sNPxNoteNoVotingData; ?>">
          </select>
			  </td>
  	</tr>

    <tr class="formRow">
    	  <td class="formLabel">Alternate NPX HTML Style?</td>
		    <td class="formField">
  			    <select name="f_nNPxHtmlStyle">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nNPxHtmlStyle, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
  	<tr class="formRow">
    	  <td class="formLabel">Enable PHP Site URL Access?</td>
		    <td class="formField">
  			     <?php $sChecked =  ($customer->nRestrictLegacySiteYN) ? 'checked' :''; ?> 
  			     <!--input type="checkbox" name="f_RestrictLegacySiteYN" value="1" <?php //echo $sChecked; ?>-->
  			     <select name="f_RestrictLegacySiteYN">
                <?php
                $arrOptions = getOptionListForRestrictAccess($gbl_arrYesNoTypesReversed, $customer->nRestrictLegacySiteYN);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
      <tr class="formRow">
    	  <td class="formLabel">Enable Java  Site URL Access?</td>
		    <td class="formField">
  			     <?php $sChecked =  ($customer->nRestrictJavaSiteYN) ? 'checked' :''; ?> 
  			     <!--input type="checkbox" name="f_RestrictJavaSiteYN" value="1" <?php //echo $sChecked; ?>-->
  			     <select name="f_RestrictJavaSiteYN">
                <?php
                $arrOptions = getOptionListForRestrictAccess($gbl_arrYesNoTypesReversed, $customer->nRestrictJavaSiteYN);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
    <tr class="dataHeaderRow">
    	<th colspan="2" class="dataHeaderCell">VDS Authentication</th>
    </tr>

    <tr class="formRow">
    	  <td class="formLabel">Enable Authentication Live?</td>
		    <td class="formField">
  			    <select name="f_nEnableAuthLive">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nEnableAuthLive, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>

    <tr class="formRow">
    	  <td class="formLabel">Enable Authentication Staging?</td>
		    <td class="formField">
  			    <select name="f_nEnableAuthStaging">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nEnableAuthStaging, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>
    <tr class="formRow">
    	<td class="formLabel">OrganizationID</td>
		  <td class="formField">
        <table style="padding:3px">
          <tr>
            <td>
              <select id="f_arrOrganizationIDRemove" name="f_arrOrganizationIDRemove[]" multiple size="<?php echo sizeof($gbl_arrOrganizationId) ?>" style="width:auto;min-width:200px;" onDblClick="move(this.form.f_arrOrganizationIDRemove,this.form.f_arrOrganizationIDAdd)">
                <?php              
                $arrOptions = getPickList($gbl_arrOrganizationId, $customer->arrHiddenOrganizationId, false);                
                foreach ($arrOptions as $option) {
                  echo $option;
                }
                ?>
              </select>
            </td>
            <td>
              <input type="button" onClick="move(this.form.f_arrOrganizationIDAdd,this.form.f_arrOrganizationIDRemove)" value="<<" />
              <br />
              <input type="button" onClick="move(this.form.f_arrOrganizationIDRemove,this.form.f_arrOrganizationIDAdd)" value=">>" />
            </td>
            <td>
              <select id="f_arrOrganizationIDAdd" name="f_arrOrganizationIDAdd[]" multiple size="<?php echo sizeof($gbl_arrOrganizationId)?>" style="width:auto;min-width:200px;" onDblClick="move(this.form.f_arrOrganizationIDAdd,this.form.f_arrOrganizationIDRemove)">
                <?php              
                $arrOptions = getPickList($gbl_arrOrganizationId, $customer->arrDisplayedOrganizationId, true);
                if ( !empty($arrOptions) ) {
                  foreach ($arrOptions as $option) {
                    echo $option;
                  }
                }
               
                ?>              
              </select>
            </td>
            <td>
              <input type="button" name="btnSRUp" id="btnSRUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrOrganizationIDAdd, 'up');" value="Up" />
              <br />
              <input type="button" name="btnSRDown" id="btnSRDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrOrganizationIDAdd, 'down');" value="Down" />
            </td>
          </tr>
        </table>
			</td>
  	</tr>         
    <tr class="dataHeaderRow">
    	<th colspan="2" class="dataHeaderCell">PHP Search Page </th>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Allowable Search Types</td>
        <td class="formField">
            <?php
            $arrOptions = getCheckboxesForTypes($gbl_arrAllowableSearchTypes, $customer->arrAllowableSearchTypes, 'f_arrAllowableSearchTypes[]', '');
            foreach ($arrOptions as $option) {
                echo $option;
            }
            ?>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Display Fund Text</td>
        <td class="formField">
            <select name="f_nDisplayFundSearchText">
            <?php
            $arrOptions = getOptionListForTypes($gbl_arrDisplayFundTextTypes, $customer->nDisplayFundSearchText, false);
            foreach ($arrOptions as $option) {
                echo $option;
            }
            ?>
            </select>
        </td>
    </tr>

    <tr class="dataHeaderRow">
    	<th colspan="2" class="dataHeaderCell">Meeting List Page</th>
    </tr>
    <tr class="formRow">
    	  <td class="formLabel">Enable Meeting List Access?</td>
		    <td class="formField">
  			    <select name="f_nEnableMeetingListAccess">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nEnableMeetingListAccess, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
			  </td>
  	</tr>

  	<tr class="formRow">
    	<td class="formLabel">PHP Search Results Columns</td>
		  <td class="formField">
        <table style="padding:3px">
          <tr>
            <td>
              <select id="f_arrSearchResultsColumnsTypesRemove" name="f_arrSearchResultsColumnsTypesRemove[]" multiple size="<?php sizeof($gbl_arrAllSearchResultsColumnsTypes) ?>" style="width:150px" onDblClick="move(this.form.f_arrSearchResultsColumnsTypesRemove,this.form.f_arrSearchResultsColumnsTypesAdd)">
                <?php               
                $arrOptions = getPickList($gbl_arrAllSearchResultsColumnsTypes, $customer->arrHiddenSearchResultsColumnsTypes, false);
                foreach ($arrOptions as $option) {
                  echo $option;
                }
                ?>
              </select>
            </td>
            <td>
              <input type="button" onClick="move(this.form.f_arrSearchResultsColumnsTypesAdd,this.form.f_arrSearchResultsColumnsTypesRemove)" value="<<" />
              <br />
              <input type="button" onClick="move(this.form.f_arrSearchResultsColumnsTypesRemove,this.form.f_arrSearchResultsColumnsTypesAdd)" value=">>" />
            </td>
            <td>
              <select id="f_arrSearchResultsColumnsTypesAdd", name="f_arrSearchResultsColumnsTypesAdd[]" multiple size="<?php sizeof($gbl_arrAllSearchResultsColumnsTypes)?>" style="width:260px" onDblClick="move(this.form.f_arrSearchResultsColumnsTypesAdd,this.form.f_arrSearchResultsColumnsTypesRemove)">>
                <?php               
                $arrOptions = getPickList($gbl_arrAllSearchResultsColumnsTypes, $customer->arrDisplayedSearchResultsColumnsTypes, true);
                if ( !empty($arrOptions) ) {
                  foreach ($arrOptions as $option) {
                    echo $option;
                  }
                }
               /*else  {
                  echo "<option value=''>Selection order determines display order</option>";
                }*/
                ?>              
              </select>
            </td>
            <td>
              <input type="button" name="btnSRUp" id="btnSRUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrSearchResultsColumnsTypesAdd, 'up');" value="Up" />
              <br />
              <input type="button" name="btnSRDown" id="btnSRDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrSearchResultsColumnsTypesAdd, 'down');" value="Down" />
            </td>
          </tr>
        </table>
			</td>
  	</tr>

    <tr class="formRow">
    	<td class="formLabel">Java Meeting List Columns</td>
		  <td class="formField">
        <table style="padding:3px">
          <tr>
            <td>
              <select id="f_arrMeetingListColumnsTypesRemove" name="f_arrMeetingListColumnsTypesRemove[]" multiple size="<?php sizeof($gbl_arrAllMeetingListColumnsTypes) ?>" style="width:150px" onDblClick="move(this.form.f_arrMeetingListColumnsTypesRemove,this.form.f_arrMeetingListColumnsTypesAdd)">
                <?php              
                $arrOptions = getPickList($gbl_arrAllMeetingListColumnsTypes, $customer->arrHiddenMeetingListColumnsTypes, false);                
                foreach ($arrOptions as $option) {
                  echo $option;
                }
                ?>
              </select>
            </td>
            <td>
              <input type="button" onClick="move(this.form.f_arrMeetingListColumnsTypesAdd,this.form.f_arrMeetingListColumnsTypesRemove)" value="<<" />
              <br />
              <input type="button" onClick="move(this.form.f_arrMeetingListColumnsTypesRemove,this.form.f_arrMeetingListColumnsTypesAdd)" value=">>" />
            </td>
            <td>
              <select id="f_arrMeetingListColumnsTypesAdd", name="f_arrMeetingListColumnsTypesAdd[]" multiple size="<?php sizeof($gbl_arrAllMeetingListColumnsTypes)?>" style="width:260px" onDblClick="move(this.form.f_arrMeetingListColumnsTypesAdd,this.form.f_arrMeetingListColumnsTypesRemove)">
                <?php              
                $arrOptions = getPickList($gbl_arrAllMeetingListColumnsTypes, $customer->arrDisplayedMeetingListColumnsTypes, true);
                if ( !empty($arrOptions) ) {
                  foreach ($arrOptions as $option) {
                    echo $option;
                  }
                }
               
                ?>              
              </select>
            </td>
            <td>
              <input type="button" name="btnSRUp" id="btnSRUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrMeetingListColumnsTypesAdd, 'up');" value="Up" />
              <br />
              <input type="button" name="btnSRDown" id="btnSRDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrMeetingListColumnsTypesAdd, 'down');" value="Down" />
            </td>
          </tr>
        </table>
			</td>
  	</tr>

    <tr class="formRow">
    	<td class="formLabel">Java Staging Meeting List Columns</td>
		  <td class="formField">
        <table style="padding:3px">
          <tr>
            <td>
              <select id="f_arrStagingMeetingListColumnsTypesRemove" name="f_arrStagingMeetingListColumnsTypesRemove[]" multiple size="<?php sizeof($gbl_arrAllStagingMeetingListColumnsTypes) ?>" style="width:150px" onDblClick="move(this.form.f_arrStagingMeetingListColumnsTypesRemove,this.form.f_arrStagingMeetingListColumnsTypesAdd)">
                <?php              
                $arrOptions = getPickList($gbl_arrAllStagingMeetingListColumnsTypes, $customer->arrHiddenStagingMeetingListColumnsTypes, false);                
                foreach ($arrOptions as $option) {
                  echo $option;
                }
                ?>
              </select>
            </td>
            <td>
              <input type="button" onClick="move(this.form.f_arrStagingMeetingListColumnsTypesAdd,this.form.f_arrStagingMeetingListColumnsTypesRemove)" value="<<" />
              <br />
              <input type="button" onClick="move(this.form.f_arrStagingMeetingListColumnsTypesRemove,this.form.f_arrStagingMeetingListColumnsTypesAdd)" value=">>" />
            </td>
            <td>
              <select id="f_arrStagingMeetingListColumnsTypesAdd", name="f_arrStagingMeetingListColumnsTypesAdd[]" multiple size="<?php sizeof($gbl_arrAllStagingMeetingListColumnsTypes)?>" style="width:260px" onDblClick="move(this.form.f_arrStagingMeetingListColumnsTypesAdd,this.form.f_arrStagingMeetingListColumnsTypesRemove)">
                <?php              
                $arrOptions = getPickList($gbl_arrAllStagingMeetingListColumnsTypes, $customer->arrDisplayedStagingMeetingListColumnsTypes, true);
                if ( !empty($arrOptions) ) {
                  foreach ($arrOptions as $option) {
                    echo $option;
                  }
                }
               
                ?>              
              </select>
            </td>
            <td>
              <input type="button" name="btnSRUp" id="btnSRUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrStagingMeetingListColumnsTypesAdd, 'up');" value="Up" />
              <br />
              <input type="button" name="btnSRDown" id="btnSRDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrStagingMeetingListColumnsTypesAdd, 'down');" value="Down" />
            </td>
          </tr>
        </table>
			</td>
  	</tr>

    <tr class="formRow">
        <td class="formLabel">Default Sort</td>
        <td class="formField">
            <select name="f_nDefaultSort">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrSortTypes, $customer->nDefaultSort, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Meeting Universe</td>
        <td class="formField">
            <select name="f_nMeetingUniverseID">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrMeetingUniverseTypes, $customer->nMeetingUniverseID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
            &nbsp;&nbsp;
            <a class="dataLink" href="javascript: openMeetingUniverseOptionsWindow(document.processCustomer)">Open selected meeting universe window ></a>
            <input type="hidden" id="f_sCountryCodes" name="f_sCountryCodes" value="<?php echo $customer->sCountryCodes; ?>">
            <input type="hidden" id="f_sIndexTypes" name="f_sIndexTypes" value="<?php echo $customer->sIndexTypes; ?>">
            <input type="hidden" id="f_sCustomSecurityFileName" name="f_sCustomSecurityFileName" value="">
            <input type="hidden" id="f_sSIDReplaceAppend" name="f_sSIDReplaceAppend" value="">
        </td>
    </tr>

  	<tr class="formRow">
    	  <td class="formLabel">Show Future Meetings?</td>
		    <td class="formField">
  			    <select name="f_nFutureMeetingYN" onChange="javascript: if (this.selectedIndex == 0) { f_nFutureVotesYN.selectedIndex = 0; f_nFutureVotesYN.disabled = true; } else { f_nFutureVotesYN.disabled = false; }" <?php if($customer->nUpdateFrequencyID != 0) print(" disabled ");?>>
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nFutureMeetingYN, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
          </select>
          &nbsp;&nbsp;&nbsp;(only valid for update frequency "Daily: day after meeting")
			  </td>
  	</tr>

    <tr class="formRow">
        <td class="formLabel">Aggregate Meetings?</td>
        <td class="formField">
            <select name="f_nAggregateMeetingsYN" onChange="javascript: if (this.selectedIndex == 0) { f_nFundLinksYN.selectedIndex = 0; f_nFundLinksYN.disabled = true; } else { f_nFundLinksYN.disabled = false; }">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nAggregateMeetingsYN, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

   <tr class="formRow">
        <td class="formLabel">Hyperlink Fund Names?</td>
        <td class="formField">
            <select name="f_nFundLinksYN" <?php if($customer->nAggregateMeetingsYN == 0) print(" disabled ");?>>
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nFundLinksYN, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
            &nbsp;&nbsp;&nbsp;(only valid when Aggregate Meetings? = Yes)
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Navigation Type</td>
        <td class="formField">
            <select name="f_nNavTypeID">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrNavTypes, $customer->nNavTypeID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="dataHeaderRow">
    	<th colspan="2" class="dataHeaderCell">Vote Details Page</th>
    </tr>

  	<tr class="formRow">
    	<td class="formLabel">Vote Details Columns</td>
		  <td class="formField">
        <table style="padding:3px">
          <tr>
            <td>
              <select id="f_arrVoteDetailColumnsTypesRemove" name="f_arrVoteDetailColumnsTypesRemove[]" multiple size="<?php sizeof($gbl_arrAllVoteDetailColumnsTypes) ?>" style="width:150px" onDblClick="move(this.form.f_arrVoteDetailColumnsTypesRemove,this.form.f_arrVoteDetailColumnsTypesAdd)">
                <?php               
                $arrOptions = getPickList($gbl_arrAllVoteDetailColumnsTypes, $customer->arrHiddenVoteDetailColumnsTypes, false);
                foreach ($arrOptions as $option) {
                  echo $option;
                }
                ?>
              </select>
            </td>
            <td>
              <input type="button" onClick="move(this.form.f_arrVoteDetailColumnsTypesAdd,this.form.f_arrVoteDetailColumnsTypesRemove)" value="<<" />
              <br />
              <input type="button" onClick="move(this.form.f_arrVoteDetailColumnsTypesRemove,this.form.f_arrVoteDetailColumnsTypesAdd)" value=">>" />
            </td>
            <td>
              <select id="f_arrVoteDetailColumnsTypesAdd", name="f_arrVoteDetailColumnsTypesAdd[]" multiple size="<?php sizeof($gbl_arrAllVoteDetailColumnsTypes)?>" style="width:260px" onDblClick="move(this.form.f_arrVoteDetailColumnsTypesAdd,this.form.f_arrVoteDetailColumnsTypesRemove)">
                <?php               
                $arrOptions = getPickList($gbl_arrAllVoteDetailColumnsTypes, $customer->arrDisplayedVoteDetailColumnsTypes, true);
                if ( !empty($arrOptions) ) {
                  foreach ($arrOptions as $option) {
                    echo $option;
                  }
                }
                else
                {
                  echo "<option value=''>Selection order determines display order</option>";
                }
                ?>               
              </select>
            </td>
            <td>
              <input type="button" name="btnVDUp" id="btnVDUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrVoteDetailColumnsTypesAdd, 'up');" value="Up" />
              <br />
              <input type="button" name="btnVDDown" id="btnVDDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrVoteDetailColumnsTypesAdd, 'down');" value="Down" />
            </td>
          </tr>
        </table>
			</td>
  	</tr>


    <tr class="formRow">
        <td class="formLabel">Rationale Display Type</td>
        <td class="formField">
            <select name="f_nDisplayNotesTypeID" >
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrDisplayNotesTypes, $customer->nDisplayNotesTypeID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="formRow">
		<td class="formLabel">Rationale Disclosure</td>
        <td class="formField">
            <select name="f_nRationaleDisclosureTypeID[]" multiple="multiple">
                <?php
                $arrOptions = getMultipleOptionListForTypes($gbl_arrRationaleDisclosureTypes, $customer->arrDisplayedRatDiscTypeIDColumnsTypes, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Rationale Meeting Start Date</td>
        <td class="formField">
            <input type="text" size="10" name="f_dRationaleMeetingDate" value="<?php echo formatDateCalendar($customer->dRationaleMeetingStartDate); ?>">
            <a id="f_dRationaleMeetingDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dRationaleMeetingDate');" onblur="document.processCustomer.f_dRationaleMeetingDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
        </td>
    </tr>


    <tr class="formRow">
        <td class="formLabel">Notes Placement</td>
        <td class="formField">
            <select name="f_nDisplayNotesPlacementID">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrDisplayNotesPlacementTypes, $customer->nDisplayNotesPlacementID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Ballot Universe</td>
        <td class="formField">
            <select name="f_nBallotUniverseID" onChange="javascript: if (this.selectedIndex == 0 || this.selectedIndex == 1) { f_nFilterAgendaYN.selectedIndex = 0; f_nFilterAgendaYN.disabled = true; } else { f_nFilterAgendaYN.disabled = false; }">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrBallotUniverseTypes, $customer->nBallotUniverseID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

  	<tr class="formRow">
    	  <td class="formLabel">Filter Agenda Items?</td>
		    <td class="formField">
  			    <select name="f_nFilterAgendaYN" onChange="javascript: if (f_nBallotUniverseID.selectedIndex == 0 || f_nBallotUniverseID.selectedIndex == 1) { this.selectedIndex = 0; this.disabled = true; } else { this.disabled = false; }" <?php if($customer->nBallotUniverseID == 0 || $customer->nBallotUniverseID == 1) print(" disabled ");?>>
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nFilterAgendaYN, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
            </select>
            &nbsp;&nbsp;&nbsp;(only valid when Ballot Universe is "Against management" or "Against policy")
			  </td>
  	</tr>

  	<tr class="formRow">
    	  <td class="formLabel">Distinguish TNA from DNV?</td>
		    <td class="formField">
  			    <select name="f_nTNAYN">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nTNAYN, false);
                foreach ($arrOptions as $option)
                {
                	echo $option;
                }
                ?>
            </select>
			  </td>
  	</tr>

    <tr class="formRow">
        <td class="formLabel">Show Future Votes?</td>
        <td class="formField">
            <select name="f_nFutureVotesYN" <?php if($customer->nUpdateFrequencyID != 0 || $customer->nFutureMeetingYN == 0) print(" disabled ");?>>
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nFutureVotesYN, false);
                foreach ($arrOptions as $option)
                {
                    echo $option;
                }
                ?>
            </select>
            &nbsp;&nbsp;&nbsp;(only valid when Show Future Meetings? = Yes)
        </td>
    </tr>

    <!--Split/Echo vote override -->
    <tr class="formRow">
        <td class="formLabel">Split/Echo Vote Override</td>
        <td class="formField">
            <select name="f_nVotecastId">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrVoteCastTypes, $customer->sVoteCastId, false);
                foreach ($arrOptions as $option)
                {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>
 




    <tr class="dataHeaderRow">
        <th colspan="2" class="dataHeaderCell">VDS Dashboard Site Configuration</th>
    </tr>

        <tr class="formRow">
        <td class="formLabel">Website Filters?</td>
            <td class="formField">
                <select name="f_nSearchFilterColumnsTypeID[]" multiple="multiple">  
                    <?php
                    $arrOptions = getMultipleOptionListForTypes($gbl_arrAllSearchFilterColumnsTypes, $customer->arrDisplayedSearchFilterColumnsTypes, false);
                    foreach ($arrOptions as $option) {
                        echo $option;
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Include Graphics?</td>
          <td class="formField">
            <table style="padding:3px">
              <tr>
                <td>
                  <select id="f_arrGraphicalMetricsTypesRemove" name="f_arrGraphicalMetricsTypesRemove[]" multiple size="<?php sizeof($gbl_arrAllGraphicalMetricsTypes) ?>" style="width:150px" onDblClick="move(this.form.f_arrGraphicalMetricsTypesRemove,this.form.f_arrGraphicalMetricsTypesAdd)">
                    <?php               
                    $arrOptions = getPickList($gbl_arrAllGraphicalMetricsTypes, $customer->arrHiddenGraphicalMetricsTypes, false);
                    foreach ($arrOptions as $option) {
                      echo $option;
                    }
                    ?>
                  </select>
                </td>
                <td>
                  <input type="button" onClick="move(this.form.f_arrGraphicalMetricsTypesAdd,this.form.f_arrGraphicalMetricsTypesRemove)" value="<<" />
                  <br />
                  <input type="button" onClick="move(this.form.f_arrGraphicalMetricsTypesRemove,this.form.f_arrGraphicalMetricsTypesAdd)" value=">>" />
                </td>
                <td>
                  <select id="f_arrGraphicalMetricsTypesAdd", name="f_arrGraphicalMetricsTypesAdd[]" multiple size="<?php sizeof($gbl_arrAllGraphicalMetricsTypes)?>" style="width:260px" onDblClick="move(this.form.f_arrGraphicalMetricsTypesAdd,this.form.f_arrGraphicalMetricsTypesRemove)">>
                    <?php               
                    $arrOptions = getPickList($gbl_arrAllGraphicalMetricsTypes, $customer->arrDisplayedGraphicalMetricsTypes, true);
                    if ( !empty($arrOptions) ) {
                      foreach ($arrOptions as $option) {
                        echo $option;
                      }
                    }
                  /*else  {
                      echo "<option value=''>Selection order determines display order</option>";
                    }*/
                    ?>              
                  </select>
                </td>
                <td>
                  <input type="button" name="btnSRUp" id="btnSRUp" class="btnUpDown" onClick="moveUpDown(this.form.f_arrGraphicalMetricsTypesAdd, 'up');" value="Up" />
                  <br />
                  <input type="button" name="btnSRDown" id="btnSRDown" class="btnUpDown" onClick="moveUpDown(this.form.f_arrGraphicalMetricsTypesAdd, 'down');" value="Down" />
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Applied Filter?</td>
                    <td class="formField">
                            <select name="f_nAppliedFilter">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nAppliedFilter, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Interactive Graphs?</td>
                    <td class="formField">
                            <select name="f_nInteractiveGraphs">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nInteractiveGraphs, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Export Meeting List?</td>
                    <td class="formField">
                            <select name="f_nMeetingListDownload">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nMeetingListDownload, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>




        <tr class="formRow">
          <td class="formLabel">Staging Export Meeting List?</td>
                    <td class="formField">
                            <select name="f_nStagingMeetingListDownload">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nStagingMeetingListDownload, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>





<tr class="formRow">
          <td class="formLabel">Export Meeting Details?</td>
                    <td class="formField">
                            <select name="f_nMeetingDetailDownload">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nMeetingDetailDownload, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>


<tr class="formRow">
          <td class="formLabel">Staging Export Meeting Details?</td>
                    <td class="formField">
                            <select name="f_nStagingMeetingDetailDownload">
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nStagingMeetingDetailDownload, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>




        <tr class="formRow">
          <td class="formLabel">Export Graphics?</td>
                    <td class="formField">
                            <select name="f_nPrintCSS"  onChange="javascript: if (this.selectedIndex == 0) { f_nPrintLogo.selectedIndex = 0;  f_nPrintHeader.selectedIndex = 0; f_nPrintPolicySection.selectedIndex = 0;   f_nPrintLogo.disabled = true;   f_nPrintHeader.disabled = true; f_nPrintPolicySection.disabled = true;  } else { f_nPrintLogo.disabled = false;   f_nPrintHeader.disabled = false; f_nPrintPolicySection.disabled = false; }" >
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nPrintCSS, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>



        <tr class="formRow">
          <td class="formLabel">Staging Export Graphics?</td>
                    <td class="formField">
                            <select name="f_nStagingPrintCSS"  onChange="javascript: if (this.selectedIndex == 0) { f_nPrintLogo.selectedIndex = 0;  f_nPrintHeader.selectedIndex = 0; f_nPrintPolicySection.selectedIndex = 0;   f_nPrintLogo.disabled = true;   f_nPrintHeader.disabled = true; f_nPrintPolicySection.disabled = true;  } else { f_nPrintLogo.disabled = false;   f_nPrintHeader.disabled = false; f_nPrintPolicySection.disabled = false; }" >
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nStagingPrintCSS, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>



        <tr class="formRow">
          <td class="formLabel">Include Logo?</td>
                    <td class="formField">
                            <select name="f_nPrintLogo" <?php if( $customer->nPrintCSS == 0 || $customer->nStagingPrintCSS == 0 ) print(" disabled ");?>>
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nPrintLogo, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Include Header?</td>
                    <td class="formField">
                            <select name="f_nPrintHeader" <?php if($customer->nPrintCSS == 0 || $customer->nStagingPrintCSS == 0) print(" disabled ");?>>
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nPrintHeader, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>

        <tr class="formRow">
          <td class="formLabel">Include Policy Section?</td>
                    <td class="formField">
                            <select name="f_nPrintPolicySection" <?php if($customer->nPrintCSS == 0 || $customer->nStagingPrintCSS == 0) print(" disabled ");?>>
               <?php
                $arrOptions = getOptionListForTypes($gbl_arrYesNoTypes, $customer->nPrintPolicySection, false);
                foreach ($arrOptions as $option)
                {
                        echo $option;
                }
                ?>
            </select>
                          </td>
        </tr>

	
  	<tr class="formDataTitleRow">
	    	<td colspan="2" class="formDataTitle">Staging</td>
  	</tr>

    <tr class="formRow">
        <td class="formLabel">Staging Update Frequency</td>
        <td class="formField">
            <select name="f_nStagingUpdateFrequencyID" onChange="checkUpdateFrequency(this, processCustomer.f_nStagingMeetingLagDays);">
                <?php
                $arrOptions = getOptionListForTypes($gbl_arrUpdateFrequencyTypes, $customer->nStagingUpdateFrequencyID, false);
                foreach ($arrOptions as $option) {
                    echo $option;
                }
                ?>
            </select>
        </td>
    </tr>

    <tr class="formRow">
      <td class="formLabel">Pre/Post Staging Meeting Date Disclosure</td>
      <td class="formField">
          <input type="text" size="3" name="f_nStagingMeetingLagDays" value="<?php echo $customer->nStagingMeetingLagDays; ?>" <?php if(!($customer->nStagingUpdateFrequencyID == 4  || $customer->nStagingUpdateFrequencyID == 6)) print(" disabled ");?>>
          &nbsp;&nbsp;&nbsp;(only valid for update frequency "Daily: N days after meeting or Daily: N days pre meeting")
      </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Staging Start Date</td>
        <td class="formField">
            <input type="text" size="10" name="f_dStagingStartDate" value="<?php echo formatDateCalendar($customer->dStagingStartDate); ?>">
            <a id="f_dStagingStartDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dStagingStartDate');" onblur="document.processCustomer.f_dStagingStartDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
        </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Staging End Date</td>
        <td class="formField">
            <input type="text" size="10" name="f_dStagingEndDate" value="<?php echo formatDateCalendar($customer->dStagingEndDate); ?>">
            <a id="f_dStagingEndDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dStagingEndDate');" onblur="document.processCustomer.f_dStagingEndDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
          </td>
    </tr>

    <tr class="formRow">
        <td class="formLabel">Staging Rollover Date</td>
        <td class="formField">
            <input type="text" size="10" name="f_dStagingRolloverDate" value="<?php echo formatDateCalendar($customer->dStagingRolloverDate); ?>">
            <a id="f_dStagingRolloverDateCal" href="javascript:void(0);" onclick="displayCal(getOffsets(this).get('right'), getOffsets(this).get('top'),'processCustomer.f_dStagingRolloverDate');" onblur="document.processCustomer.f_dStagingRolloverDate.focus();" tabindex="-1"><img src="images/cal.gif" border="0"></a>
        </td>
    </tr>
    <tr class="formRow">
    	  <td class="formLabel">Staging Rollover Time (U.S. Eastern)</td>
		    <td class="formField">
            <input type="text" size="10" name="f_dStagingRolloverTime" pattern="([01]?[0-9]{1}|2[0-3]{1}):[0-5]{1}[0-9]{1}:[0-5]{1}[0-9]{1}" title="HH:mm:ss" value="<?php echo $customer->dStagingRolloverTime; ?>"> (HH:mm:ss)
			  </td>
  	  </tr>

    <tr class="formRow">
        <td class="formLabel">Staging Allowable Search Types</td>
        <td class="formField">
            <?php
                $arrOptions = getCheckboxesForTypes($gbl_arrAllowableSearchTypes, $customer->arrStagingAllowableSearchTypes, 'f_arrStagingAllowableSearchTypes[]', '');
                foreach ($arrOptions as $option) {
                    echo $option;
                }
            ?>
        </td>
    </tr>

  	<tr class="formRow">
    	  <td class="formLabel">&nbsp;</td>
     		<td class="formFieldButtons">
     			<input type="submit" name="" value="Save Changes" onClick="selectAll(this.form.f_arrSearchResultsColumnsTypesAdd); selectAll(this.form.f_arrVoteDetailColumnsTypesAdd);selectAll(this.form.f_arrGraphicalMetricsTypesAdd);selectAll(this.form.f_arrMeetingListColumnsTypesAdd);selectAll(this.form.f_arrStagingMeetingListColumnsTypesAdd);selectAll(this.form.f_arrOrganizationIDAdd);">
     			<input type="button" name="" value="Cancel" onClick="window.history.back();">
     		</td>
  	</tr>
  	
	</table>

<!--	</form>
</div> -->



<!--  <form name="processCustomerSOP" method="POST" action="<?php echo LINK_PROCESS_CUSTOMER; ?>" >
-->
<div class="dataSection">
    <div class="dataSectionTitleBox">
        <div class="dataSectionTitle">SOPs</div>
    </div>
   	<ul class="dataSectionLinks">
   		<li class="dataSectionLink">
          <a href="javascript:void(0);" onclick="javascript: window.open('<?php echo LINK_CHOOSE_SOP; ?>?formname=processCustomer&fieldname=f_nCustSOP&type=0&sopid=0&arrayname=f_arrCustSOP', 'viewSopChooser', 'width=800,height=400,resizable=yes,scrollbars=yes,menubar=yes');" class="dataLink">Link an SOP</a>
      </li>
   	</ul> 
    <table name="CustSOPTable" id="CustSOPTable" class="dataTable" cellspacing="1">
        <tr class="dataHeaderRow">
        	<th class="dataHeaderCell">&nbsp;</th>
    		<th class="dataHeaderCell">SOP ID</th>
    		<th class="dataHeaderCell">13F Number</th>
    		<th class="dataHeaderCell">SOP Registrant Name</th>
        </tr>
        

<?php
$iCount = 0;
$arrSOPs = $customer->arrSOPs;
if(!empty($arrSOPs))
{
	foreach($arrSOPs as $sop)
	{
		$dataRowClass = 'dataRowA';
		if ( ($iCount % 2) > 0 )
		{
	  		$dataRowClass = 'dataRowB';
		}

?>
	<tr class="<?php echo $dataRowClass; ?>">
         <input type="hidden" name="f_arrDBCustSOP_IDs[]" value="<?php echo $subadv->nSubadvisorID;; ?>">     	
         <td class="dataCellLink"><input type="checkbox" name="f_arrCustSOP_IDs[]" value=<?php echo $sop->nICAID; ?> checked></td>
		<td class="dataCell"><?php echo $sop->nICAID; ?></td>
		<td class="dataCell"><?php echo $sop->sICAFileNum; ?></td>
		<td class="dataCell"><?php echo $sop->sRegistrantName; ?></td>
	</tr>
<?php
		$iCount++;
	}  // end ($arrSOPs as $sop)
}
?>
       		<tr>
        	<td class="dataCellLink" id="f_nCustSOP_Checkbox"></td>
            <td class="dataCell" id="f_nCustSOP_ID"></td>
            <td class="dataCell" id="f_nCustSOP_13F"></td>
        	<td class="dataCell" id="f_nCustSOP_Name"></td>                    
    	</tr>    
       
              
    </table>
	
</div>
	<input type="submit" name="" value="Save Changes" onClick="">
	<input type="button" name="" value="Cancel" onClick="window.history.back();">
	<br/><br/>	

 </form> 
</div>


<?php
include("adminFooter.php");
?>
