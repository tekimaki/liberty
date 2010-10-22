<?php
/**
 * get_content_list
 *
 * @author   Christian Fowler>
 * @version  $Revision$
 * @package  liberty
 * @subpackage functions
 */

/**
 * required setup
 * expects data hash: $cListRequest instead of $_REQUEST to be set
 */
require_once( LIBERTY_PKG_PATH."LibertyContent.php" );
global $gContent;
global $gLibertySystem;

if( empty( $gContent ) || !is_object( $gContent ) ) {
	$gContent = new LibertyContent();
}

$contentTypeGuids = array();
if( !empty( $cListRequest['content_type_guid'] )) {
	if( !is_array( $cListRequest['content_type_guid'] )) {
		$guids = explode( ",", $cListRequest['content_type_guid'] );
	} else {
		$guids = $cListRequest['content_type_guid'];
	}
	/**
	 * if an empty string was passed in an array (likely since it is used for ALL) then the user has requested all so return all
	 * even if they have requested additional content types too - ALL is ALL
	 * this check is reversed in that if no empty string in the array then we pass the array of content types to be limited on
	 **/
	if( !in_array( "", $guids ) ){
		$contentTypeGuids = $guids;
	}
}

// get_content_list_inc doesn't use $cListRequest parameters as it might not be the only list in the page that needs sorting and limiting
if( empty( $contentListHash ) ) {
	$contentListHash = array(
		'content_type_guid' => $contentSelect = empty( $cListRequest['content_type_guid'] ) ? NULL : $contentTypeGuids,
		// pagination offset
		'offset'            => !empty( $offset_content ) ? $offset_content : NULL,
		// maximum number of records displayed on a page
		'max_records'       => !empty( $max_content ) ? $max_content : ( !empty( $cListRequest['max_records'] ) ? $cListRequest['max_records'] : 100 ),
		// sort by this: <table column>_asc (or _desc)
		'sort_mode'         => !empty( $content_sort_mode ) ? $content_sort_mode : 'title_asc',
		// limit the result to this set
		'find'              => !empty( $cListRequest["find"] ) ? $cListRequest["find"] : NULL,
		// display this page number - replaces antiquated offset
		'page'              => !empty( $cListRequest["list_page"] ) ? $cListRequest["list_page"] : NULL,
		// only display content by this user
		'user_id'           => @BitBase::verifyId( $cListRequest['user_id'] ) ? $cListRequest['user_id'] : NULL,
		// only display content modified more recently than this (UTC timestamp)
		'from_date'         => !empty( $cListRequest["from_date"] ) ? $cListRequest["from_date"] : NULL,
		// only display content modified before this (UTC timestamp)
		'until_date'        => !empty( $cListRequest["until_date"] ) ? $cListRequest["until_date"] : NULL,
		// get a thumbnail - off by default because it is expensive
		'thumbnail_size'    => !empty( $cListRequest["thumbnail_size"] ) ? $cListRequest["thumbnail_size"] : NULL,
	);

	if( !empty( $cListRequest['output'] ) && ( $cListRequest['output'] == 'json' || $cListRequest['output'] == 'ajax' ) ) {	
		foreach( $cListRequest as $key => $value ) {
			if ( !is_array($value) ){
				if( strstr( $value, ',' ) ) {
					$cListRequest[$key] = explode( ",", $value );
				}
			}
		}
	}

	$contentListHash = array_merge( $cListRequest, $contentListHash );
}

// Finally we're ready to get some content
$contentList = $gContent->getContentList( $contentListHash );

if( empty( $contentTypes ) ) {
	$contentTypes = array( '' => tra( 'All Content' ) );
	foreach( $gLibertySystem->mContentTypes as $cType ) {
		$contentTypes[$cType['content_type_guid']] = $gLibertySystem->getContentTypeName( $cType['content_type_guid'], TRUE );
	}
	asort( $contentTypes );
}
global $gBitSystem, $gBitUser;
if( $gBitSystem->isFeatureActive( 'liberty_display_status' ) &&  $gBitUser->hasPermission( 'p_liberty_view_all_status' )) {
	$contentStatuses = $gContent->getAvailableContentStatuses();
	$contentStatuses[''] = 'All Statuses';
	$contentStatuses['not_available'] = 'All but Available';
	$gBitSmarty->assign( 'content_statuses', $contentStatuses );
}
?>
