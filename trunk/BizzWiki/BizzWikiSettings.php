<?php

/***************************************************************************
	
	BizzWikiSettings.php
	
	$Id$

	TODO:
	=====
	- disable proxy caching functionality for 'file system' & BizzWiki namespaces
	

****************************************************************************/
// Define the base of BizzWiki
define('BIZZWIKI', '1_10_9alpha');
$bwVersion = BIZZWIKI;
$bwPath    = $IP.'/BizzWiki';
$bwExtPath = $IP.'/BizzWiki/extensions';

// Base class for multiple extensions
require('extensions/ExtensionClass.php');
require('extensions/RunPHP_class.php');
require('extensions/ParserPhase2/ParserPhase2.php');

// Parser & Page caching.
$wgEnableParserCache = true;
$wgParserCacheType   = CACHE_ANYTHING;
$wgCachePages        = true;

// Disable raw html
// (There is the extension 'SecureHTML' to better cover this)
$wgRawHtml = false;  // on protected pages, one can use 'SecureHTML' extension 
					// to achieve the same goal

// Need to include standard 'Namespace.php'
require($IP.'/includes/Namespace.php');

## DEFINE NEW NAMESPACES HERE 
## {{

	// define new namespaces constants
	define('NS_BIZZWIKI',   100);
	define('NS_FILESYSTEM', 102);
	define('NS_INTERWIKI',  104);
	define('NS_API',		106);	
	
	// Add the new namespaces to the global variables
	$wgExtraNamespaces[NS_BIZZWIKI]   = 'Bizzwiki';
	$wgExtraNamespaces[NS_FILESYSTEM] = 'Filesystem';
	$wgExtraNamespaces[NS_INTERWIKI]  = 'Interwiki';
	$wgExtraNamespaces[NS_API]		  = 'Api';	

## }}

$wgCanonicalNamespaceNames[NS_MAIN] = 'Main';

// Put all the namespaces in the global variable
$wgCanonicalNamespaceNames = $wgCanonicalNamespaceNames + $wgExtraNamespaces;

// Subpages
$bwNamespacesWithSubpages = array ( NS_MAIN,
									NS_TALK,
									NS_PROJECT,
									NS_PROJECT_TALK,
									NS_CATEGORY,
									NS_CATEGORY_TALK,
									NS_MEDIAWIKI,
									NS_MEDIAWIKI_TALK,									
									NS_BIZZWIKI,
									NS_FILESYSTEM,
									NS_INTERWIKI,	// not used at the moment.
									);
foreach ( $bwNamespacesWithSubpages as $index => $bwx )
	$wgNamespacesWithSubpages[ $bwx ] = true;
	
/* *******************************************************
	Apply new permission management functionality
	1) Hierarchical Namespace Permissions
	2) Wipe out the standard Mediawiki permission settings
	3) All rights
	4) Provision the new permission settings
*/
require('extensions/HierarchicalNamespacePermissions/HierarchicalNamespacePermissions.php');
require('extensions/RawRight/RawRight.php');
require('extensions/ViewsourceRight/ViewsourceRight.php');
require('extensions/WatchRight/WatchRight.php');
require('extensions/MakeSysop/SpecialMakesysop.php');
require('extensions/DeSysop/SpecialDesysop.php');

// define group hierarchy
// default is: sysop -> user -> *
#hnpClass::singleton()->setGroupHierarchy( array('sysop', 'user', '*') ));

unset( $wgGroupPermissions );

$bwNamespaceIndependantRights =  array( 'createaccount',
										'ipblock-exempt',
										'hideuser',
										'userrights',		
										'siteadmin',
										'import',
										'importupload',										
										'deletedhistory',
										'deleterevision',	// TODO
										'hiderevision',		// TODO
										'block',
										'bot',             	// TODO
										'proxyunbannable', 	// TODO
										'trackback',		// TODO
										'unwatchedpages',	// TODO
										'readlog',  		// BizzWiki specific
										'siteupdate',		// BizzWiki specific
										'undelete',			// BizzWiki specific
										
										'skipcaptcha',		// ReCAPTCHA specific
										'makesysop',		// MakeSysop extension specific
										'desysop',			// DeSysop extension specific										
										
									);


$bwNamespaceDependantRights =  array(	'read', 'edit', 'minoredit', 'create', 'delete', 'move',								
										'nominornewtalk', 
										"createpage", "createtalk",
										
										"rollback",			// TODO
										'protect',
										'patrol', "autopatrol",
										'purge',			// TODO
										'upload', 'reupload', 'reupload-shared', // for now, those rights
										'upload_by_url',						// are only relevant to NS_IMAGE
										
										'raw',        // BizzWiki specific
										'viewsource', // BizzWiki specific
										'browse',     // BizzWiki specific
										'search',     // BizzWiki specific
										'watch',		// BizzWiki specific
										'unwatch',		// BizzWiki specific
										
									);
									
