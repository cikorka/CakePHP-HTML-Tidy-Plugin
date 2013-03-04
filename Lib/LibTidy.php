<?php

class LibTidy {

/**
 * Constructor
 *
 */
	public function __construct() {
	}


	public static function execute($content, $options = array()) {
		$tidy = $options += array('minify' => false);
		unset($tidy['minify'], $tidy['js'], $tidy['css']);
		$content = LibTidy::tidy($content, $tidy);
		if ($options['minify'] === true) {
			$content = LibTidy::minify($content, $options);
		}
		return $content;
	}

/**
 * Generate tided HTML code
 *
 * @param string $content
 * @return string modified content if avaible, otherwise origin content
 */
	public static function tidy($content, $options = array()) {
		$options += array('tidy' => dirname(__DIR__) . DS . 'Vendor' . DS . 'tidy-html5' . DS . 'bin' . DS . 'tidy');

		if (!is_executable($options['tidy'])) {
			CakeLog::error('Tidy not executable.');
			return $content;
		}

		$configFile = self::_config($options);

		$origFile = CACHE . md5($content);
		$tidedFile = CACHE . 'tided_' . md5($content);

		if (is_writeable(dirname($origFile)) && $configFile) {
			if (file_put_contents($origFile, $content)) {
				exec($options['tidy'] . ' -config ' . $configFile . ' ' . $origFile . ' > ' . $tidedFile);
				if (file_exists($tidedFile)) {
					$tidedContent = file_get_contents($tidedFile);
					unlink($tidedFile);
				} else {
					$tidedContent = null;
				}
				unlink($origFile);
			}
		}
		return (empty($tidedContent)) ? $content : $tidedContent;
		
	}

/**
 * Generate minified HTML code
 *
 * @param string $content
 * @param array $options
 *
 * - `html` - boolean - Minify HTML code
 * - `js`   - boolean - Minify JS
 * - `css`  - boolean - Minify CSS
 *
 * @return string modified content if avaible, otherwise origin content
 */
	public static function minify($content, $options = array()) {
		$options += array('html' => true, 'js' => true, 'css' => true);
		
		$minifyOptions = array();
		if ($options['html'] === true) {
			App::uses('Minify', 'Tidy.Vendor/Minify');
			$minifyOptions['xhtml'] = false;
			if ($options['js'] === true) {
				App::uses('JSMin', 'Tidy.Vendor/Minify');
				$minifyOptions['jsMinifier'] = array("JSMin", "minify");
			}
			if ($options['css'] === true) {
				App::uses('Minify_CSS', 'Tidy.Vendor/Minify');
				$minifyOptions['cssMinifier'] = array("Minify_CSS", "minify");
			}
			$Minify = new Minify();
			$minified = $Minify->process($content, $minifyOptions);

			if (!empty($minified)) {
				return $minified;
			}
		}
		return $content;
	}

/**
 * Create tidy configuration file for tidy
 *
 *
 * @link http://tidy.sourceforge.net/docs/quickref.html
 * @param array $options
 * @return string filename with path
 */
	protected static function _config($options = array()) {
		$options += array(
			'indent' => 'auto',
			'indent-spaces' => 4,
			'wrap' => 7200,
			'drop-empty-elements' => false,
			'tidy-mark' => false,
			'markup' => true,
			'output-xml' => false,
			'input-xml' => false,
			'show-warnings' => true,
			'numeric-entities' => true,
			'quote-marks' => true,
			'quote-nbsp' => true,
			'quote-ampersand' => false,
			'break-before-br' => false,
			'uppercase-tags' => false,
			'uppercase-attributes' => false,
			'char-encoding' => Configure::read('App.encoding'), //'utf8',
			'join-styles' => true
		);

		unset($options['tidy']);

		$fileName = CACHE . 'views' . DS . get_class() . md5(serialize($options)) . '.conf';

		if (file_exists($fileName)) {
			return $fileName;
		}

		$content = null;
		foreach ($options as $config => $value) {
			if ($value === true) {
				$value = 'yes';
			} else if ($value === false) {
				$value = 'no';
			}
			$content .= "$config:$value\n";
		}

		if (is_writeable(dirname($fileName))) {
			if (file_put_contents($fileName, $content)) {
				return $fileName;
			}
		}
		return false;
	}

}