<!DOCTYPE html>

<?php
/*
Noctifer Directory Gallery Script 1.06
Copyright 2015 Laurens R Krol
noctifer.net

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
*/

error_reporting(0);

# this script will automatically create thumbnails in a new subdirectory if any are missing.
# make sure that the script has permission to make these write actions.
$thumbnailSize = 200;                               # size of the largest side of the thumbnail
$thumbnailDirectory = "NoctiferGalleryThumbnails";  # directory in which to store the thumbnails
$thumbnailPrefix = "NoctiferGalleryThumbnail_";     # prefix for the thumbnail filename

# maximum number of thumbnails displayed next to each other
$thumbnailColumns = 4;

# black colour scheme
$bgColour = 'black';                # page background
$bgColourHighlight = '#1c1c1c';     # browse page title & highlights background
$fgColour = 'grey';                 # colour of text and lines
$fgColourHighlight = 'white';       # colour of active links

/*
# white-blue colour scheme
$bgColour = 'white';
$bgColourHighlight = '#c8cfd4';
$fgColour = '#4b6d67';
$fgColourHighlight = '#03223a';
*/

# case-insensitive list of allowed file extensions.
# note: if you want to add an extension, make sure php can create thumbnails for it
# and add the appropriate command to the switch where the thumbnails are created.
$allowedExtensions = array("jpg", "jpeg", "png", "gif");

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
                $madeDir = mkdir($thumbnailDirectory, 0775, true);
            }
            
            # cancelling if thumbnail directory still not available
            if ( !is_dir( $thumbnailDirectory ) ) { break; }

            # getting image size, type, identifier
            $imageinfo = getimagesize($image);
            $width = $imageinfo[0];
            $height = $imageinfo[1];
            $type = $imageinfo[2];

            switch ($type) {
                case 1 :
                    $img = imageCreateFromGif( $image );
                break;
                case 2 :
                    $img = imageCreateFromJpeg( $image );
                break;
                case 3 :
                    $img = imageCreateFromPng( $image );
                break;
            }

            # calculating thumbnail size
            if ( $width > $height ) {
                $new_width = $thumbnailSize;
                $new_height = floor( $height * ( $thumbnailSize / $width ) );
            } else {
                $new_height = $thumbnailSize;
                $new_width = floor( $width * ( $thumbnailSize / $height ) );
            }

            # creating thumbnail
            $thumbnail = imagecreatetruecolor( $new_width, $new_height );
            imagecopyresampled( $thumbnail, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
            $madeThumbnail = imagejpeg( $thumbnail, $thumbnailDirectory . "/" . $thumbnailPrefix . $image . ".jpg");
        }
    }

    $currentDir = str_replace('/' . basename(__FILE__), '', $_SERVER['SCRIPT_NAME']);

    $title = "Contents of " . $currentDir . " (" . count($imageList) . ")";
}

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $title; ?></title>

    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="description" content="Photo gallery using the Noctifer Directory Gallery Script">
    <meta name="author" content="Laurens R Krol" />
    
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php echo $title ?>" />
    <meta property="og:description" content="Photo gallery using the Noctifer Directory Gallery Script" />
<?php
        if ( $mode == 'browse' && sizeof( $imageList ) > 0 ) {
            # in browse mode, setting all images as open graph images, if available
            foreach ($imageList as $ogTempImg) {
                $ogImg = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"] . rawurlencode($ogTempImg);
                echo "    <meta property=\"og:image\" content=\"$ogImg\" />\n";
            }
        } else if ( $mode == 'view' ) {
            # otherwise, using the selected image
            $ogImg = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER["REQUEST_URI"]) . '/' . rawurlencode($photo);
            echo "    <meta property=\"og:image\" content=\"$ogImg\" />\n";
        }