// Critical permission system initialization -- DO NOT TOUCH
## {{
hnpClass::singleton()->addNamespaceIndependantRights( $bwNamespaceIndependantRights );
hnpClass::singleton()->addNamespaceDependantRights(   $bwNamespaceDependantRights );
## }}

	// Steward Group
$wgGroupPermissions['steward' ][hnpClass::buildPermissionKey("~","~","makesysop")]		= true;	
$wgGroupPermissions['steward' ][hnpClass::buildPermissionKey("~","~","userrights")]		= true;	

	// Bureaucrat Group
$wgGroupPermissions['bureaucrat' ][hnpClass::buildPermissionKey("~","~","makesysop")]	= true;	
$wgGroupPermissions['bureaucrat' ][hnpClass::buildPermissionKey("~","~","desysop")]		= true;	

	// Sysop gets all the rights.
	// ##########################
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","~")]     = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","!bot")]  = true;  // required !!
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/commitfil",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/upload",		"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/delete",   	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/rights",     "browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/move",     	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/block",    	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/emaillog",  	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usetchglog",	"browse")] = true;
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/watchlog",	"browse")] = true;
// replication related.
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/ftchrclog",	"browse")] = true;	// partner RC
$wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/schlog",		"browse")] = true;	// task scheduler

	// Anonymous
	// #########
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey("~","~","createaccount")] = true;

if (!defined('BIZZWIKIDEMO'))
{
	// remove access to some log entries.
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers",	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/commitfil",	"!browse")] = true; // FileManager extension
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/upload",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/delete",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/rights",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/move",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/block",		"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/emaillog",  	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/usetchglog",	"!browse")] = true;
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/watchlog",	"!browse")] = true;

	// replication related.
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/ftchrclog",	"!browse")] = true;	// partner RC
$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/schlog",		"!browse")] = true;	// task scheduler

#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey("~","~","readlog")] = true;  // debugging
}

	// Namespace accessible by 'Anonynous'
	// ###################################
$bwAnonymousNamespaces = array( NS_MAIN, NS_TALK,
								NS_PROJECT, NS_PROJECT_TALK,
								NS_CATEGORY, NS_CATEGORY_TALK,
								NS_HELP, NS_HELP_TALK,
								NS_SPECIAL,
								NS_INTERWIKI // BizzWiki specific
								); 

	// Rights available to 'Anonymous'
	// ###############################
foreach( $bwAnonymousNamespaces as $index => $bwx )
{	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","read")]   = true;
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;
	#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","raw")] = true;   
	#$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","viewsource")] = true;
}

	// 'Users' inherit all rights from '*' (anonymous)
	// ############################################### 
	
		// Log Entries access
$wgGroupPermissions['user' ][hnpClass::buildPermissionKey(NS_SPECIAL,"Log/newusers","!browse")] = true;

	// Namespace accessible by 'User'
	// ##############################
$bwUserNamespaces = array (	NS_TEMPLATE, NS_TEMPLATE_TALK,
							NS_IMAGE, NS_IMAGE_TALK,
							NS_USER, NS_USER_TALK														
							);	

$bwUserNamespaces = array_merge( $bwAnonymousNamespaces, $bwUserNamespaces);

	// Additional rights available to 'User'
	// #####################################
foreach( $bwUserNamespaces as $index => $bwx )
	{
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","read")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","watch")] = true;		
		$wgGroupPermissions['user' ][hnpClass::buildPermissionKey($bwx,"~","unwatch")] = true;				
	}

	// Equivalent functionality to 'KeepYourHandsToYourself' extension
	// ###############################################################
$wgExtensionFunctions[] = 'bwKeepYourHandsToYourself';

function bwKeepYourHandsToYourself()
{
	global $wgUser, $wgGroupPermissions;
	$userName = $wgUser->getName();
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"edit")]   = true;
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"create")] = true;	
	$wgGroupPermissions['user'][hnpClass::buildPermissionKey(NS_USER,$userName,"delete")] = true;	
}

	// Page Level Restrictions
	// %%%%%%%%%%%%%%%%%%%%%%%
require('extensions/PageRestrictions/PageRestrictions.php');

// For testing QueryPage.php functionality
// as in 'SpecialPopularpages.php'.
// DEBUG
## {{
$wgContentNamespaces[] = NS_BIZZWIKI;
$wgContentNamespaces[] = NS_FILESYSTEM;
$wgContentNamespaces[] = NS_INTERWIKI;
## }}

