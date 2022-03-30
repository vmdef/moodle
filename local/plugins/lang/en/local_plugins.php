<?php

/**
 * This file contains the English language strings for this plugin.
 *
 * This file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

/**
 * General strings for use within the local plugin.
 * @var array $string
 */
$string['addcontributor'] = 'Add a new contributor';
$string['adddocumentation'] = 'Add documentation';
$string['addfaqs'] = 'Add FAQ\'s';
$string['addnewversion'] = 'Add a new version';
$string['addaward'] = 'Award this plugin';
$string['addsite'] = 'Add site';
$string['addtoset'] = 'Add to set';
$string['addversiontext'] = 'Add version text';
$string['addversiontextdefault'] = '<p>When adding a new version you only need to upload the new version file and select the previous version, we will try to do the rest for you.
Once you click Submit and the file has uploaded we will unzip it and look for some key files that most Moodle plugins have and will collect information about this version from them.
The following are the files that will be inspected as part of this process and the information we are hoping to find:</p>

<p><strong>version.php</strong></p>
<ul>
  <li>$plugin->version - If this exists it is used as the version build number.</li>
  <li>$plugin->requires - If this exists we match it to a Moodle version and add that as a required version.</li>
  <li>$plugin->release - If this exists it is used as the release name for this version.</li>
  <li>$plugin->maturity - If this exists it is used to ascertain the stability of the version.</li>
</ul>

<p><span>If <strong>README.*</strong> file exist it is used as the release notes for this version.</span></p>

Once we have collected all the information we can the new version will be added at which point you can double check the information is correct and edit the version to add any extra or missing information you want.';
$string['addversiontextdesc'] = 'The text to appear on the top of Add new version page. If not set it will be taked from string "addversiontextdefault"';
$string['allcategories'] = 'All plugin types';
$string['allreviews'] = 'All plugin version reviews';
$string['altdownload'] = 'Alternative download location';
$string['altdownloadurl'] = 'Alternative download URL';
$string['altdownloadurl_help'] = 'If you have a URL that can be used to download this version of the plugin then you can put it here.

Please note that downloads will still be processed through Moodle however this will be shown to users so that if for any reason they have trouble downloading they can try this URL.';
$string['amosapikey'] = 'AMOS API key';
$string['amosapikey_desc'] = 'Web service token that this site should use when calling AMOS web service.';
$string['amosexportdetails'] = '{$a->componentname}: Found strings: {$a->found} - Updated strings: {$a->changes}';
$string['amosexportretry'] = 'Re-try exporting strings to AMOS';
$string['amosexportresult'] = 'Log of strings updates';
$string['amosexportresultnone'] = 'No logs data available';
$string['amosexportresulttimecreated'] = 'Submitted to AMOS';
$string['amosexportresultbranch'] = 'Branch';
$string['amosexportresultexception'] = 'Error: AMOS threw exception {$a->exception}: {$a->message}';
$string['amosexportresulterrorparse'] = 'Error: Unable to parse AMOS response';
$string['amosexportresulterrorplugin'] = 'No updatable strings found in the package. Maybe it is not the latest version or the plugin is misconfigured.';
$string['amosexportresult_help'] = 'AMOS is the Moodle tranlation tool. English strings defined in your plugin are automatically exported and sent into AMOS shortly after you upload a new version of the plugin or edit the current version. Successfully imported strings are immediately available for translation. Once they are translated in AMOS, strings for your plugin are distributed as a part of the standard language package for the given Moodle version.

Note that strings for the most recent approved plugin versions only are exported into AMOS.';
$string['amosexportresult_link'] = 'AMOS';
$string['amosexportstatus'] = 'Registration of the plugin strings in AMOS';
$string['amosexportstatus_pending'] = 'Pending';
$string['amosexportstatus_processing'] = 'Processing';
$string['amosexportstatus_ok'] = 'Successful';
$string['amosexportstatus_problem'] = 'Failed';
$string['amosurl'] = 'AMOS site URL';
$string['amosurl_desc'] = 'The URL of the Moodle site hosting AMOS. Having other than the default value may be useful for debugging.';
$string['apiaccess'] = 'API access';
$string['apiaccessabout'] = '<p>The Plugins directory exposes some of its features via web services layer, allowing the community to develop custom tools and integrations with other services such as GitHub Actions or Travis CI.</p><p>See more details at <a href="https://docs.moodle.org/dev/Plugins_directory_API">Plugins directory API</a> docs page.</p>';
$string['apiaccessdetails'] = 'Your API access details';
$string['apiaccessexample'] = 'API call example';
$string['apinotoken'] = 'No API access token found.';
$string['apiaccesscreatetoken'] = 'Generate new token';
$string['apiaccessrevoketoken'] = 'Revoke token';
$string['approval'] = 'Approval';
$string['approval_approve'] = '<p>You are going to <span class="badge badge-success">approve</span> this plugin. The maintainers will be informed by a message and the plugin will be made publicly available.</p>';
$string['approval_disapprove'] = '<p>You are going to mark this plugin as <span class="badge badge-danger">needing more work</span>. The maintainers will be informed by a message.</p>';
$string['approval_scheduleapprove'] = '<p>You are going to schedule this plugin for re-approval. Make sure all the issues raised during the previous review were resolved. This usually means you should have a new plugin version uploaded with the code fixed and this new version should be now displayed as the current one at the Download versions tab.</p><p>Your plugin will be queued for the new review and marked as <span class="badge badge-warning">Waiting for approval</span>. You will be notified as soon as reviewers get to your plugin again. Thanks for your patience with the review and approval process.</p>';
$string['approvalissue'] = 'Approval issue';
$string['approvalissuesubject'] = 'Plugin approval issue created: {$a}';
$string['approvalissuemessage'] = 'Hi!

Thanks for sharing your plugin \'{$a->pluginname}\' with the Moodle community!

I have created a new issue where the progress of your plugin\'s approval process will be tracked:

https://tracker.moodle.org/browse/{$a->issue}

Please make sure you have an account there in the Moodle tracker and start watching that issue now. It is a place where reviewers will give you feedback on your plugin. It is also the best place for you to contact us with regards to the plugin approval.

More information on using the Moodle tracker can be found at https://docs.moodle.org/dev/Tracker_introduction

Please do not forget to check your plugin page at {$a->viewlink} to make sure all required information has been provided. See https://docs.moodle.org/dev/Plugin_contribution_checklist for help with setting up a good plugin page.

Thank you in advance for your patience with the review and approval process. We will do our best to get to reviewing your plugin soon.

-- 
Moodle Plugins bot';

$string['approve'] = 'Approve';
$string['approved'] = 'Approved';
$string['approvethisplugin'] = 'Approve this plugin';
$string['archiveautoremoved'] = 'The following files were automatically removed from the archive:<br /><b>{$a}</b>';
$string['archiveshouldberemoved'] = 'It is strongly discouraged for the following files to be present in archive:<br /><b>{$a}</b><br />You may choose the <b>\'Auto remove system files\'</b> option below in order to automaticaly remove them from archive (click \'Back\' if form is uneditable)';
$string['archiveonedir'] = 'Archive contains one directory with the name <b>{$a}</b>';
$string['autoremove'] = 'Auto remove system files';
$string['autoremove_help'] = 'Some files known to be added silently by the OS, VCS and/or IDE will be automatically removed from the package.';
$string['availabilitymessageapproved'] =  'Thank you for your plugin contribution.

The plugin \'{$a->pluginname}\' has been published in the plugins directory and is now available for download.

See {$a->viewlink} for more details.';
$string['availabilitymessagesubject'] =  'Plugin availability update: {$a}';
$string['availabilitymessageunapproved'] =  'Thank you for your plugin contribution.

The plugin \'{$a->pluginname}\' needs some attention before being published.

See {$a->viewlink} for more details.

Please note, once the raised issues are fixed, use the button \'Request re-approval\' to put the plugin back to the queue of plugins waiting for approval. It will be reviewed again then.

