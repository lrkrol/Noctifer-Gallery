<!doctype html>

<!--
Noctifer Directory Gallery Script 1.18
Copyright 2015, 2018 Laurens R Krol
noctifer.net, lrkrol.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<?php
error_reporting(0);

### configuration ###

# meta data
$description = "Photo gallery using the Noctifer Directory Gallery Script";
$author = "Laurens R Krol";

# this script will automatically create thumbnails in a new subdirectory if any are missing.
# make sure that the script has permission to make these write actions.
$thumbnailSize = 225;                               # size of the largest side of the thumbnail
$thumbnailSquare = true;                            # whether or not to make all thumbnails square
$thumbnailDirectory = "NoctiferGalleryThumbnails";  # directory in which to store the thumbnails
$thumbnailPrefix = "NoctiferGalleryThumbnail_";     # prefix for the thumbnail filename

# maximum number of thumbnails displayed next to each other
$thumbnailColumns = 4;
$thumbnailColumnsMobile = 2;

# thumbnail jpeg quality, 0 = worst (smaller file), 100 = best (larger file)
$thumbnailQuality = 100;

# whether or not to insert this script into subdirectories when no other index file is present,
# and if so, array of directories to not copy to
$copyToSubs = true;
$copyToSubsExcluded = array("..", ".git");

# dark colour scheme
$bgColour = '#0a0a0a';                  # page background
$bgColourHighlight = '#1c1c1c';         # browse page title & highlights background
$fgColour = 'grey';                     # colour of text and lines
$fgColourHighlight = 'white';           # colour of active links
$transparentRGB = array(10, 10, 10);    # RGB values of colour that will replace transparency in thumbnails

/*
# light colour scheme
$bgColour = '#fafafa';
$bgColourHighlight = '#c8cfd4';
$fgColour = '#4b6d67';
$fgColourHighlight = '#03223a';
$transparentRGB = array(250, 250, 250);
*/

# case-insensitive list of allowed file extensions.
# note: if you want to add an extension, make sure php can create thumbnails for it
# and add the appropriate command to the switch where the thumbnails are created.
if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
    $allowedExtensions = array("bmp", "jpg", "jpeg", "png", "gif");
} else {
    $allowedExtensions = array("jpg", "jpeg", "png", "gif");
}


### main script ###

# reading directory
$imageList = array();
$dirList = array();
if ( $dh = opendir( '.' ) ) {
    while ( $itemName = readdir( $dh ) ) {
        if ( is_file( $itemName ) ) {
            $info = pathinfo( $itemName );

            if ( in_array( strtolower($info['extension'] ), $allowedExtensions ) ) {
                # adding allowed image file types to image list
                $imageList[] = $info['filename'] . "." . $info['extension'];
            }
        } else if ( is_dir($itemName) && $itemName != "." && $itemName != $thumbnailDirectory ) {
            # adding non-thumbnail directories to directory list
            $dirList[] = $itemName;

            # copying script to subdirectory if no index file is present
            if ( $copyToSubs && !is_file( $itemName . DIRECTORY_SEPARATOR . "index.php" )
                             && !is_file( $itemName . DIRECTORY_SEPARATOR . "index.html" )
                             && !is_file( $itemName . DIRECTORY_SEPARATOR . "index.htm" )
                             && !in_array( $itemName, $copyToSubsExcluded ) ) {
                copy("index.php", $itemName . DIRECTORY_SEPARATOR . "index.php");
            }
        }
    }
}
closedir($dh);

# sorting lists alphabetically
usort( $imageList, 'strnatcasecmp' );
usort( $dirList, 'strnatcasecmp' );

