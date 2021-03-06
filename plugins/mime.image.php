<?php
/**
 * @version		$Header$
 *
 * @author		xing  <xing@synapse.plus.com>
 * @version		$Revision$
 * created		Thursday May 08, 2008
 * @package		liberty
 * @subpackage	liberty_mime_handler
 **/

/**
 * setup
 */
global $gLibertySystem;

/**
 *  This is the name of the plugin - max char length is 16
 * As a naming convention, the liberty mime handler definition should start with:
 * PLUGIN_MIME_GUID_
 */
define( 'PLUGIN_MIME_GUID_IMAGE', 'mimeimage' );

$pluginParams = array (
	// Set of functions and what they are called in this paricular plugin
	// Use the GUID as your namespace
	'verify_function'     => 'mime_default_verify',
	'store_function'      => 'mime_image_store',
	'update_function'     => 'mime_image_update',
	'load_function'       => 'mime_image_load',
	'download_function'   => 'mime_default_download',
	'expunge_function'    => 'mime_default_expunge',
	'help_function'       => 'mime_image_help',
	// Brief description of what the plugin does
	'title'               => 'Advanced Image Processing',
	'description'         => 'Extract image meta data and display relevant information to the user and pick individual display options for images.',
	// Templates to display the files
	'view_tpl'            => 'bitpackage:liberty/mime/image/view.tpl',
	'attachment_tpl'      => 'bitpackage:liberty/mime/image/attachment.tpl',
	// url to page with options for this plugin
	'plugin_settings_url' => LIBERTY_PKG_URL.'admin/plugins/mime_image.php',
	// This should be the same for all mime plugins
	'plugin_type'         => MIME_PLUGIN,
	// Set this to TRUE if you want the plugin active right after installation
	'auto_activate'       => TRUE,
	// Help page on bitweaver.org
	//'help_page'           => 'LibertyMime+Image+Plugin',
	// this should pick up all image
	'mimetypes'           => array(
		'#image/.*#i',
	),
);
// currently, there's only one option in the image edit file - panorama image setting
if( $gBitSystem->isFeatureActive( 'mime_image_panoramas' )) {
	$pluginParams['edit_tpl'] =  'bitpackage:liberty/mime/image/edit.tpl';
}
$gLibertySystem->registerPlugin( PLUGIN_MIME_GUID_IMAGE, $pluginParams );

/**
 * Store the data in the database
 * 
 * @param array $pStoreRow File data needed to store details in the database - sanitised and generated in the verify function
 * @access public
 * @return TRUE on success, FALSE on failure - $pStoreRow[errors] will contain reason
 */
function mime_image_store( &$pStoreRow ) {
	global $gBitSystem;
	// this will set the correct pluign guid, even if we let default handle the store process
	$pStoreRow['attachment_plugin_guid'] = PLUGIN_MIME_GUID_IMAGE;
	$pStoreRow['log'] = array();

	// if storing works, we process the image
	if( $ret = mime_default_store( $pStoreRow )) {
		if( $gBitSystem->isFeatureActive( 'mime_image_exif_data' ) ){
			if( !mime_image_store_exif_data( $pStoreRow )) {
				// if it all goes tits up, we'll know why
				$pStoreRow['errors'] = $pStoreRow['log'];
				$ret = FALSE;
			}
		}
	}
	return $ret;
}

/**
 * mime_image_update update file information in the database if there were changes.
 * 
 * @param array $pStoreRow File data needed to update details in the database
 * @access public
 * @return TRUE on success, FALSE on failure - $pStoreRow[errors] will contain reason
 */
function mime_image_update( &$pStoreRow, $pParams = NULL ) {
	global $gThumbSizes, $gBitSystem;

	$ret = TRUE;

	// this will set the correct pluign guid, even if we let default handle the store process
	$pStoreRow['attachment_plugin_guid'] = PLUGIN_MIME_GUID_IMAGE;

	// if storing works, we process the image
	if( !empty( $pStoreRow['upload'] ) && $ret = mime_default_update( $pStoreRow )) {
		if( $gBitSystem->isFeatureActive( 'mime_image_exif_data' ) ){
			if( !mime_image_store_exif_data( $pStoreRow )) {
				// if it all goes tits up, we'll know why
				$pStoreRow['errors'] = $pStoreRow['log'];
				$ret = FALSE;
			}
		}
	} elseif( $gBitSystem->isFeatureActive( 'mime_image_panoramas' ) && !empty( $pParams['preference']['is_panorama'] ) && empty( $pStoreRow['thumbnail_url']['panorama'] )) {
		if( !mime_image_create_panorama( $pStoreRow )) {
			$ret = FALSE;
		}
	} elseif( empty( $pParams['preference']['is_panorama'] ) && !empty( $pStoreRow['thumbnail_url']['panorama'] )) {
		// we remove the panorama setting in the database and the panorama thumb
		if( LibertyAttachable::validateStoragePath( BIT_ROOT_PATH.$pStoreRow['thumbnail_url']['panorama'] )) {
			@unlink( BIT_ROOT_PATH.$pStoreRow['thumbnail_url']['panorama'] );
		}
	}

	return $ret;
}

