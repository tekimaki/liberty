<?php

$formLibertyFeatures = array(
	"liberty_display_status" => array(
		'label' => 'Enforce content status',
		'note' => 'This causes queries to enforce content status settings on content. Content status setting options are new, and you can uncheck this to opt out of status options.',
	),
	"liberty_display_status_menu" => array(
		'label' => 'Display content status menu',
		'note' => 'Display a status selection menu when editing content (not fully developed, experimental feature). Enforce content status above must also be checked.',
	),
	"liberty_allow_change_owner" => array(
		'label' => 'Allow Owner Change',
		'note' => 'Allow users with the proper permission to change the owner of content.',
	),
	"liberty_inline_diff" => array(
		'label' => 'Use Inline Diff',
		'note' => 'Uses PEAR Text_Wiki, which is installed with the command pear install Text_Wiki, for an inline diff of revisions instead of the line based unified diff tool. This is particularly useful when using a WYSIWYG editor which tends to put things all on one line. Note that this diff can take advantage of the PECL xdiff package for speed if you have that installed.',
	),
	// not entirely sure where this should go. liberty plugins? here? some other tab?
	'liberty_use_span_wrapper' => array(
		'label'  => 'Use a span wrapper',
		'note' => 'Some plugins such as the attachment plugin wrap their output with a div. This might not work well when you are using a WYSYWIG such as TinyMCE or FCKeditor in combination with HTML Purifier. There are Pros and Cons to using either wrapper.',
		'default' => 'n'
	),
);

if( $gBitSystem->isPackageActive( 'quota' )) {
	$formLibertyFeatures['liberty_quota'] = array(
		'label' => 'Quota Usage System',
		'note' => 'Limit users\' disk usage.',
	);
}

if( $gBitSystem->isPackageActive( 'protector' )) {
	$formLibertyFeatures['protector_single_group'] = array(
		'label' => 'Protector System Single group per content item',
		'note' => 'Limit the protector system to asign a content item to a single group. The alternative allows content in multiple groups.',
	);
}
$gBitSmarty->assign( 'formLibertyFeatures', $formLibertyFeatures );

$formLibertyTextareaFeatures = array(
	"liberty_textarea_height" => array(
		'label' => 'Default Textarea Height',
		'note' => 'The default height of textareas.',
		'default' => '20',
	),
	"liberty_textarea_width" => array(
		'label' => 'Default Textarea Width',
		'note' => 'The default width of textareas.',
		'default' => '35',
	),
);
$gBitSmarty->assign( 'formLibertyTextareaFeatures', $formLibertyTextareaFeatures );

$attachmentStyleOptions = array(
	"standard" => tra( 'Standard attachment system<br /><small>Allows a single upload when content is saved.</small>' ),
	"multiple" => tra( 'Multiple attachments<br /><small>Allow multiple attachments in a single upload.</small>' ),
	"ajax"     => tra( 'Ajax attachments<br /><small>Allow Ajax attachments where attachment is made before save so attachment id can be used in current edit.</small>' ),
);
$gBitSmarty->assign( 'attachmentStyleOptions', $attachmentStyleOptions );


$imageProcessors = array(
	'gd' => array(
		'installed'    => ( extension_loaded( 'gd' ) ? TRUE : FALSE ),
		'install_note' => 'The GD library is not installed. For newer Linux systems (Fedora, etc.), you need to install the php-gd package with a command such as "yum install php-gd".',
		'label'        => 'PHP - GD',
		'note'         => 'The GD libraries are usually readily available but do not support as many image types as the other image processors. If you plan on uploading many images to your server, please consider using one of the more advanced image porcessors.',
	),
	'imagick' => array(
		'installed'    => ( extension_loaded( 'imagick' ) ? TRUE : FALSE ),
		'install_note' => 'The pecl imagick extension has recently been updated to version 2.0, which is still in the early phases of devlopment. We will automaigically make use of the correct installed version but please note that the 2.* version is still in beta and might not work well depending on the build you have. Many distributions have php-imagick available through their package manager. Try something like: yum install php-imagick or emerge dev-php5/pecl-imagick. You can get the latest version here: <a href="http://pecl.php.net/package/imagick">Pecl :: Package :: imagick</a>',
		'label'        => 'PHP - Imagick',
		'note'         => 'This pecl extension is a popular and frequently used extension. This extension is recommended and works with most image types. We support the older version 0.* and the new 2.* extension (please note that version 2.* is still in beta and some versions might not work as well as others).',
		'recommended'  => TRUE,
	),
	'magickwand' => array(
		'installed'    => extension_loaded( 'magickwand' ) ? TRUE : FALSE,
		'install_note' => 'To use MagickWand, you need to install the magickwand php extension. Unix and Windows users can find source code at <a href="http://www.magickwand.org/download/php/">the ImageMagick downloads website.</a>.',
		'label'        => 'PHP - Magickwand',
		'note'         => 'The PHP imagick pecl extension is a rather new interface between PHP and ImageMagick. It is probably the best of the currently available image processing extensions.',
		'recommended'  => TRUE,
	),
);
$gBitSmarty->assign( 'imageProcessors', $imageProcessors );


