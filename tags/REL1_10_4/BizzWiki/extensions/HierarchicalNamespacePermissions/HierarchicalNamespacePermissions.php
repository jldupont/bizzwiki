<?php
/* 
 * HierarchicalNamespacePermissions.php
 * MediaWiki extension
 * 
 * Provides an hierarchical (aka Prefix based) permission
 * sub-system for Mediawiki.
 *
 * @author: Jean-Lou Dupont
 *
 * TOP LEVEL NAMESPACES
 * ====================
 * Must be created through the standard MW way i.e.
 *
 * define('NS_ADMIN', 100);
 * $wgExtraNamespaces = array (NS_ADMIN => "Admin" );
 *
 * Hierarchical Namespace Permission atomic unit description
 * =========================================================
 *
 * [namespace:page => action]
 *
 * - Where "page" can (and obviously benefits) from having an
 * hierarchical structure e.g. top\sub1\pageXYZ
 * - Where "page" can support the "~" wildcard e.g.
 *   top\sub1\~
 * - Where "action" is any: this extension does not interpret
 *   the meaning of a particular action. This is of course left
 *   to the users of this extension.
 *   Just as example, let's consider the usual MW actions:
 *   ( 'read', 'edit', 'move', 'create' )
 *
 * - The wildcard "~" can only be used in the following patterns:
 *   a)  [ ~:~       => action ]
 *   b)  [ ns:~      => action ]
 *   c)  [ ns:page/~ => action ]
 *
 *   Where "page" can be:
 *   1) title            (just one title name)
 *   2) title/title2     (a sub-page)
 *
 * - The User Rights managed by this extension can be stored
 *   in the database OR dynamically added through "LocalSettings.php". 
 * 
 * IMPLEMENTATION NOTES:
 * =====================
 *
 * 1) The permission (aka 'right') is stored in the 'user_groups' table 
 *    using the following syntax:
 *    Each record consists of one "group" right (as standard MW)
 *    where each "group right" is formatted:
 *   
 *    "ns|NAMESPACE|PAGE|ACTION"
 *
 *    The pipe symbol | was chosen because it is considered 
 *    illegal in a MW title; this helps create a "barrier" between
 *    the normal MW title semantic and the semantic implemented
 *    in this extension.
 *
 * 2) The wildcard character used is ~ as it is illegal in a MW title
 *    and not used as a command code in PHP PCRE (which makes
 *    overall implementation much simpler)
 *
 *
 * FEATURES:
 * =========
 * 0) Can be used independantly of BizzWiki environment 
 *  - No new database table required: all "prefixes" are stored
 *    in the existing 'user_groups' table.
 *
 *  - Adds new right level "SubmitWithoutRead" whereas a user
 *    can have the right to create/edit a page without having the right
 *    to view it. This is especially useful when processing "forms".
 *    This feature is necessary since MW (at least in v1.8.2) does not
 *    allow a form that requires creation of a new page to be posted
 *    without the user having the 'read' right. 
 *    See "wiki.php\preliminaryChecks" 
 *
 *  - Ability to explicitly give "exclude" rights.
 *    E.g.
 *      $wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","~")]    = true;
 *      $wgGroupPermissions['sysop' ][hnpClass::buildPermissionKey("~","~","!bot")] = true;
 *   Simple sysop definition where the sysop group gets everyright EXCEPT the "bot" right.
 *   This is especially useful when "editing" pages as MW will mark "rc_bot = true" in 
 *   the "recentchanges" table, thus preventing a standard view on the "Recent Changes"
 *   special page.
 
 	 - Ability to define a 'group hierarchy'
	   e.g. sysop -> user -> *
	   'sysop' rights have precedence over 'user' rights, which in turn
	   have precedence over '*'
 
 *
 * MEDIAWIKI NOTES:
 * ================
 * 1) Only the forward slash is interpreted (when enabled) as an
 *    indicator of "sub-page". Thus, only the forward slash and ~ are
 *    considered here to be part of the hierarchical functionality.
 * 
 * 2) The allowed characters in a title are defined in "DefaultSettings.php".
 *    $wgLegalTitleChars = " %!\"$&'()*,\\-.\\/0-9:;=?@A-Z\\\\^_`a-z~\\x80-\\xFF+";
 *    NOTE that #{}[]| are not part of this list.
 *  
 * 3) Special command characters for preg_match do not include the following:
 *    #`'~,@
 *    Those were tested with preg_quote
 *
 * 4) Be careful not to choose namespace name identifiers too long as the 
 *    standard limit in Mediawiki is 16characters long.  This extension
 *    automatically creates the "top level namespace managers" with the 
 *    the following pattern:
 *                "Namespace Canonical Name|Mng"
 *    The total length of this identifier is not allowed to be greated than
 *    16 characters long and as such the limit for the Namesapce Canonical Name
 *    is thus limited to 12characters.
 *
 * Version 1.0:
 *  - Initial availability
 * Version 1.1:
 *  - Changed name identifier for automatically created "top level manager" groups
 *    in order to respect MW's "ug_group" field in the database table "user_groups"
 *    16 characters limitation.
 * Version 1.2:
 *  - Added "exclude action" functionality through the "!" metacharacter in the 
 *    "action" field.
 *  - Corrected some corner cases
 * -------------------------------
 * Moved to BizzWiki project
 *  
 *  - added singleton functionality
 *  - added hook support for 'UserIsAllowed'
 *  - added namespace level action checking.
 *  - TODO add namespace-independant right checking.
 *  - added group hierarchy functionality.
 */

	// instantiate one