# determining page mode, setting variables
if ( isset( $_GET['view'] ) && in_array( $_GET['view'], $imageList ) ) {
    $mode = 'view';
    $photo = $_GET['view'];

    # setting next/previous, or defaulting to current photo
    $key = array_search( $photo, $imageList );

    if ( array_key_exists( $key - 1, $imageList ) ) {
        $previous = $imageList[$key - 1];
    } else {
        $previous = $photo;
    }

    if ( array_key_exists( $key + 1, $imageList ) ) {
        $next = $imageList[$key + 1];
    } else {
        $next = $photo;
    }

    $first = reset( $imageList );
    $last = end( $imageList );

    # encoding urls
    $photoUrl = rawurlencode( $photo );
    $firstUrl = rawurlencode( $first );
    $previousUrl = rawurlencode( $previous );
    $nextUrl = rawurlencode( $next );
    $lastUrl = rawurlencode( $last );

    # setting zoom mode
    if(isset($_COOKIE['zoom']) && strcmp($_COOKIE['zoom'], 'originalSize') == 0) {
        $zoomFit = false;
    } else {
        $zoomFit = true;
    }

    # setting page title
    $currentImageNum = $key + 1;
    $numImages = count($imageList);
    $title = "($currentImageNum/$numImages) $photo";
} else {
    $mode = 'browse';

    # creating thumbnails if not available
    foreach ( $imageList as $image ) {
        if ( !file_exists( $thumbnailDirectory . "/" . $thumbnailPrefix . $image . ".jpg" ) ) {
            # creating thumbnail directory if not available
            if ( !is_dir( $thumbnailDirectory ) ) {
                $madeDir = mkdir($thumbnailDirectory, 0755, true);
            }

            # cancelling if thumbnail directory still not available
            if ( !is_dir( $thumbnailDirectory ) ) { break; }

            # getting image size, type, identifier
            $imageinfo = getimagesize($image);
            $width = $imageinfo[0];
            $height = $imageinfo[1];
            $type = $imageinfo[2];

            switch ($type) {
                case IMAGETYPE_GIF :
                    $img = imageCreateFromGif( $image );
                break;
                case IMAGETYPE_JPEG :
                    $img = imageCreateFromJpeg( $image );
                break;
                case IMAGETYPE_PNG :
                    $img = imageCreateFromPng( $image );
                break;
                case IMAGETYPE_BMP :
                    $img = imageCreateFromBmp( $image );
                break;
            }

            # calculating thumbnail size
            if  ( $thumbnailSquare ) {
                $new_width = $thumbnailSize;
                $new_height = $thumbnailSize;
                if ( $width > $height ) {
                    $cropx = ( $width - $height ) / 2;
                    $cropy = 0;
                } else {
                    $cropx = 0;
                    $cropy = ( $height - $width ) / 2;
                }
            } else {
                if ( $width > $height ) {
                    $new_width = $thumbnailSize;
                    $new_height = floor( $height * ( $thumbnailSize / $width ) );
                } else {
                    $new_height = $thumbnailSize;
                    $new_width = floor( $width * ( $thumbnailSize / $height ) );
                }
                $cropx = 0;
                $cropy = 0;
            }

            # creating thumbnail
            $thumbnail = imagecreatetruecolor( $new_width, $new_height );
            imagefill($thumbnail, 0, 0, imagecolorallocate($thumbnail, $transparentRGB[0], $transparentRGB[1], $transparentRGB[2]));
            imagecopyresampled( $thumbnail, $img, 0, 0, $cropx, $cropy, $new_width, $new_height, $width-2*$cropx, $height-2*$cropy );
            $madeThumbnail = imagejpeg( $thumbnail, $thumbnailDirectory . "/" . $thumbnailPrefix . $image . ".jpg", $thumbnailQuality );
        }
    }

    $currentDir = str_replace('/' . basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);

    $title = "Contents of " . $currentDir . " (" . count($imageList) . " images)";
}

?>
<html lang="" prefix="og: http://ogp.me/ns#">
<head>
    <meta charset="utf-8" />

    <title><?php echo $title; ?></title>

    <meta name="description" content="<?php echo $description; ?>">
    <meta name="author" content="<?php echo $author; ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" id="viewport" />

<?php
    $protocol = ( ( !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ) || $_SERVER['SERVER_PORT'] == 443 ) ? 'https://' : 'http://';
    if ( $mode == 'browse' ) {
        if ( sizeof( $imageList ) > 0 ) {
            # in browse mode, using the first image as open graph image, if available
            $ogImg = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"] . rawurlencode($imageList[0]);
            $ogUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
            $gotImg = true;
        } else {
            // in browse mode but there are no images
            $ogUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
            $gotImg = false;
        }
    } else if ( $mode == 'view' ) {
        # otherwise, using the selected image
        $ogImg = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER["REQUEST_URI"]) . '/' . rawurlencode($photo);
        $ogUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER["REQUEST_URI"]) . '/?view=' . rawurlencode($photo);
        $gotImg = true;
    }
    
    if ( $gotImg ) {
        if ( $mode == 'browse' ) {
            $ogImgInfo = getimagesize($imageList[0]);
        } else {
            $ogImgInfo = getimagesize($photo);
        }
        $ogImgWidth = $imageinfo[0];
        $ogImgHeight = $imageinfo[1];
        $ogImgType = $imageinfo['mime'];
    }