/**
 * Load file data from the database
 * 
 * @param array $pFileHash Contains all file information
 * @param array $pPrefs Attachment preferences taken liberty_attachment_prefs
 * @param array $pParams Parameters for loading the plugin - e.g.: might contain values such as thumbnail size from the view page
 * @access public
 * @return TRUE on success, FALSE on failure - $pStoreRow[errors] will contain reason
 */
function mime_image_load( &$pFileHash, &$pPrefs, $pParams = NULL ) {
	global $gBitSystem;
	// don't load a mime image if we don't have an image for this file
	if( $ret = mime_default_load( $pFileHash, $pPrefs, $pParams )) {
		// fetch meta data from the db
		$ret['meta'] = LibertyMime::getMetaData( $ret['attachment_id'], "EXIF" );

		// if we have GPS data and geo is active, we calculate geo stuff
		if( $gBitSystem->isPackageActive( 'geo' ) && ( $ret['gps'] = LibertyMime::getMetaData( $ret['attachment_id'], "GPS" ))) {
			// longitude
			if( !empty( $ret['gps']['gpslongitude'] )) {
				$ret['geo']['lng'] = $ret['gps']['gpslongitude'];
				if( !empty( $ret['gps']['gpslongituderef'] ) && $ret['gps']['gpslongituderef'] == 'W' ) {
					$ret['geo']['lng'] = 0 - $ret['geo']['lng'];
				}
			}
			// latitude
			if( !empty( $ret['gps']['gpslatitude'] )) {
				$ret['geo']['lat'] = $ret['gps']['gpslatitude'];
				if( !empty( $ret['gps']['gpslatituderef'] ) && $ret['gps']['gpslatituderef'] == 'S' ) {
					$ret['geo']['lat'] = 0 - $ret['geo']['lat'];
				}
			}
			// set sea level data when available
			if( !empty( $ret['gps']['gpsaltitude'] )) {
				list( $dividend, $divisor ) = explode( "/", $ret['gps']['gpsaltitude'] );
				$ret['geo']['amsl'] = $dividend / $divisor;
				$ret['geo']['amsl_unit'] = 'm';
			}

			// final check to see if we have enough data
			if( empty( $ret['geo']['lng'] ) || empty( $ret['geo']['lat'] )) {
				unset( $ret['geo'] );
			}
		}

		// check for panorama image
		if( is_file( BIT_ROOT_PATH.dirname( $ret['storage_path'] )."/thumbs/panorama.jpg" )) {
			// if the panorama doesn't have 180⁰ vertical field of view we will restrict up / down movement
			if(( $ret['pano'] = LibertyMime::getMetaData( $ret['attachment_id'], "PANO" )) && !empty( $ret['pano']['aspect'] )) {
				// calculation based on logarythmic regression curve
				$ret['pano']['pa'] = round( 40 - 31 * log( $ret['pano']['aspect'] - 1.4 ));
				if( $ret['pano']['pa'] > 49 ) {
					$ret['pano']['pa'] = 90;
				} elseif( $ret['pano']['pa'] < 0 ) {
					$ret['pano']['pa'] = 0;
				}
			}
			$ret['thumbnail_url']['panorama'] = storage_path_to_url( dirname( $ret['storage_path'] )."/thumbs/panorama.jpg" );
		}
	}
	return $ret;
}

/**
 * mime_image_store_exif_data Process a JPEG and store its EXIF data as meta data.
 * 
 * @param array $pFileHash file details.
 * @param array $pFileHash[upload] should contain a complete hash from $_FILES
 * @access public
 * @return TRUE on success, FALSE on failure
 */
function mime_image_store_exif_data( $pFileHash ) {
	global $gBitSystem;
	if( !empty( $pFileHash['upload'] )) {
		$upload = &$pFileHash['upload'];
	}

	if( @BitBase::verifyId( $pFileHash['attachment_id'] ) && $exifHash = mime_image_get_exif_data( $upload ) ) {
		// only makes sense to store the GPS data if we at least have latitude and longitude
		if( !empty( $exifHash['GPS'] )) {
			LibertyMime::storeMetaData( $pFileHash['attachment_id'], 'GPS', $exifHash['GPS'] );
		}

		if( !empty( $exifHash['EXIF'] )) {
			LibertyMime::storeMetaData( $pFileHash['attachment_id'], 'EXIF', $exifHash['EXIF'] );
		}
	}

	return TRUE;
}