hnpClass::singleton();

class hnpClass
{
	var $lNsD; // namespace dependant rights list
	var $lNsI; // namespace independant rights list
	static $groupHierarchy; 
	const id       = '$Id$';	
	
	public static function &singleton() 
	{
		static $instance;
		if ( !isset( $instance ) ) 
			$instance = new hnpClass( );
		return $instance;
	}
	static function getRevisionId()
	{
		$data = explode( ' ', self::id );
		return $data[2];
	}
	function hnpClass()
	{
		$this->lNsD = array();
		$this->lNsI = array();
		
		global $wgExtensionCredits;
		
		$wgExtensionCredits['other'][] = array(
		    'name'    => "HierarchicalNamespacePermissions",
			'version' => self::getRevisionId(),
			'author'  => 'Jean-Lou Dupont' 
		);
			
	
		global $wgHooks;
		global $hnpObjDebug;
		
		if (!$hnpObjDebug)
		{
			$wgHooks['userCan'][] = array( $this, 'userCan' );
			$wgHooks['UserIsAllowed'][] = array( $this, 'hUserIsAllowed' );
		}
			
		$this->initGroups();
		
		// default hierarchy
		self::$groupHierarchy = array ( 'sysop', 'user', '*' );
	}
	public function addNamespaceDependantRights( $rights )   
	{ 
		$this->lNsD = array_merge( $rights, $this->lNsD ); 
	}
	public function addNamespaceIndependantRights( $rights ) 
	{ 
		$this->lNsI = array_merge( $rights, $this->lNsI ); 
	}
	public function setGroupHierarchy( $gh ) { self::$groupHierarchy = $gh; }
	
	function hUserIsAllowed( &$user, $ns=null, $titre=null, &$action, &$result )
	{
		$result = false; // disallow by default.
		if ($action == '') return true;
		
		// Namespace independant right ??
		if ( in_array( $action, $this->lNsI ) )
		{
			$result = hnpClass::userCanInternal( $user, '~', '~' , $action );
			return false;	
		}

		// debugging...
		if (! in_array( $action, $this->lNsD) )
		{
			echo 'hnpClass: action <b>'.$action.'</b> not found in namespace dependant array. <br/>';
			return false;	
		}

		// Namespace dependant right:
		// Two cases:
		// 1) the request comes from a stock Mediawiki method that does not know about hnpClass
		//    * request might come from a SpecialPage context.
		//
		// 2) the request comes from an hnpClass aware method somewhere.
		
		// are we asked to check for a specific action in a specific namespace??

		global $wgTitle;
		// Does the request come from NS_SPECIAL and namespace dependant??
		$cns = $wgTitle->getNamespace();
		$cti = $wgTitle->mDbkeyform;
		
		if ( ($cns == NS_SPECIAL) && ($ns === null) )
		{
			echo 'hnpClass: action <b>'.$action.'</b> namespace dependant but called from NS_SPECIAL. <br/>';
#			var_dump( debug_backtrace() );
			return false;	
		}

		// Finally, the request comes from a valid namespace & with a valid namespace dependant action
		if ( $ns === null )    $ns = $cns;
		if ( $titre === null ) $titre = $cti;

		$result = hnpClass::userCanInternal( $user, $ns, $titre , $action );
	
		return false;
	}
    function userCanStub( &$t, &$u, $a, &$r )
	{
		$r = true;
		return false;
	}

	// t-> title, u-> user, a-> action, r-> result
	function userCan( &$t, &$u, $a, &$r )
	{
		// Check if we have a case of "page creation/edition"
		// Form posting support function.
		$submit = hnpClass::isRequestToSubmit();

		// Can the user perform a read operation?
		$ns = $t->getNamespace();
		$pt = $t->mDbkeyform;

		// Is the user allowed to post a form for
		// creation/update even without 'read' right?
		if ( $submit && ($a == 'read') )
			$a = "SubmitWithoutRead";

		#echo " Namespace: $ns  Title=$pt  Action=$a \n <br/>";

		// Normal processing path.
		$r = hnpClass::userCanInternal( $u, $ns, $pt, $a );
		
		// don't let other extensions override this result.			
		return false; 
	}
	static function userCanX( $ns, $pt, $a )
	{ 
		global $wgUser;
		return hnpClass::userCanInternal($wgUser, $ns, $pt, $a); 
	}
	
