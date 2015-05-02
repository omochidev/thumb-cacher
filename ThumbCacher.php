<?php
/**
 * Class for creating caches for thumbs.
 *
 *  @package    app.Vendor
 *  @category   vendors
 *  @author     Rafael F. Silva <rafael@omochi.com.br>
 *  @copyright  Omochi
 *  @since      1.0.0
**/

class ThumbCacher
{
	/* Set this if you want to use a specific folder to save the cached thumbs */
	private static $_useResizedFolder = true;

	/* Set the base folder, where it will be originals and resized folders */
	private static $_physicalFolder = null;

	/* Set the base folder, where it resolve the virtual paths */
	private static $_virtualFolder = null;

	/* Set the allowed extesions to handle */
	private static $_allowedExtensions = array(
		IMAGETYPE_GIF => "gif",
		IMAGETYPE_JPEG => "jpeg",
		IMAGETYPE_PNG => "png",
		IMAGETYPE_WBMP => "wbmp"
	);

	/* Set the outputted JPEG quality */
	private static $_JPEGQuality = 90;

	/* Set the outputted PNG quality */
	private static $_PNGQuality = 8;

	/**
	 * Sets the physical folder.
	 *
 	 *  @author     Rafael F. Silva <rafael@omochi.com.br>
	 *  @param      (string) $path
	 *  @since      1.0.0
	**/
	public static function setPhysicalFolder($path = null)
	{
		if( is_null($path) )
		{
			throw new Exception('ThumbCacher: Path to the physical folder cannot be null.');
		}

		self::$_physicalFolder = $path;
	}

	/**
	 * Sets the virtual folder.
	 *
 	 *  @author     Rafael F. Silva <rafael@omochi.com.br>
	 *  @param      (string) $path
	 *  @since      1.0.0
	**/
	public static function setVirtualFolder($path = null)
	{
		if( is_null($path) )
		{
			throw new Exception('ThumbCacher: Path to the virtual folder cannot be null.');
		}

		self::$_virtualFolder = $path;
	}

	/**
	 * Get the especified image. If it does not exists in cache, it will be created.
	 *
 	 *  @author     Rafael F. Silva <rafael@omochi.com.br>
	 *  @param      (string) $originalURL
	 *  @param      (array) $options
	 *  @return     (string) the image path
	 *  @since      1.0.0
	**/
	public static function image($originalFileName, $options = array())
	{
		if( is_null(self::$_physicalFolder) )
		{
			throw new Exception('ThumbCacher: Physical folder cannot be null.');
		}

		if( is_null(self::$_virtualFolder) )
		{
			throw new Exception('ThumbCacher: Virtual folder cannot be null.');
		}

		if( is_numeric(strpos($originalFileName, '/')) )
		{
			return $originalFileName;
		}

		$originalURL = self::$_virtualFolder . '/originals/' . $originalFileName;
		$cached = false;
		$originalPath = self::$_physicalFolder . DIRECTORY_SEPARATOR . 'originals' . DIRECTORY_SEPARATOR . $originalFileName;
		$resizedFolder = self::$_physicalFolder . DIRECTORY_SEPARATOR . 'resized' . DIRECTORY_SEPARATOR;

		if( !is_file($originalPath) )
		{
			return false;
		}

		// Check if desired width and height were passed
		if( empty($options['width']) && empty($options['height']) )
		{
			return $originalURL;
		}

		list($originalWidth, $originalHeight, $type) = getimagesize($originalPath);

		if( !isset(self::$_allowedExtensions[$type]) )
		{
			return $originalURL;
		}

		// Calculate the proportinal width if it was defined
		if( empty($options['width']) )
		{
			$options['width'] = floor(($originalWidth * $options['height']) / $originalHeight);
		}

		// Calculate the proportinal height if it was defined
		if( empty($options['height']) )
		{
			$options['height'] = floor(($options['width'] * $originalHeight) / $originalWidth);
		}

		$resizedWidth = $options['width'];
		$resizedHeight = $options['height'];

		// Calculate the ratios
		$originalRatio = $originalWidth / $originalHeight;
		$resizedRatio = $resizedWidth / $resizedHeight;

		// Check the highest dimension and resize the other
		if($originalRatio > $resizedRatio)
		{
			$finalWidth = $originalHeight * $resizedRatio;
			$finalHeight = $originalHeight;
			$finalX = ($originalWidth - $finalWidth) / 2;
			$finalY = 0;
		}
		else
		{
			$finalWidth = $originalWidth;
			$finalHeight = $originalWidth / $resizedRatio;
			$finalX = 0;
			$finalY = ($originalHeight - $finalHeight) / 2;
		}

		$cacheFileName = $resizedWidth . 'x' . $resizedHeight . '_' . $originalFileName;
		$cachePath = '';
		$cacheURL = '';
		if( self::$_useResizedFolder )
		{
			// If the resized folder does not exists, it will be created
			if( file_exists($resizedFolder) == false )
			{
				mkdir($resizedFolder, 0777);
			}

			$cachePath = $resizedFolder . $cacheFileName;
			$cacheURL = str_replace('originals', 'resized', $originalURL);
			$cacheURL = str_replace($originalFileName, $cacheFileName, $cacheURL);
		}
		else
		{
			$cachePath = str_replace($originalFileName, $cacheFileName, $originalPath);
			$cacheURL = str_replace($originalFileName, $cacheFileName, $originalURL);
		}

		if( file_exists($cachePath) )
		{
			if( filemtime($cachePath) >= filemtime($originalPath) )
			{
				$cached = true;
			}
		}

		// If it is not in cache
		if( $cached === false )
		{
			// Creates an image object from the original
			$image = call_user_func('imagecreatefrom' . self::$_allowedExtensions[$type], $originalPath);

			// Creates a new image object and copy from the original
			if( function_exists('imagecreatetruecolor') )
			{
				$temp = imagecreatetruecolor($resizedWidth, $resizedHeight);

				if( $type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG )
				{
					imagecolortransparent($temp, imagecolorallocatealpha($temp, 0, 0, 0, 127));
					imagealphablending($temp, false);
					imagesavealpha($temp, true);
				}

				imagecopyresampled($temp, $image, 0, 0, $finalX, $finalY, $resizedWidth, $resizedHeight, $finalWidth, $finalHeight);
			}

			// Creates a new image object and copy from the original
			else
			{
				$temp = imagecreate($resizedWidth, $resizedHeight);
				imagecopyresized($temp, $image, 0, 0, $finalX, $finalY, $resizedWidth, $resizedHeight, $finalWidth, $finalHeight);
			}

			switch( $type )
			{
				case IMAGETYPE_JPEG:
					imagejpeg($temp, $cachePath, self::$_JPEGQuality);
					break;

				case IMAGETYPE_PNG:
					imagepng($temp, $cachePath, self::$_PNGQuality);
					break;

				default:
					call_user_func('image' . self::$_allowedExtensions[$type], $temp, $cachePath);
			}

			imagedestroy($image);
			imagedestroy($temp);
		}

		return $cacheURL;
	}
}