// *****************************************************************************************

// Protect critical namespaces from Templating level permission bypassing.
// It is strongly suggested not to mess with this.
$wgNonincludableNamespaces[] = NS_FILESYSTEM;
$wgNonincludableNamespaces[] = NS_BIZZWIKI;
$wgNonincludableNamespaces[] = NS_INTERWIKI;

// *****************************************************************************************

/**
 * Show a bar of language selection links in the user login and user
 * registration forms; edit the "loginlanguagelinks" message to
 * customise these
 */
$wgLoginLanguageSelector = false;

// readfile & commitfile rights
require('extensions/FileManager/FileManager.php');

// syntax highlighting for the NS_FILESYSTEM namespace.
require('extensions/FileSystemSyntaxColoring/FileSystemSyntaxColoring.php');

// New User Logging
require('extensions/NewUserLog/Newuserlog.php');

// Show Redirect Page Text extension
require('extensions/ShowRedirectPageText/ShowRedirectPageText.php');

// Interwiki table management
require('extensions/InterWikiLinkManager/InterWikiLinkManager.php');

// Sidebar extended
require('extensions/SidebarEx/SidebarEx.php');

// Generic Syntax Highlighter
require('extensions/GeSHi/geshi.php');

// Enhanced Special Pages
require('extensions/SpecialPagesManager/SpecialPagesManager.php');
 // Use the following to customize the source path of the files.
#SpecialPagesManagerClass::singleton()->setPagesPath('page title');

// Form/Page related tools
require('extensions/SecureHTML/SecureHTML.php');
	# SecureHTMLclass::enableExemptNamespaces = false; # turn off
	# SecureHTMLclass::exemptNamespaces[] = NS_XYZ;    # to add namespaces to exemption list

require('extensions/SecureProperties/SecureProperties.php');
require('extensions/ParserExt/ParserTools/ParserTools.php');
require('extensions/FormProc/FormProc.php');

require('extensions/AutoLanguage/AutoLanguage.php');
AutoLanguageClass::$exemptNamespaces[] = NS_BIZZWIKI;
AutoLanguageClass::$exemptNamespaces[] = NS_INTERWIKI;
AutoLanguageClass::$exemptNamespaces[] = NS_FILESYSTEM;

require('extensions/CacheTools/CacheTools.php');

	// Parser Extensions
	// %%%%%%%%%%%%%%%%%
// http://meta.wikimedia.org/wiki/ParserFunctions
require( 'extensions/ParserExt/ParserFunctions/ParserFunctions.php' );

// http://www.mediawiki.org/wiki/Extension:StringFunctions
require( 'extensions/ParserExt/StringFunctions/StringFunctions.php' );

require( 'extensions/ParserExt/ForeachFunction/ForeachFunction.php' );
require('extensions/ParserExt/PageFunctions/PageFunctions.php'); 
require('extensions/ParserExt/PermissionFunctions/PermissionFunctions.php');
require('extensions/ParserExt/NamespaceFunctions/NamespaceFunctions.php');
require('extensions/ParserExt/RegexTools/RegexTools.php');
//require('extensions/ParserExt/MiscParserFunctions/MiscParserFunctions.php');

	// Stubs
	//  Used for rare events handling.
require('extensions/StubManager.php');

StubManager::createStub(	'EmailLog', 
							$bwExtPath.'/EmailLog/EmailLog.php',
							$bwExtPath.'/EmailLog/EmailLog.i18n.php',							
							array('EmailUserComplete'),
							true
						 );
StubManager::createStub(	'UserSettingsChangedLog', 
							$bwExtPath.'/UserSettingsChangedLog/UserSettingsChangedLog.php',
							$bwExtPath.'/UserSettingsChangedLog/UserSettingsChangedLog.i18n.php',							
							array('UserSettingsChanged'),
							true
						 );

StubManager::createStub(	'WatchLog', 
							$bwExtPath.'/WatchLog/WatchLog.php',
							$bwExtPath.'/WatchLog/WatchLog.i18n.php',							
							array('WatchArticleComplete', 'UnwatchArticleComplete' ),
							true
						 );

StubManager::createStub(	'RegexNamespaceContext', 
							$bwExtPath.'/RegexNamespaceContext/RegexNamespaceContext.php',
							null,							
							array( 'EditFormPreloadText', 'ParserAfterTidy' ),
							false
						 );


StubManager::createStub(	'RawPageTools', 
							$bwExtPath.'/RawPageTools/RawPageTools.php',
							null,							
							array( 'RawPageViewBeforeOutput' ),
							false
						 );


