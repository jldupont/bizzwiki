<?php
/*
	Origin:  MW 1.10
	-------
	
	BizzWiki:  $Id$
	
	TODO:
	=====
 
	
	HISTORY:
	========
	1) Namespace level permission policing.
	2) Added 'define' for easying installation procedure
*/
define( 'BIZZWIKI_SPECIALSTATISTICS', '$Id$' );

/**
*
* @addtogroup SpecialPage
*/

/**
* constructor
*/
function wfSpecialStatistics() {
	global $wgOut, $wgLang, $wgRequest;
	$fname = 'wfSpecialStatistics';

	$action = $wgRequest->getVal( 'action' );

	$dbr = wfGetDB( DB_SLAVE );

	$views = SiteStats::views();
	$edits = SiteStats::edits();
	$good = SiteStats::articles();
	$images = SiteStats::images();
	$total = SiteStats::pages();
	$users = SiteStats::users();

	$admins = $dbr->selectField( 'user_groups', 'COUNT(*)', array( 'ug_group' => 'sysop' ), $fname );
	$numJobs = $dbr->estimateRowCount('job');

	if ($action == 'raw') {
		$wgOut->disable();
		header( 'Pragma: nocache' );
		echo "total=$total;good=$good;views=$views;edits=$edits;users=$users;admins=$admins;images=$images;jobs=$numJobs\n";
		return;
	} else {
		$text = '==' . wfMsg( 'sitestats' ) . "==\n" ;
		$text .= wfMsgExt( 'sitestatstext', array ( 'parsemag' ),
			$wgLang->formatNum( $total ),
			$wgLang->formatNum( $good ),
			$wgLang->formatNum( $views ),
			$wgLang->formatNum( $edits ),
			$wgLang->formatNum( sprintf( '%.2f', $total ? $edits / $total : 0 ) ),
			$wgLang->formatNum( sprintf( '%.2f', $edits ? $views / $edits : 0 ) ),
			$wgLang->formatNum( $numJobs ),
			$wgLang->formatNum( $images )
	   	);

		$text .= "\n==" . wfMsg( 'userstats' ) . "==\n";

		$text .= wfMsgExt( 'userstatstext', array ( 'parsemag' ),
			$wgLang->formatNum( $users ),
			$wgLang->formatNum( $admins ),
			'[[' . wfMsgForContent( 'grouppage-sysop' ) . ']]', # TODO somehow remove, kept for backwards compatibility
			$wgLang->formatNum( sprintf( '%.2f', $admins / $users * 100 ) ),
			User::makeGroupLinkWiki( 'sysop' )
		);

		$wgOut->addWikiText( $text );

		global $wgDisableCounters, $wgMiserMode, $wgUser, $wgLang, $wgContLang;
		if( !$wgDisableCounters && !$wgMiserMode ) {
			$page = $dbr->tableName( 'page' );
			$sql = "SELECT page_namespace, page_title, page_counter FROM {$page} WHERE page_is_redirect = 0 AND page_counter > 0 ORDER BY page_counter DESC";
			$sql = $dbr->limitResult($sql, 10, 0);
			$res = $dbr->query( $sql, $fname );
			if( $res ) {
				$wgOut->addHtml( '<h2>' . wfMsgHtml( 'statistics-mostpopular' ) . '</h2>' );
				$skin = $wgUser->getSkin();
				$wgOut->addHtml( '<ol>' );
				while( $row = $dbr->fetchObject( $res ) ) {
					// BizzWiki begin {{
					if ( !$wgUser->isAllowed( 'browse', $row->page_namespace ) ) continue;
					// BizzWiki end }}
					$link = $skin->makeKnownLinkObj( Title::makeTitleSafe( $row->page_namespace, $row->page_title ) );
					$dirmark = $wgContLang->getDirMark();
					$wgOut->addHtml( '<li>' . $link . $dirmark . ' [' . $wgLang->formatNum( $row->page_counter ) . ']</li>' );
				}
				$wgOut->addHtml( '</ol>' );
				$dbr->freeResult( $res );
			}
		}
		
		$footer = wfMsg( 'statistics-footer' );
		if( !wfEmptyMsg( 'statistics-footer', $footer ) && $footer != '' )
			$wgOut->addWikiText( $footer );
		
	}
}
?>