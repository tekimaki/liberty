<?php
/**
 * required setup
 */
require_once( '../kernel/setup_inc.php' );
require_once( LIBERTY_PKG_PATH."LibertyContent.php");

$staticContent = new LibertyContent();
if (isset($_REQUEST['content_id'])) {
  $gContent = $staticContent->getLibertyObject( $_REQUEST['content_id'] );
  if (isset($gContent) && $gContent->isValid()) {
    if ($gContent->hasExpungePermission()) {
      if ($gContent->expunge()) {
	echo '{"status":1, "message":"'.tra("Content Deleted").'"}';
	die;
      } else {
	echo '{"status":0, "message":"'.tra("Unknown Error").'"}';
	die;
      }
    } else {
      echo '{"status":0, "message":"'.tra("Permission Denied").'"}';
      die;
    }
  }
}
echo '{"status":0, "message":"'.tra("Invalid Request").'"}';
die;