/**
 * mime_image_get_exif_data fetch meta data from uploaded image
 * 
 * @param array $pUpload uploaded file data
 * @access public
 * @return array filled with exif goodies
 */
function mime_image_get_exif_data( $pUpload ) {
	$exifHash = array();
	if( function_exists( 'exif_read_data' ) && !empty( $pUpload['source_file'] ) && is_file( $pUpload['source_file'] ) && preg_match( "#/(jpe?g|tiff)#i", $pUpload['type'] )) {
		// exif_read_data can be noisy due to crappy files, e.g. "Incorrect APP1 Exif Identifier Code" etc...
		$exifHash = @exif_read_data( $pUpload['source_file'], 0, TRUE );

		// extract more information if we can find it
		if( ini_get( 'short_open_tag' )) {
			require_once UTIL_PKG_PATH.'jpeg_metadata_tk/JPEG.php';
			require_once UTIL_PKG_PATH.'jpeg_metadata_tk/JFIF.php';
			require_once UTIL_PKG_PATH.'jpeg_metadata_tk/PictureInfo.php';
			require_once UTIL_PKG_PATH.'jpeg_metadata_tk/XMP.php';
			require_once UTIL_PKG_PATH.'jpeg_metadata_tk/EXIF.php';

			// Retrieve the header information from the JPEG file
			$jpeg_header_data = get_jpeg_header_data( $pUpload['source_file'] );

			// Retrieve EXIF information from the JPEG file
			$Exif_array = get_EXIF_JPEG( $pUpload['source_file'] );

			// Retrieve XMP information from the JPEG file
			$XMP_array = read_XMP_array_from_text( get_XMP_text( $jpeg_header_data ) );

			// Retrieve Photoshop IRB information from the JPEG file
			$IRB_array = get_Photoshop_IRB( $jpeg_header_data );
			if( !empty( $exifHash['IFD0']['Software'] ) && preg_match( '/photoshop/i', $exifHash['IFD0']['Software'] ) ) {
				require_once UTIL_PKG_PATH.'jpeg_metadata_tk/Photoshop_File_Info.php';
				// Retrieve Photoshop File Info from the three previous arrays
				$psFileInfo = get_photoshop_file_info( $Exif_array, $XMP_array, $IRB_array );

				if( !empty( $psFileInfo['headline'] ) ) {
					$exifHash['headline'] = $psFileInfo['headline'];
				}

				if( !empty( $psFileInfo['caption'] ) ) {
					$exifHash['caption'] = $psFileInfo['caption'];
				}
			}
		}

		// only makes sense to store the GPS data if we at least have latitude and longitude
		if( !empty( $exifHash['GPS'] )) {
			// store GPS coordinates as deg decimal float
			$gpsConv = array( 'GPSLatitude', 'GPSDestLatitude', 'GPSLongitude', 'GPSDestLongitude' );
			foreach( $gpsConv as $conv ) {
				if( !empty( $exifHash['GPS'][$conv] ) && is_array( $exifHash['GPS'][$conv] )) {
					$exifHash['GPS'][$conv] = mime_image_convert_exifgps( $exifHash['GPS'][$conv] );
				}
			}
		}
	}

	return $exifHash;
}

/**
 * mime_image_convert_exifgps GPS EIXF data is stored as fractions in an array. here we convert this to a degree decimal float value for easy storing
 * 
 * @param array $pParams array of positional data in fractions form EXIF tag
 * @access public
 * @return numeric value of positional data
 */
function mime_image_convert_exifgps( $pParams ) {
	$ret = 0;
	if( !empty( $pParams ) && is_array( $pParams ) && count( $pParams ) == 3 ) {
		list( $lng['deg'], $lng['min'], $lng['sec'] ) = array_values( $pParams );
		foreach( $lng as $key => $fraction ) {
			list( $dividend, $divisor ) = explode( "/", $fraction );
			$num = $dividend / $divisor;
			if( $key == 'min' ) {
				$num = $num / 60;
			} elseif( $key == 'sec' ) {
				$num = $num / 3600;
			}
			$ret += $num;
		}
	}
	return $ret;
}

