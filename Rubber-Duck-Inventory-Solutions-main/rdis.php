<?php

/**
 * Plugin Name: Rubber Duck Inventory Solutions
 * Plugin URI:  https://rubberducktech.com/products/rubber-duck-inventory-solutions
 * Description: Comprehensive inventory management system by Rubber Duck Tech Solutions.
 * Version:     1.0
 * Author:      Long Nguyen
 * Author URI:  https://rubberducktech.com
 * Text Domain: rubber-duck-inventory-solutions
 */

//include files
include_once plugin_dir_path(__FILE__) . 'rdis-db.php';
include_once plugin_dir_path(__FILE__) . 'rdis-crud.php';
include_once plugin_dir_path(__FILE__) . 'rdis-ocr-handler.php';
include_once plugin_dir_path(__FILE__) . 'rdis-vin-decode.php';
include_once plugin_dir_path(__FILE__) . 'rdis-inventory-handler.php';
include_once plugin_dir_path(__FILE__) . 'rdis-utility-functions.php';

include_once plugin_dir_path(__FILE__) . 'rdis-fields.php';


register_activation_hook(__FILE__, 'rd_db_activates');

function rd_db_activates()
{
    rd_inventory_create_tables();
    rd_inventory_create_sales_table();
    rd_inventory_upgrade_tables();
    // Other activation code...
}

//enquene styles and scripts
function rd_inventory_admin_styles()
{
    wp_enqueue_style('rd_inventory_admin_css', plugin_dir_url(__FILE__) . 'assets/css/rdis-style.css');
    wp_enqueue_script('rdis-admin-script', plugins_url('/assets/js/rdis-script.js', __FILE__), array('jquery'), '1.0', true);
    // Enqueue jQuery UI
    wp_enqueue_script('jquery-ui-sortable');
    if (!did_action('wp_enqueue_media')) {
        wp_enqueue_media();
    }
}

function rdis_enqueue_ocr_scripts()
{
    wp_enqueue_script('rdis-add-script', plugins_url('/assets/js/rdis-add-inventory.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('rdis-sell-script', plugins_url('/assets/js/rdis-sell-inventory.js', __FILE__), array('jquery'), '1.0', true);
    wp_enqueue_script('rdis-ocr-script', plugins_url('/assets/js/rdis-ocr.js', __FILE__), array('jquery', 'rdis-add-script', 'rdis-sell-script'), '1.0', true);

    // Create a nonce for AJAX request verification
    $nonce = wp_create_nonce('rdis_ocr_nonce');

    // Localize script to include AJAX URL
    wp_localize_script('rdis-ocr-script', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => $nonce
    ));

    // Define Bootstrap URLs
    $bootstrapCssUrl = 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css';
    $bootstrapJsUrl = 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js';
    $jqueryUrl = 'https://code.jquery.com/jquery-3.5.1.slim.min.js';
    $popperUrl = 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js';

    // Check if Bootstrap CSS is already enqueued
    if (!wp_style_is('bootstrap-css', 'enqueued')) {
        wp_enqueue_style('bootstrap-css', $bootstrapCssUrl, array(), null);
    }

    // Enqueue jQuery (if not already enqueued) as it's a dependency for Bootstrap's JavaScript
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery', $jqueryUrl, array(), null, true);
    }

    // Enqueue Popper.js (required for Bootstrap's JavaScript)
    wp_enqueue_script('popper', $popperUrl, array('jquery'), null, true);

    // Enqueue Bootstrap JavaScript
    wp_enqueue_script('bootstrap-js', $bootstrapJsUrl, array('jquery', 'popper'), null, true);
}

add_action('admin_enqueue_scripts', 'rdis_enqueue_ocr_scripts');


function rdis_enqueue_admin_scripts($hook)
{
    // Check if on the specific settings page
    if ('rubber-duck-tech-solutions_page_rdts_settings' === $hook) {
        // Enqueue the script for the settings page
        wp_enqueue_script('rdis-settings-script', plugins_url('/assets/js/rdis-settings.js', __FILE__), array('jquery'), '1.0', true);
    }

    // Only enqueue the script for the edit screens of the 'rd_inventory' post type
    if ('post.php' !== $hook && 'post-new.php' !== $hook) {
        return;
    }

    // Define the main menu slug for your plugin
    $main_menu_slug = 'rdts_main_menu';

    // Check if on a submenu under the main plugin menu
    if (strpos($hook, $main_menu_slug) !== false) {
        // Enqueue the script for all submenus of your plugin
        wp_enqueue_script('rdis-admin-script', plugins_url('/assets/js/rdis-script.js', __FILE__), array('jquery'), '1.0', true);
    }
}

