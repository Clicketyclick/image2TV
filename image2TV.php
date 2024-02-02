<?php
/**
 *  @file      image2TV.php
 *  @brief     Convert an image to a display version with metadata
 *  
 *  @details   Using IPTC headline, caption, location as metadata and add flag from countrycode
 *             Configuration is loaded from im.json into $GLOBALS['config']
 *             Modification can be made by command line arguments for any configuration
 *  
 *  
 *  php image.php 
 *  php image.php {Options}
 *  
 *  @see       Requires imagick
 *  @link      https://stackoverflow.com/q/42783581 - Resize image and place in center of canvas
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2024-01-24T13:38:42 / erba
 *  @version   2024-02-02 15:01:59
 *  
 */

include_once( 'handleIptc.php' );
include_once( 'init.php' );

// Show file desc
$contents	= file_get_contents(__FILE__);
$pattern	= "/\@(file|brief|version)(.*)/m"; 

if(preg_match_all($pattern, $contents, $matches)){
    fprintf( STDERR, implode("\n", $matches[0]) );
}

fprintf( STDERR, "\n" );

// Load config
$GLOBALS['config']		= json_decode( file_get_contents( pathinfo( __FILE__, PATHINFO_FILENAME ) . ".json"), TRUE);

// Merge args
$GLOBALS['config']		= array_merge( $GLOBALS['config'], $_REQUEST );

// If no configuration exit w usage
if ( ! $GLOBALS['config'] )
{
	fprintf( STDERR, "usage: \n-path=./ -pattern=\.jpg\nOr use im.json\n" );
	exit;
}
else
{
	// Print config
	verbose( "Config",	json_encode( $GLOBALS['config'], JSON_PRETTY_PRINT ) );
}


$path		= $GLOBALS['config']['source_path'] 	?? "./";
$pattern	= $GLOBALS['config']['source_pattern']	?? "\.jpg";		//"\.jpg|\.png";
$target		= $GLOBALS['config']['target_path']		?? "../TV/";	//"\.jpg";

// Get all images
$files	= getFilesRecursively( $path, $pattern );

// Loop through all images
foreach ( $files as $file )
{
	$new	= $target .  $file ;
	mycopy( $file, $new );		// Copy source to target and create path
	fprintf( STDERR, "- [%s] -> [%s]\n", $file, $new );

	build_tv( $file, $new );
}

//---------------------------------------------------------------------

/**
 *  @fn         build_tv
 *  @brief      Build TV picture sized to standard dimentions and w. annotation
 *  
 *  @param [in] $source 	Source file
 *  @param [in] $target		Target file
 *  @return     VOID
 *  
 *  @details    More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see        https://
 *  @since      2024-01-31T10:44:21 / erba
 */