?>
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo $ogUrl; ?>" />
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:description" content="<?php echo $description; ?>" />
<?php if ( $gotImg ) {
echo <<<END
    <meta property="og:image" content="{$ogImg}" />
    <meta property="og:image:width" content="{$ogImgInfo[0]}" />
    <meta property="og:image:height" content="{$ogImgInfo[1]}" />
    <meta property="og:image:type" content="{$ogImgInfo['mime']}" />
END;
if ( $protocol == 'https://' ) { echo "\n    <meta property=\"og:image:secure_url\" content=\"$ogImg\" />"; }
}
echo "\n";
?>

    <link rel="icon" href="data:image/png;base64,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" />

    <script>
        function toggleView(photoFile) {
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) {
                window.location.href = photoFile;
            } else {
                var photo = document.getElementById('viewPhoto');
                if (photo.innerHTML == '') {
                    photo.innerHTML = '<img src="'.concat(photoFile, '" alt="" />');
                    photo.style.backgroundImage = 'url()';
                    document.cookie="zoom=originalSize; path=/";
                } else {
                    photo.innerHTML = '';
                    photo.style.backgroundImage = 'url('.concat(photoFile, ')');
                    document.cookie="zoom=fit; path=/";
                }
            }
        }

<?php if ($mode == "browse") {
$mobileMaxWidth = $thumbnailColumnsMobile * ($thumbnailSize + 12);
echo <<<END

        // based on code from saike @ https://stackoverflow.com/a/39642318
        (function(){
            function apply_viewport(){
                if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)) {
                    var ww = window.screen.width;
                    var mw = $mobileMaxWidth;
                    var ratio =  ww / mw;
                    var viewport_meta_tag = document.getElementById('viewport');
                    viewport_meta_tag.setAttribute('content', 'initial-scale=' + ratio + ', maximum-scale=' + ratio + ', minimum-scale=' + ratio + ', width=' + mw);
                }
            }

            window.addEventListener('resize', function(){
                apply_viewport();
            });

            apply_viewport();
        }());

