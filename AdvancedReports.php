<?php

namespace Nottingham\AdvancedReports;

class AdvancedReports extends \ExternalModules\AbstractExternalModule
{

	// Show the advanced reports link based on whether the user is able to view or edit any
	// reports. If the user has no access, hide the link.
	function redcap_module_link_check_display( $project_id, $link )
	{
		if ( $this->isReportEditable() )
		{
			return $link;
		}
		$listReports = $this->getReportList();
		foreach ( $listReports as $reportID => $data )
		{
			if ( $this->isReportAccessible( $reportID ) )
			{
				return $link;
			}
		}
		return null;
	}



	// As the REDCap built-in module configuration only contains options for administrators, hide
	// this configuration from all non-administrators.
	function redcap_module_configure_button_display( $project_id )
	{
		return $this->framework->getUser()->isSuperUser() ? true : null;
	}



	// Check if the specified report is accessible by the current user,
	// as determined by the specified access roles.
	function isReportAccessible( $reportID )
	{
		// Load the report config.
		$reportConfig = $this->getReportConfig( $reportID );
		// Always grant access to reports to users who can edit them.
		if ( $this->isReportEditable( $reportConfig['type'] ) )
		{
			return true;
		}
		// Check each allowed role and allow access if the user has the role.
		foreach ( explode( "\n", $reportConfig['roles_access'] ) as $role )
		{
			$role = trim( $role );
			if ( $role === '*' || $role === $this->getUserRole() )
			{
				return true;
			}
		}
		// Don't allow access for remaining users.
		return false;
	}



	// Check if the specified report can be downloaded by the current user,
	// as determined by the download setting and download roles.
	function isReportDownloadable( $reportID )
	{
		// Load the report config.
		$reportConfig = $this->getReportConfig( $reportID );
		// Don't allow downloads if they are deactivated.
		if ( ! isset( $reportConfig['download'] ) || ! $reportConfig['download'] )
		{
			return false;
		}
		// Otherwise, if downloads are activated...
		// Always allow downloads by users who can edit the report.
		if ( $this->isReportEditable( $reportConfig['type'] ) )
		{
			return true;
		}
		// Don't allow downloads by users who cannot access the report.
		if ( ! $this->isReportAccessible( $reportID ) )
		{
			return false;
		}
		// Check each allowed role and allow downloads if the user has the role.
		foreach ( explode( "\n", $reportConfig['roles_download'] ) as $role )
		{
			$role = trim( $role );
			if ( $role === '*' || $role === $this->getUserRole() )
			{
				return true;
			}
		}
		// Don't allow downloads for remaining users.
		return false;
	}



	// Check if the specified report type can be edited by the current user.
	function isReportEditable( $reportType = null )
	{
		// Administrators can edit all reports.
		$isSuperUser = $this->framework->getUser()->isSuperUser();
		$userRights = $this->framework->getUser()->getRights();
		if ( $isSuperUser )
		{
			return true;
		}
		// Don't allow editing by non-administrators without user rights.
		// (in practice, such users probably cannot access the project)
		// SQL reports are never editable by non-administrators.
		if ( $userRights === null || $reportType == 'sql' )
		{
			return false;
		}
		// Allow editing if enabled for the user's role.
		if ( ( $this->getProjectSetting( 'edit-if-design' ) && $userRights[ 'design' ] == '1' ) ||
			 ( $this->getProjectSetting( 'edit-if-reports' ) && $userRights[ 'reports' ] == '1' ) )
		{
			return true;
		}
		// Otherwise don't allow editing.
		return false;
	}



	// Add a new report, with the specified ID (unique name), report type, and label.
	function addReport( $reportID, $reportType, $reportLabel )
	{
		// Set the report configuration.
		$config = [ 'type' => $reportType, 'label' => $reportLabel, 'visible' => false ];
		$this->setProjectSetting( "report-config-$reportID", json_encode( $config ) );
		// Add the report to the list of reports.
		$listIDs = $this->getProjectSetting( 'report-list' );
		if ( $listIDs === null )
		{
			$listIDs = [];
		}
		else
		{
			$listIDs = json_decode( $listIDs, true );
		}
		$listIDs[] = $reportID;
		$this->setProjectSetting( 'report-list', json_encode( $listIDs ) );
	}



	// Delete the specified report.
	function deleteReport( $reportID )
	{
		// Remove the report configuration and data.
		$this->removeProjectSetting( "report-config-$reportID" );
		$this->removeProjectSetting( "report-data-$reportID" );
		// Remove the report from the list of reports.
		$listIDs = $this->getProjectSetting( 'report-list' );
		if ( $listIDs === null )
		{
			return;
		}
		$listIDs = json_decode( $listIDs, true );
		if ( ( $k = array_search( $reportID, $listIDs ) ) !== false )
		{
			unset( $listIDs[$k] );
		}
		$this->setProjectSetting( 'report-list', json_encode( $listIDs ) );
	}