function build_tv( $source, $target )
{
	//$flag_unknown	= flag-unknown.png ;
	//>>>---Source---------------------------------------------------------
	// Open source image object
	$im 		= new Imagick($source );
	// Size of image in bytes
	$size 		= getimagesize($source, $info);
	// Dimentions of source image
	$imageprops = $im->getImageGeometry();
	$width 		= $imageprops['width'];
	$height 	= $imageprops['height'];

	verbose( "Source",	$source );
	verbose( "Size", 	$width . "x" . $height );
	//<<<---Source---------------------------------------------------------


	//>>>---New img size---------------------------------------------------
	$newHeight	= $GLOBALS['config']['max_height'];	//390;
	$newWidth	= $GLOBALS['config']['max_width'] ;

	// Resize source to output
	$im->resizeImage($newWidth,$newHeight, Imagick::FILTER_LANCZOS, 0.9, true);

	verbose( "New size", 	$newWidth . "x". $newHeight );
	//<<<---New img size---------------------------------------------------




	//>>>---EXIF-----------------------------------------------------------

    $exif = exif_read_data($source, 'ANY_TAG', TRUE);
    debug( "EXIF", 	var_export( $exif, TRUE ) );
    //verbose( "EXIF", 	json_encode( $exif ?? "??" ) );
    verbose( "EXIF", 	var_export( $exif , TRUE) );
    verbose( "EXIF:DateTimeOriginal", 	var_export( $exif['EXIF']['DateTimeOriginal'] ?? "??", TRUE) );
        
    list($lat, $lng)    = getGpsCoordinates($exif) ?? [FALSE, FALSE];
    $alt                = getGpsAltitude( $exif );

    $coor               = sprintf( "%.3fx%.3f : %.2fm", $lat, $lng, $alt );
    verbose( "EXIF: coor : alt", $coor );

	//<<<---EXIF-----------------------------------------------------------


	//>>>---Iptc-----------------------------------------------------------
	$iptc_data = [];
	if(isset($info['APP13']))
	{
		$iptc_data	= iptcparse($info['APP13']);
        
        debug( "IPTC_debug", 	json_encode( $iptc_data ?? "??" ) );
		$iptc		= parseIptc( $iptc_data );
	}

	verbose( "IPTC", 	json_encode( $iptc ?? "??" ) );
	//<<<---Iptc-----------------------------------------------------------


	//>>>---Flag-----------------------------------------------------------
	// Set flag name
	$flag_path	= sprintf( "%s%s.%s"
	,	$GLOBALS['config']['flag_dir']
	//,	strtolower( $iptc['Country-PrimaryLocationCode'][0] ?? $GLOBALS['config']['flag_unknown'] )
	,	strtoupper( $iptc['Country-PrimaryLocationCode'][0] ?? $GLOBALS['config']['flag_unknown'] )
	,	$GLOBALS['config']['output_extention']
	);

	if ( file_exists( $flag_path ) )
	{
		$flag = new Imagick($flag_path);
		verbose( "Flag", 	$flag_path, $iptc['Country-PrimaryLocationCode'][0] ?? "??" );
	} 
	else
	{
		fprintf( STDERR, "Flag not found: [%s] [%s]\n"
		,	$iptc['Country-PrimaryLocationCode'][0]
		,	$flag_path
		);
	}
	//<<<---Flag-----------------------------------------------------------

	$draw 				= new ImagickDraw();
	$canvas 			= new Imagick();
	// 4K: 3840 x 2160
	$finalWidth 		= $GLOBALS['config']['max_width'];
	$finalHeight 		= $GLOBALS['config']['max_height'];
	
	$backgroundColor	= $GLOBALS['config']['background_color'];
	
	$outputType			= $GLOBALS['config']['output_type'];
	$offsetX 			= (int)($finalWidth  / 2) - (int)($newWidth  / 2);
	$offsetY 			= (int)($finalHeight / 2) - (int)($newHeight / 2);

	$canvas->newImage($finalWidth, $finalHeight, $backgroundColor, $outputType );

	verbose( "Offset", 	$offsetX ."x". $offsetY );

	$offsetX 			= $GLOBALS['config']['image_offset_x'];
	$offsetY 			= $GLOBALS['config']['image_offset_y'];

	verbose(  "Image Offset", 	$offsetX ."x". $offsetY );

	$canvas->compositeImage( $im, 	imagick::COMPOSITE_OVER, $offsetX, $offsetY );
	
	if ( file_exists( $flag_path ) )
	{
		$canvas->compositeImage( $flag, imagick::COMPOSITE_OVER, $GLOBALS['config']['flag_offset_x'], $GLOBALS['config']['flag_offset_y'] );
		verbose(  "Flag Offset", $GLOBALS['config']['flag_offset_x']  ."x".  $GLOBALS['config']['flag_offset_y'] );
	}
	else
		fprintf( STDERR, "No flag added\n" );

	//>>>---Headline-------------------------------------------------------
	$draw->setFillColor( $GLOBALS['config']['headline_font_color'] ?? "gray" );	// Headline color
	$draw->setFontSize( $GLOBALS['config']['headline_font_size'] ?? 10 );
	
	$draw->setTextAlignment(\Imagick::ALIGN_RIGHT);
	$ypos	= $GLOBALS['config']['headline_start_vpos'];
	$ypos	= $newHeight * $GLOBALS['config']['headline_start_vpos'] / 100;

	$canvas->annotateImage($draw, $finalWidth - 10, $ypos, 0, $iptc['Headline'][0] ?? "" );

	verbose( "IPTC Headline", 	$iptc['Headline'][0] ?? "??");
	//<<<---Headline-------------------------------------------------------

	//>>>---Caption--------------------------------------------------------
	$draw->setFillColor('white');
	$draw->setFontSize( $GLOBALS['config']['caption_font_size'] ?? 10 );
	$draw->setFillColor( $GLOBALS['config']['caption_font_color']  ?? "gray" );	// Caption color
	
	$bbox 			= imageftbbox(12, 0, 'Arial.ttf', 'This is a test');
	$width_of_text 	= $bbox[2] - $bbox[0];

	$boxwidth		= $GLOBALS['config']['boxwidth'];		// Box width
	$xpos			= $finalWidth;							// Ajust to right border
	$ypos			= $newHeight * $GLOBALS['config']['caption_start_vpos'] / 100;	// Vertical position
	
	// Add captions
	list($lines, $lineHeight) = wordWrapAnnotation($canvas, $draw, $iptc['Caption-Abstract'][0] ?? "", $boxwidth);
	for($i = 0; $i < count($lines); $i++)
		$canvas->annotateImage($draw, $xpos, $ypos + $i*$lineHeight, 0, $lines[$i]);

	verbose( "IPTC Caption", 	$iptc['Caption-Abstract'][0] ?? "??");
	//<<<---Caption--------------------------------------------------------


	//>>>---Location-------------------------------------------------------
	// Add location
	$ypos			= $newHeight * $GLOBALS['config']['location_start_vpos'] / 100;			// Vertical position
	
	$draw->setFillColor( $GLOBALS['config']['location_font_color']  ?? "gray" );	// Location color
	$draw->setFontSize( $GLOBALS['config']['location_font_size'] ?? 10 );

	// Merge location
	$msg	= implode( ", ", array_filter(	// Remove empty elements
		[
			$iptc['Sub-location'][0]				?? ""
		,	$iptc['City'][0]						?? ""
		,	$iptc['Province-State'][0] 				?? ""
		,	$iptc['Country-PrimaryLocationName'][0]	?? ""

		]
	)
	);
    $msg    .= 0 === strcmp( '0.000x0.000 : 0.00m', $coor) ? "" : "\n\n$coor";

	list($lines, $lineHeight) = wordWrapAnnotation($canvas, $draw, $msg, $boxwidth);
	for($i = 0; $i < count($lines); $i++)
		$canvas->annotateImage($draw, $xpos, $ypos + $i*$lineHeight, 0, $lines[$i]);

	verbose( "IPTC Location", 	$msg );
	//<<<---Location-------------------------------------------------------


	// Convert the IPTC tags into binary code
	$new_iptc_data = '';

	foreach($iptc_data as $tag => $string)
	{
		$tag = substr($tag, 2);

		// Single elements OR lists (Keywords)
		foreach ( $string as $element )
			$new_iptc_data .= iptc_make_tag(2, $tag, $element );
	}


	// Write images to file
	$canvas->writeImage( 
		$target
	);


	// Embed the IPTC data
	$content = iptcembed($new_iptc_data, $target
	);

	// Write the new image data out to the file.
	$fp = fopen( $target, "wb");

	fwrite($fp, $content);
	fclose($fp);
}	// build_tv()