Thanks for your patience with the review and approval process.';
$string['available'] = 'Available';
$string['availablenot'] = 'Not available';
$string['averagereviewgradestitle'] = 'Official reviews';
$string['award'] = 'Award';
$string['awardicon'] = 'Award icon';
$string['awardicon_help'] = 'This icon will be used when displaying the award. It is a good idea to keep all award icons the same time so that they are a routine size when viewing a plugin with multiple awards.

Recommended size is 64px x 64px';
$string['awardrecentlyawarded'] = 'Plugins recently awarded the {$a} award';
$string['browse'] = 'Browse';
$string['browsenamed'] = 'Browse {$a}';
$string['bugtrackerurl'] = 'Bug tracker URL';
$string['bugtrackerurl_help'] = 'Link to tracker system used to report bugs and suggest improvements';
$string['bugtrackerurllink'] = 'Bug tracker';
$string['cachedef_translationstats'] = 'Plugins directory translation stats';
$string['cachedef_queuestats'] = 'Primary data for approval queue stats';
$string['cancel'] = 'Cancel';
$string['cannotbeupdated'] = 'Version(s) <b>{$a}</b> can not be updated to this version because they have bigger build number';
$string['cannotbeupdateable'] = 'Version(s) <b>{$a}</b> claim that they can be updated from this version but their version build number is smaller than this one\'s';
$string['categories'] = 'Plugin types';
$string['categoriesrequired'] = 'Before you can start using this plugin you need to first create one or more plugin type categories for plugins.';
$string['category'] = 'Plugin type';
$string['categoryplugintype'] = 'Type prefix';
$string['categoryplugintype_help'] = 'This is a prefix for plugins frankenstyle component names in this type category (without trailing underscore).<br />Leave empty if you do not want any plugins to be registered in this type category.<br />Set to "-" if plugins in this type category should not have frankenstyle component name at all.';
$string['categoryplugintype_link'] = 'Development:Frankenstyle';
$string['categoryshortdescription'] = 'Plugin type short description';
$string['categoryshortdescription_help'] = 'This should be a short description of the plugin type. It will be used as a tooltip for the heading name and will also be shown to users when they are browsing the plugins of the type.';
$string['overviewstats'] = 'Overview stats';
$string['categorystats'] = 'Plugin type stats';
$string['categorynamed'] = 'Plugin type: {$a}';
$string['categorytreedesc'] = 'The tree of all plugin types available.';
$string['categorytreename'] = 'Plugin types';
$string['changelog'] = 'Change log';
$string['changelogurl'] = 'Change log URL';
$string['changesfilefound'] = 'Changes log found in file <b>{$a}</b>';
$string['changesfilenotfound'] = 'Changes log file not found';
$string['checkerpluginresults'] = 'Plugin needs maintenance';
$string['checkerresult_checkrepositoryname'] = 'Check the repository name';
$string['checkerresult_checkrepositoryname_help'] = 'In order to provide a consistent experience for Moodle developers and site administrators, it is suggested to follow the repository naming convention for Moodle plugins, namely, moodle-{plugintype}_{pluginname}.

Please consider renaming your Github repository so that it conforms with the naming convention. This is not a requirement if you have reasons for not wishing to rename it. We like to encourage following the naming convention for consistency and to have that name correct before we approve the plugin.';
$string['checkerresult_fillbugtrackerurl'] = 'Provide bug tracker URL';
$string['checkerresult_fillbugtrackerurl_help'] = 'Bug tracker is not specified for your plugin. Providing a place for users of your plugin to report issues encourages participation and provides a way for users to report bugs, make feature requests, or suggest other types of improvements.

There are a couple of options. You are welcome to request that a component be created in the Moodle tracker. This will allow for you to become more familiar with how issues are managed in Moodle core but may take a little more time to setup. Alternatively, for folks who are using Github, you can use the issues feature of Github to handle such requests.';
$string['checkerresult_filldescription'] = 'Provide the plugin description';
$string['checkerresult_filldescription_help'] = 'Please add a full description of the plugin. You can describe the plugin\'s features, purpose and typical usage scenarios there, for example.';
$string['checkerresult_filldocumentationurl'] = 'Provide the external documentation URL';
$string['checkerresult_filldocumentationurl_help'] = 'Please consider filling the external documentation URL field. You are welcome to create your documentation in Moodle Docs.';
$string['checkerresult_filldocumentationurl_link'] = 'Development:Plugin documentation';
$string['checkerresult_fillsourcecontrolurl'] = 'Provide the source control URL';
$string['checkerresult_fillsourcecontrolurl_help'] = 'In order to facilitate easier sharing and further development of your open-source plugin, please provide publicly accessible URL of your code repository. The suggested naming convention of the repository is moodle-{plugintype}_{pluginname}.';
$string['checkerresult_invalidurl'] = 'Fix invalid URL';
$string['checkerresult_invalidurl_help'] = 'All URLs in the plugin form as expected to start with `https://`  or `http://` protocol and contain valid address of the web page.';
$string['checkerresult_providescreenshots'] = 'Provide illustrative screenshots';
$string['checkerresult_providescreenshots_help'] = 'Please add some screenshots of your plugin to help folks get an idea of what it looks like when installed.';
$string['checkerresultlabelrecommended'] = 'Recommended';
$string['checkerresultlabelrequired'] = 'Required';
$string['checkerresultlabelsuggested'] = 'Suggested';
$string['children'] = 'Children';
$string['codeprechecks'] = 'Code prechecks';
$string['codesearchresults'] = 'View {$a} code search results';
$string['cohort'] = 'Cohort';
$string['commentnotifunapprovedsubject'] = 'Unapproved plugin notification: {$a}';
$string['commentnotifunapprovedmessage'] = '{$a->fullname} has commented on a plugin that is not approved yet:
---------------------------------------------------------------------

{$a->message}

---------------------------------------------------------------------
Plugin link: {$a->viewlink}

';
$string['commentnotifcontributorsubject'] = 'Plugin contributor\'s notification: {$a}';
$string['commentnotifcontributormessage'] = '{$a->fullname} has commented on a plugin you are contributing to:
---------------------------------------------------------------------

{$a->message}

---------------------------------------------------------------------
Plugin link: {$a->viewlink}

';
$string['commentnotifsubscriptionsubject'] = 'Plugin comment notification: {$a}';
$string['commentnotifsubscriptionmessage'] = '{$a->fullname} has commented on a plugin you are subscribed to:
---------------------------------------------------------------------

{$a->message}

---------------------------------------------------------------------
Plugin link: {$a->viewlink}

