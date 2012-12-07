<?php
/**************************************************************************
Plugin Name: Live Theme Preview
Plugin URI: https://github.com/mgmartel/WP-Live-Theme-Preview
Description: Live Theme Preview allows users to preview themes on their website before customizing or activating them.
Version: 0.9
Author: Mike_Cowobo
Author URI: http://trenvo.com

**************************************************************************/

// Exit if accessed directly
if (!defined('ABSPATH'))
    exit(-1);

/**
 * Version number
 *
 * @since 0.1
 */
define ( 'WP_LTP_VERSION', '0.9' );

/**
 * PATHs and URLs
 *
 * @since 0.1
 */
define( 'WP_LTP_DIR', plugin_dir_path(__FILE__) );
define( 'WP_LTP_URL', plugin_dir_url(__FILE__) );
define( 'WP_LTP_INC_URL', WP_LTP_URL . '_inc/' );

if (!class_exists('WP_LiveThemePreview')) :

    class WP_LiveThemePreview    {

        /**
         * Creates an instance of the WP_LiveThemePreview class
         *
         * @return WP_LiveThemePreview object
         * @since 0.1
         * @static
         */
        public static function &init() {
            static $instance = false;

            if (!$instance) {
                //load_plugin_textdomain('wp-ltp', false, WP_LTP_DIR . '/languages/');
                $instance = new WP_LiveThemePreview;
            }

            return $instance;
        }

        /**
         * Constructor
         *
         * @since 0.1
         */
        public function __construct() {
            $this->actions_and_filters();

            if ( isset($_REQUEST['live']) && $_REQUEST['live'] == true && $GLOBALS['pagenow'] == 'themes.php' )
                add_action ('admin_init', array ( &$this, 'live' ) );

        }

            /**
             * PHP4
             *
             * @since 0.1
             */
            public function WP_LiveThemePreview() {
                $this->__construct();
            }

        /**
         * Show the live theme preview!
         *
         * @since 0.1
         */
        public function live() {
            $this->maybe_activate();
            $this->display();
            exit;
        }

        /**
         * Are we activating a theme?
         *
         * @since 0.1
         */
        protected function maybe_activate() {
            if( $_GET['action'] && $_GET['action'] == 'activate' && check_admin_referer( 'live-theme-preview_' . $_GET['stylesheet'] ) ) {
                switch_theme( $_GET['stylesheet'] );
            }
        }

        /**
         * Load the various actions and filters
         *
         * @since 0.1
         * @todo Make the admin menu modification optional
         */
        private function actions_and_filters() {
            // Make sure theme options of the previewed theme are loaded when available
            if ( $_REQUEST['preview'] && true == $_REQUEST['preview'] )
                add_filter( 'pre_option_theme_mods_' . get_option( 'stylesheet' ), array ( &$this, 'return_theme_options' ) );

            // Set Live Preview as the default theme selector in the WP admin menus
            add_action('admin_menu', array ( 'WP_LiveThemePreview', 'set_as_theme_chooser' ) );
            // and as the default return for the Theme Customizer if the theme is not active
            add_action('customize_controls_init', array ( &$this, 'modify_redirect' ) );
        }

        /**
         * Set the js vars and print the scripts
         *
         * Uses wp_ltp_js_vars filter.
         *
         * @global str $active_theme
         * @since 0.1
         */
        public function print_scripts() {
            global $active_theme;

            $theme = ( isset ( $_GET['theme'] ) && ! empty ( $_GET['theme'] ) ) ? $_GET['theme'] : $active_theme;
            $tmp = wp_get_theme( $theme );
            $template = $tmp->template;
            unset ( $tmp );

            $args = apply_filters ( 'wp_ltp_js_vars', array (
                "blog_url"                 => get_bloginfo('url'),
                "previewed_theme"          => $theme,
                "previewed_theme_template" => $template,
            ) );

            wp_localize_script( "live-theme-preview", 'wp_ltp', $args);

            wp_print_scripts( array ('live-theme-preview', 'jquery') );
        }

        /**
         * Load the template
         *
         * @since 0.1
         */
        protected function display() {
            require( WP_LTP_DIR . '/live-theme-preview-template.php' );
        }

        /**
         * Sets theme options not to be from the options table, but from the requested stylesheet
         *
         * @return array
         */
        public function return_theme_options( $i ) {
            if ( $_GET['stylesheet'] != get_option( 'stylesheet' ) )
                return get_option ( 'theme_mods_' . $_GET['stylesheet'] );
            else return false;
        }

        /**
         * Sets LTP as the default option for themes in wp-admin
         *
         * @global array $submenu
         */
        public static function set_as_theme_chooser() {
            global $submenu;

            $submenu['themes.php'][5][2] .= "?live=1";
            add_submenu_page('themes.php','', 'Manage Themes', 'switch_themes', 'themes.php');
        }

        /**
         * Return to LTP after visiting the customizer when the theme is not activated (and make sure we go back to that theme). Leave it alone when the theme is active.
         *
         * @global str $return
         * @global WP_Customize_Manager $wp_customize
         */
        public function modify_redirect() {
            global $return, $wp_customize;
            if ( ! $wp_customize->is_theme_active() )
                $return = admin_url("themes.php?live=1&theme={$wp_customize->get_stylesheet()}");
        }
    }
    //WP_LiveThemePreview::init();
    add_action ( 'init', array ( 'WP_LiveThemePreview', 'init' ) );
endif;