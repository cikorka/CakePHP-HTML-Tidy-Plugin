<?php

/**
 *
 * PHP 5
 *
 * CakePHP(™) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Petr Jeřábek : CakePHP HTML Tidy Plugin
 * Copyright 2013, Petr Jeřábek (http://github.com/cikorka)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright	Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @copyright	Copyright 2013, Petr Jeřábek  (http://github.com/cikorka)
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class TidyComponent extends Component {

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$tidy = dirname(dirname(__DIR__)) . DS . 'Vendor' . DS . 'tidy-html5' . DS . 'bin' . DS . 'tidy';
		$settings += array('tidy' => $tidy);
		parent::__construct($collection, $settings);
	}

/**
 * Called before the Controller::beforeFilter().
 *
 * Check if we can run Tidy, if not, disable controller component
 *
 * @param Controller $controller Controller with components to initialize
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::initialize
 */
	public function initialize(Controller $controller) {
		if (!is_executable($this->settings['tidy'])) {
			CakeLog::error('Tidy not executable.');
		}

		$enabled = (
			is_executable($this->settings['tidy']) &&
			!$controller->request->isAjax() &&
			!isset($controller->request->params['ext'])
		);

		if (!$enabled) {
			$controller->Components->disable('Tidy');
			CakeLog::info('Tidy disabled.');
		}
	}

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::startup
 */
	public function startup(Controller $controller) {
	}

/**
 * Called before the Controller::beforeRender(), and before
 * the view class is loaded, and before Controller::render()
 *
 * @param Controller $controller Controller with components to beforeRender
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::beforeRender
 */
	public function beforeRender(Controller $controller) {
	}

/**
 * Called after Controller::render() and before the output is printed to the browser.
 *
 * @param Controller $controller Controller with components to shutdown
 * @return void
 * @link http://book.cakephp.org/2.0/en/controllers/components.html#Component::shutdown0
 */
	public function shutdown(Controller $controller) {
		$content = $controller->response->body();
		if (is_string($content)) {
			$content = $this->execute($content);
			$controller->response->body($content);

		/**
		 * If is Cache enabled we tidy cached file
		 */
			if (
				in_array('Cache', $controller->helpers) ||
				array_key_exists('Cache', $controller->helpers)
			) {
				$path = $controller->request->here();
				if ($path === '/') {
					$path = 'home';
				}
				$prefix = Configure::read('Cache.viewPrefix');
				if ($prefix) {
					$path = $prefix . '_' . $path;
				}
				$cache = strtolower(Inflector::slug($path));

				if (empty($cache)) {
					return;
				}
				$cache = CACHE . 'views' . DS . $cache . '.php';

				if (file_exists($cache)) {
					$content = file_get_contents($cache);
					if ($content) {
						$content = $this->execute($content);
						file_put_contents($cache, $content);
					}
				}
			}
		}
	}

/**
 * Generate tided HTML code
 *
 * @param string $content
 * @return string modified content if avaible, otherwise origin content
 */
	public function execute($content, $options = array()) {
		$configFile = $this->configFile($options);
		$origFile = CACHE . md5($content);
		$tidedFile = CACHE . 'tided_' . md5($content);

		if (is_writeable(dirname($origFile)) && $configFile) {
			if (file_put_contents($origFile, $content)) {
				exec($this->settings['tidy'] . ' -config ' . $configFile . ' ' . $origFile . ' > ' . $tidedFile);
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
 * Create tidy configuration file for tidy
 *
 *
 * @link http://tidy.sourceforge.net/docs/quickref.html
 * @param array $options
 * @return string filename with path
 */
	public function configFile($options = array()) {
		$options += $this->settings;
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