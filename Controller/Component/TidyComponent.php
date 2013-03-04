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

App::uses('Component', 'Controller');
App::uses('LibTidy', 'Tidy.Lib');

class TidyComponent extends Component {

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
		$enabled = (
			!$controller->request->isAjax() &&
			!isset($controller->request->params['ext'])
		);

		if (!$enabled) {
			$controller->Components->disable('Tidy');
		}
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

					$comment = null;
					if (isset($this->settings['minify']) && $this->settings['minify'] === true) {
						if (preg_match('/^<!--cachetime:(\\d+)-->/', $content, $match)) {
							$comment = '<!--cachetime:' . $match['1'] . '-->';
						}
					}

				/**
				 * Replace PHP blocks with keys and tidy and minify withot PHP blocks
				 * After Tidy or Minify, replace keys php blocks
				 */
					if (preg_match_all('/(?<=<\?php)([\\s\\S]*?)(?=\?>)/i', $content, $results, PREG_PATTERN_ORDER)) {
						$_replace = array();
						foreach ($results[0] as $replace) {
							$_replace[md5($replace)] = $replace;
							$content = str_replace($replace, md5($replace), $content);
						}

						$content = $this->execute($content);

						foreach ($_replace as $_name => $_content) {
							$content = str_replace($_name, $_content, $content);
						}
					} else {
						$content = $this->execute($content);
					}

					file_put_contents($cache, $comment . $content);

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
		$options += $this->settings;
		return LibTidy::execute($content, $options);
	}

}