';
$string['comments'] = 'Comments';
$string['commentslogintoview'] = 'Please login to view comments';
$string['commentslogintopost'] = 'Please login to post comments';
$string['confirmawarddelete'] = 'Are you sure you want to delete this award?';
$string['confirmcategorydelete'] = 'Are you sure you want to delete this plugin type category?';
$string['confirmdeleteversion'] = 'Are you sure you want to delete this version?';
$string['confirmreviewcriteriondelete'] = 'Are you sure you want to delete this review criteria?<br />Doing so will also delete all review outcomes for this criteria, this information cannot be recovered once the criteria is deleted.';
$string['confirmsoftwareversiondelete'] = 'Are you sure you want to delete this software version?';
$string['confirmsetdelete'] = 'Are you sure you want to delete this set?';
$string['contributionsmadeby'] = 'Contributions made by {$a->fullname} {$a->picture}';
$string['contributor'] = 'Contributor';
$string['contributors'] = 'Contributors';
$string['contributorrole'] = 'Contributor role';
$string['contributorrole_help'] = 'Describe the role this user had when working on this plugin';
$string['create'] = 'Create';
$string['createaward'] = 'Create award';
$string['createcategory'] = 'Create a new plugin type category';
$string['created'] = 'Created';
$string['createnewplugin'] = 'Register a new plugin';
$string['createnewpluginincategory'] = 'Register a new {$a} plugin';
$string['createnewpluginversion'] = 'Create a new version';
$string['createset'] = 'Create plugin set';
$string['createsoftwareversion'] = 'Create software version';
$string['createreviewcriterion'] = 'Create review criterion';
$string['creator'] = 'Creator';
$string['criterioncohort'] = 'Review criterion cohort';
$string['criterioncohort_help'] = 'This allows you to select a cohort to associate with this review criterion. When a reviewer is writing a review they will only be able to fill out this criterion if they are a member of the related cohort.';
$string['criterionscale'] = 'Review criterion scale';
$string['criterionscale_help'] = 'This is the scale that reviewers will use when completing a review of a plugin in regards to this criterion.';
$string['currentversion'] = 'Current version';
$string['currentversions'] = 'Current versions';
$string['currentversionsnum'] = 'Current versions available: {$a}';
$string['currentunavailableversion'] = 'Current unavailable version';
$string['currentunavailableversions'] = 'Current unavailable versions';
$string['cvs'] = 'CVS - Concurrent Versions System';
$string['defaultlogo'] = 'Default logo';
$string['defaultlogo_help'] = 'Default logo for plugins in this type category.'; // Not used at the moment
$string['defaultreleasename'] = '{$a}'; // If release name is not specified it will be equal to the version build number
$string['descriptors'] = 'Descriptors';
$string['descriptoradd'] = 'Add a new descriptor';
$string['descriptortitle'] = 'Title';
$string['descriptorsave'] = 'Save labels';
$string['descriptorsort'] = 'Sort order';
$string['descriptorvalues'] = 'Values';
$string['deleteaward'] = 'Delete award';
$string['deletesiteareyousure'] = '{$a}';
$string['deletesiteareyousuremessage'] = 'The site entry will be removed from the list of your sites. Are you sure?';
$string['deletesitedone'] = 'The site entry has been deleted.';
$string['deleteversion'] = 'Delete version';
$string['deleteplugin'] = 'Delete plugin';
$string['deletepluginconfirm'] = 'Are you absolutely sure that you want to permanently delete the plugin {$a}?';
$string['deletereviewcriterion'] = 'Delete review criterion';
$string['deleteset'] = 'Delete set';
$string['deletesoftwareversion'] = 'Delete software version';
$string['deleteusersite'] = 'Delete';
$string['deletingsite'] = 'Deleting site.';
$string['description'] = 'Description';
$string['development'] = 'Development';
$string['devzone'] = 'Developer zone';
$string['disapprove'] = 'Unapprove';
$string['disapprovethisplugin'] = 'Unapprove this plugin';
$string['discussionurl'] = 'Discussion URL';
$string['discussionurl_help'] = 'Link to forum discussion about this plugin.';
$string['discussionurllink'] = 'Discussion';
$string['docs'] = 'Docs';
$string['documentation'] = 'Documentation';
$string['documentationupdated'] = 'The documentation for this plugin has been successfully updated.';
$string['documentationurl'] = 'External documentation URL';
$string['documentationurllink'] = 'More documentation on this plugin';
$string['download'] = 'Download';
$string['downloadalltimebyplugin'] = 'Top plugin downloads in the last  year:';
$string['downloadcomplete'] = 'Download complete';
$string['downloadingnow'] = 'Downloading now';
$string['downloadlatest'] = 'Download latest version';
$string['downloadmessage'] = 'Your download will begin shortly. Click <a href="{$a->downloadlink}">here</a> if nothing happens.';
$string['downloadmonth'] = 'Downloads by month:';
$string['downloadmonthver'] = 'Version downloads by month:';
$string['downloadnostats'] = 'No download statistics available at the moment.';
$string['downloadredirectorurl'] = 'Download proxy URL';
$string['downloadstats'] = 'Download stats';
$string['downloadredirectorurl_desc'] = 'The base URL to any download proxy. This will be used and appended to (with the same local paths) for any external proxy to handle.';
$string['downloadstarting'] = 'Downloading starting';
$string['downloads'] = 'Downloads';
$string['downloadsrecent'] = 'Total downloads in last 90 days: {$a}';
$string['downloadstotalrecentplugins'] = 'Top plugin downloads in the last {$a} months:';
$string['downloadversions'] = 'Versions';
$string['edit'] = 'Edit';
$string['editablecategoryaction'] = 'Plugin type actions';
$string['editablecategoryaction_help'] = 'Providing you have the require capabilities you are able to edit type categories and providing the type category contains no plugins you are also able to delete it.';
$string['editaward'] = 'Edit award';
$string['editdetails'] = 'Edit details';
$string['editcategory'] = 'Edit plugin type';
$string['editcontributor'] = 'Edit contributor';
$string['editdocs'] = 'Edit docs';
$string['editdocumentation'] = 'Edit the documentation for this plugin';
$string['editfaqs'] = 'Edit FAQs';
$string['editplugin'] = 'Edit this plugin';
$string['editreview'] = 'Edit review';
$string['editreviewcriterion'] = 'Edit review criterion';
$string['editset'] = 'Edit plugin set';
$string['editsite'] = 'Edit site';
$string['editsoftwareversion'] = 'Edit software version';
$string['editversion'] = 'Edit version {$a}';
$string['experimental'] = 'Experimental';
$string['extratypes'] = 'Extra plugin types';
$string['extratypes_desc'] = 'Allows to define extra plugin types not known to the current moodle core yet. Eventually you can use this to rename the default plugin types. Example:
```
assignment: Assignment / Legacy plugin
media: Media player
extra: Future plugin type
```
';
$string['faqs'] = 'FAQs';
$string['faqsupdated'] = 'The FAQ\'s for this plugin have been successfully updated';
$string['favouritedbyx'] = 'Favourited';
$string['favouritesadd'] = 'Add to my favourites';
$string['favouritesremove'] = 'Remove';
$string['favouritesinfo'] = 'Favourited by {$a} user(s)';
$string['favouritesmodified'] = 'Favourited';
$string['filefoundinarchive'] = 'File <b>{$a}</b> is found in archive';
$string['frankenstyleexists'] = 'Another plugin with the same frankenstyle component name {$a} already exists in this Plugins database. If you are the owner you need to add new versions to the existing plugin instead of registering a new one. If your plugin has the same name you need to rename it.';
$string['frontpageawards'] = 'Awards to display on the front page';
$string['frontpageawardsdesc'] = 'Each award selected here will be displayed on the front page along with 5 newest plugins that were awarded the selected award.';
$string['frontpagecategories'] = 'Types to display on the front page'; // Not supported any more.
$string['frontpagecategoriesdesc'] = 'Each category selected here will be displayed on the front page along with the 5 newest plugins added to that category.';
$string['frontpagesets'] = 'Sets to display on the front page';
$string['frontpagesetsdesc'] = 'Each set selected here will be displayed on the front page along with the 5 newest plugins added to that set.';
$string['functionfoundinfile'] = 'Function <b>{$a->funcname}</b> is present in file <b>{$a->filename}</b>';
$string['functionnotfoundinfile'] = 'Function <b>{$a->funcname}</b> is not present in file <b>{$a->filename}</b>';
$string['generalcriterion'] = 'General comments';
$string['generalcriteriondesc'] = '<p>This section of the review should contain your general comments about the plugin you are reviewing.</p>';
$string['git'] = 'GIT';
$string['go'] = 'Go';
$string['grade'] = 'Grade';
$string['hide'] = 'Hide';
$string['hidethisplugin'] = 'Hide this plugin';
$string['installgetlogin'] = 'Log in';
$string['installgetbrowse'] = 'Browse plugins';
$string['installget'] = 'Installing plugins';
$string['installgetinfo'] = '<p>To install a plugin directly on your Moodle site, you need to log in with your moodle.org account.</p><p>Alternatively, you can browse the plugins directory as a guest and download a plugin for installing manually on your site.</p>';
$string['installinstructions'] = 'Installation instructions';
$string['installinstructionsforcategory'] = 'Default installation instructions for plugins of the type {$a}';
$string['installinstructions_help'] = 'These are install instructions for the plugins that belong in this type category. This is displayed to users when downloading a version of a plugin of the given type.';
$string['installplugin'] = 'Install now';
$string['installxmlbadtablenames'] = 'Table(s) names <b>{$a->tables}</b> defined in db/install.xml do not start with <b>{$a->prefix}</b>';
$string['installxmlbadtablenamesalt'] = 'Table(s) names <b>{$a->tables}</b> defined in db/install.xml do not start with <b>{$a->prefix}</b> or <b>{$a->prefix_alt}</b>';
$string['installxmlchecked'] = '<b>db/install.xml</b> is validated and tables names have the proper prefix';
$string['installxmlhaserrors'] = 'Errors parsing db/install.xml: {$a}';
$string['invalidfrankenstyle'] = 'Invalid frankenstyle component name of the plugin. Regular expression used to validate the name is: {$a}';
$string['invalidcategory'] = 'You cannot register plugins of this type. Please choose one of the related plugin types.';
$string['invalidurl'] = 'The URL you just entered is not valid';
$string['invisible'] = 'Invisible';
$string['issues'] = 'Issues';
$string['isthelatest'] = 'Latest release for {$a->requirements}';
$string['langfilefound'] = 'English language file is found: <b>{$a}</b>';
$string['langfilenotfound'] = 'English language file not found. Searching for file <b>{$a}</b>';
$string['langfilenopluginname'] = 'English language file <b>{$a->filename}</b> does not contain $string[\'{$a->stringkey}\']';
$string['langfilepluginnamefound'] = '$string[\'{$a->stringkey}\'] is found in English language file: <b>{$a->pluginname}</b>';
$string['lastmodified'] = 'Last modified';
$string['learnmore'] = 'Learn more';
$string['learnmoreabout'] = 'Release <b>{$a}</b>';
$string['leadmaintainer'] = 'Lead maintainer';
$string['leadmaintainer_postfix'] = '(Lead maintainer)';
$string['links'] = 'Links';
$string['logidentifieraward'] = 'Award {$a}';
$string['logidentifiercategory'] = 'Plugin type category {$a}';
$string['logidentifiercriterion'] = 'Review criterion {$a}';
$string['logidentifierplugin'] = 'Plugin {$a->plugin} ({$a->category})';
$string['logidentifierpluginobject'] = '{$a->pluginidentifier}: {$a->object}';
$string['logidentifierreview'] = 'Review of {$a->version} by {$a->user}';
$string['logidentifierset'] = 'Set {$a}';
$string['logidentifiersoftwarevers'] = 'Software version {$a}';
$string['logidentifierversion'] = 'Version {$a}';
$string['logintocontact'] = 'Please login to view contributors details and/or to contact them';
$string['logo'] = 'Logo';
$string['logo_help'] = 'You can upload a logo for your plugin. The logo will be resized automatically to fit a square area 90px wide and high.';
$string['mainpagetext'] = 'Main page text';
$string['mainpagetextdesc'] = 'The text to appear on the Plugins main page above the search box.';
$string['maintainer'] = 'Maintainer';
$string['maintainer_help'] = 'Contributors who are not maintainers are only listed on the plugins page. Maintainers are also able to add versions and change the existing information.

