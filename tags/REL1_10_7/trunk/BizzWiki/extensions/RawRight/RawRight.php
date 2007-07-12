<?php
/*
 * RawRight.php
 *
 * @author Jean-Lou Dupont -- www.bluecortex.com
 * @package MediaWiki
 * @subpackage Extensions
 * 
 * <b>Purpose:</b>  This extension adds a 'raw' right.
 * Only the users with the 'raw' permission can 'raw view' an article's source wikitext.
 *
 * FEATURES:
 * =========
 * 0) Can be used independantly of BizzWiki environment
 * 1) Displays operational information in 'Special:Version' page
 * 2) Integrates with Hierarchical Namespace Permissions extension to provide
 *    'raw' right.
 *
 * DEPENDANCIES:
 * =============
 * 1) ExtensionClass (>v1.3)
 * 2) Hierarchical Namespace Permissions extension
 * 3) MW > 1.10 (or patched earlier version)
 *
 * Installation:
 * include("extensions/RawRight.php");
 *
 * HISTORY:
 * ========
 *
 * TODO
 * ====
 * 1) Internationalization: add messages to cache
 *    i18n file
 */
 
class RawRight extends ExtensionClass
{
	const thisName = 'RawRight';
	const thisType = 'other';  // must use this type in order to display useful info in Special:Version
	const id       = '$Id$';	
	
	public static function &singleton( )
	{ return parent::singleton( ); }
	
	// Our class defines magic words: tell it to our helper class.
	public function RawRight() 
	{ 
		parent::__construct( ); 
	
		global $wgExtensionCredits;
		$wgExtensionCredits[self::thisType][] = array( 
			'name'    => self::thisName, 
			'version'     => self::getRevisionId( self::id ),
			'author'  => 'Jean-Lou Dupont', 
			'description' => "Status: ",
			'url' => self::getFullUrl(__FILE__),			
		);
	}
	
	public function setup()
	{
		parent::setup();
	}
	public function hUpdateExtensionCredits( &$sp, &$extensionTypes )
	// setup of this hook occurs in 'ExtensionClass' base class.
	{
		global $wgExtensionCredits;

		// first check if the proper rights management class is in place.
		if (class_exists('hnpClass'))
			$hresult = '<b>Hierarchical Namespace Permissions extension operational</b>';
		else
			$hresult = '<b>Hierarchical Namespace Permissions extension <i>not</i> operational</b>';

		// check directly in the source if the hook is present 
		$rawpage = @file_get_contents('includes/RawPage.php');
		
		if (!empty($rawpage))
			$r = preg_match('/RawPageViewBeforeOutput/si',$rawpage);
		
		if ( $r==1 )
			$rresult = '<b>RawPageViewBeforeOutput hook operational</b>';
		else
			$rresult = '<b>RawPageViewBeforeOutput hook <i>not</i> operational</b>';
		
		foreach ( $wgExtensionCredits[self::thisType] as $index => &$el )
			if ($el['name']==self::thisName)
				$el['description'].=$hresult." and ".$rresult;
				
		return true; // continue hook-chain.
	}
	
	public function hRawPageViewBeforeOutput( &$rawpage, &$text )
	{
		global $wgUser;
		
		if (! $wgUser->isAllowed( "raw") )		
		{
			$text = '';
			wfHttpError( 403, 'Forbidden', 'Unsufficient access rights.' );
			return false;
		}
		
		return true; // continue hook-chain.
	}
} // end class definition.

RawRight::singleton();
?>