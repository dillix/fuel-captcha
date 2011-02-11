<?php
/**
 * Fuel Captcha
 *
 * A completely fresh Captcha for Fuel.
 *
 * @package		Fuel Captcha
 * @version		alpha
 * @author		Mikhail Khasaya
 * @license		MIT License
 * @copyright	2010 - 2011 Mikhail Khasaya
 */

Fuel\Core\Autoloader::add_core_namespace('Captcha');

Fuel\Core\Autoloader::add_classes(array(
	'Captcha\\Captcha'					=> __DIR__.DS.'classes'.DS.'captcha.php',
));

/* End of file bootstrap.php */