//---------------------------------------------------------------------

/** 
 * @subpackage  getFilesRecursively()
 *
 * Recursivly get a list of files matching a pattern
 *
 * @example         getFilesRecursively( "./lib/", '.+\.php' );
 * @param path      Root path of search
 * @param path      Pattern of file name
 * @param realpath  TRUE: realpaths returned; FALSE: relative paths returned
 * @return          List of files
 * 
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function getFilesRecursively( $path, $pattern = '.+\.php', $realpath = FALSE ) {
    if ( $realpath ) $path = realpath($path);
    $pattern = '/' . $pattern . '/i';

    // The prefix "\" means "in global namespace"
    $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
    $Regex = new \RegexIterator($objects, $pattern, \RecursiveRegexIterator::GET_MATCH);

    $phparray = array();
    foreach($objects as $name => $object){
        if ( preg_match($pattern, $name) )  array_push($phparray, $name);
    }
    
    //setlocale(LC_ALL, $GLOBALS['config']['LC_ALL']);
    return( $phparray ) ;
    return( CustomSort( $phparray ) );
}   // getFilesRecursively()

//---------------------------------------------------------------------

/**
 *  @fn         mycopy
 *  @brief      Copy a file and create path
 *  
 *  @param [in] $s1 Source file
 *  @param [in] $s2 Target file
 *  @return     TRUE on success else FALSE
 *  
 *  @details    More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see        https://stackoverflow.com/a/26387878
 *  @since      2024-01-31T11:13:36 / erba
 */
function mycopy($s1, $s2) {
    $path = pathinfo($s2);
    if (!file_exists($path['dirname'])) {
        mkdir($path['dirname'], 0777, true);
    }
    if (!copy($s1, $s2)) {
        echo "copy failed \n";
		return FALSE;
    }
	return TRUE;
}	// mycopy()

//---------------------------------------------------------------------

function verbose( $tag, $data )
{
	if ( $GLOBALS['config']['verbose'] ) 
	{
		fprintf( STDERR, "%-20.20s: %s\n", 	$tag, var_export( $data, TRUE ) );
	}
}	// verbose()

//---------------------------------------------------------------------

function debug( $tag, $data )
{
	if ( $GLOBALS['config']['debug'] ) 
	{
		fprintf( STDERR, "%-20.20s: %s\n", 	$tag, var_export( $data, TRUE ) );
	}
}	// debug()

//---------------------------------------------------------------------

?>