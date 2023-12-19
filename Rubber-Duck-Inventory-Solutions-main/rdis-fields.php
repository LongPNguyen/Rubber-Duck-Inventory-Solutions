<?php

add_action('add_meta_boxes', 'rd_inventory_add_meta_boxes');
function rd_inventory_add_meta_boxes()
{
    add_meta_box(
        'rd_inventory_details',
        'Inventory Details',
        'rd_inventory_meta_box_callback',
        'rd_inventory',
        'normal',
        'default'
    );
}

// Function to register the settings page or section
function register_feature_names_settings()
{
    register_setting('inventory_settings', 'standard_feature_names');
    // Add other settings fields and sections as necessary
}

add_action('admin_init', 'register_feature_names_settings');


function rd_inventory_meta_box_callback($post)
{

    // Nonce field for security
    wp_nonce_field('rd_inventory_save_meta_box_data', 'rd_inventory_meta_box_nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    // Fetching the inventory item ID from the custom table
    $inventory_item_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE post_id = %d", $post->ID));
    // Get the count of all entries in the table
    $inventory_item_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    // Fetching the inventory item ID and additional_info from the custom table
    $inventory_data = get_inventory_data($post->ID, $wpdb, $table_name);

    // Get current post meta data
    $id = $post->ID; // Default to empty string if not set
    // Fetch existing values
    $status = $inventory_data['status'] ?? "Pending";
    $gallery_data = get_post_meta($post->ID, 'rd_inventory_gallery', true) ?? array();
    $buy_price = $inventory_data !== null ? $inventory_data['buy_price'] : get_post_meta($post->ID, 'rd_inventory_buy_price', true) ?? '';
    $labor = $inventory_data['labor_cost'] ?? "";
    $parts = $inventory_data['parts_cost'] ?? "";
    $interest = $inventory_data['interest_rate'] ?? "";
    $posted_price = $inventory_data !== null ? $inventory_data['posted_price'] : get_post_meta($post->ID, 'rd_inventory_posted_price', true) ?? '';
    $quantity = $inventory_data !== null ? $inventory_data['quantity'] : "";
    $user = wp_get_current_user();


    $featureMappings = [
        "Type" => "type",
        "Receipt Date" => "receipt_date",
        "Age" => "age",
        "VIN" => "vin",
        "Year" => "year",
        "Make" => "make",
        "Model" => "model",
        "Body" => "body",
        "Series" => "series",
        "Odometer" => "odometer",
        "Color" => "color",
        "Interior" => "interior",
        "Key #" => "key_number",
        "Engine" => "engine",
        "Transmission" => "transmission",
        "Drive" => "drive",
        "Fuel" => "fuel"
    ];
    $features = $inventory_data !== null ? [
        $inventory_data['type'],
        $inventory_data['receipt_date'],
        $inventory_data['age'],
        $inventory_data['vin'],
        $inventory_data['year'],
        $inventory_data['make'],
        $inventory_data['model'],
        $inventory_data['body'],
        $inventory_data['series'],
        $inventory_data['odometer'],
        $inventory_data['color'],
        $inventory_data['interior'],
        $inventory_data['key_number'],
        $inventory_data['engine'],
        $inventory_data['transmission'],
        $inventory_data['drive'],
        $inventory_data['fuel']
    ] : [];
    $default_features = get_option('rd_inventory_feature_defaults', array());
    $auto_pop_fts = maybe_unserialize(get_post_meta($post->ID, 'additional_info', true)) ?? [];

    // var_dump($auto_pop_fts);
    // var_dump($features);
    // var_dump($featureMappings);

    if ($features) {
        foreach ($features as $index => $description) {
            // var_dump($auto_pop_fts[$index]['name']);
            $features_structured[] = [
                'name' => $auto_pop_fts[$index]['name'],
                'description' => $description
            ];
        }
        $features = $features_structured;
    }

    if (empty($features) && !empty($auto_pop_fts)) {
        $features = $auto_pop_fts;
    } elseif (empty($features) && empty($auto_pop_fts) && !empty($default_features)) {
        $features = $default_features;
    } elseif (empty($features) && empty($auto_pop_fts) && empty($default_features)) {
        $features = $featureMappings;
    }

    // HTML for the meta box form fields
    include(plugin_dir_path(__FILE__) . '/templates/rdis-fields-template.php');
}

function rd_inventory_settings_page()
{
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if the form was submitted
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['rd_inventory_feature_defaults'])) {
        // Verify nonce
        if (isset($_POST['rd_inventory_settings_nonce']) && wp_verify_nonce($_POST['rd_inventory_settings_nonce'], 'rdis_settings_action')) {
            // Save the default feature names
            $defaults = array_map('sanitize_text_field', $_POST['rd_inventory_feature_defaults']);
            update_option('rd_inventory_feature_defaults', $defaults);

            // Add a settings updated message
            add_settings_error('rd_inventory_settings', 'rd_inventory_settings_updated', 'Defaults saved.', 'updated');
            settings_errors('rd_inventory_settings');
        } else {
            // Handle the case where the nonce was not valid
            add_settings_error('rd_inventory_settings', 'rd_inventory_settings_nonce_failed', 'Error: Nonce verification failed.', 'error');
        }
    }

    // Retrieve the current defaults
    $defaults = get_option('rd_inventory_feature_defaults', array());

    // Include the settings page HTML
    include_once plugin_dir_path(__FILE__) . 'partials/rdis-settings.php';
}

