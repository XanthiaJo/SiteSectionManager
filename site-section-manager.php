<?php
/**
 * Plugin Name: Simple Section Manager
 * Description: Organize a single WordPress install into section-scoped pages, posts, categories, and tags without multisite.
 * Version: 0.0.0-dev
 * Author: XanthiaJo & Codex
 * Text Domain: site-section-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SSM_VERSION', '0.0.0-dev' );
define( 'SSM_FILE', __FILE__ );
define( 'SSM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SSM_URL', plugin_dir_url( __FILE__ ) );

require_once SSM_PATH . 'includes/class-ssm-plugin.php';
require_once SSM_PATH . 'includes/class-ssm-admin.php';
require_once SSM_PATH . 'includes/class-ssm-content.php';
require_once SSM_PATH . 'includes/class-ssm-content-admin.php';
require_once SSM_PATH . 'includes/class-ssm-section-admin-page.php';

register_activation_hook( __FILE__, array( 'SSM_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SSM_Plugin', 'deactivate' ) );

SSM_Plugin::instance();