END;
} else {
echo <<<END

        document.onkeydown = function(e){
            switch (e.keyCode) {
                case 35: // end
                    document.getElementById('aLast').focus();
                    window.location = '?view=$lastUrl';
                    break;
                case 36: // home
                    document.getElementById('aFirst').focus();
                    window.location = '?view=$firstUrl';
                    break;
                case 37: // left
                    document.getElementById('aPrevious').focus();
                    window.location = '?view=$previousUrl';
                    break;
                case 38: // up
                    document.getElementById('aClose').focus();
                    window.location = '.';
                    break;
                case 39: // right
                    document.getElementById('aNext').focus();
                    window.location = '?view=$nextUrl';
                    break;
                case 13: // enter
                    toggleView('$photoUrl');
                    break;
            }
        };

        function swipedetect(el, callback){
            // based on code from JavaScript Kit @ http://www.javascriptkit.com/javatutors/touchevents2.shtml
            var touchsurface = el,
                swipedir,
                startX,
                startY,
                distX,
                distY,
                threshold = 50,
                handleswipe = callback || function(swipedir){}

            touchsurface.addEventListener('touchstart', function(e){
                var touchobj = e.changedTouches[0]
                swipedir = 'none'
                dist = 0
                startX = touchobj.pageX
                startY = touchobj.pageY
            }, false)

            touchsurface.addEventListener('touchend', function(e){
                var touchobj = e.changedTouches[0]
                distX = touchobj.pageX - startX
                distY = touchobj.pageY - startY
                if (Math.abs(distX) >= threshold && Math.abs(distX) > Math.abs(distY)){
                    swipedir = (distX < 0)? 'left' : 'right'
                } else if (Math.abs(distY) >= threshold && Math.abs(distY) > Math.abs(distX)){
                    swipedir = (distY < 0)? 'up' : 'down'
                }
                handleswipe(swipedir)
            }, false)
        };

        window.addEventListener('load', function(){
            var el = document.getElementById('viewPhotoContainer');
            swipedetect(el, function(swipedir){
                if (swipedir == 'left'){
                    document.getElementById('aNext').focus();
                    window.location = '?view=$nextUrl';
                } else if (swipedir == 'right'){
                    document.getElementById('aPrevious').focus();
                    window.location = '?view=$previousUrl';
                } else if (swipedir == 'up'){
                    document.getElementById('aClose').focus();
                    window.location = '.';
                }
            })
        }, false);

END;
} ?>
    </script>

    <style>
        :root {
                --size-thumbnail: <?php echo $thumbnailSize; ?>px;
                --size-thumbnail-plus-margin: <?php echo $thumbnailSize + 10; ?>px;
                --size-thumbnail-plus-margin-minus-one: <?php echo $thumbnailSize + 9; ?>px; }

        body {
                margin: 0px;
                padding: 0px;
                background-color: <?php echo $bgColour; ?>;
                font-family: sans-serif;
                font-size: medium;
                color: <?php echo $fgColour; ?>;
                text-align: center; }

        #viewContainer {
                width: 100%;
                height: 100%;
                position: absolute; }

            #viewTitleContainer {
                    height: 5%;
                    width: 100%;
                    display: table; }

                #viewTitle {
                        display: table-cell;
                        vertical-align: middle; }

                    #viewTitle a {
                            color: <?php echo $fgColour; ?>;
                            padding: 5px 15px;
                            text-decoration: none; }

                    #viewTitle a:hover {
                            color: <?php echo $fgColourHighlight; ?>;
                            background-color: <?php echo $bgColourHighlight; ?>; }

                    #viewTitleArrow {
                            height: .75em;
                            fill: <?php echo $fgColour; ?>; }

                    #viewTitle a:hover > #viewTitleArrow path {
                            fill: <?php echo $fgColourHighlight; ?>; }

            #viewPhotoContainer {
                    height: 89%;
                    width: 100%;
                    display: table; }

                #viewPhoto {
                        background-repeat: no-repeat;
                        background-position: center;
                        background-size: contain;
                        cursor: zoom-in;
                        display: table-cell;
                        vertical-align: middle; }

            #viewControlsContainer {
                    height: 5%;
                    width: 100%;
                    display: table; }

                #viewControls {
                        display: table-cell;
                        vertical-align: middle; }

                    #viewControls a {
                            color: <?php echo $fgColour; ?>;
                            padding: 5px 15px;
                            text-decoration: none; }

                    #viewControls a:hover, #viewControls a:focus {
                            color: <?php echo $fgColourHighlight; ?>;
                            background-color: <?php echo $bgColourHighlight; ?>;  }

        #viewPreloader {
                position: absolute;
                width: 0;
                height: 0;
                overflow: hidden;
                z-index: -1; }

        #browseTitle     {
                padding: 5px;
                margin-bottom: 15px;
                background-color: <?php echo $bgColourHighlight; ?>;
                border-bottom: 1px solid <?php echo $fgColour; ?>; }

        #browseContainer {
                margin: 0px auto 50px auto;
                max-width: <?php echo $thumbnailColumns * ($thumbnailSize + 12); ?>px; }

            .directoryLink {
                    text-align: left;
                    display: block;
                    padding: 5px;
                    color: <?php echo $fgColour; ?>;
                    border: 1px solid <?php echo $bgColour; ?>;
                    border-radius: 5px;
                    text-decoration: none; }

                .directoryLink:hover {
                        background-color: <?php echo $bgColourHighlight; ?>;
                        border: 1px solid <?php echo $fgColour; ?>;
                        color: <?php echo $fgColourHighlight; ?>; }

                .directoryLinkArrow {
                        text-align: center;
                        width: 25px;
                        display: inline-block; }

            .thumbnailLink {
                    display: block; }

                .thumbnail {
                        float: left;
                        border-top-left-radius: 5px;
                        border-top-right-radius: 5px;
                        background-repeat: no-repeat;
                        background-position: center;
                        border: 1px solid <?php echo $bgColour; ?>;
                        overflow: hidden;
                        width: <?php echo $thumbnailSize + 10; ?>px;
                        height: <?php echo $thumbnailSize + 10; ?>px; }

                    .thumbnailTitle {
                            width: <?php echo $thumbnailSize + 10; ?>px;
                            border: 1px solid <?php echo $fgColour; ?>;
                            border-top: 0px;
                            border-bottom-left-radius: 5px;
                            border-bottom-right-radius: 5px;
                            box-shadow: 0px 10px 10px <?php echo $bgColour; ?>;
                            padding-bottom: 5px;
                            position: relative;
                            top: <?php echo $thumbnailSize + 9; ?>px;
                            left: -1px;
                            word-wrap: break-word;
                            background-color: inherit; }

                    .thumbnail:hover {
                            overflow: visible;
                            color: <?php echo $fgColourHighlight; ?>;
                            border: 1px solid <?php echo $fgColour; ?>;
                            background-color: <?php echo $bgColourHighlight; ?>; }

        #browseFooter {
                clear: both; }

        a {
                outline: 0; }

        @media screen and (max-width: 1000px) {
            body { font-size: large; }
        }

        @media screen and (max-width: 1000px) and (orientation: landscape) {
            #viewPhotoContainer { height: 80%; }
        }
    </style>