add_action('save_post_rd_inventory', 'rd_inventory_save_meta_box_data');
function rd_inventory_save_meta_box_data($post_id)
{
    // Check if our nonce is set.
    if (!isset($_POST['rd_inventory_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['rd_inventory_meta_box_nonce'], 'rd_inventory_save_meta_box_data')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'rd_inventory' == $_POST['post_type']) {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // // Make sure that it is set.
    if (!isset($_POST['rd_inventory_buy_price'], $_POST['rd_inventory_posted_price'], $_POST['rd_inventory_quantity'])) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';
    $inventory_data = get_inventory_data($post_id, $wpdb, $table_name);

    // Use the post title as the name of the inventory item
    $name = get_the_title($post_id) ?? $inventory_data['name'];
    $buy_price_value = sanitize_text_field($_POST['rd_inventory_buy_price'] ?? $inventory_data['buy_price']);
    $posted_price_value = sanitize_text_field($_POST['rd_inventory_posted_price'] ?? $inventory_data['post_price']);
    $labor = sanitize_text_field($_POST['rd_inventory_labor_cost'] ?? $inventory_data['labor_cost']);
    $parts = sanitize_text_field($_POST['rd_inventory_parts_cost'] ?? $inventory_data['parts_cost']);
    $interest = sanitize_text_field($_POST['rd_inventory_interest_rate'] ?? $inventory_data['interest_rate']);
    $type = sanitize_text_field($_POST['rd_inventory_type'] ?? $inventory_data['type']);
    $receipt_date = sanitize_text_field($_POST['rd_inventory_receipt_date'] ?? $inventory_data['receipt_date']);
    $age = sanitize_text_field($_POST['rd_inventory_age'] ?? $inventory_data['age']);
    $vin = sanitize_text_field($_POST['rd_inventory_vin'] ?? $inventory_data['vin']);
    $year = sanitize_text_field($_POST['rd_inventory_year'] ?? $inventory_data['year']);
    $make = sanitize_text_field($_POST['rd_inventory_make'] ?? $inventory_data['make']);
    $model = sanitize_text_field($_POST['rd_inventory_model'] ?? $inventory_data['model']);
    $body = sanitize_text_field($_POST['rd_inventory_body'] ?? $inventory_data['body']);
    $series = sanitize_text_field($_POST['rd_inventory_series'] ?? $inventory_data['series']);
    $odometer = sanitize_text_field($_POST['rd_inventory_odometer'] ?? $inventory_data['odometer']);
    $color = sanitize_text_field($_POST['rd_inventory_color'] ?? $inventory_data['color']);
    $interior = sanitize_text_field($_POST['rd_inventory_interior'] ?? $inventory_data['interior']);
    $key_number = sanitize_text_field($_POST['rd_inventory_key_number'] ?? $inventory_data['key_number']);
    $engine = sanitize_text_field($_POST['rd_inventory_engine'] ?? $inventory_data['engine']);
    $transmission = sanitize_text_field($_POST['rd_inventory_transmission'] ?? $inventory_data['transmission']);
    $drive = sanitize_text_field($_POST['rd_inventory_drive'] ?? $inventory_data['drive']);
    $fuel =  sanitize_text_field($_POST['rd_inventory_fuel'] ?? $inventory_data['fuel']);
    $status = sanitize_text_field($_POST['rd_inventory_status'] ?? $inventory_data['status']);
    $quantity_value = sanitize_text_field($_POST['rd_inventory_quantity'] ?? $inventory_data['quantity']);


    // Save gallery data if it's set.
    if (isset($_POST['rd_inventory_gallery'])) {
        error_log(print_r($_POST, true));

        // Sanitize the gallery data and update the post meta.
        $gallery_data = explode(',', sanitize_text_field($_POST['rd_inventory_gallery']));
        $gallery_data = array_filter($gallery_data, 'is_numeric');
        update_post_meta($post_id, 'rd_inventory_gallery', $gallery_data);
    } else {
        error_log(print_r($_POST, true));
        delete_post_meta($post_id, 'rd_inventory_gallery');
    }

    $data = array(
        'post_id' => $post_id,
        'name' => $name,
        'type' => $type,
        'receipt_date' => $receipt_date,
        'age' => $age,
        'vin' => $vin,
        'year' => $year,
        'make' => $make,
        'model' => $model,
        'body' => $body,
        'series' => $series,
        'odometer' => $odometer,
        'color' => $color,
        'interior' => $interior,
        'key_number' => $key_number,
        'engine' => $engine,
        'transmission' => $transmission,
        'drive' => $drive,
        'fuel' => $fuel,
        'buy_price' => $buy_price_value,
        'labor_cost' => $labor,
        'parts_cost' => $parts,
        'interest_rate' => $interest,
        'posted_price' => $posted_price_value,
        'quantity' => $quantity_value,
        'status' => $status
    );

    // Check if an entry already exists in the custom table
    $existing_item = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE post_id = %d", $post_id));

    if ($existing_item) {
        unset($data['post_id']);
        // Update existing entry
        save_inventory_data($post_id, $data, $wpdb, $table_name, $existing_item);
    } else {
        // Insert new entry
        save_inventory_data($post_id, $data, $wpdb, $table_name, $existing_item);
    }
}

function get_inventory_data($post_id, $wpdb, $table_name)
{
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id), ARRAY_A);
}

function save_inventory_data($post_id, $data, $wpdb, $table_name, $existing_item)
{

    if ($existing_item) {
        // Update existing entry
        $wpdb->update($table_name, $data, array('post_id' => $post_id), array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s'), array('%d'));
    } else {
        // Insert new entry
        $wpdb->insert($table_name, $data, array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%d', '%s'));
    }
}


function get_rdis_field($field, $post_id = null)
{
    if (null === $post_id) {
        $post_id = get_the_ID();
    }
    return get_post_meta($post_id, 'rd_inventory_' . $field, true);
}