	/*
	 * The complex processing takes place here.
	*/
	static function userCanInternal( $user, $ns, $pt, $a )
	{
		// NOTE: the term "group" is somewhat confusing.
		//       Use the following semantic to interpret:
		//       " User X is part of Group Y if X can
		//        perform Action A on the Page T of
		//        Namespace NS "
		// A User with Rights in the sub-space X\Y\* (as example)
		// is entitled *only* (assuming no other superset group is
		// defined for this User) to this sub-space i.e.
		// User can not have access to higher level pages e.g. X\*
		//

		foreach ( self::$groupHierarchy as $index => $group )
		{
			// is the user part of the group?
			if ( !self::isUserPartOfGroup( $user, $group ) ) continue;
			
			$groupa = array( $group );
			$grights = $user->getGroupPermissions( $groupa ); 

			// FIRST GROUP OF TESTS
			//   EXCLUDE ACTION tests
			$rights = hnpClass::prepareRightsTable( $grights, false );
			$eqs = hnpClass::buildPermissionKey( $ns, $pt, "!${a}" );		
			$r = hnpClass::testRightsWildcard( $eqs, $rights );
			if ($r) return false;		
		
			// SECOND GROUP OF TESTS
			// ---------------------
			// Go through all the group membership and
			// extract the rights looking for the ones
			// dynamically created (e.g. by this extension i.e. createGroups)
			// which are compatible with this extension.
			$rights = hnpClass::prepareRightsTable( $grights );
			$qs = hnpClass::buildPermissionKey( $ns, $pt, $a );
			$r = hnpClass::testRightsWildcard( $qs, $rights );
			if ($r) return true;		
		}

		// If all tests fail, then conclude the user does not have the require right.
		return false;
	}
	/*
	 * Translate the User's Groups array format to one compatible
	 * with the matching function. 
	*/
	static function prepareRightsTable( $rights, $wildAction = true )
	{
		if (empty($rights))
			return null;

		// Go through each group record and:
		// 0) Make sure we are dealing with a 'valid' (as per
		//    this extension semantic) group.
		//    We must also support the base groups of MW,
		//    namely '*' and 'user'.
		// 1) Translate the "~" wildcard to (.*)
		// 2) Escape all PCRE command characters
		// 3) Add the required pattern syntax characters
		//    for preg_match
		// 4) Espace the forward slash / used to make up
		//    the relative/hierarchical subspaces.
		// 5) Put the begining "ns|" has non-capturing pattern.
		// 6) Get rid of "~" action rights IF required.
		
		$index = 0 ;
		$r = array();
		foreach ($rights as $a)
		{
			if (preg_match("/^ns/", $a)==0)           # 0
				continue;
			if ($wildAction == false)
			{
				$wa = preg_match("/~$/",$a);
				if ($wa) continue;
			}
				
			$b = preg_quote( $a );                    # 2
			$bb= preg_replace("/ns/","(?:ns)", $b);  # 5
			$c = preg_replace("/\//", "\/" , $bb);      # 4
			$e = preg_replace( "/~/", "(.*)", $c );    # 1
			$r[$index] = "/^".$e."$/";                 # 3
			$index++;
		}
		return $r;
	}
	
	static function testRightsWildcard( $q, $rights )
	{	
		if (empty($rights))
			return false;
			
		// Go through each right
		// and look if the query matches with it
		// In reality, the array $rights is (should be!) already
		// formatted for use with the matching function, acting as
		// the pattern in question.
		foreach ($rights as $pattern)
			if ( preg_match( $pattern, $q ) > 0 )
				return true;	
		
		return false;		
	}
	
	/*
	 * Is the user posted a form that requires
	 * creation/updating a wiki page?
	*/
	static function isRequestToSubmit()
	{
		global    $wgRequest;
		$action = $wgRequest->getVal( 'wpSave', 'view' );
		return    ($action == "submit" ? true:false);
	}

	static function buildPermissionKey( $ns, $titre, $action )
	{	return "ns|{$ns}|{$titre}|{$action}";	}

	/*
	*  Create default groups
	*/
	function initGroups()
	{
		global $wgGroupPermissions, $wgCanonicalNamespaceNames;
		
		/* For each Ns, create a "manager" group having
		 * every right in the namespace.
		*/
		foreach( $wgCanonicalNamespaceNames as $num => $titre )
		{
	        $wgGroupPermissions[ "Gr|{$num}|NsMng" ][ "ns|{$titre}|~|~"   ] = true;
	        $wgGroupPermissions[ "Gr|{$num}|NsMng" ][ "ns|{$num}|~|~"     ] = true;
		}
	
	}
	public static function isUserPartOfGroup( &$user, $group )
	{
		if (empty( $group )) return false;
		
		return in_array( $group, $user->getEffectiveGroups() );
	}

} # end class definition

?>