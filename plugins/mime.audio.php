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
define( 'PLUGIN_MIME_GUID_AUDIO', 'mimeaudio' );

$pluginParams = array (
	// Set of functions and what they are called in this paricular plugin
	// Use the GUID as your namespace
	'preload_function'    => 'mime_audio_preload',
	'verify_function'     => 'mime_default_verify',
	'store_function'      => 'mime_audio_store',
	'update_function'     => 'mime_audio_update',
	'load_function'       => 'mime_audio_load',
	'download_function'   => 'mime_default_download',
	'expunge_function'    => 'mime_default_expunge',
	// Brief description of what the plugin does
	'title'               => 'Listen to uploaded Audio files',
	'description'         => 'This plugin will extract as much information about an uploaded audio file as possible and allow you to listen to it on the website using a streaming player.',
	// Templates to display the files
	'view_tpl'            => 'bitpackage:liberty/mime/audio/view.tpl',
	'inline_tpl'          => 'bitpackage:liberty/mime/audio/inline.tpl',
	'storage_tpl'         => 'bitpackage:liberty/mime/audio/storage.tpl',
	'attachment_tpl'      => 'bitpackage:liberty/mime/audio/attachment.tpl',
	'edit_tpl'            => 'bitpackage:liberty/mime/audio/edit.tpl',
	// url to page with options for this plugin
	'plugin_settings_url' => LIBERTY_PKG_URL.'admin/plugins/mime_audio.php',
	// This should be the same for all mime plugins
	'plugin_type'         => MIME_PLUGIN,
	// Set this to TRUE if you want the plugin active right after installation
	'auto_activate'       => FALSE,
	// Help page on bitweaver.org
	//'help_page'           => 'LibertyMime+Audio+Plugin',
	// this should pick up all audio
	'mimetypes'           => array(
		'#audio/.*#i',
	),
);
$gLibertySystem->registerPlugin( PLUGIN_MIME_GUID_AUDIO, $pluginParams );

/**
 * mime_audio_preload This function is loaded on every page load before anything happens and is used to load required scripts.
 * 
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_audio_preload() {
	global $gBitThemes;
	$gBitThemes->loadJavascript( UTIL_PKG_PATH."javascript/flv_player/swfobject.js", FALSE, 25 );
}

/**
 * Store the data in the database
 * 
 * @param array $pStoreRow File data needed to store details in the database - sanitised and generated in the verify function
 * @access public
 * @return TRUE on success, FALSE on failure - $pStoreRow['errors'] will contain reason
 */
function mime_audio_store( &$pStoreRow ) {
	// this will set the correct pluign guid, even if we let default handle the store process
	$pStoreRow['attachment_plugin_guid'] = PLUGIN_MIME_GUID_AUDIO;
	$pStoreRow['log'] = array();

	// if storing works, we process the audio
	if( $ret = mime_default_store( $pStoreRow )) {
		if( !mime_audio_converter( $pStoreRow )) {
			// if it all goes tits up, we'll know why
			$pStoreRow['errors'] = $pStoreRow['log'];
			$ret = FALSE;
		}
	}
	return $ret;
}