$cacheTimes = array(
	0       => tra( "(no cache)" ),
	60      => "1 ".tra( "minute" ),
	300      => "5 ".tra( "minutes" ),
	1800      => "30 ".tra( "minutes" ),
	3600    => "1 ".tra( "hour" ),
	14400   => "4 ".tra( "hours" ),
	28800   => "8 ".tra( "hours" ),
	86400   => "1 ".tra( "day" ),
	604800  => "1 ".tra( "week" ),
	1209600 => "2 ".tra( "weeks" ),
	2419200 => "1 ".tra( "month" ),
);
$gBitSmarty->assign( "cacheTimes", $cacheTimes );


$formCaptcha = array(
	"liberty_use_captcha_freecap" => array(
		'label' => 'Use Freecap',
		'note' => 'Use the Freecap captcha library to create the captcha. This produces nicer and better images than the bitweaver internal captcha method.',
	),
	"freecap_use_dict" => array(
		'label' => 'Use Dictionary',
		'note' => 'Freecap can use real words or use random letters. Random letters are more difficult to decipher for humans as well as for bots.',
	),
);
$gBitSmarty->assign( 'formCaptcha', $formCaptcha );

$formCaptchaTextareaFeatures = array(
	"liberty_unstrusted_max_http_in_content" => array(
		'label' => 'Limit Comment Links',
		'note' => 'Do not allow edits from users without p_liberty_trusted_editor that has more than this many http links. (Currently only enforced in comments)',
		'default' => '1',
	),
);
$gBitSmarty->assign( 'formCaptchaTextareaFeatures', $formCaptchaTextareaFeatures );

$formImageFeatures = array(
	"liberty_thumbnail_pdf" => array(
		'label' => 'Create PDF Thumbnails',
		'note' => 'Create thumbnails from PDF files. This is a <strong>very resource intensive process</strong>. Even the simplest text files can take up hundreds of MB of memory to convert. Additionally, any PDF files that can not be converted will cause the upload process to be aborted without the uploaded file being stored on the server. This option requires that you have <strong>GhostScript</strong> installed.',
		'page' => '',
	),
	"liberty_jpeg_originals" => array(
		'label' => 'JPEG Originals',
		'note' => 'Automatically create JPEG versions of original images named \'original.jpg\' in the attachment directory with other thumbnails.',
		'page' => '',
	),
);
$gBitSmarty->assign( 'formImageFeatures', $formImageFeatures );

$thumbFormats = array(
	''    => tra( 'Automatically select the best format' ),
	'jpg' => tra( 'Force JPEG thumbnails' ),
	'png' => tra( 'Force PNG thumbnails' ),
	'gif' => tra( 'Force GIF thumbnails' ),
);
$gBitSmarty->assign( 'thumbFormats', $thumbFormats );

$formValues = array( 'image_processor', 'liberty_attachment_link_format', 'comments_per_page', 'comments_default_ordering', 'comments_default_display_mode' );

if( !empty( $_REQUEST['change_prefs'] )) {
	$formFeatures = array_merge( $formLibertyFeatures, $formImageFeatures, $formCaptcha );
	foreach( $formFeatures as $item => $data ) {
		simple_set_toggle( $item, LIBERTY_PKG_NAME );
	}
	foreach( $formLibertyTextareaFeatures as $item => $data ) {
		simple_set_value( $item, LIBERTY_PKG_NAME );
	}
	foreach( $formCaptchaTextareaFeatures as $item => $data ) {
		simple_set_value( $item, LIBERTY_PKG_NAME );
	}
	simple_set_value( 'liberty_thumbnail_format', LIBERTY_PKG_NAME );
	simple_set_value( 'liberty_attachment_style', LIBERTY_PKG_NAME );
	$gBitSystem->storeConfig( 'liberty_cache', $_REQUEST['liberty_cache'], LIBERTY_PKG_NAME );
	$gBitSystem->storeConfig( 'liberty_auto_display_attachment_thumbs', $_REQUEST['liberty_auto_display_attachment_thumbs'], LIBERTY_PKG_NAME );

	foreach( $formValues as $item ) {
		simple_set_value( $item, LIBERTY_PKG_NAME );
	}
}

$thumbSizes[''] = tra( 'Off' );
foreach( array_keys( $gThumbSizes ) as $thumb ) {
	$thumbSizes[$thumb] = tra( ucfirst( $thumb ));
}
$gBitSmarty->assign( 'thumbSizes', $thumbSizes );

?>