?>

    <script src="http://code.jquery.com/jquery-latest.js" type="text/javascript"></script>
    <script type="text/javascript">
        function toggleView(photoFile) {
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
<?php if ($mode == "view") {
echo <<<END
        
        document.onkeydown = function(e){
            switch (e.keyCode) {
                case 35: // end
                    window.location = '?view=$lastUrl';
                    break;
                case 36: // home
                    window.location = '?view=$firstUrl';
                    break;
                case 37: // left
                    window.location = '?view=$previousUrl';
                    break;
                case 38: // up
                    window.location = '.';
                    break;
                case 39: // right
                    window.location = '?view=$nextUrl';
                    break;
                case 13: // enter
                    toggleView('$photoUrl');
                    break;
            }
        };

        $(window).load(function () {
            var nextPhotoPreload = new Image();
            nextPhotoPreload.src = '$next';
            var previousPhotoPreload = new Image();
            previousPhotoPreload.src = '$previous';
        });

END;
} ?>
    </script>

    <style type="text/css">
        body {
                margin: 0px;
                padding: 0px;
                background-color: <?php echo $bgColour; ?>;
                font-family: sans-serif;
                font-size: small;
                color: <?php echo $fgColour; ?>;
                text-align: center; }

        #viewContainer {
                width: 100%;
                height: 100%;
                position: absolute;
                top: 0px;
                left: 0px; }

            #viewTitleContainer {
                    height: 5%;
                    width: 100%;
                    display: table; }
                    
                #viewTitle {
                        display: table-cell;
                        vertical-align: middle; }

                    #viewTitle a {
                            color: <?php echo $fgColour; ?>;
                            font-style: italic;
                            font-size: medium;
                            text-decoration: none; }

                    #viewTitle a:before, #viewTitle a:after {
                            content: '\00a0\00a0\00a0\00a0'; }

                    #viewTitle a:hover {
                            color: <?php echo $fgColourHighlight; ?>;
                            background-color: <?php echo $bgColourHighlight; ?>;  }

            #viewPhotoContainer {
                    height: 90%;
                    width: 100%;
                    display: table; }

                #viewPhoto {
                    background-repeat: no-repeat;
                    background-position: center center;
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
                            text-decoration: none; }

                    #viewControls a:before, #viewControls a:after {
                            content: '\00a0\00a0\00a0\00a0'; }

                    #viewControls a:hover {
                                color: <?php echo $fgColourHighlight; ?>;
                                background-color: <?php echo $bgColourHighlight; ?>;  }

        #browseTitle     {
                padding: 5px;
                margin-bottom: 15px;
                font-size: medium;
                font-style: italic;
                background-color: <?php echo $bgColourHighlight; ?>;
                border-bottom: 1px solid <?php echo $fgColour; ?>; }

        #browseContainer {
                margin: 0px auto 50px auto;
                max-width: <?php echo $thumbnailColumns * ($thumbnailSize + 22); ?>px; }

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
                        margin: 5px;
                        float: left;
                        border-top-left-radius: 5px;
                        border-top-right-radius: 5px;
                        background-repeat: no-repeat;
                        background-position: center center;
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
        <div id="viewTitleContainer"><div id="viewTitle"><a href="$photo">$photo</a></div></div>
        <div id="viewPhotoContainer"><div id="viewPhoto" $bgCode onclick="toggleView('$photoUrl');">$imgCode</div></div>
        <div id="viewControlsContainer"><div id="viewControls">
            <a href="?view=$firstUrl">&lt;&lt;</a>
            <a href="?view=$previousUrl">&lt;</a>
            <a href=".">Browse</a>
            <a href="?view=$nextUrl">&gt;</a>
            <a href="?view=$lastUrl">&gt;&gt;</a>
        </div></div>
    </div>
END;
} else {
    # photo browser
    echo "<div id=\"browseTitle\">$title</div>\n";
    echo "<div id=\"browseContainer\">\n";

    foreach ( $dirList as $dir ) {
        $directoryUrl = rawurlencode($dir);
        if ($dir == '..') { $dir = "Up"; }

        echo "    <a class=\"directoryLink\" href=\"$directoryUrl\"><span class=\"directoryLinkArrow\">" . ($dir == "Up" ? "&uarr;" : "&rarr;") . "</span> $dir</a><br />\n";
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