/**
 * mime_image_create_panorama 
 * 
 * @param array $pStoreRow 
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_image_create_panorama( &$pStoreRow ) {
	global $gBitSystem, $gThumbSizes;
	// we know the panorama image will be a jpeg, so we don't need the canThumbFunc check here
	if(( $panoramaFunc = liberty_get_function( 'panorama' )) && !empty( $pStoreRow['storage_path'] ) && !empty( $pStoreRow['source_file'] ) && is_file( $pStoreRow['source_file'] )) {
		// the panorama has to be a jpg
		$gBitSystem->setConfig( 'liberty_thumbnail_format', 'jpg' );
		$width = $gBitSystem->getConfig( 'mime_image_panorama_width', 3000 );
		$gThumbSizes['panorama'] = array( $width, $width / 2 );

		// for the panorama, we will force a quality lower than 75 to reduce image size
		if( $gBitSystem->getConfig( 'liberty_thumbnail_quality', 85 ) > 75 ) {
			$gBitSystem->setConfig( 'liberty_thumbnail_quality', 75 );
		}

		$genHash = array(
			'attachment_id'   => $pStoreRow['attachment_id'],
			'dest_path'       => dirname( $pStoreRow['storage_path'] )."/",
			'source_file'     => $pStoreRow['source_file'],
			'type'            => $pStoreRow['mime_type'],
			'thumbnail_sizes' => array( 'panorama' ),
		);
		if( liberty_generate_thumbnails( $genHash )) {
			// we want to modify the panorama
			$genHash['source_file'] = $genHash['icon_thumb_path'];
			if( !$panoramaFunc( $genHash )) {
				$pStoreRow['errors']['panorama'] = $genHash['error'];
			}
		}

		return( empty( $pStoreRow['errors'] ));
	}
}

/**
 * liberty_magickwand_panorama_image - strictly speaking, this belongs in one of the image processing plugin files, but we'll leave it here for the moment
 * 
 * @param array $pFileHash File hash - souce_file is required
 * @param array $pOptions 
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_magickwand_panorama_image( &$pFileHash, $pOptions = array() ) {
	$magickWand = NewMagickWand();
	$pFileHash['error'] = NULL;
	if( !empty( $pFileHash['source_file'] ) && is_file( $pFileHash['source_file'] )) {
		if( !$pFileHash['error'] = liberty_magickwand_check_error( MagickReadImage( $magickWand, $pFileHash['source_file'] ), $magickWand )) {
			// calculate border width
			$iwidth  = round( MagickGetImageWidth( $magickWand ));
			$iheight = round( MagickGetImageHeight( $magickWand ));
			$aspect  = $iwidth / $iheight;
			$metaHash = array(
				'width'  => $iwidth,
				'height' => $iheight,
				'aspect' => $aspect,
			);
			// store original file information that we can adjust the viewer
			LibertyMime::storeMetaData( $pFileHash['attachment_id'], 'PANO', $metaHash );
			// we need to pad the image if the aspect ratio is not 2:1 (give it a wee bit of leeway that we don't add annoying borders if not really needed)
			if( $aspect > 2.1 || $aspect < 1.9 ) {
				$bwidth = $bheight = 0;
				if( $aspect > 2 ) {
					$bheight = round((( $iwidth / 2 ) - $iheight ) / 2 );
				} else {
					$bwidth = round((( $iheight / 2 ) - $iwidth ) / 2 );
				}
				// if the ratio has nothing to do with a panorama image - i.e. gives us a negative number here, we won't generate a panorama image
				if( $bheight > 0 ) {
					$pixelWand = NewPixelWand();
					PixelSetColor( $pixelWand, ( !empty( $pOptions['background'] ) ? $pOptions['background'] : 'black' ));
					if( !$pFileHash['error'] = liberty_magickwand_check_error( MagickBorderImage( $magickWand, $pixelWand, $bwidth, $bheight ), $magickWand )) {
						if( !$pFileHash['error'] = liberty_magickwand_check_error( MagickWriteImage( $magickWand, $pFileHash['source_file'] ), $magickWand )) {
							// yay!
						}
					}
					DestroyPixelWand( $pixelWand );
				}
			}
		}
	}
	DestroyMagickWand( $magickWand );
	return( empty( $pFileHash['error'] ));
}

/**
 * mime_image_help 
 * 
 * @access public
 * @return string
 */
function mime_image_help() {
	$help =
		tra( "If you have a panoramic image and you are using <strong>{attachment}</strong> to insert it, you can use <strong>panosize</strong> as you would with the size parameter to specify the size." )."<br />"
		.tra( "Example:" ).' '."{attachment id='13' panosize='small'}";
	return $help;
}
?>