Only the lead maintainer can manage the contributors.

Lead maintainer is the person who registered plugin unless changed by administrator';
$string['maintainedby'] = 'Maintained by {$a}';
$string['makeleadmaintainer'] = 'Make lead maintainer';
$string['manageawards'] = 'Manage awards';
$string['managecategories'] = 'Manage plugin types';
$string['managedescriptors'] = 'Manage descriptors';
$string['managereviewcriteria'] = 'Manage review criteria';
$string['managesets'] = 'Manage sets';
$string['managesetsdesc'] = 'Sets are a way of organising plugins based upon a relationship that they share. Sets are designed to be used for things like `Best plugins of 2011`, `Staff picks` or any other relationship that you want.';
$string['managesoftwareversions'] = 'Manage software versions';
$string['maturity'] = 'Maturity';
$string['maturity_help'] = 'The maturity level declares how stable the particular plugin version is. This affects the available updates notification feature in Moodle. Administrators can configure their site so that they are not notified about an available update unless it has certain maturity level declared.

The value selected here should match the `$plugin->maturity` statement in your version.php.';
$string['maturity_link'] = 'Development:version.php';
$string['maxcontributors'] = 'Maximum contributors';
$string['maxcontributorsdesc'] = 'This is the maximum number of contributors that a plugin lead maintainer can add.';
$string['maxplugins'] = 'Max plugins';
$string['maxplugins_help'] = 'This is the maximum number of plugins that can be added to this set before it is considered full and no more plugins can be added.';
$string['maxscreenshots'] = 'Max screenshots';
$string['maxscreenshotsdesc'] = 'This is the maximum number of screenshots that can be added for a plugin. By default this is 10 however you can set it to what ever you want.';
$string['md5sum'] = 'MD5 Sum';
$string['mercurial'] = 'Mercurial';
$string['messageprovider:availability'] = 'Approval notifications for plugins you maintain';
$string['messageprovider:award'] = 'Award notifications';
$string['messageprovider:comment'] = 'Comment notifications';
$string['messageprovider:contributor'] = 'Comment notifications for plugins you maintain';
$string['messageprovider:registration'] = 'Approval notifications';
$string['messageprovider:review'] = 'Review notifications';
$string['messageprovider:version'] = 'Version notifications';
$string['messageprovidersetting'] = 'To change your preference for receiving these notifications, check the section "{$a}" in your messaging settings.';
$string['moodleversionmismatchold'] = 'You specified in your version.php that this plugin is for Moodle version before 2.0 and the location of the language files suggests that this plugin is for version 2.0 or later.';
$string['moodleversionmismatchnew'] = 'You specified in your version.php that this plugin is for Moodle version 2.0 or later but the location of the language files suggests that this plugin is for earlier version of Moodle.';
$string['mostrecentplugins'] = 'Recently released plugins';
$string['mostrecentpluginsnew'] = 'Recently added plugins';
$string['mostrecentpluginsupdated'] = 'Recently updated plugins';
$string['mustbeemptyfrankenstyle'] = 'No frankenstyle component name should be specified for contributions of the type {$a}';
$string['mycontributions'] = 'My contributions';
$string['mymoodlesites'] = 'My sites';
$string['myprofilecattitle'] = 'Plugins contributions';
$string['myprofilebrowse'] = 'See details';
$string['myprofilemaintainer'] = 'Maintainer';
$string['myprofilecontributor'] = 'Contributor';
$string['name'] = 'Name';
$string['noawards'] = 'No awards';
$string['nocomments'] = 'No comments';
$string['nocomments_desc'] = 'No one has made any comments about this plugin yet.';
$string['nodocumenation'] = 'No documentation has been written for this plugin yet.';
$string['nofaqs'] = 'No frequently asked questions have been written for this plugin yet.';
$string['none'] = 'None';
$string['noreviewcriteria'] = 'No review criteria have been created yet.';
$string['nopluginawards'] = 'This plugin has not received any awards yet';
$string['nopluginreviews'] = 'No one has reviewed this plugin yet';
$string['nopluginsets'] = 'This plugin is not yet in any sets';
$string['nosets'] = 'There are not yet any plugin sets.';
$string['nosoftwareversions'] = 'There are not yet any software versions registered.';
$string['notapproved'] = 'Needs more work';
$string['notavailable'] = 'Not available';
$string['notspecified'] = 'Not specified';
$string['notthelatest'] = 'The more recent release {$a->version} exists for {$a->requirements}';
$string['noversionstoreview'] = 'There are no versions to review.';
$string['other'] = 'Other...';
$string['onfrontpage'] = 'Show on front page';
$string['pendingapproval'] = 'Waiting for approval';
$string['parentcategory'] = 'Parent type';
$string['parentcategory_help'] = 'This is the plugin type that the new type will be created within. If you leave it as none the plugin type will be a root one without a parent.';
$string['plugin'] = 'Plugin';
$string['pluginadministration'] = 'Plugins directory administration';
$string['plugincount'] = 'Plugins';
$string['plugindeleted'] = 'Plugin deleted';
$string['plugindescription'] = 'Description';
$string['plugindirectory'] = 'Plugin directory';
$string['pluginfrankenstyle'] = 'Frankenstyle component name';
$string['pluginfrankenstyle_help'] = 'Please enter the full frankenstyle component name for your module, such as mod_book or gradereport_mystats. Your uploaded zip file will be checked to make sure it contains a plugin folder with the correct name.