function rdis_enqueue_cropper_scripts()
{
    wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css');
    wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', array('jquery'), '1.5.12', true);
}

add_action('admin_enqueue_scripts', 'rdis_enqueue_admin_scripts');
add_action('admin_enqueue_scripts', 'rd_inventory_admin_styles');
add_action('admin_enqueue_scripts', 'rdis_enqueue_cropper_scripts');


add_action('init', 'rd_inventory_register_cpt');

function rd_inventory_register_cpt()
{
    $labels = array(
        'name'               => _x('Inventory Items', 'post type general name', 'rubber-duck-inventory-solutions'),
        'singular_name'      => _x('Inventory', 'post type singular name', 'rubber-duck-inventory-solutions'),
        'menu_name'          => _x('Rubber Duck Inventory Solutions', 'admin menu', 'rubber-duck-inventory-solutions'),
        'name_admin_bar'     => _x('Inventory', 'add new on admin bar', 'rubber-duck-inventory-solutions'),
        'add_new'            => _x('Add New', 'inventory', 'rubber-duck-inventory-solutions'),
        'add_new_item'       => __('Add New Inventory Item', 'rubber-duck-inventory-solutions'),
        'new_item'           => __('New Inventory Item', 'rubber-duck-inventory-solutions'),
        'edit_item'          => __('Edit Inventory Item', 'rubber-duck-inventory-solutions'),
        'view_item'          => __('View Inventory Item', 'rubber-duck-inventory-solutions'),
        'all_items'          => __('All Inventory Items', 'rubber-duck-inventory-solutions'),
        'search_items'       => __('Search Inventory Items', 'rubber-duck-inventory-solutions'),
        'parent_item_colon'  => __('Parent Inventory Item:', 'rubber-duck-inventory-solutions'),
        'not_found'          => __('No inventories found.', 'rubber-duck-inventory-solutions'),
        'not_found_in_trash' => __('No inventories found in Trash.', 'rubber-duck-inventory-solutions')
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'inventory'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'menu_icon'          => 'dashicons-cart',
        'supports'           => array('title'),
        'taxonomies'         => array('category'),
        'show_in_rest'       => false // This enables the Gutenberg editor for the CPT
    );

    register_post_type('rd_inventory', $args);
}

// Register RDIS settings in RDTS
add_action('admin_init', function () {
    if (function_exists('rdts_register_addon_settings')) {
        rdts_register_addon_settings('rdis', 'rdis_settings_page_content');
    }
});

// Define the callback function for RDIS settings
function rdis_settings_page_content()
{
    // Include the RDIS settings content here
    include_once plugin_dir_path(__FILE__) . 'partials/rdis-settings.php';
}

// New Function to Handle Form Submission and Redirect
function rdis_handle_form_submission()
{
    // Check if the form was submitted and if the current user has the required capability
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['rd_inventory_feature_defaults']) && current_user_can('manage_options')) {
        // Verify nonce
        if (isset($_POST['rdts_settings_nonce']) && wp_verify_nonce($_POST['rdts_settings_nonce'], 'rdts_settings_action')) {
            // Save the default feature names
            $defaults = array_map('sanitize_text_field', $_POST['rd_inventory_feature_defaults']);
            update_option('rd_inventory_feature_defaults', $defaults);

            // Add a settings updated message
            add_settings_error('rd_inventory_settings', 'rd_inventory_settings_updated', 'Defaults saved.', 'updated');

            // Redirect
            $redirect_url = add_query_arg(['page' => 'rdts_settings', 'settings-updated' => 'true'], admin_url('admin.php'));
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            // Handle the case where the nonce was not valid
            add_settings_error('rd_inventory_settings', 'rd_inventory_settings_nonce_failed', 'Error: Nonce verification failed.', 'error');
        }
    }
}

// Hook to handle form submission early in the WordPress loading process
add_action('admin_init', 'rdis_handle_form_submission');
