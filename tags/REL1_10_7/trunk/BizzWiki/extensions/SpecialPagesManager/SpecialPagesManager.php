<?php
/*<wikitext>
{| border=1
| <b>File</b> || SpecialPagesManager.php
|-
| <b>Revision</b> || $Id$
|-
| <b>Author</b> || Jean-Lou Dupont
|}<br/><br/>
 
== Purpose==
Gives the ability to a sysop to enhance a Mediawiki installation with custom 'special pages'
managed directly from the database (instead of PHP files).

== Features ==
* Default to 'Bizzwiki:Special Pages' page
* Can be changed through using
<source lang=php>
SpecialPagesManager->singleton()->setSpecialPage('page name');
</source>

== Dependancy ==
* ExtensionClass extension

== Installation ==
To install independantly from BizzWiki:
* Download 'ExtensionClass' extension
* Apply the following changes to 'LocalSettings.php'
<source lang=php>
require('extensions/ExtensionClass.php');
require('extensions/SpecialPagesManager/SpecialPagesManager.php');
</source>

== Rights ==
The extension defines a new right 'siteupdate' required to access the update functionality.

== History ==

== Code ==
</wikitext>*/

// Verify if 'ExtensionClass' is present.
if ( !class_exists('ExtensionClass') )
	echo 'SpecialPagesManager extension: ExtensionClass missing.';	
else
{
	require('SpecialPagesManagerClass.php');
	SpecialPagesManagerClass::singleton();
}
?>