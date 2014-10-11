<?php
/**
 * Plugin Name: Mail Image Embedder
 * Plugin URI: https://github.com/Southen/wordpress-mail-image-embedder
 * Description: Plugin to automagically transmute linked images in HTML e-mails sent by Wordpress into embedded images.
 * Version: 1.0
 * Author: Sebastian Southen
 * Author URI: https://github.com/Southen/
 * License: GPLv3 or later
 * Text Domain: mail-image-embedder
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
if (!function_exists('add_action')) exit;

class MailImageEmbedder {
        const VERSION = 1.0;
	public function __construct() {
		register_activation_hook(__FILE__, array(&$this, 'activate'));
		register_deactivation_hook(__FILE__, array(&$this, 'deactivate'));
		// https://core.trac.wordpress.org/browser/tags/4.0/src/wp-includes/pluggable.php#L492
		add_action('phpmailer_init', array(&$this, 'embedimages'));
	}
	public function activate() {
		add_option('MailImageEmbedder', number_format($this->VERSION, 1, '.', ''));
	}
	public function deactivate() { }
	public function embedimages(&$phpmailer) {
		if ($phpmailer->ContentType != 'text/html') {
			return;
		}
		if (empty($phpmailer->Body)) {
			return;
		}
		if (empty($phpmailer->AltBody)) {
			$phpmailer->AltBody = str_replace(html_entity_decode("&nbsp;"), ' ', html_entity_decode(strip_tags($phpmailer->Body), ENT_HTML5|ENT_QUOTES, $phpmailer->CharSet));
		}
		$matches = array();
		$images  = array();
		while (preg_match("/(<img[^>]+\s+src=[\"'])(http[^\"']+)([\"']\s+[^>]+>)/", $phpmailer->Body, $matches)) {
			$target = addcslashes($matches[0], '/');
			if (empty($images[$matches[2]])) {
				$images[$matches[2]] = strtr(basename($matches[2]), '.', '-');
				//var_dump($matches, $target, $images);
				$phpmailer->addStringEmbeddedImage(file_get_contents($matches[2]), $images[$matches[2]], basename($matches[2]));
			}
			$phpmailer->Body = preg_replace("/$target/", $matches[1] . 'cid:' . $images[$matches[2]] . $matches[3], $phpmailer->Body, 1);
		}
		//return $phpmailer;
	}
}

$plugin = new MailImageEmbedder();
