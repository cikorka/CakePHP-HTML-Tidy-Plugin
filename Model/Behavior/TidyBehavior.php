<?php

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

App::uses('LibTidy', 'Tidy.Lib');

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
