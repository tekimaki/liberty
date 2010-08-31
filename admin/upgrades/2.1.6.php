<?php
/**
 * @version $Header$
 */
global $gBitInstaller;

$infoHash = array(
	'package'      => LIBERTY_PKG_NAME,
	'version'      => str_replace( '.php', '', basename( __FILE__ )),
	'description'  => "Update content permissions table to allow longer character string for guid values.",
	'post_upgrade' => NULL,
);

$gBitInstaller->registerPackageUpgrade( $infoHash, array(

array( 'DATADICT' => array(
	// insert new column
	array( 'ALTER' => array(
		'liberty_content_permissions' => array(
			'perm_name' => array( '`perm_name`', 'TYPE VARCHAR(128)' ),
		),
	)),
)),

));
