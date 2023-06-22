<?php
/**
 *	Instrument Query Reports edit page.
 */



// Check user can edit Instrument Query reports and verify the report exists and is an Instrument
// Query report.
// Redirect to main reports page if not.
$reportID = $_GET['report_id'];
$listReports = $module->getReportList();
if ( ! $module->isReportEditable( 'instrument' ) ||
     ! isset( $listReports[$reportID] ) || $listReports[$reportID]['type'] != 'instrument' )
{
	header( 'Location: ' . $module->getUrl( 'reports.php' ) );
	exit;
}
$reportConfig = $listReports[$reportID];
$reportData = $module->getReportData( $reportID );



// Handle form submissions.
if ( ! empty( $_POST ) )
{
	// Validate data
	$validData = true;
	$validationMsg = '';
	foreach ( $_POST['query_form'] as $i => $formName )
	{
		if ( $formName == '' &&
		     ( $i == 0 || $_POST['query_form_alias'][$i] != '' ||
		       $_POST['query_form_on'][ $i - 1 ] != '' ) )
		{
			$validationMsg =
					'Form cannot be empty if the first entry or if alias/condition specified.';
		}
	}
	if ( isset( $_SERVER['HTTP_X_RC_ADVREP_INSTQUERYCHK'] ) )
	{
		header( 'Content-Type: application/json' );
		if ( $validData )
		{
			echo 'true';
		}
		else
		{
			echo json_encode( $validationMsg );
		}
		exit;
	}
	if ( ! $validData )
	{
		exit;
	}

	// Save data
	$module->submitReportConfig( $reportID );
	$reportData = [ 'forms' => [], 'where' => $_POST['query_where'],
	                'orderby' => $_POST['query_orderby'], 'select' => $_POST['query_select'],
	                'nomissingdatacodes' => isset( $_POST['query_nomissingdatacodes'] ) ];
	foreach ( $_POST['query_form'] as $i => $formName )
	{
		if ( $formName == '' )
		{
			continue;
		}
		$reportData['forms'][] = [ 'form' => $formName, 'alias' => $_POST['query_form_alias'][$i],
		                           'on' => ( ( $i == 0 ) ? ''
		                                                 : $_POST['query_form_on'][ $i - 1 ] ) ];
	}
	$module->setReportData( $reportID, $reportData );
	header( 'Location: ' . $module->getUrl( 'reports_edit.php' ) );
	exit;
}



// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$module->writeStyle();

?>
<div class="projhdr">
 Advanced Reports &#8212; Edit Instrument Query Report: <?php echo "$reportID\n"; ?>
</div>
<p style="font-size:11px">
 <a href="<?php echo $module->getUrl( 'reports_edit.php' )
?>" class=""><i class="fas fa-arrow-circle-left fs11"></i> Back to edit reports</a>
</p>
<form method="post" id="queryform">
 <table class="mod-advrep-formtable">
<?php $module->outputReportConfigOptions( $reportConfig ); ?>
  <tr><th colspan="2">Report Definition</th></tr>
  <tr>
   <td>Instruments</td>
   <td>
    <table id="inst-entries-tbl">
     <tr>
      <td style="text-align:left;width:unset"></td>
      <td style="text-align:left;width:unset"><?php
$module->outputInstrumentDropdown( 'query_form[]', $reportData['forms'][0]['form'] ?? '' );
?></td>
      <td style="text-align:left;width:unset">
       <input type="text" name="query_form_alias[]" placeholder="alias (optional)"
              value="<?php echo $module->escapeHTML( $reportData['forms'][0]['alias'] ?? '' ); ?>"
              style="width:250px">
      </td>
     </tr>