	// Get the configuration for the specified report.
	// Optionally specify the configuration option name, otherwise all options are returned.
	function getReportConfig( $reportID, $configName = null )
	{
		$config = $this->getProjectSetting( "report-config-$reportID" );
		if ( $config !== null )
		{
			$config = json_decode( $config, true );
			if ( $configName !== null )
			{
				if ( array_key_exists( $configName, $config ) )
				{
					$config = $config[ $configName ];
				}
				else
				{
					$config = null;
				}
			}
		}
		return $config;
	}



	// Get the report definition data for the specified report.
	function getReportData( $reportID )
	{
		$data = $this->getProjectSetting( "report-data-$reportID" );
		if ( $data !== null )
		{
			$data = json_decode( $data, true );
		}
		return $data;
	}



	// Gets the list of reports, with the configuration data for each report.
	function getReportList()
	{
		$listIDs = $this->getProjectSetting( 'report-list' );
		if ( $listIDs === null )
		{
			return [];
		}
		$listIDs = json_decode( $listIDs, true );
		$listReports = [];
		foreach ( $listIDs as $id )
		{
			$infoReport = [];
			$config = $this->getReportConfig( $id );
			if ( $config !== null )
			{
				$infoReport = $config;
			}
			$listReports[ $id ] = $infoReport;
		}
		return $listReports;
	}



	// Get the role name of the current user.
	function getUserRole()
	{
		$userRights = $this->framework->getUser()->getRights();
		if ( $userRights === null )
		{
			return null;
		}
		if ( $userRights[ 'role_id' ] === null )
		{
			return null;
		}
		return $userRights[ 'role_name' ];
	}



	// Output the form controls to set the report configuration on the edit report page.
	// These are the settings which are the same for all reports.
	function outputReportConfigOptions( $reportConfig, $includeDownload = true )
	{
?>
  <tr><th colspan="2">Report Label and Category</th></tr>
  <tr>
   <td>Report Label</td>
   <td>
    <input type="text" name="report_label" required
           value="<?php echo htmlspecialchars( $reportConfig['label'] ); ?>">
   </td>
  </tr>
  <tr>
   <td>Report Category</td>
   <td>
    <input type="text" name="report_category"
           value="<?php echo htmlspecialchars( $reportConfig['category'] ); ?>">
   </td>
  </tr>
  <tr><th colspan="2">Access Permissions</th></tr>
  <tr>
   <td>Report is visible</td>
   <td>
    <label>
     <input type="radio" name="report_visible" value="Y" required<?php
		echo $reportConfig['visible'] ? ' checked' : ''; ?>> Yes
    </label>
    <br>
    <label>
     <input type="radio" name="report_visible" value="N" required<?php
		echo $reportConfig['visible'] ? '' : ' checked'; ?>> No
    </label>
   </td>
  </tr>
  <tr>
   <td>Grant access to roles</td>
   <td>
    <textarea name="report_roles_access"><?php echo $reportConfig['roles_access']; ?></textarea>
    <br>
    <span class="field-desc">
     Enter each role name on a separate line.
     <br>
     If left blank, the report will be accessible to users with edit access.
     <br>
     Enter * to grant access to all users.
    </span>
   </td>
  </tr>
<?php
		if ( $includeDownload )
		{
?>
  <tr>
   <td>Allow downloads</td>
   <td>
    <label>
     <input type="radio" name="report_download" value="Y" required<?php
		echo $reportConfig['download'] ? ' checked' : ''; ?>> Yes
    </label>
    <br>
    <label>
     <input type="radio" name="report_download" value="N" required<?php
		echo $reportConfig['download'] ? '' : ' checked'; ?>> No
    </label>
   </td>
  </tr>
  <tr>
   <td>Grant downloads to roles</td>
   <td>
    <textarea name="report_roles_download"><?php echo $reportConfig['roles_download']; ?></textarea>
    <br>
    <span class="field-desc">
     Enter each role name on a separate line. Reports can only be downloaded by users with access.
     <br>
     If left blank, the report can be downloaded by users with edit access.
     <br>
     Enter * to allow downloads by all users with access.
    </span>
   </td>
  </tr>
<?php
		}
	}



	// Output the report navigation links.
	function outputViewReportHeader( $reportLabel )
	{
		$canDownload = $this->isReportDownloadable( $_GET['report_id'] );
		$this->writeStyle();

?>
<div class="projhdr">
 <?php echo htmlspecialchars( $reportLabel ), "\n"; ?>
</div>
<p style="font-size:11px" class="hide_in_print">
 <a href="<?php echo $this->getUrl( 'reports.php' )
?>" class="fas fa-arrow-circle-left fs11"> Back to Advanced Reports</a>
<?php

		// If report can be downloaded, show the download link.
		if ( $canDownload )
		{

?>
 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
 <a href="<?php
			echo $this->getUrl( 'sql_view.php?report_id=' . $_GET['report_id'] . '&download=1' );
?>" class="fas fa-file-download fs11"> Download report</a>
<?php

		}

	}



