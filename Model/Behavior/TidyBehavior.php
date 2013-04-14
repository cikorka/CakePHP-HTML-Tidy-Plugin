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

App::uses('LibTidy', 'Tidy.Lib');

/**
 * Tidy behavior class.
 *
 * Tidy and Minify fields passed to behaviors by using the behavior name as index. Eg:
 *
 * `public $actsAs = array('Tidy.Tidy' => array('field' => array('minify' => true)));`
 *
 * or
 *
 * `public $actsAs = array('Tidy.Tidy' => array('field'));`
 *
 * @package Cake.Model
 * @see Model::$actsAs
 * @see BehaviorCollection::load()
 */

class TidyBehavior extends ModelBehavior {

/**
 * Setup this behavior with the specified configuration settings.
 *
 * @param Model $model Model using this behavior
 * @param array $config Configuration settings for $model
 * @return void
 */
	public function setup(Model $model, $config = array()) {
		$this->settings[$model->alias] = Hash::normalize($config);
	}

/**
 * beforeSave is called before a model is saved. Returning false from a beforeSave callback
 * will abort the save operation.
 *
 * @param Model $model Model using this behavior
 * @return mixed False if the operation should abort. Any other result will continue.
 */
	public function beforeSave(Model $model) {
		$settings = $this->settings[$model->alias];
		foreach (array_keys($settings) as $field) {
			$options = $this->settings[$model->alias][$field] + array('show-body-only' => true);
			$model->data[$model->alias][$field] = LibTidy::execute($model->data[$model->alias][$field], $options);
		}
		return true;
	}

}
