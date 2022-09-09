# Advanced-Reports

This REDCap module provides advanced reporting capability.



## Accessing Advanced Reports

The link to the advanced reports plugin pages will appear under the external modules heading once
the module has been enabled in a project. This will display the list of visible reports.
Administrators will also see a link to edit reports, as well as administrative options. Other users
granted report editing rights will also see the edit reports link.

To view a report, click on the link in the list of reports. Users with download rights will also get
a download link on the report page.



## Configuring Edit Rights

By default, only administrators can edit advanced reports. Edit rights can be granted to other users
by changing the system setting **Module configuration permissions in projects** to **Require
module-specific user privilege**. Edit rights can then be granted by assigning the module-specific
privilege to a user or role within the REDCap user rights page.

Note that some report types can only be edited by administrators regardless of these settings.



## Editing Advanced Reports

On the edit page, a new report can be added, or an existing report can be edited or deleted. There
is also a link here to view the report, which can be used even if the report is hidden from the main
report list.

The following report types are currently available:

* **Gantt**
* **SQL** (direct database query) \*

\* only administrators can edit

### Common report options

* **Unique Report Name** is the identifier used by the system for your report
* **Report Label** is what the report is called in the list of reports
* **Report Category** *(optional)* is the heading under which the report appears in the list
* **Report is visible** determines whether the report is shown in the list
* **Grant access to roles** apart from users with edit rights, only the users with roles listed here
  can see the report
* **Allow downloads** \* determines whether a download link is provided for this report
* **Grant downloads to roles** \* apart from users with edit rights, only the users with roles
  listed here will see the download link.
* **Allow retrieval as image** \* determines whether the report can be retrieved as an image file,
  rather than as a REDCap page, which is useful for embedding a report in a data collection
  instrument
  * Append *&as_image=1* to the report URL to retrieve as an image.
  * Access rights are still enforced, so reports may not be visible if embedded in surveys.

\* only available on some report types

### Gantt report options

* **Label Fields** are shown on the left of the chart and identify each row
  * **Label Name** is the heading for the label column
  * **Label Field** is the event/field which contains the label data
* **Chart Categories** are the bars on the Gantt chart
  * **Name** is the name of the category, shown within the bars and in the legend at the bottom of
    the chart
  * **Start Date Field** is the event/field which contains the date which determines where the bar
    starts on the chart
  * **End Date Field** is the event/field which contains the date which determines where the bar
    ends on the chart

Calculated fields can be used for the start and end dates, the Gantt report will interpret the value
as a Unix timestamp representing a UTC date/time.

### SQL report options

* **Description** brief descriptive text to appear above the report
  * HTML links (&lt;a&gt; tags) as well as the placeholders `$$PROJECT$$` (project ID) and
    `$$WEBROOT$$` (REDCap version directory web path) can be used in the description.
* **SQL Query** enter SELECT query here
* **Result Type** choose how the SQL result is to be interpreted (normal or EAV)
* **Result Columns** pre-specify column names for EAV datasets

Please refer to the [SQL report instructions](README-SQL.md) for more information.



## Project-level configuration options

The following project-level configuration options are deprecated. They are only visible to
administrators and only if at least one of them has been set. To permit non-administrators to edit
reports, please use the REDCap module permission as described above.

* Allow users with *project design and setup* rights to edit reports
* Allow users with *add/edit/organize reports* rights to edit reports
