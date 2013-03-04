# CakePHP-HTML-Tidy-Plugin

HTML Tidy Plugin validate and generate tided HTML output

# Install and Setup
* First clone the repository into your `app/Plugin/Tidy` directory

		git clone git@github.com:cikorka/CakePHP-HTML-Tidy-Plugin.git app/Plugin/Tidy

		cd Plugin/Tidy
		git submodule init
		git submodule update

		cd Vendor/tidy-html5
		
		make -C build/gmake/
		sudo make install -C build/gmake/
		
* Load the plugin in your `app/Config/bootstrap.php` file:

		//app/Config/bootstrap.php
		CakePlugin::load('Tidy');

* Add component in `app/Controller/AppController.php` file:

		//app/Controller/AppController.php
		public $components = array('Tidy.Tidy');
		
		// if you want minify HTML code
		public $components = array('Tidy.Tidy' => array('minify' => true));
		
		// if you want minify HTML code and disable CSS and JS minification
		public $components = array('Tidy.Tidy' => array('minify' => true, 'js' => false, 'css' => false));

* Add behavior in `app/Model/YourModel.php` file:

		//If you want use Tidy and optional Minify fileds content before save into db
		public $actsAs = array('Tidy.Tidy' => array('field_name' => array('minify' => true)));