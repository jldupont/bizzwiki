<?php
/*<wikitext>
== geshi extension ==
The purpose of this extension is to provide a 'generic syntax highlighting' function to Mediawiki.

== Revision Id ==
$Id$

== Features ==
Use <nowiki><geshi lang=LANG lines=LINES source=SOURCE></geshi></nowiki> where:
* <b>LANG</b>
* <b>LINES</b>
** line = 0 --> no line numbers
** line = 1 --> line numbers included

* <b>SOURCE</b> can be 

* Use <nowiki><php lines=LINES source=SOURCE></nowiki>

== Examples ==

== Installation == 

</wikitext>*/

// Verify if 'ExtensionClass' is present.
if ( !class_exists('ExtensionClass') )
	echo 'ExtensionClass missing: geshi extension will not work!';	
else
{
	include_once('geshi/geshi.php');
	require( "geshiClass.php" );
	geshiClass::singleton();
}
?>