/**
 * mime_audio_update 
 * 
 * @param array $pStoreRow 
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_audio_update( &$pStoreRow, $pParams = NULL ) {
	$ret = FALSE;
	if( BitBase::verifyId( $pStoreRow['attachment_id'] )) {
		$pStoreRow['log'] = array();

		// set the correct pluign guid, even if we let default handle the store process
		$pStoreRow['attachment_plugin_guid'] = PLUGIN_MIME_GUID_AUDIO;
		// remove the entire directory
		$pStoreRow['unlink_dir'] = TRUE;

		// if storing works, we process the audio
		if( !empty( $pStoreRow['upload'] ) && $ret = mime_default_update( $pStoreRow )) {
			if( !mime_audio_converter( $pStoreRow )) {
				// if it all goes tits up, we'll know why
				$pStoreRow['errors'] = $pStoreRow['log'];
				$ret = FALSE;
			}
		}

		// if there was no upload we'll process the file parameters
		if( empty( $pStoreRow['upload'] ) && !empty( $pParams['meta'] )) {
			// update our local version of the file
			$file = BIT_ROOT_PATH.$pStoreRow['storage_path'];
			if( is_file( dirname( $file ).'/bitverted.mp3' )) {
				$verted = dirname( $file ).'/bitverted.mp3';
			} elseif( is_file( dirname( $file ).'/bitverted.m4a' )) {
				$verted = dirname( $file ).'/bitverted.m4a';
			}

			// update audio tags of converted and original file (ignore errors since these might be m4a)
			mime_audio_update_tags( $verted, $pParams['meta'] );
			mime_audio_update_tags( $file, $pParams['meta'] );

			// finally we update the meta table data
			if( !LibertyMime::storeMetaData( $pStoreRow['attachment_id'], 'ID3', $pParams['meta'] )) {
				$log['store_meta'] = "There was a problem storing the meta data in the database";
			}

			if( empty( $log )) {
				$ret = TRUE;
			} else {
				$pStoreRow['errors'] = $log;
			}
		}
	}
	return $ret;
}

/**
 * Load file data from the database
 * 
 * @param array $pFileHash Contains all file information
 * @param array $pPrefs Attachment preferences taken liberty_attachment_prefs
 * @param array $pParams Parameters for loading the plugin - e.g.: might contain values from the view page
 * @access public
 * @return TRUE on success, FALSE on failure - ['errors'] will contain reason for failure
 */
function mime_audio_load( &$pFileHash, &$pPrefs, $pParams = NULL ) {
	global $gLibertySystem, $gBitThemes;

	// don't load a mime image if we don't have an image for this file
	if( $ret = mime_default_load( $pFileHash, $pPrefs, $pParams )) {
		// fetch meta data from the db
		$ret['meta'] = LibertyMime::getMetaData( $pFileHash['attachment_id'], "ID3" );

		if( !empty( $ret['storage_path'] )) {
			if( is_file( dirname( BIT_ROOT_PATH.$ret['storage_path'] ).'/bitverted.mp3' )) {
				$ret['media_url'] = storage_path_to_url( dirname( $ret['storage_path'] )).'/bitverted.mp3';
				// we need some javascript for the player:
			} elseif( is_file( dirname( BIT_ROOT_PATH.$ret['storage_path'] ).'/bitverted.m4a' )) {
				$ret['media_url'] = storage_path_to_url( dirname( $ret['storage_path'] )).'/bitverted.m4a';
			}
		}
	}
	return $ret;
}