</head>

<body>
<?php
if ($mode == 'view') {
    # single photo viewer
    if ($zoomFit) {
        $bgCode = "style=\"background-image: url($photoUrl);\"";
        $imgCode = "";
    } else {
        $imgCode = "<img src=\"$photoUrl\" alt=\"\" />";
        $bgCode = "";
    }

    echo <<<END
    <div id="viewContainer">
        <div id="viewTitleContainer"><div id="viewTitle"><a href="$photo">$photo <svg id="viewTitleArrow" version="1.1" viewBox="0 0 1000 1500" xmlns="http://www.w3.org/2000/svg"><path d="M536 1500l-464 -445 270 0 0 -631c0,-142 -23,-240 -69,-294 -46,-55 -128,-82 -246,-82l-27 0 0 -48 196 0c132,0 228,10 288,30 60,20 113,59 157,117 60,77 89,208 89,392l0 516 270 0 -464 445z" /></svg></a></div></div>
        <div id="viewPhotoContainer"><div id="viewPhoto" $bgCode onclick="toggleView('$photoUrl');">$imgCode</div></div>
        <div id="viewControlsContainer"><div id="viewControls"><a id="aFirst" href="?view=$firstUrl">&#x25c4;&#x25c4;</a>&nbsp;<a id="aPrevious" href="?view=$previousUrl">&#x25c4;</a>&nbsp;<a id="aClose" href=".">&#x2715;</a>&nbsp;<a id="aNext" href="?view=$nextUrl">&#x25ba;</a>&nbsp;<a id="aLast" href="?view=$lastUrl">&#x25ba;&#x25ba;</a></div></div>
    </div>
    <div id="viewPreloader"><img src="./$next" alt="" /><img src="./$previous" alt="" /></div>
END;
} else {
    # photo browser
    echo "<div id=\"browseTitle\">Contents of <b>" . str_replace('/', ' &rarr; ', substr($currentDir, 1)) . "</b> (" . count($imageList) . " images)</div>\n";
    echo "<div id=\"browseContainer\">\n";

    foreach ( $dirList as $dir ) {
        $directoryUrl = rawurlencode($dir);
        if ($dir == '..') { $dir = "Back"; }

        echo "    <a class=\"directoryLink\" href=\"$directoryUrl\"><span class=\"directoryLinkArrow\">" . ($dir == "Back" ? "&#x25c4;" : "&#x25ba;") . "</span> $dir</a><br />\n";
    }

    if ( sizeof( $dirList ) == 0 && sizeof( $imageList ) == 0 ) {
        echo "This directory is empty. ";
    } elseif ( sizeof($imageList) == 0 ) {
        echo "This directory contains no images. ";
    }

    if ( isset( $madeDir ) && !$madeDir ) { echo "Error creating thumbnails directory. Check script / directory permissions.\n"; }
    if ( isset( $madeThumbnail ) && !$madeThumbnail ) { echo "Error creating thumbnail file(s). Check script / directory permissions.\n"; }

    foreach ( $imageList as $image ) {
        $thumbnailUrl = rawurlencode($thumbnailPrefix . $image . ".jpg");
        $viewPhotoUrl = rawurlencode($image);

        echo "    <a class=\"thumbnailLink\" href=\"?view=$viewPhotoUrl\"><div class=\"thumbnail\" style=\"background-image: url($thumbnailDirectory/$thumbnailUrl);\"><div class=\"thumbnailTitle\">$image</div></div></a>\n";
    }

    echo "    <div id=\"browseFooter\"></div>\n";
    echo "</div>";
}

?>

</body>
</html>