<?php
$firstForm = true;
foreach ( $reportData['forms'] as $formData )
{
	if ( $firstForm )
	{
		$firstForm = false;
		continue;
	}
?>
     <tr>
      <td style="text-align:left;width:unset">JOIN</td>
      <td style="text-align:left;width:unset"><?php
	$module->outputInstrumentDropdown( 'query_form[]', $formData['form'] );
?></td>
      <td style="text-align:left;width:unset">
       <input type="text" name="query_form_alias[]" placeholder="alias (optional)"
              value="<?php echo $module->escapeHTML( $formData['alias'] ); ?>" style="width:250px">
      </td>
     </tr>
     <tr>
      <td style="text-align:left;width:unset">ON</td>
      <td colspan="2" style="text-align:left;width:unset">
       <input type="text" name="query_form_on[]" placeholder="condition" style="width:100%"
              value="<?php echo $module->escapeHTML( $formData['on'] ); ?>">
      </td>
     </tr>
<?php
}
?>
     <tr style="display:none">
      <td style="text-align:left;width:unset">JOIN</td>
      <td style="text-align:left;width:unset"><?php
$module->outputInstrumentDropdown( 'query_form[]', '' );
?></td>
      <td style="text-align:left;width:unset">
       <input type="text" name="query_form_alias[]" placeholder="alias (optional)"
              style="width:250px">
      </td>
     </tr>
     <tr style="display:none">
      <td style="text-align:left;width:unset">ON</td>
      <td colspan="2" style="text-align:left;width:unset">
       <input type="text" name="query_form_on[]" placeholder="condition" style="width:100%">
      </td>
     </tr>
    </table>
    <span id="inst-entries-link" style="display:none">
     <a onclick="$('#inst-entries-tbl tr').slice(-2).clone().css('display',''
                    ).insertBefore($('#inst-entries-tbl tr').slice(-2,-1));return false"
        href="#" class=""><i class="fas fa-plus-circle fs12"></i> Add instrument</a>
    </span>
   </td>
  </tr>
  <tr>
   <td>Condition</td>
   <td>
    <input type="text" name="query_where" style="width:100%"
           value="<?php echo $module->escapeHTML( $reportData['where'] ?? '' ); ?>">
   </td>
  </tr>
  <tr>
   <td>Sorting</td>
   <td>
    <input type="text" name="query_orderby" style="width:100%"
           value="<?php echo $module->escapeHTML( $reportData['orderby'] ?? '' ); ?>">
   </td>
  </tr>
  <tr>
   <td>Fields to display</td>
   <td>
    <input type="text" name="query_select" style="width:100%"
           value="<?php echo $module->escapeHTML( $reportData['select'] ?? '' ); ?>">
   </td>
  </tr>
  <tr>
   <td>Hide missing data codes</td>
   <td>
    <input type="checkbox" name="query_nomissingdatacodes"<?php
echo $reportData['nomissingdatacodes'] ? ' checked' : '';
?>>
   </td>
  </tr>
  <tr><td colspan="2">&nbsp;</td></tr>
  <tr>
   <td></td>
   <td>
    <input type="submit" value="Save Report">
   </td>
  </tr>
 </table>
</form>
<script type="text/javascript">
 (function ()
 {
   $('#inst-entries-link').css('display','')
   var vValidated = false
   $('#queryform')[0].onsubmit = function()
   {
     if ( vValidated )
     {
       return true
     }
     $.ajax( { url : '<?php echo $module->getUrl( 'sql_edit.php?report_id=' . $reportID ); ?>',
               method : 'POST',
               data : { sql_query : $('[name=sql_query')[0].value },
                        headers : { 'X-RC-AdvRep-InstQueryChk' : '1' },
                        dataType : 'json',
                        success : function ( result )
                        {
                          if ( result === true )
                          {
                            vValidated = true
                            $('#queryform')[0].submit()
                          }
                          else
                          {
                            var vMsg = 'Invalid instrument query: ' + result
                            $('[name="instrument_query"]')[0].setCustomValidity( vMsg )
                          }
                        }
             } )
     return false
   }
 })()
</script>
<?php

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
