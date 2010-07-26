<?php
/**
 * @version $Header$
 */
global $gBitInstaller;

$infoHash = array(
	'package'      => LIBERTY_PKG_NAME,
	'version'      => str_replace( '.php', '', basename( __FILE__ )),
	'description'  => "Update content type guid table to allow longer character string for guid values. Move liberty structures to its own package.",
	'post_upgrade' => NULL,
);

$gBitInstaller->registerPackageUpgrade( $infoHash, array(

array( 'DATADICT' => array(
	// insert new column
	array( 'ALTER' => array(
		'liberty_content_types' => array(
			'content_type_guid' => array( '`content_type_guid`', 'VARCHAR(32)' ),
		),
		'liberty_content' => array(
			'content_type_guid' => array( '`content_type_guid`', 'VARCHAR(32)' ),
		),
	)),
)),

));