The value should match the `$plugin->component` value defined in your version.php.';
$string['pluginfrankenstyle_link'] = 'Development:Frankenstyle';
$string['plugininformation'] = 'Plugin information';
$string['plugininset'] = 'This plugin is part of set {$a}.';
$string['plugininsets'] = 'This plugin is part of sets {$a}.';
$string['pluginlinks'] = 'Useful links';
$string['pluginname'] = 'Moodle plugins directory';
$string['pluginreports'] = 'Plugin reports';
$string['pluginreviews'] = 'Plugin reviews';
$string['plugins'] = 'Plugins';
$string['pluginsawards'] = 'Awards';
$string['pluginshortdescription'] = 'Plugin short description';
$string['pluginshortdescription_help'] = 'This should be a short description that sums up features of your plugin in a sentence or two.';
$string['pluginssets'] = 'Sets';
$string['plugintitle'] = '{$a->category}: {$a->plugin}';
$string['pluginupdated'] = 'Plugin successfully updated';
$string['pluginversionupdated'] = 'Plugin version successfully updated';
$string['previousversion'] = 'Previous version';
$string['previousversions'] = 'Previous versions';
$string['privacy:plugin'] = 'Plugin {$a->id}: {$a->name}';
$string['privacy:metadata:db:contributor'] = 'Tracks contributors to the plugin';
$string['privacy:metadata:db:contributor:maintainer'] = 'Contributor role';
$string['privacy:metadata:db:contributor:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:contributor:timecreated'] = 'Timestamp of when the contribution was recorded';
$string['privacy:metadata:db:contributor:type'] = 'Contribution description';
$string['privacy:metadata:db:favourite'] = 'Tracks plugins marked as favourite by users';
$string['privacy:metadata:db:favourite:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:favourite:status'] = 'Whether the plugin is marked as favourite one by the user';
$string['privacy:metadata:db:favourite:timecreated'] = 'Timestamp of when the plugin was first marked as favourite';
$string['privacy:metadata:db:favourite:timemodified'] = 'Timestamp of when the status was last modified';
$string['privacy:metadata:db:log'] = 'Log of editing actions on plugins directory';
$string['privacy:metadata:db:log:action'] = 'Identified of the action';
$string['privacy:metadata:db:log:bulkid'] = 'Joins the multiple log entries performed within the same request';
$string['privacy:metadata:db:log:info'] = 'Stores information and comments for logged action in internal format';
$string['privacy:metadata:db:log:ip'] = 'Detected IP address';
$string['privacy:metadata:db:log:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:log:time'] = 'Timestamp of when the event happened';
$string['privacy:metadata:db:plugin'] = 'List of contributed plugins';
$string['privacy:metadata:db:plugin:approved'] = 'Approval status of the plugin';
$string['privacy:metadata:db:plugin:name'] = 'Name of the plugin';
$string['privacy:metadata:db:plugin:timefirstapproved'] = 'Timestamp of when the plugin was first approved';
$string['privacy:metadata:db:plugin:timelastapprovedchange'] = 'Last time the approval status was changed';
$string['privacy:metadata:db:pluginawards'] = 'Contains the different awards that can be granted to a plugin';
$string['privacy:metadata:db:pluginawards:awardid'] = 'Award identifier';
$string['privacy:metadata:db:pluginawards:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:pluginawards:timeawarded'] = 'Timestamp of the award was granted';
$string['privacy:metadata:db:review'] = 'Holds the plugins reviews';
$string['privacy:metadata:db:review:status'] = 'The review status';
$string['privacy:metadata:db:review:timereviewed'] = 'The time the plugin version was reviewed';
$string['privacy:metadata:db:review:versionid'] = 'Version identifier';
$string['privacy:metadata:db:setplugin'] = 'Tracks if the plugin is part of a set';
$string['privacy:metadata:db:setplugin:setid'] = 'Set identifier';
$string['privacy:metadata:db:setplugin:timeadded'] = 'Timestamp of when the plugin was added to the set';
$string['privacy:metadata:db:statsraw'] = 'Holds raw download stats';
$string['privacy:metadata:db:statsraw:downloadmethod'] = 'How was the plugin downloaded';
$string['privacy:metadata:db:statsraw:exclude'] = 'Should this record be excluded from download stats';
$string['privacy:metadata:db:statsraw:info'] = 'Additional info';
$string['privacy:metadata:db:statsraw:ip'] = 'Detected IP address';
$string['privacy:metadata:db:statsraw:timedownloaded'] = 'Timestamp of the download event';
$string['privacy:metadata:db:statsraw:versionid'] = 'Version identifier';
$string['privacy:metadata:db:subscription'] = 'Tracks subscriptions';
$string['privacy:metadata:db:subscription:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:subscription:type'] = 'Type of subscription';
$string['privacy:metadata:db:usersite'] = 'Holds the list of sites adminitered by the user';
$string['privacy:metadata:db:usersite:sitename'] = 'Name of the site';
$string['privacy:metadata:db:usersite:siteurl'] = 'URL of the site';
$string['privacy:metadata:db:usersite:version'] = 'Moodle version of the site';
$string['privacy:metadata:db:vers'] = 'Holds information about plugin version';
$string['privacy:metadata:db:vers:pluginid'] = 'Plugin identifier';
$string['privacy:metadata:db:vers:timecreated'] = 'Timestamp of when the version was added';
$string['privacy:metadata:preference:moodleversion'] = 'The Moodle version user is interested in';
$string['privacy:metadata:preference:plugincategory'] = 'Holds recently used plugins category when showing stats';
$string['privacy:metadata:subsystem:comment'] = 'Users can leave comments on plugins';
$string['queuestats'] = 'Approval queue stats';
$string['queuestatsnodata'] = 'No data available';
$string['queuestatschartinfo'] = 'MEDIAN = {$a->median} days, N = {$a->sample} plugins';
$string['queuestatsdistreview'] = 'Distribution of plugin initial review times';
$string['queuestatsdistreview_help'] = 'The histogram displays how long it took to provide the initial review and feedback on submitted plugins. After the initial review, the plugin is either approved immediately, or sent back to the maintainer as needing more work. These statistics make use of the plugin status changes log to estimate the time between when the plugin had been submitted and when its status changed for the first time. Only plugins submitted in last 180 days are analysed.';
$string['queuestatschartaxisx'] = 'Days spent in the approval queue forthe initial feedback';
$string['queuestatschartaxisy'] = 'Number of plugins';
$string['rate'] = 'Rate';
$string['ratethisversion'] = 'Rate this version';
$string['rateaverage_none'] = 'This has not been rated yet';
$string['rateaverage_one'] = 'There is a single rating of {$a->aggregate}';
$string['rateaverage_many'] = '{$a->ratings} ratings with an average of {$a->aggregate}';
$string['redirecteditaddedversion'] = 'The version is added. Please proceed to edit version screen and fill the missing information.';
$string['redirecteditregisteredplugin'] = 'The plugin is registered and the version is added. Proceeding now to edit plugin\'s latest version and fill the missing information.';
$string['redirecteditplugin'] = 'Your new plugin is registered and now awaiting approval, along with {$a->pendingcount} other plugins in the queue. Please fill the rest of plugin information.';
$string['registerplugintext'] = 'Register plugin text';
$string['registerplugintextdefault'] = 'Thank you for choosing to share your plugin with the rest of the Moodle community.<br />When creating a plugin you must first tell us about the plugin you are adding and then upload the version of your plugin that you wish to share.';
$string['registerplugintextdesc'] = 'The text to appear on the top of Register new plugin page. If not set it will be taked from string "registerplugintextdefault"';
$string['renamereadme'] = 'Fix README file name';
$string['renamereadme_help'] = 'If your README file has no extension or doesn\'t have one of supported extensions (txt, html, htm or md), users may not recognize the file type and fail to open it locally. When this option is checked and your README file does not have a supported extension, it will be renamed to README.txt.';
$string['renameroot'] = 'Rename root directory';
$string['renameroot_help'] = 'Assures that the name of the root directory in the archive matches the expected name of the plugin. This option is particularly useful for archives generated at Github.';
$string['reports'] = 'Reports';
$string['reportsoverview'] = 'Overview';
$string['repository'] = 'Repository';
$string['required'] = 'Required';
$string['requirements'] = 'Requirements';
$string['releasename'] = 'Release name';
$string['releasenotes'] = 'Release notes';
$string['releasenotesextension'] = '{$a} found with unsupported extension. It is recommended to set explicit extension txt, htm, html or md. You may choose the <b>\'Fix README file name\'</b> option to rename this file to README.txt (click \'Back\' if form is uneditable)';
$string['releasenotesnotfound'] = 'Release notes not found (searched for files README, README.*)';
$string['releasenotesfound'] = 'Release notes are found in file <b>{$a}</b>';
$string['releasenotesfoundmore'] = 'Found more than one file with possible release notes. Used text from <b>{$a}</b>';
$string['releasenotesrenamed'] = 'File {$a} renamed to README.txt';
$string['review'] = 'Review';
$string['reviewer'] = 'Reviewer';
$string['reviewaversion'] = 'Write a review of this plugin version';
$string['reviewpublished'] = 'Review successfully published';
$string['reviews'] = 'Reviews';
$string['reviewsetstatus0'] = 'Unpublish';
$string['reviewsetstatus1'] = 'Publish';
$string['reviewstatus0'] = 'Waiting for publishing';
$string['reviewstatus1'] = 'Published';
$string['reviewtype'] = 'Review type';
$string['reviewtype0'] = 'Initial';
$string['reviewtype1'] = 'Re-approval';
$string['search'] = 'Search';
$string['searchcode'] = 'Search code';
$string['searchplugins'] = 'Search';
$string['searchresults'] = 'Search results';
$string['searchresultsnum'] = '{$a} search results';
$string['selectmoodleversion'] = 'Your Moodle version';
$string['selectplugincategory'] = 'Select plugin type:';
$string['set'] = 'Set';
$string['scale'] = 'Scale';
$string['scheduleapprove'] = 'Request re-approval';
$string['screenshots'] = 'Screenshots';
$string['screenshots_help'] = 'You are encouraged to upload screenshots that demonstrate your plugin\'s essential features.';
$string['setuprequired'] = 'This plugin needs to be setup before it can be used. Please contact your system administrator and ask him to complete the setup procedure.';
$string['shortdescription'] = 'Short description';
$string['shortname'] = 'Short name';
$string['show'] = 'Show';
$string['showthisplugin'] = 'Show this plugin';
$string['siteadded'] = 'Site added';
$string['sitename'] = 'Site name';
$string['sitename_help'] = 'The name of your moodle site.';
$string['siteurl'] = 'Site URL';
$string['siteurl_help'] = 'The URL to your site.';
$string['siteupdated'] = 'Site updated.';
$string['software'] = 'Software';
$string['softwareversion'] = 'Software version';
$string['softwareversionname'] = 'Software version release name';
$string['softwareversionname_help'] = 'This is the release name of the software version.