/**
 * mime_audio_converter 
 * 
 * @param array $pParamHash 
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_audio_converter( &$pParamHash ) {
	global $gBitSystem;

	// audio conversion can take a while
	ini_set( "max_execution_time", "1800" );

	$ret = FALSE;
	$log = array();

	$source = BIT_ROOT_PATH.$pParamHash['upload']['dest_path'].$pParamHash['upload']['name'];
	$dest_path = dirname( $source );

	if( @BitBase::verifyId( $pParamHash['attachment_id'] )) {
		$pattern = "#.*\.(mp3|m4a)$#i";
		if( !$gBitSystem->isFeatureActive( 'mime_audio_force_encode' ) && preg_match( $pattern, $pParamHash['upload']['name'] )) {
			// make a copy of the original maintaining the original extension
			$dest_file = $dest_path.'/bitverted.'.preg_replace( $pattern, "$1", strtolower( $pParamHash['upload']['name'] ));
			if( !is_file( $dest_file ) && !link( $source, $dest_file )) {
				copy( $source, $dest_file );
			}
			$ret = TRUE;
		} else {
			// TODO: have a better mechanism of converting audio to mp3. ffmpeg works well as long as the source is 'perfect'
			//       there are many audiofiles that can't be read by ffmpeg but by other tools like flac, faac, oggenc
			//       mplayer is very good, but has a lot of dependencies and not many servers have it installed
			//       also, using mplayer is a 2 step process: decoding and encoding

			// if we convert audio, we always make an mp3
			$dest_file = $dest_path.'/bitverted.mp3';
			if( !( $ret = mime_audio_converter_ffmpeg( $pParamHash, $source, $dest_file ))) {
				// fall back to using slower mplayer / lame combo
				$ret = mime_audio_converter_mplayer_lame( $pParamHash, $source, $dest_file );
			}
		}

		// if the conversion was successful, we'll copy the tags to the new mp3 file and import data to meta tables
		if( $ret == TRUE ) {
			$log['success'] = 'Successfully converted to mp3 audio';

			// now that we have a new mp3 file, we might as well copy the tags accross in case someone downloads it
			require_once( UTIL_PKG_PATH.'getid3/getid3/getid3.php' );
			$getID3 = new getID3;
			// we silence this since this will spew lots of ugly errors when using UTF-8 and some odd character in the file ID
			$meta = @$getID3->analyze( $source );
			getid3_lib::CopyTagsToComments( $meta );

			// write tags to new mp3 file
			if( $errors = mime_audio_update_tags( $dest_file, $meta['comments'] )) {
				$log['tagging'] = $errors;
			}

			// getID3 returns everything in subarrays - we want to store everything in [0]
			foreach( $meta['comments'] as $key => $comment ) {
				$store[$key] = $comment[0];
			}
			$store['playtimeseconds'] = $meta['playtime_seconds'];
			$store['playtimestring']  = $meta['playtime_string'];

			// make sure we remove previous entries first
			LibertyMime::expungeMetaData( $pParamHash['attachment_id'] );
			if( !LibertyMime::storeMetaData( $pParamHash['attachment_id'], 'ID3', $store )) {
				$log['store_meta'] = "There was a problem storing the meta data in the database";
			}

			// if we have an image in the id3v2 tag, we might as well do something with it
			// we'll simply use the first image we can find in the file
			if( !empty( $meta['id3v2']['APIC'][0]['data'] )) {
				$image = $meta['id3v2']['APIC'][0];
			} elseif( !empty( $meta['id3v2']['PIC'][0]['data'] )) {
				$image = $meta['id3v2']['PIC'][0];
			}

			if ( !empty( $image )) {
				// write the image to temp file for us to process
				$tmpfile = str_replace( "//", "/", tempnam( TEMP_PKG_PATH, LIBERTY_PKG_NAME ));

				if( $fp = fopen( $tmpfile, 'w' )) {
					fwrite( $fp, $image['data'] );
					fclose( $fp );

					$fileHash['type']            = $image['mime'];
					$fileHash['source_file']     = $tmpfile;
					$fileHash['dest_path']       = $pParamHash['upload']['dest_path'];
					liberty_generate_thumbnails( $fileHash );

					// remove temp file
					if( !empty( $tmpfile ) && is_file( $tmpfile )) {
						unlink( $tmpfile );
					}
				}
			}

			// TODO: when tags package is enabled add an option to add tags
			//       recommended tags might be artist and album

			// TODO: fetch album cover from amazon.com or musicbrainz.org
			//       fetch lyrics from lyricwiki.org

			//$item->mLogs['audio_converter'] = "Audio file was successfully converted to MP3.";
		}
	}

	// update log
	$pParamHash['log'] = array_merge( $pParamHash['log'], $log );

	return $ret;
}

/**
 * mime_audio_converter_mplayer_lame will decode the audio to wav using mplayer and then encode to mp3 using lame
 * 
 * @param array $pParamHash file information
 * @param array $pSource source file
 * @param array $pDest destination file
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_audio_converter_mplayer_lame( &$pParamHash, $pSource, $pDest ) {
	global $gBitSystem;
	$ret = FALSE;
	$log = array();

	if( !empty( $pParamHash ) && !empty( $pSource ) && is_file( $pSource ) && !empty( $pDest )) {
		$mplayer = trim( $gBitSystem->getConfig( 'mplayer_path', shell_exec( 'which mplayer' )));
		$lame    = trim( $gBitSystem->getConfig( 'lame_path', shell_exec( 'which lame' )));

		// confirm that both applications are available
		if( $mm = shell_exec( "$mplayer 2>&1" ) && $ll = shell_exec( "$lame 2>&1" )) {
			// we will decode the audio file using mplayer and encode using lame
			$mplayer_params = " -quiet -vo null -vc dummy -af volume=0,resample=44100:0:1 -ao pcm:waveheader:file='$pSource.wav' '$pSource' ";
			$lame_params    = $gBitSystem->getConfig( "mime_audio_lame_options", " -b ".( $gBitSystem->getConfig( 'mime_audio_bitrate', 64000 ) / 1000 ))." '$pSource.wav' '$pDest' ";
			$command        = "$mplayer $mplayer_params && $lame $lame_params";
			$debug          = shell_exec( "$command 2>&1" );

			// remove the temporary wav file again
			@unlink( "$pSource.wav" );

			// make sure the conversion was successfull
			if( is_file( $pDest ) && filesize( $pDest ) > 1 ) {
				$ret = TRUE;
			} else {
				// remove unsuccessfully converted file
				@unlink( $pDest );
				$log['message'] = 'ERROR: The audio you uploaded could not be converted by mplayer and lame. DEBUG OUTPUT: '.nl2br( $debug );

				// write error message to error file
				$h = fopen( dirname( $pDest )."/error", 'w' );
				fwrite( $h, "$command\n\n$mm\n\n$ll\n\n$debug" );
				fclose( $h );
			}
		}
	}

	// update log
	$pParamHash['log'] = array_merge( $pParamHash['log'], $log );

	return $ret;
}

/**
 * mime_audio_converter_ffmpeg 
 * 
 * @param array $pParamHash file information
 * @param array $pSource source file
 * @param array $pDest destination file
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function mime_audio_converter_ffmpeg( &$pParamHash, $pSource, $pDest ) {
	global $gBitSystem;
	$ret = FALSE;
	$log = array();

	if( !empty( $pParamHash ) && !empty( $pSource ) && is_file( $pSource ) && !empty( $pDest )) {
		// these are set in the liberty plugin admin screen
		$ffmpeg = trim( $gBitSystem->getConfig( 'ffmpeg_path', shell_exec( 'which ffmpeg' )));

		if( $ff = shell_exec( "$ffmpeg 2>&1" )) {
			// set up parameters to convert audio
			$params =
				" -i '$pSource'".
				" -acodec ".$gBitSystem->getConfig( 'ffmpeg_mp3_lib', 'libmp3lame' ).
				" -ab ".trim( $gBitSystem->getConfig( 'mime_audio_bitrate', 64000 ).'b' ).
				" -ar ".trim( $gBitSystem->getConfig( 'mime_audio_samplerate', 22050 )).
				" -y '$pDest'";
			$debug = shell_exec( "$ffmpeg $params 2>&1" );

			// make sure the conversion was successfull
			if( is_file( $pDest ) && filesize( $pDest ) > 1 ) {
				$ret = TRUE;
			} else {
				// remove unsuccessfully converted file
				@unlink( $pDest );
				$log['message'] = 'ERROR: The audio you uploaded could not be converted by ffmpeg. DEBUG OUTPUT: '.nl2br( $debug );

				// write error message to error file
				$h = fopen( dirname( $pDest )."/error", 'w' );
				fwrite( $h, "$ffmpeg $params\n\n$ff\n\n$debug" );
				fclose( $h );
			}
		}
	}

	// update log
	$pParamHash['log'] = array_merge( $pParamHash['log'], $log );

	return $ret;
}

/**
 * mime_audio_update_tags will update the tags of a given audio file
 * 
 * @param array $pFile absolute path to file
 * @param array $pMetaData Hash of data that should be passed to the file.
 * @access public
 * @return NULL on success, String of errors on failure
 */
function mime_audio_update_tags( $pFile, $pMetaData ) {
	$ret = NULL;
	if( !empty( $pFile ) && is_file( $pFile ) && is_array( $pMetaData )) {
		// we need to initiate getID3 for the writer to work
		require_once( UTIL_PKG_PATH.'getid3/getid3/getid3.php' );
		$getID3 = new getID3;

		require_once( UTIL_PKG_PATH.'getid3/getid3/write.php' );
		// Initialize getID3 tag-writing module
		$tagwriter = new getid3_writetags();
		$tagwriter->filename       = $pFile;
		$tagwriter->tagformats     = array( 'id3v1', 'id3v2.3' );

		// set various options
		$tagwriter->overwrite_tags = TRUE;
		$tagwriter->tag_encoding   = "UTF-8";

		// prepare meta data for storing
		foreach( $pMetaData as $key => $data ) {
			if( !is_array( $data )) {
				$data = array( $data );
			}
			$write[$key] = $data;
		}

		// store the tags
		if( !empty( $write )) {
			$tagwriter->tag_data = $write;
			if( !$tagwriter->WriteTags() ) {
				$ret = 'Failed to write tags!<br />'.implode( '<br /><br />', $tagwriter->errors );
			}
		}
	}
	return $ret;
}
?>
