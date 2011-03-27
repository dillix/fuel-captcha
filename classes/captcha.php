<?php
/**
 * Fuel Captcha
 *
 * Captcha package for Fuel framework.
 *
 * @package		Captcha
 * @version		alpha
 * @author		Mikhail Khasaya
 * @author		Kruglov Sergei
 * @license		MIT License
 * @copyright	2011 Mikhail Khasaya
 */


namespace Captcha;

class Captcha {

	// Don't change without changing font files!
	protected static $alphabet = '0123456789abcdefghijklmnopqrstuvwxyz';

	// Alphabet without similar symbols (o=0, 1=l, i=j, t=f)
	protected static $allowed_symbols = '23456789abcdeghkmnpqsuvxyz';

	// Folder with fonts
	protected static $fonts_dir = '';

	// Captcha string length
	protected static $length = 5;

	// Captcha image size (you don't need to change it, whis parameters is optimal)
	protected static $width = 120;
	protected static $height = 60;

	// Symbol's vertical fluctuation amplitude divided by 2
	protected static $fluctuation_amplitude = 5;
	
	// Increase safety by prevention of spaces between symbols
	protected static $no_spaces = true;

	// Show credits, credits adds 12 pixels to image height
	protected static $show_credits = false;
	
	// Captcha image colors (RGB, 0-255)
	protected static $foreground_color = array(82, 75, 28);
	protected static $background_color = array(212, 247, 228);

	// JPEG quality of Captcha image (bigger is better quality, but larger file size)
	protected static $jpeg_quality = 90;
	
	// Captcha keystring 
	//private
	protected static $keystring = '';

	/**
	 * class constructor
	 *
	 * @param	void
	 * @access	private
	 * @return	void
	 */
	final private function __construct() {}

	/**
	 * Init
	 *
	 * Loads in the config and sets the variables
	 *
	 * @access	public
	 * @return	void
	 */
	public static function _init()
	{
		// load the config
		$config = \Config::load('captcha', true);

		// update the defaults with the configed values
		foreach($config as $key => $value)
		{
			isset(static::${$key}) && static::${$key} = $value;
			//print_r($value);
		}
	}

	/*
	 * set a configuration value
	 *
	 * @param	string	name of the configuration key
	 * @param	string	value to be set
	 * @access	public
	 * @return	void
	 */
	public static function set($name = false, $value = null)
	{
		$name && isset(static::${$name}) && static::${$name} = $value;
	}

	/*
	 * get a configuration value
	 *
	 * @param	string	name of the configuration key
	 * @access	public
	 * @return	string	the configuration key value, or false if the key is invalid
	 */
	public static function get($name = false)
	{
		return $name && isset(static::${$name}) ? static::${$name} : false;
	}

	public static function check()
	{
		$captcha_keystring = \Session::get('captcha_keystring');
		$post_keystring = \Input::post('keystring');
		
		if($post_keystring !== null && $captcha_keystring !== null && $captcha_keystring == $post_keystring) return true;

		return false;
	}
	
