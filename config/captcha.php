<?php
return array(
	// Don't change without changing font files!
	'alphabet'				=> '0123456789abcdefghijklmnopqrstuvwxyz',

	// Alphabet without similar symbols (o=0, 1=l, i=j, t=f)
	'allowed_symbols'		=> '23456789abcdeghkmnpqsuvxyz',

	// Folder with fonts
	'fonts_dir'				=> PKGPATH.'captcha'.DS.'fonts'.DS,

	// Captcha string length
	'length'				=> mt_rand(5,6),

	// Captcha image size (you don't need to change it, whis parameters is optimal)
	'width'					=> 120,
	'height'				=> 60,

	// Symbol's vertical fluctuation amplitude divided by 2
	'fluctuation_amplitude'	=> 5,
	
	// Increase safety by prevention of spaces between symbols
	'no_spaces'				=> false,

	// Show credits, credits adds 12 pixels to image height
	'show_credits'			=> true,
	
	// Captcha image colors (RGB, 0-255)
	'foreground_color'		=> array(mt_rand(0,100), mt_rand(0,100), mt_rand(0,100)),
	'background_color'		=> array(mt_rand(200,255), mt_rand(200,255), mt_rand(200,255)),

	// JPEG quality of Captcha image (bigger is better quality, but larger file size)
	'jpeg_quality'			=> 90,

);