	// Returns the supplied string with any HTML entity encoded, with the exception of hyperlinks.
	// If the $forDownload parameter is true, hyperlink tags will be stripped instead.
	function parseHTML( $str, $forDownload = false )
	{
		if ( $forDownload )
		{
			return preg_replace( '/<a href="[^"]*"( target="_blank")?>(.*?)<\/a>/', '$2', $str );
		}
		return preg_replace( '/&lt;a href="([^"]*)"( target="_blank")?&gt;(.*?)&lt;\/a&gt;/',
		                     '<a href="$1"$2>$3</a>',
		                     htmlspecialchars( $str, ENT_NOQUOTES ) );
	}



	// Sets the specified configuration option for a report to the specified value.
	function setReportConfig( $reportID, $configName, $configValue )
	{
		$reportConfig = $this->getReportConfig( $reportID );
		$reportConfig[ $configName ] = $configValue;
		$this->setProjectSetting( "report-config-$reportID", json_encode( $reportConfig ) );
	}



	// Sets the definition data for the specified report.
	function setReportData( $reportID, $reportData )
	{
		$this->setProjectSetting( "report-data-$reportID", json_encode( $reportData ) );
	}



	// Perform submission of all the report config values (upon edit form submission).
	// These are the values which are the same for each report type (e.g. visibility, category).
	function submitReportConfig( $reportID, $includeDownload = true )
	{
		if ( $includeDownload )
		{
			$listConfig =
				[ 'label', 'category', 'visible', 'download', 'roles_access', 'roles_download' ];
		}
		else
		{
			$listConfig = [ 'label', 'category', 'visible', 'roles_access' ];
		}
		foreach ( $listConfig as $configSetting )
		{
			$configValue = $_POST["report_$configSetting"];
			if ( in_array( $configSetting, [ 'visible', 'download' ] ) )
			{
				$configValue = $configValue == 'Y' ? true : false;
			}
			elseif ( trim( $configValue ) === '' )
			{
				$configValue = null;
			}
			$this->setReportConfig( $reportID, $configSetting, $configValue );
		}
	}



	// Outputs HTTP headers for a report download (.csv file).
	function writeCSVDownloadHeaders( $reportID )
	{
		$queryDev = $this->query( "SELECT value FROM redcap.redcap_config" .
		                          " WHERE field_name = 'is_development_server'" );
		$isDev = mysqli_fetch_row( $queryDev );
		$isDev = $isDev[0] == '1';
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' .
		        trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', \REDCap::getProjectTitle() ), '_-' ) .
		        "_{$reportID}_" . gmdate( 'Ymd-His' ) . ( $isDev ? '_dev' : '' ) . '.csv"' );
	}



	// CSS style for advanced report pages.
	function writeStyle()
	{
		$style = '
			.mod-advrep-formtable
			{
				width: 97%;
				border: solid 1px #000;
			}
			.mod-advrep-formtable th
			{
				padding: 5px;
				font-size: 130%;
				font-weight: bold;
			}
			.mod-advrep-formtable td
			{
				padding: 5px;
			}
			.mod-advrep-formtable td:first-child
			{
				width: 200px;
				padding-top: 7px;
				padding-right: 8px;
				text-align:right;
				vertical-align: top;
			}
			.mod-advrep-formtable input:not([type=submit]):not([type=radio]):not([type=checkbox])
			{
				width: 95%;
				max-width: 600px;
			}
			.mod-advrep-formtable textarea
			{
				width: 95%;
				max-width: 600px;
				height: 100px;
			}
			.mod-advrep-formtable label
			{
				margin-bottom: 0px;
			}
			.mod-advrep-formtable span.field-desc
			{
				font-size: 90%;
			}
			.mod-advrep-datatable
			{
				border: solid 1px #000;
				border-collapse: separate;
				border-spacing: 0px;
			}
			.mod-advrep-datatable th
			{
				background: #fff;
				padding: 13px 5px;
				font-weight: bold;
				border: solid 1px #000;
				text-align: center;
			}
			.mod-advrep-datatable td
			{
				background: #fff;
				padding: 3px;
				border: solid 1px #000;
				text-align: center;
			}
			.mod-advrep-datatable th:first-child,
			.mod-advrep-datatable td:first-child
			{
				position: sticky;
				left: 0px;
				border-right-width: 3px;
			}
			.mod-advrep-datatable th:nth-child(2),
			.mod-advrep-datatable td:nth-child(2)
			{
				border-left-width: 0px;
			}
			.mod-advrep-datatable tr:first-child th
			{
				position: sticky;
				top: 0px;
				border-bottom-width: 3px;
			}
			.mod-advrep-datatable tr:nth-child(2) td
			{
				border-top-width: 0px;
			}
			.mod-advrep-datatable tr:first-child :first-child
			{
				z-index: 2;
			}
			.mod-advrep-datatable tr:nth-child(2n+1) td
			{
				background: #f8f8f8;
			}
			';
		echo '<script type="text/javascript">',
			 '(function (){var el = document.createElement(\'style\');',
			 'el.setAttribute(\'type\',\'text/css\');',
			 'el.innerText = \'', addslashes( preg_replace( "/[\t\r\n ]+/", ' ', $style ) ), '\';',
			 'document.getElementsByTagName(\'head\')[0].appendChild(el)})()</script>';
	}


}