	public static function generate()
	{
		$fonts = array();

		//Get avaliable fonts
		foreach (glob(static::$fonts_dir.'*.png') as $filename)
		{
			$fonts[]= $filename;
		}
	
		$alphabet_length = strlen(static::$alphabet);

		do
		{
			// generating random keystring
			while(true)
			{
				static::$keystring = '';
				for($i=0; $i < static::$length; $i++)
				{
					static::$keystring .= static::$allowed_symbols[mt_rand(0, strlen(static::$allowed_symbols) - 1)];
				}
				if(!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', static::$keystring)) break;
			}
			
			//Save to session
			\Session::set('captcha_keystring', static::$keystring);
		
			$font_file = $fonts[mt_rand(0, count($fonts) - 1)];
			$font = imagecreatefrompng($font_file);
			imagealphablending($font, true);
			$fontfile_width = imagesx($font);
			$fontfile_height = imagesy($font) - 1;
			$font_metrics = array();
			$symbol = 0;
			$reading_symbol = false;

			// loading font
			for($i=0; $i < $fontfile_width && $symbol < $alphabet_length; $i++)
			{
				$transparent = (imagecolorat($font, $i, 0) >> 24) == 127;

				if(!$reading_symbol && !$transparent)
				{
					$font_metrics[static::$alphabet[$symbol]] = array('start' => $i);
					$reading_symbol = true;
					continue;
				}

				if($reading_symbol && $transparent)
				{
					$font_metrics[static::$alphabet[$symbol]]['end'] = $i;
					$reading_symbol = false;
					$symbol++;
					continue;
				}
			}
			

			$img = imagecreatetruecolor(static::$width, static::$height);
			imagealphablending($img, true);
			$white = imagecolorallocate($img, 255, 255, 255);
			$black = imagecolorallocate($img, 0, 0, 0);

			imagefilledrectangle($img, 0, 0, static::$width - 1, static::$height - 1, $white);

			// draw text
			$x=1;
			for($i=0; $i < static::$length; $i++)
			{
				$m = $font_metrics[static::$keystring[$i]];

				$y = mt_rand(-static::$fluctuation_amplitude, static::$fluctuation_amplitude) + (static::$height - $fontfile_height) / 2 + 2;

				if(static::$no_spaces)
				{
					$shift = 0;
					if($i > 0)
					{
						$shift = 10000;
						for($sy = 7; $sy < $fontfile_height - 20; $sy+=1)
						{
							for($sx = $m['start'] - 1; $sx < $m['end']; $sx+=1)
							{
				        		$rgb = imagecolorat($font, $sx, $sy);
				        		$opacity = $rgb >> 24;
								if($opacity < 127)
								{
									$left = $sx - $m['start'] + $x;
									$py = $sy + $y;
									if($py>static::$height) break;
									for($px = min($left, static::$width - 1); $px > $left - 12 && $px >=0; $px-=1)
									{
						        		$color = imagecolorat($img, $px, $py) & 0xff;
										if($color + $opacity < 190)
										{
											if($shift > $left - $px)
											{
												$shift = $left - $px;
											}
											break;
										}
									}
									break;
								}
							}
						}
						if($shift == 10000)
						{
							$shift=mt_rand(4, 6);
						}

					}
				}
				else
				{
					$shift = 1;
				}
				imagecopy($img, $font, $x-$shift, $y, $m['start'], 1, $m['end'] - $m['start'], $fontfile_height);
				$x+=$m['end']-$m['start']-$shift;
			}
		} while($x >= static::$width - 10); // while not fit in canvas

		$center = $x / 2;

		// credits. To remove, see configuration file
		$img2 = imagecreatetruecolor(static::$width, static::$height + (static::$show_credits ? 12 : 0));
		$foreground=imagecolorallocate($img2, static::$foreground_color[0], static::$foreground_color[1], static::$foreground_color[2]);
		$background=imagecolorallocate($img2, static::$background_color[0], static::$background_color[1], static::$background_color[2]);
		imagefilledrectangle($img2, 0, 0, static::$width - 1, static::$height - 1, $background);		
		imagefilledrectangle($img2, 0, static::$height, static::$width - 1, static::$height + 12, $foreground);
		$credits = $_SERVER['HTTP_HOST'];
		imagestring($img2, 2, static::$width / 2 - imagefontwidth(2) * strlen($credits) / 2, static::$height - 2, $credits, $background);

		// periods
		$rand1 = mt_rand(750000, 1200000) / 10000000;
		$rand2 = mt_rand(750000, 1200000) / 10000000;
		$rand3 = mt_rand(750000, 1200000) / 10000000;
		$rand4 = mt_rand(750000, 1200000) / 10000000;
		// phases
		$rand5 = mt_rand(0, 31415926) / 10000000;
		$rand6 = mt_rand(0, 31415926) / 10000000;
		$rand7 = mt_rand(0, 31415926) / 10000000;
		$rand8 = mt_rand(0, 31415926) / 10000000;
		// amplitudes
		$rand9 = mt_rand(330, 420) / 110;
		$rand10 = mt_rand(330, 450) / 110;

		//wave distortion

		for($x = 0; $x < static::$width; $x++)
		{
			for($y = 0; $y < static::$height; $y++)
			{
				$sx=$x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9 - static::$width / 2 + $center + 1;
				$sy=$y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;

				if($sx < 0 || $sy < 0 || $sx >= static::$width - 1 || $sy >= static::$height - 1)
				{
					continue;
				}
				else
				{
					$color = imagecolorat($img, $sx, $sy) & 0xFF;
					$color_x = imagecolorat($img, $sx+1, $sy) & 0xFF;
					$color_y = imagecolorat($img, $sx, $sy+1) & 0xFF;
					$color_xy = imagecolorat($img, $sx+1, $sy+1) & 0xFF;
				}

				if($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255)
				{
					continue;
				}
				else if($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0)
				{
					$newred = static::$foreground_color[0];
					$newgreen = static::$foreground_color[1];
					$newblue = static::$foreground_color[2];
				}
				else
				{
					$frsx = $sx - floor($sx);
					$frsy = $sy - floor($sy);
					$frsx1 = 1 - $frsx;
					$frsy1 = 1 - $frsy;

					$newcolor = (
						$color * $frsx1 * $frsy1 +
						$color_x * $frsx * $frsy1 +
						$color_y * $frsx1 * $frsy +
						$color_xy * $frsx * $frsy
					);

					if($newcolor > 255) $newcolor = 255;
					$newcolor = $newcolor / 255;
					$newcolor0 = 1 - $newcolor;

					$newred = $newcolor0 * static::$foreground_color[0] + $newcolor * static::$background_color[0];
					$newgreen = $newcolor0 * static::$foreground_color[1] + $newcolor * static::$background_color[1];
					$newblue=$newcolor0 * static::$foreground_color[2] + $newcolor * static::$background_color[2];
				}

				imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));
			}
		}
		
		
		// Set no cache
		//ToDo: test this 2 headers
		///header("Cache-Control: no-store, no-cache, must-revalidate"); 
		///header("Expires: " . date("r"));
		\Output::set_header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
		\Output::set_header('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
		\Output::set_header('Pragma', 'no-cache');

		if(function_exists('imagejpeg'))
		{
			\Output::set_header('Content-Type','image/jpeg');
			imagejpeg($img2, null, static::$jpeg_quality);
		}
		else if(function_exists('imagegif'))
		{
			\Output::set_header('Content-Type','image/gif');
			imagegif($img2);
		}
		else if(function_exists('imagepng'))
		{
			\Output::set_header('Content-Type','image/x-png');
			imagepng($img2);
		}
	}
}