In the case of Moodle this is the major version e.g. 1.9, 2.2 or 2.4. This value is also used by the AMOS exporter to determine the correct Moodle branch.

In the case of PHP this is the version number.';
$string['softwareversionnumber'] = 'Software version build';
$string['softwareversionnumber_help'] = 'This is the version build number of the software release. In the case of Moodle this is the $version in version.php, in the case of PHP this is just the release number e.g. 5.3.2';
$string['sortorder'] = 'Sort order';
$string['sourcecontrolurl'] = 'Source control URL';
$string['sourcecontrolurllink'] = 'Source control URL';
$string['sourcecontrolurl_help'] = 'If you use a public repository for all of your work on this plugin please enter its URL here.';
$string['supportedmoodle'] = 'Supports Moodle {$a}';
$string['supportedmoodleversion'] = 'Supported Moodle versions';
$string['supportedmoodleversion_help'] = 'Select all Moodle versions that this plugin version supports. This information is essential to make the available updates notification work and to allow users install your plugin via the web interface.';
$string['supportedmoodleversion_link'] = 'Development:Releases';
$string['supportedsoftware'] = 'Supported software';
$string['supportedsoftwarename'] = 'Supported {$a}';
$string['supportablesoftware'] = 'Supportable software';
$string['supportablesoftware_help'] = 'This is the software solution this is required. By default only the version Moodle and the version of PHP required can be tracked however more can be adding the following code to your config.php file:

    $CFG->local_plugins_supportablesoftware = \'Moodle,PHP\';';
$string['supportablesoftwaredesc'] = 'A comma separated list of supportable software for plugin versions.';
$string['stable'] = 'Stable';
$string['stats'] = 'Stats';
$string['statistics'] = 'Statistics';
$string['status'] = 'Status';
$string['subcategories'] = 'Related plugin types';
$string['subscribecomments'] = 'Subscribe to comments';
$string['unsubscribecomments'] = 'Unsubscribe from comments';
$string['subscriptionupdated'] = 'Subscription updated';
$string['svn'] = 'SVN - Subversion';
$string['timecreated'] = 'Release date';
$string['timecreatedsubmitted'] = 'Submitted';
$string['timecreateddate'] = 'Released: {$a}';
$string['timefirstapproved'] = 'Approved';
$string['timelastreleaseddate'] = 'Latest release: {$a}';
$string['timequeuing'] = 'Queuing';
$string['trackingwidgets'] = 'Potential privacy issues';
$string['trackingwidgets_help'] = 'Describe any potential privacy issues there might be with this plugin. Examples include embedded widgets or images from other sites like Facebook, Google and so on, analytics code, and so on.';
$string['translationsamos'] = 'Plugin translations';
$string['translationscontribute'] = 'Contribute translations for {$a}';
$string['translationscontributeotherlangs'] = 'other languages';
$string['translationstab'] = 'Translations';
$string['translationstats'] = 'Translation stats';
$string['translationratio'] = 'Percentage of language strings translated';
$string['translationsnumofstrings'] = 'Number of strings defined by the plugin: {$a}';
$string['translationunavailable'] = 'Translation stats not available. ';
$string['translationunapproved'] = 'Translation stats are not available for unapproved plugins.';
$string['unknown'] = 'Unknown';
$string['unknownuser'] = 'Unknown user {$a}';
$string['unstable'] = 'Unstable';
$string['updateableversions'] = 'Can be updated from';
$string['updateableversions_help'] = 'Specify other versions of this plugin that can be updated to this version.

Note that this information *is not* taken into account in available update notifications generated by the plugins directory.';
$string['updatetoversions'] = 'Can be updated to';
$string['uploadonearchive'] = 'Upload only one archive';
$string['uploadversionarchive'] = 'Upload a zip of the plugin';
$string['usagebyverchart'] = 'Sites using this plugin by Moodle version';
$string['usagebyverserie'] = 'Number of sites';
$string['usageinfo'] = 'Used on {$a} site(s)';
$string['usagemonthlychart'] = 'Number of sites using the plugin: {$a}';
$string['usagemonthlyserie'] = 'Number of sites';
$string['usagestats'] = 'Usage stats';
$string['usagestatsfilesroot'] = 'Usage stats files location';
$string['usagestatsfilesroot_desc'] = 'Full path to the directory where generated stats files are located. This directory should contain years as subdirectories and there should exist files like _2016/01/monthly.stats_ in expected format. If left empty, stats will not be processed.';
$string['usagenostats'] = 'No usage statistics available at the moment.';
$string['username'] = 'User';
$string['username_help'] = 'Enter either moodle.org username or id';
$string['userrating'] = 'You rated this {$a}';
$string['validationinfo'] = 'Code validation information';
$string['validationinfolabel'] = 'Info';
$string['validationerrors'] = 'Code validation errors';
$string['validationerrorslabel'] = 'Important';
$string['validationwarnings'] = 'Code validation warnings';
$string['validationwarningslabel'] = 'Warning';
$string['vcsbranch'] = 'VCS branch';
$string['vcsbranch_help'] = 'If this versions of the plugin is on a particular branch this should contain the name of that branch.';
$string['vcsrepositoryurl'] = 'VCS repository URL';
$string['vcsrepositoryurl_help'] = 'This should be the URL to the public repository you used.';
$string['vcssystem'] = 'Version control system (VCS)';
$string['vcssystem_help'] = 'This is the <b>V</b>ersion <b>C</b>ontrol <b>S</b>ystem that you use to track your development efforts on this version of the plugin. Please select the CVS you use or alternatively select other and tell us which one you use.';
$string['vcssystemother'] = 'Other...';
$string['vcstag'] = 'VCS tag';
$string['vcstag_help'] = 'If this version was given a particular tag you should enter the tag name here.';
$string['vcswidgetnotags'] = 'No tags found';
$string['vcswidgetnotags_help'] = 'If you tag your code at GitHub, you can publish the tagged version easily here in the plugins directory.';
$string['vcswidgetnotags_link'] = 'http://git-scm.com/book/en/v2/Git-Basics-Tagging';
$string['version'] = 'Version';
$string['versionformaterror'] = 'Please use version number in the form of YYYYMMDDRR.';
$string['versionnotavailable'] = '<span class="badge badge-info">Note</span> This plugin version is no longer available here in the plugins directory.';
$string['versionnumber'] = 'Version build number';
$string['versionnumber_help'] = 'This is the version number of the plugin. It should match the `$plugin->version` value defined in the version.php file if the plugin has a version.php file.

