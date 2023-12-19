<?php
// Adjusted function to process and query VIN - OCR
function process_and_query_vin($cleaned_result)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';
    $variants = generate_variants($cleaned_result);
    $response = [];

    // Fetch all additional_info from the database
    $results = $wpdb->get_results("SELECT * FROM {$table_name}");

    // Loop through each database entry
    foreach ($results as $result) {
        foreach ($variants as $variant) {
            if ($variant == $result->vin) {
                // Match found, process and add to response
                process_matching_post($result->post_id, $response);
                break 2; // Break out of both foreach loops
            }
        }
    }

    // Send response or error based on matches found
    if (empty($response)) {
        wp_send_json_error('No close matches found');
    } else {
        wp_send_json_success(array_values($response));
    }
}

add_action('wp_ajax_rdis_record_sale', 'rdis_record_sale_callback');

function rdis_record_sale_callback()
{
    // Verify the nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rdis_ocr_nonce')) {
        wp_send_json_error("Security check failed");
        wp_die();
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error("Insufficient user permissions");
        wp_die();
    }


    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $sales_table_name = $wpdb->prefix . 'inventory_sales';
    $cust_table_name = $wpdb->prefix . 'inventory_customer_info';
    $inventory_table_name =  $wpdb->prefix . 'inventory';

    $post_id = intval($_POST['post_id']);
    $sold_price = floatval($_POST['sold_price']);
    $quantity = intval($_POST['quantity']);

    //BUYER
    $cust_first_name = sanitize_text_field($_POST['cust-first']);
    $cust_last_name = sanitize_text_field($_POST['cust-last']);
    $cust_middle_name = sanitize_text_field($_POST['cust-middle']);
    $cust_address = sanitize_text_field($_POST['cust-address']);
    $cust_city = sanitize_text_field($_POST['cust-city']);
    $cust_state = sanitize_text_field($_POST['cust-state']);
    $cust_zipcode = sanitize_text_field($_POST['cust-zipcode']);
    $cust_country = sanitize_text_field($_POST['cust-country']);

    $cust_date_of_birth = sanitize_text_field($_POST['cust-dob']);

    $cust_phone = sanitize_text_field($_POST['cust-phone']);
    $cust_email = sanitize_email($_POST['cust-email']);
    $cust_drivers_license = sanitize_text_field($_POST['cust-dl']);

    //COBUYER
    $co_buyer_bool = sanitize_text_field($_POST['co-buyer-bool']);

    $co_cust_first_name = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-first']) : NULL;
    $co_cust_last_name = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-last']) : NULL;
    $co_cust_middle_name = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-middle']) : NULL;
    $co_cust_address = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-address']) : NULL;
    $co_cust_city = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-city']) : NULL;
    $co_cust_state = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-state']) : NULL;
    $co_cust_zipcode = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-zipcode']) : NULL;
    $co_cust_country = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-country']) : NULL;

    $co_cust_date_of_birth = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-dob']) : NULL;

    $co_cust_phone = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-phone']) : NULL;
    $co_cust_email = $co_buyer_bool == 1 ? sanitize_email($_POST['co-cust-email']) : NULL;
    $co_cust_drivers_license = $co_buyer_bool == 1 ? sanitize_text_field($_POST['co-cust-dl']) : NULL;


    if (isset($_FILES['cust-dlp'])) {
        $attachment_id = media_handle_upload('cust-dlp', 0);
        if (is_wp_error($attachment_id)) {
            error_log('No buyer license upload');
        } else {
            echo
            "BUYER: " .
                $post_id . ", " .
                $sold_price . ", " .
                $quantity . ", " .
                $cust_first_name . ", " .
                $cust_last_name . ", " .
                $cust_middle_name . ", " .
                $cust_address . ", " .
                $cust_city . ", " .
                $cust_state . ", " .
                $cust_zipcode . ", " .
                $cust_country . ", " .
                $cust_date_of_birth . ", " .
                $cust_phone . ", " .
                $cust_email . ", " .
                $cust_drivers_license . ", " .
                intval($attachment_id);
        }
    }

    if (isset($_FILES['co-cust-dlp'])) {
        $co_attachment_id = media_handle_upload('co-cust-dlp', 0);
        if (is_wp_error($co_attachment_id)) {
            error_log('No co buyer license upload');
        } else {
            echo
            "COBUYER: " .
                $co_buyer_bool . ", " .
                $co_cust_first_name . ", " .
                $co_cust_last_name . ", " .
                $co_cust_middle_name . ", " .
                $co_cust_address . ", " .
                $co_cust_city . ", " .
                $co_cust_state . ", " .
                $co_cust_zipcode . ", " .
                $co_cust_country . ", " .
                $co_cust_date_of_birth . ", " .
                $co_cust_phone . ", " .
                $co_cust_email . ", " .
                $co_cust_drivers_license . ", " .
                intval($co_attachment_id);
        }
    }

    wp_die();

    // Assuming $attachment_id is obtained from a file upload handling process
    $cust_drivers_license_photo_id = intval($attachment_id); // Sanitize as integer
    $co_cust_drivers_license_photo_id = $co_buyer_bool == 1 ? intval($co_attachment_id) : NULL; // Sanitize as integer

    // Fetch all info from the wp_inventory table where post id match.
    $fetch_inventory_data = $wpdb->get_row($wpdb->prepare(
        "SELECT buy_price, quantity FROM {$inventory_table_name} WHERE post_id = %d",
        $post_id
    ), ARRAY_A);

    $buy_price = floatval($fetch_inventory_data['buy_price']);
    $inventory_qty = intval($fetch_inventory_data['quantity']);
    $total_profit = floatval(($sold_price * $quantity) - ($buy_price * $quantity)); // Calculate total profit
    $items_left = intval($inventory_qty - $quantity);
    $draft_post = array(
        'ID'          => $post_id,
        'post_status' => 'draft'
    );

    if ($items_left < 0) {
        wp_send_json_error("Error recording sale: You do not have enough of this item to make the sale. items left: " . $items_left . " db qty:" . $inventory_qty . " input qty:" . $quantity . " post ID:" . $post_id);
        wp_die();
    } elseif ($items_left == 0) {
        wp_update_post($draft_post);
    }

    // Insert the sale record
    $result = $wpdb->insert(
        $sales_table_name,
        array(
            'post_id' => $post_id,
            'drivers_license' => $cust_drivers_license,
            'sold_price' => $sold_price,
            'quantity' => $quantity,
            'total_profit' => $total_profit
        ),
        array('%d', '%f', '%d', '%f')
    );

    $cust_info = $wpdb->insert(
        $cust_table_name,
        array(
            'first_name' => $cust_first_name,
            'last_name' => $cust_last_name,
            'middle_name' => $cust_middle_name,
            'address' => $cust_address,
            'city' => $cust_city,
            'state' => $cust_state,
            'zipcode' => $cust_zipcode,
            'country' => $cust_country,
            'date_of_birth' => $cust_date_of_birth,
            'phone' => $cust_phone,
            'email' => $cust_email,
            'drivers_license' => $cust_drivers_license,
            'drivers_license_photo' => $cust_drivers_license_photo_id,
            'co_signer' => $co_signer_bool,
            'co_first_name' => $co_cust_first_name,
            'co_last_name' => $co_cust_last_name,
            'co_middle_name' => $co_cust_middle_name,
            'co_address' => $co_cust_address,
            'co_city' => $co_cust_city,
            'co_state' => $co_cust_state,
            'co_zipcode' => $co_cust_zipcode,
            'co_country' => $co_cust_country,
            'co_date_of_birth' => $co_cust_date_of_birth,
            'co_phone' => $co_cust_phone,
            'co_email' => $co_cust_email,
            'co_drivers_license' => $co_cust_drivers_license,
            'co_drivers_license_photo' => $co_cust_drivers_license_photo_id
        ),
        array(),
    );

    //Update the quantity in the inventory table
    $update_qty = $wpdb->update(
        $inventory_table_name,
        array(
            'quantity' => $items_left,
            'status' => 'SOLD'
        ), // columns to update
        array('post_id' => $post_id), // where clause
        array('%d', '%d'), // format of columns to update
        array('%d')
    );

    if ($result === false) {
        wp_send_json_error("Error recording sale: " . $wpdb->last_error);
    } else {
        wp_send_json_success("Sale recorded successfully.");
    }

    if ($cust_info === false) {
        wp_send_json_error("Error recording sale: " . $wpdb->last_error);
    } else {
        wp_send_json_success("Customer Added successfully.");
    }

    if ($update_qty === false) {
        error_log('Update error: ' . $wpdb->last_error);
        wp_send_json_error("Error updating inventory: " . $wpdb->last_error);
        wp_die();
    } else {
        wp_send_json_success("Sale recorded successfully.");
    }

    wp_die();
}
