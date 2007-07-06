<?php
/*
 * AddScriptCss.php
 * 
 * MediaWiki extension
 * @author: Jean-Lou Dupont
 * 
 * Purpose:  Inserts <script> & <link> (i.e. CSS) tags at the bottom of the page's head
 * ========  or within the page's body.
 *
 * Features:
 * *********
 * 
 * -- Local files (URI) only
 * -- Files must be located in wiki installation
 *    home directory/scripts
 *
 * Examples:
 * =========
 * -- <addscript src='local URL' />
 *    1) e.g. <addscript src=/sarissa/sarissa type=js />
 *    2) e.g. {{#addscript: src=/styleinfo|pos=head|type=css}}
 *
 *    R1) Results in /home/scripts/sarissa/sarissa.js
 *        being added to the page's body section
 *        provided the said file exists.
 *
 *    R2) The CSS file /home/scripts/styleinfo.css will be
 *        added to the page's HEAD section (provided it exists).
 *
 * Syntax:
 * =======
 * Form 1: <addscript src=filename [type={js|css}] [pos={head|body}] />
 *
 * Form 2: {{#addscript:src=filename [|type={js|css} [|pos={head|body}] }}
 *
 * If no 'type' field is present, then the extension assumes 'js'.
 *
 * If no 'pos' field is present, then the extension assumes 'body'
 *
 * DEPENDANCY:  ExtensionClass ( v>=306 )  
 * 
 * USAGE NOTES:
 * ============
 * 1) When using 'pos=body', it is recommended to use
 *    the extension 'ParserCacheControl' in order to better
 *    integrate this extension with the standard MW parser cache.
 * 
 * Tested Compatibility:  MW 1.8.2, 1.10
 *
 * History:
 * - v1.0  Builds on existing 'AddScript' extension
 =========== Moved to BizzWiki
   - Adjusted for new ExtensionClass version (no automatic registering of hooks of ExtensionClass)
   
 * TODO:
 * =====
 * - adjust for 'autoloading'
 * - internationalize
 *
 */

// Verify if 'ExtensionClass' is present.
if ( !class_exists('ExtensionClass') )
	echo 'ExtensionClass missing: AddScriptCss extension will not work!';	
else
	AddScriptCssClass::singleton();

class AddScriptCssClass extends ExtensionClass
{
	// constants.
	const thisName = 'AddScriptCss';
	const thisType = 'other'; 
	const id       = '$Id$';

	// error codes.
	const error_none     = 0;
	const error_uri      = 1;
	const error_bad_type = 2;
	const error_bad_pos  = 3;
		
	static $base = 'BizzWiki/scripts/';

	public static function &singleton()
	{ return parent::singleton( );	}
	
	function AddScriptCssClass( $mgwords = null, $passingStyle = self::mw_style, $depth = 1 )
	{
		parent::__construct( );

		global $wgScriptPath;
		global $wgExtensionCredits;
		$wgExtensionCredits['other'][] = array( 
			'name'        => self::thisName, 
			'version'     => self::getRevisionId( self::id ),
			'author'      => 'Jean-Lou Dupont', 
			'description' => 'Adds javascript and css scripts to the page HEAD or BODY sections',
			'url' => self::getFullUrl(__FILE__),
		);

		// always initialise or else no 'head' scripts will be processed!!
		$this->initHeadScriptsHook();
	}
	public function setup() 
	{ 
		parent::setup();
		
		// <addscript... />
		
		// not required with latest ExtensionClass extension; done automatically.
		// global $wgParser;
		// $wgParser->setHook( 'addscript', array( &$this, 'tag_addscript' ) );
	} 

	public function tag_addscript( &$text, &$params, &$parser)
	{ return $this->process( $params );	}
	
	public function mg_addscript( &$parser )
	{
		$params = $this->processArgList( func_get_args(), true );		
		return $this->process( $params );
	}
	private function setupParams( &$params )
	{
		$template = array(
			array( 'key' => 'src',  'index' => '0', 'default' => '' ),
			array( 'key' => 'type', 'index' => '1', 'default' => 'js' ),
			array( 'key' => 'pos',  'index' => '2', 'default' => 'body' ),
			#array( 'key' => '', 'index' => '', 'default' => '' ),
		);
		// ask initParams to strip off the parameters
		// which aren't registered in $template.
		parent::initParams( $params, $template, true );
	}
	private function normalizeParams( &$params )
	{
		// This function checks the validity of the following
		// parameters: 'type' and 'pos'
		extract( $params );
		
		$type=strtolower( $type );
		if ( ($type!='js') && ($type!='css') )
			return self::error_bad_type;

		$pos=strtolower( $pos );
		if ( ($pos!='head') && ($pos!='body') )
			return self::error_bad_pos;

		return self::error_none;		
	}
	private function process( &$params )
	{
		$this->setupParams( $params );

		$errCode = self::error_none;
		$r = $this->normalizeParams( $params );
		if ($r!=self::error_none) return $this->errMessage( $r );

		// src, type, pos
		extract( $params );
		
		$src = $this->cleanURI( $src, $type );
		if (!$this->checkURI( $src, $type ))
			return $this->errMessage( self::error_uri ); 

		global $wgScriptPath;
		$p = $wgScriptPath.'/'.self::$base.$src.'.'.$type;

		// Which type of script does the user want?
		switch( $type )
		{
			case 'css': $t = '<link href="'.$p.'" rel="stylesheet" type="text/css" />'; break;		
			default:
			case 'js':	$t = '<script src="'.$p.'" type="text/javascript"></script>';   break;
		}	

		// Where does the user want the script?
		switch( $pos )
		{
			case 'head':
				// For 'head' scripts, we need to embed a 'meta tag' in the text
				// This 'meta tag' will be saved in the parser cache waiting to be
				// look at by the hook 'OutputPageBeforeHTML'.
				$this->addHeadScript( $t );
				break;
			default:
			case 'body': 
				// For 'body' scripts, we need to intercept the processing flow
				// after the 'tidy' process in the parser and feed script tags there.
				// No need to encode them, they should be safe from the parser/parser cache.
				$this->addBodyScript( $t );
				break;	
		}
		// everything OK
		return null;
	}
	private function cleanURI( $uri )
	{
		return str_replace( array('/../', '../', '\\..\\',
									"..\\",'"','`','&','?',
									'<','>','.' ), "", $uri);
	}
	private function checkURI( $uri, $type )
	{
		// uri must resolved to a local file in the $base directory.
		$spath = self::$base.$uri.'.'.$type;
		
		global $IP;
	
		return file_exists( $IP."/{$spath}" );
	} 
	private function errMessage( $errCode )  // FIXME
	{
		$m = array(
			self::error_none     => 'no error',
			self::error_uri      => 'invalid URI',
			self::error_bad_type => 'invalid TYPE parameter',
			self::error_bad_pos  => 'invalid POS parameter',
		);
		return 'AddScriptCss: '.$m[ $errCode ];
	}

} // END CLASS DEFINITION
?>