Even if you are uploading a package without version.php file, we recommend to follow the same versioning scheme. Use today\'s date as string followed by two zeros in the format YYYYMMDD00 e.g. 2014052000 for 20th May 2014.';
$string['versionnumber_link'] = 'Development:version.php';
$string['viewcontributorscontribution'] = 'View other contributions';
$string['visibilitychanged'] = 'The visibility of this plugin has been successfully changed';
$string['visible'] = 'Visible';
$string['versioncontrolinfo'] = 'Version control information';
$string['versioncreated'] = 'Successfully created a new plugin version';
$string['versionmissing'] = '??';
$string['versioninformation'] = 'Version information';
$string['versionname'] = 'Version release name';
$string['versionname_help'] = 'This is the release name of this version of the plugin. It should match the `$plugin->release` value defined in the version.php file. It can be anything you like although it is recommended to choose a pattern and stick with it. Common practise is to use a human friendly version number.';
$string['versionname_link'] = 'Development:version.php';
$string['versionphpcomponentfound'] = 'Component found in version.php: <b>{$a}</b>';
$string['versionphpcomponentnotfound'] = 'The plugin does not declare its <b>component</b> in version.php. This declaration will be required for Moodle 3.0.';
$string['versionphpcomponentmismatch'] = 'Component declared in version.php file: <b>{$a->found}</b> does not match the expected component: <b>{$a->expected}</b>';
$string['versionphpfilenotfound'] = 'File <b>version.php</b> not found';
$string['versionphpinvalidformat'] = 'Invalid format of the version.php file';
$string['versionphplegacyformat'] = 'The usage of $module in mod/xxx/version.php files was deprecated in Moodle 2.7. It is recommended to use the $plugin notation which has been supported since Moodle 2.6';
$string['versionphpmaturityfound'] = 'Maturity information is found in version.php: <b>{$a}</b>';
$string['versionphpmaturitynotfound'] = 'Maturity information (${$a}->maturity) not found in version.php';
$string['versionphpmixedformat'] = 'The version.php file must not use both $module and $plugin notation';
$string['versionphpreleasefound'] = 'Release name is found in version.php: <b>{$a}</b>';
$string['versionphpreleasenotfound'] = 'Release name (${$a}->release) not found in version.php';
$string['versionphprequirestoobig'] = 'In your version.php you have specified <b>${$a->plugintype}->requires = {$a->requires}</b>, which corresponds to <b>Moodle {$a->minrelease}</b> and will not install on <b>Moodle {$a->release}</b> that you chose in the form</b>';
$string['versionphprequirestoosmall'] = 'In your version.php you have specified <b>${$a->plugintype}->requires = {$a->requires}</b>, which is earlier than the official release of <b>Moodle {$a->release} ({$a->version})</b>';
$string['versionphpsoftwareversionsfound'] = 'Required Moodle core version found in version.php: {$a}';
$string['versionphpsoftwareversionsnotfound'] = 'Required Moodle core version (${$a}->requires) not found in version.php';
$string['versionphpversionfound'] = 'Build number is found in version.php: <b>{$a}</b>';
$string['versionphpversionnotfound'] = 'Build number (${$a}->version) not found in version.php';
$string['versionphpversionformaterror'] = 'Build number (${$a}->version) in version.php has an incorrect format. Please use version number in the form of YYYYMMDDxx';
$string['versionphpversionformatok'] = 'Build number (${$a}->version) format OK in version.php';
$string['versionphprequiresformaterror'] = 'Required Moodle version (${$a}->requires) in version.php has an incorrect format. Please use Moodle core version number in the form of YYYYMMDDRR or YYYYMMDDRR.XX';
$string['versionphpincompatibleformaterror'] = 'Incompatible Moodle branch number (${$a}->incompatible) in version.php has an incorrect format. Please use branch code number like 39 (for Moodle 3.9) or 311 (for Moodle 3.11).';
$string['versionreleasefor'] = '{$a->version} for {$a->requirements}';
$string['versionreviews'] = 'Version reviews';
$string['versions'] = 'Versions';
$string['version_help'] = 'The  moodle release version of your moodle site.';
$string['versionthruto'] = ' to ';
$string['view'] = 'View';
$string['viewallinaward'] = 'View all plugins that have been awarded this award';
$string['viewallincategory'] = 'View all plugins of this type';
$string['viewallinset'] = 'View all plugins in this set';
$string['viewallreviews'] = 'View all reviews on this version ({$a})';
$string['viewfullreview'] = 'View full review';
$string['viewvalidation'] = 'View validation results';
$string['viewreviews'] = 'View version reviews ({$a})';
$string['website'] = 'Website';
$string['websiteurl'] = 'Website URL';
$string['websiteurllink'] = 'Website URL';
$string['websiteurl_help'] = 'If you have a website set up that has more information about your plugin or your work you can put its URL here.';
$string['writereview'] = 'Write a review';
$string['writereviewon'] = 'Write a review on {$a}';
$string['wrongplugincategory'] = 'Illegal frankenstyle component name for plugin of this type. The plugin with the frankenstyle component name you specified should be registered as {$a}';

/**
 * Exceptions that can be thrown within the local_plugins plugin
 */
