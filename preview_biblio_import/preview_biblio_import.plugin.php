<?php
/**
 * Plugin Name: preview_biblio_import
 * Plugin URI: https://github.com/drajathasan/preview_biblio_import
 * Description: Plugin untuk preview import biblio
 * Version: 1.0.0
 * Author: Drajat Hasan
 * Author URI: https://github.com/drajathasan/
 */

// get plugin instance
$plugin = \SLiMS\Plugins::getInstance();

// registering menus
$plugin->registerMenu('bibliography', 'Preview Import', __DIR__ . '/index.php');
