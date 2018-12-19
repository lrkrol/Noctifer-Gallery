# Noctifer Directory Gallery Script
This is a single-file PHP script that handles everything you need in order to turn a directory into an online image gallery. Simply drop `index.php` into the directory (with appropriate permissions), and you'll get both an image browser and a full-screen viewer. 


## Folder & Image Thumbnail Browser
By default, when calling this script, it shows a browser. The browser shows thumbnails for supported image files (currently bmp, jpg, png, gif), and lists subdirectories for navigation. When new files are encountered, it automatically creates thumbnails and stores them in a special-purpose subdirectory. (Because of this, the script may take a while to load when accessing it for the first time. It may even time out. Simply refresh until all thumbnails are created.)

Use `$thumbnailSize` to set the size of the thumbnails' longest edge in pixels. `$thumbnailSquare` enforces square thumbnails, cropping the middle of the images. Thumbnails will be JPEGs, with `$thumbnailQuality` determining their image quality.

To organise the thumbnails in the browser, `$thumbnailColumns` and `$thumbnailColumnsMobile` set the maximum number of thumbnail columns in desktop mode, and the exact number of thumbnail columns on mobile, respectively. 


## Image Viewer
When selecting a thumbnail in the browser, or when accessing a specific image directly using `?view=image.ext` in the URL, the script serves up a full-screen image viewer. With navigational controls. When on desktop, this image viewer can additionally show images either at 100% of their size, or fit them to the screen, and can be controlled by keyboard. On mobile, swipes can be used for navigation. From this screen, the image can also be downloaded.

Keyboard shortcuts:
* `Home`: Go to first image in directory
* `Left`: Go to previous image in directory
* `Right`: Go to next image in directory
* `End`: Go to last image in directory
* `Up`: Go back to directory browser
* `Enter`: Switch zoom mode

Mobile swipe shortcuts:
* `Swipe right`: Go to previous image in directory
* `Swipe left`: Go to next image in directory
* `Swipe up`: Go back to directory browser


## Colour Scheme
The style sheet uses only four colours: background, foreground (text, lines), and highlights for these two (for focus/hover). These can be set using `$bgColour`, `$fgColour`, `$bgColourHighlight`, and `$fgColourHighlight` using CSS colour codes. A light and a dark colour scheme are included. [This online example](http://files.noctifer.net/2014_california/) uses the dark colour scheme.


## Notes on usage
Generally, simply drop the script into a directory and it will do the rest. Some things to keep in mind, though:

By default, **this script copies itself to all subdirectories it finds** that do not yet have an index file. This allows you to drop it once into the root of a larger gallery structure without putting it into every directory manually. This behaviour can bedisabled by setting `$copyToSubs` to false. 

Since this script needs write access (to create thumbnail directories, thumbnail images, and potentially new copies of the script itself), it requires appropriate permissions. These may need to be set manually for the directory containing the script. Furthermore, the script will be the owner of the files it writes. Depending on your server configuration, you may not be able to simply delete these files. Should this happen, ask your host how to regain ownership of the files. Another option is to run the script on a local server first before copying all subsequently generated files, including thumbnails, onto your online host.

The script is about 30 kB. This is well below recommended sizes for fast page loads. If you want to reduce it, however, a decent chunk of the size is due to the favicon, stored inline in base64 format to remain the single-file utility. Removing this line will save you about 10 kB.