$string['exc_archivecontents'] = 'Zip archive should contain one directory with the name <b>{$a}</b>';
$string['exc_archivenotfound'] = 'No zip archive was found.';
$string['exc_archiveonedir'] = 'Zip archive should contain one directory';
$string['exc_awardnamerequired'] = 'You must give the award a name.';
$string['exc_cannotaddcontributors'] = 'You are only allowed to add maximum of {$a} contributors';
$string['exc_cannotchangeversionarchive'] = 'You cannot change a plugin archive once it has been set. You need to create a new plugin version.';
$string['exc_cannotcreateversion'] = 'You do not have permission to create a version of this plugin';
$string['exc_cannotcreateplugindir'] = 'Cannot create plugin storage directory within your Moodle data directory';
$string['exc_cannoteditplugin'] = 'You do not have permission to edit this plugin';
$string['exc_cannotmanagecontributors'] = 'You do not have permission to manage contributors for this plugin';
$string['exc_cannotviewplugin'] = 'Sorry, we have not found such plugin. Maybe it does not exist. Or it has been deleted, temporarily disabled, or you do not have permissions to view it.';
$string['exc_cannotviewversion'] = 'Sorry, we have not found such plugin version. Maybe it does not exist. Or it has been deleted, temporarily disabled, or you do not have permissions to view it.';
$string['exc_categorycontainsplugins'] = 'You must remove all plugins of a type before deleting it.';
$string['exc_categorycontainssubcategories'] = 'You must remove all child types from a parent type before deleting it.';
$string['exc_categoryforbidden'] = 'This special plugin type container is not supposed to contain any plugins.';
$string['exc_categoryinvalidparent'] = 'Invalid parent type';
$string['exc_categorynamealreadyexists'] = 'The plugin type name you have selected has already been used. You must choose a unique name.';
$string['exc_categorynamerequired'] = 'You must provide a name when creating a plugin type';
$string['exc_categoryshortdescriptionrequired'] = 'You must provide a short description when creating a plugin type';
$string['exc_couldnotstorearchive'] = 'Could not move uploaded archive to plugin storage';
$string['exc_didnotfindarchiveinfilesystem'] = 'No archive file was found in specified location within the filesystem.';
$string['exc_emptyarchive'] = 'The zip archive you uploaded did not contain any files';
$string['exc_filenotfoundinarchive'] = 'File <b>{$a}</b> must exist in archive and is not found.';
$string['exc_invalidaward'] = 'Invalid award requested';
$string['exc_invalidreport'] = 'Invalid report requested';
$string['exc_invalidreview'] = 'Invalid review requested';
$string['exc_invalidreviewcriterion'] = 'Invalid review criterion requested';
$string['exc_invalidversion'] = 'Invalid version requested';
$string['exc_invalidset'] = 'Invalid set requested';
$string['exc_invalidsoftwareversion'] = 'Invalid software version';
$string['exc_noguestsallowed'] = 'Guests are not allowed to access this area';
$string['exc_permissiondenied'] = 'Permission denied';
$string['exc_plugincategoryinvalid'] = 'The plugin type you have selected is invalid or no longer an option.';
$string['exc_plugincategoryrequired'] = 'You must select a type of your plugin.';
$string['exc_pluginnamerequired'] = 'You must provide a name for your plugin.';
$string['exc_pluginnotfound'] = 'Plugin not found';
$string['exc_pluginshortdescriptionrequired'] = 'You must provide a short description of your plugin.';
$string['exc_reviewcriterionnamerequired'] = 'You must enter a name for the review criteria';
$string['exc_setnameexists'] = 'The set name you have chosen already exists';
$string['exc_setnamerequired'] = 'A set must have a valid name';
$string['exc_softwareversexists'] = 'A software version with the same build number already exists';
$string['exc_useriscontributor'] = 'This user is already registered as a contributor of this plugin.';
$string['exc_usernotfound'] = 'User with this username or id is not found.';
$string['exc_specifypluginattributes'] = 'Unable to determine type, frankenstyle component name or required Moodle core version from uploaded archive.';
$string['exc_zipvalidationerrors'] = 'Unable to add a new version - validation errors';
$string['exc_versionalreadyexists'] = 'There is already a plugin version with this version number \'{$a}\'. Every release of a plugin should have the version build number increased.';
$string['exc_addinglowerversionnoeffect'] = 'There is already more recent version {$a} available for all Moodle versions supported by this newly added version.';
$string['exc_addinglowerversionpartialeffect'] = 'There are more recent versions available for some of the Moodle versions supported by this newly added version.';
$string['exc_invalidbase64'] = 'ZIP file contents not encoded with MIME base64.';

$string['report_approval_reviews'] = 'Recent approval reviews';
$string['report_approval_reviews_desc'] = 'Lists all plugins that had their approval status changed in the given timeframe t (default="1 week").';
$string['report_approval_reviews_since'] = 'Showing plugins reviewed since {$a}';
$string['report_approvedplugins'] = 'Recently approved plugins';
$string['report_approvedpluginsdesc'] = 'This report lists all published plugins that have been approved in the given timeframe.';
$string['report_approvedpluginssince'] = 'Showing plugins approved since: {$a}';
$string['report_unapprovedplugins'] = 'Unapproved plugins';
$string['report_unapprovedpluginsdesc'] = 'This report contains all of the plugins that have been unapproved (marked as needing more work).';
$string['report_unapprovedplugins_public'] = 'Not yet approved plugins';
$string['report_unapprovedplugins_public_desc'] = 'The following {$a} plugins have been submitted to the plugins directory but are not yet approved.';
$string['report_unapprovedversions'] = 'Unapproved versions';
$string['report_unapprovedversionsdesc'] = 'This report contains all of the plugin versions that have been unapproved (marked as needing more work).';
$string['report_pendingapprovalplugins'] = 'Waiting for approval plugins';
$string['report_pendingapprovalpluginsdesc'] = 'This report contains all of the plugins that are waiting for approval.';
$string['report_pendingapprovalversions'] = 'Waiting for approval versions';
$string['report_pendingapprovalversionsdesc'] = 'This report contains all of the plugin versions that exist within the system but have not yet been approved.';
$string['report_reviews'] = 'Reviewed plugins';
$string['report_reviewsdesc'] = 'List of plugins with a community review.';
$string['report_userfavourites'] = 'My favourite plugins';
$string['report_userfavouritesdesc'] = 'List of plugins you have marked as your favourite ones.';
$string['report_usersites'] = 'My sites';
$string['report_usersitesdesc'] = '<p>Sites entered here are used when choosing to directly install plugins to your site. Moodle 2.5 or higher is required.</p>';
$string['report_favourites'] = 'Favourite plugins';
$string['report_favouritesdesc'] = 'List of plugins that have been marked as favourite by the Moodle community members.';

/**
 * Capabilities
 */
$string['plugins:view']                   = 'View contributed plugins';
$string['plugins:viewunapproved']         = 'View unapproved contributed plugins';
$string['plugins:viewreports']            = 'View reports on contributed plugins';
$string['plugins:createplugins']          = 'Create contributed plugins';
$string['plugins:editownplugins']         = 'Edit own contributed plugins';
$string['plugins:editanyplugin']          = 'Edit any contributed plugin';
$string['plugins:deleteownplugin']        = 'Delete own contributed plugins';
$string['plugins:deleteanyplugin']        = 'Delete any contributed plugin';
$string['plugins:deleteownpluginversion'] = 'Delete own contributed plugin versions';
$string['plugins:deleteanypluginversion'] = 'Delete any contributed plugin version';
$string['plugins:approveplugin']          = 'Approve contributed plugin';
$string['plugins:approvepluginversion']   = 'Approve contributed plugin version';
$string['plugins:autoapproveplugins']     = 'Auto approve contributed plugins';
$string['plugins:autoapprovepluginversions']= 'Auto approve contributed plugin versions';
$string['plugins:approvereviews']         = 'Approve submitted reviews on contributed plugins';
$string['plugins:publishreviews']         = 'Submit reviews on contributed plugins';
$string['plugins:editownreview']          = 'Edit own reviews on contributed plugins';
$string['plugins:editanyreview']          = 'Edit any review on contributed plugins';
$string['plugins:comment']                = 'Comment on contributed plugins';
$string['plugins:rate']                   = 'Rate contributed plugins';
$string['plugins:editowntags']            = 'Edit own tags for contributed plugin';
$string['plugins:editanytags']            = 'Edit any tags for contributed plugins';
$string['plugins:managedescriptors']      = 'Manage plugins descriptors';
$string['plugins:managesupportableversions']= 'Manage supportable versions for contributed plugins module';
$string['plugins:managesets']             = 'Manage sets for contributed plugins module';
$string['plugins:addtosets']              = 'Add contributed plugins to sets';
$string['plugins:manageawards']           = 'Manage awards for contributed plugins';
$string['plugins:handoutawards']          = 'Hand out awards for contributed plugins';
$string['plugins:managecategories']       = 'Manage types of contributed plugins';
$string['plugins:managereviewcriteria']   = 'Manage review criteria for contributed plugins';
$string['plugins:notifiedunapprovedactivity'] = 'Be notified on an activity in unapproved plugins';
$string['plugins:markfavourite']          = 'Mark favourite plugins';
$string['plugins:viewqueuestats'] = 'View approval queue stats';
$string['plugins:viewprecheckreport'] = 'View plugins prechecks report';

/**
 * Task API
 */
$string['taskupdatecontribcohort'] = 'Update plugins contributors cohort';
$string['taskupdatedownloadstats'] = 'Update download stats';
$string['taskinvalidatequeuestatscache'] = 'Invalidate approval queue stats cache';
$string['taskupdateusagestats'] = 'Update plugin usage stats';