StubManager::createStub(	'AddScriptCssClass', 
							$bwExtPath.'/AddScriptCss/AddScriptCss.php',
							null,							
							array( 'OutputPageBeforeHTML', 'ParserAfterTidy' ),
							false, // no need for logging support
							array( 'addtohead', 'addscript' ),	// tags
							array( 'addscript' ), 				//of parser function magic words,
							null
						 );

StubManager::createStub(	'MiscParserFunctions', 
							$bwExtPath.'/ParserExt/MiscParserFunctions/MiscParserFunctions.php',
							null,							
							null,
							false, // no need for logging support
							null,	// tags
							array( 'trim','nowikitext','gettagsection' ),  //of parser function magic words,
							null
						 );

require('extensions/RecentChangesManager/RecentChangesManager.php');
require('extensions/DocProc/DocProc.php');
require('extensions/ImageLink/ImageLink.php');
require('extensions/PageAfterAndBefore/PageAfterAndBefore.php');

/* TODO
require('extensions/DPL/DynamicPageList2.php');

#Adjust the following parameters according to user permissions:
$wgDPL2AllowedNamespaces = array(

								);
$wgDPL2Options['namespace'] 
$wgDPL2Options['notnamespace'] 
*/

// ReCaptcha plug-in
//  Some customization required below.
//  Depending on your setup, it might be more appropriate to set these keys
//  in your LocalSettings.php
#require('extensions/ReCaptcha/ReCaptcha.php');
#$recaptcha_public_key = '';
#$recaptcha_private_key = '';

// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//                           TASK SCHEDULING FUNCTIONALITY
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
StubManager::createStub(	'TaskScheduler', 
							$bwExtPath.'/TaskScheduler/TaskScheduler.php',
							$bwExtPath.'/TaskScheduler/TaskScheduler.i18n.php',							
							array( 'SpecialVersionExtensionTypes','ClockTickEvent' ), // created by 'ClockTick' extension
							true // logging included
						 );

// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
//                           REPLICATION FUNCTIONALITY
//                             *** EXPERIMENTAL ***
// ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
# Customize 'clocktick.php' as required
require('extensions/SimpleReplicator/SimpleReplicator.php');
// Customize the following parameters
#PartnerMachine::$url		= 'http://bizzwiki.org';
#PartnerMachine::$port		= 80; 	//HTTP/TCP port
#PartnerMachine::$timeout	= 15;	//Timeout (in seconds) for request to partner machine

// Enable this extension if you require locally generated 'ClockTickEvent'
// A local 'cron' job must be setup to request a page from the local web server
// with an 'action=ping' command.
#require('extensions/ClockTick/ClockTick.php');



## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads     = true;

## This must be set if the 'Updater' extension is to be functional
$wgFileExtensions[]  = "zip";

// Updater extension (ongoing)
#require('extensions/Updater/Updater.php');


// The API functionality requires this function defined in MW1.11
if (!defined('wfScript'))
{
	function wfScript( $script = 'index' ) 
	{
		global $wgScriptPath, $wgScriptExtension;
		return "{$wgScriptPath}/{$script}{$wgScriptExtension}";
	}
}	



  // for web site demo only.
  // %%%%%%%%%%%%%%%%%%%%%%%
if (defined('BIZZWIKIDEMO'))
{
	require('extensions/ReCaptcha/ReCaptcha.php');
	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!raw")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!raw")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"LocalSettings.php","!viewsource")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"AdminSettings.php","!viewsource")] = true;  

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"read")]   = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"browse")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"viewsource")] = true; 	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_FILESYSTEM,"~",	"raw")] = true; 	

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"read")]   = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"browse")] = true; 
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"viewsource")] = true; 	
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_BIZZWIKI,	"~",	"raw")] = true; 	

	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~",	"read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER_TALK,"~",	"read")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER,		"~",	"browse")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_USER_TALK,"~",	"browse")] = true;  
	$wgGroupPermissions['*' ][hnpClass::buildPermissionKey(NS_SPECIAL,	"Log/*","browse")] = true;

	$bwAnonymousNamespaces = array( NS_MAIN, NS_TALK,
									NS_PROJECT, NS_PROJECT_TALK,
									NS_TEMPLATE,NS_TEMPLATE_TALK,
									NS_CATEGORY, NS_CATEGORY_TALK,
									NS_HELP, NS_HELP_TALK,
									NS_SPECIAL,
									NS_INTERWIKI // BizzWiki specific
									); 

	foreach( $bwAnonymousNamespaces as $index => $bwx )
	{	
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","read")] = true;   
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","browse")] = true;
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","search")] = true;   		
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","raw")] = true;   
		$wgGroupPermissions['*' ][hnpClass::buildPermissionKey($bwx,"~","viewsource")] = true;
	}
	
}

?>