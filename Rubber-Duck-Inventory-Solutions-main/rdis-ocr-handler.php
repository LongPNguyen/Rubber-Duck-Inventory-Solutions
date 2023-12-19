<?php
// Tesseract Handler 
function rdis_process_image_with_ocr($image_path)
{
    $output = shell_exec("tesseract " . escapeshellarg($image_path) . " stdout");
    return $output;
}

function rdis_handle_cropped_image_callback()
{
    if (!$_POST['manual_vin']) {
        // Check if the image is set
        if (!isset($_FILES['croppedImage'])) {
            echo 'No image received';
            wp_die();
        }

        // Include necessary WordPress files for handling media
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Handle the uploaded image file
        $file_id = 'croppedImage';
        $attachment_id = media_handle_upload($file_id, 0); // 0 = no parent post

        // Check for errors in file upload
        if (is_wp_error($attachment_id)) {
            echo "Error processing file: " . $attachment_id->get_error_message();
            wp_die();
        }

        // Retrieve the URL of the uploaded image
        $image_url = wp_get_attachment_url($attachment_id);

        // Process the image with OCR
        $ocr_result = rdis_process_image_with_ocr($image_url);

        if ($ocr_result === null) {
            wp_send_json_error("Looks like the image quality was too hard to read, please adjust the crop and try again.");
            wp_die();
        }

        wp_delete_attachment($attachment_id); // Clean up: Delete the temporary file

        // Clean the OCR result
        $cleaned_result = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($ocr_result));
        // Validate and process the VIN
        if (isset($_POST['process_action'])) {
            if ($_POST['process_action'] === 'add') {
                process_and_query_vin_api($cleaned_result);
            } elseif ($_POST['process_action'] === 'sell') {
                process_and_query_vin($cleaned_result);
            }
        }
    } else {
        process_and_query_vin_api(strtoupper($_POST['manual_vin']));
    }

    wp_die(); // Terminate AJAX request
}

add_action('wp_ajax_rdis_handle_cropped_image', 'rdis_handle_cropped_image_callback');


function rdis_query_vin_last_six_callback()
{
    $last_six_vin = isset($_POST['last_six_vin']) ? sanitize_text_field($_POST['last_six_vin']) : '';

    if (strlen($last_six_vin) !== 6) {
        wp_send_json_error("Invalid VIN segment.");
        wp_die();
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';
    $response = [];

    $results = $wpdb->get_results("SELECT * FROM {$table_name}");

    foreach ($results as $result) {
        if (substr($result->vin, -6) === $last_six_vin) {
            process_matching_post($result->post_id, $response);
            break; // Break out of loop once a match is found
        }
    }

    if (empty($response)) {
        wp_send_json_error('No matches found for the provided VIN segment.');
    } else {
        wp_send_json_success(array_values($response));
    }

    wp_die();
}

add_action('wp_ajax_rdis_query_vin_last_six', 'rdis_query_vin_last_six_callback');

function process_matching_post($post_id, &$response)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    // Retrieve the specific post from the database
    $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE post_id = %d AND quantity > 0", $post_id);
    $matching_post = $wpdb->get_row($query);

    // Check if the post exists
    if ($matching_post) {
        // Ensure unique posts in the response
        if (!isset($response[$matching_post->post_id])) {
            // Retrieve gallery images (assumes gallery data is stored in post meta)
            $gallery_images = get_rdis_field('gallery', $matching_post->post_id);

            // Get the URL of the first image, if available
            $first_image_url = '';
            if (!empty($gallery_images) && is_array($gallery_images)) {
                $first_image_id = $gallery_images[0]; // First image ID
                $first_image_url = wp_get_attachment_url($first_image_id);
            }

            // Get the URL of the first image, if available
            if (is_array($gallery_images) && !empty($gallery_images)) {
                $first_image_id = $gallery_images[0]; // First image ID
                $first_image_url = wp_get_attachment_image_src($first_image_id, 'thumbnail')[0];
            }

            // Add the post to the response array
            $response[$matching_post->post_id] = [
                'id' => $matching_post->id,
                'post_id' => $matching_post->post_id,
                'name' => $matching_post->name,
                'post_price' => $matching_post->posted_price,
                'first_image_url' => $first_image_url,
                'type' => $matching_post->type,
                'receipt_date' => $matching_post->receipt_date,
                'age' => $matching_post->age,
                'vin' => $matching_post->vin,
                'year' => $matching_post->year,
                'make' => $matching_post->make,
                'model' => $matching_post->model,
                'body' => $matching_post->body,
                'series' => $matching_post->series,
                'odometer' => $matching_post->odometer,
                'color' => $matching_post->color,
                'interior' => $matching_post->interior,
                'key_number' => $matching_post->key_number,
                'engine' => $matching_post->engine,
                'transmission' => $matching_post->transmission,
                'drive' => $matching_post->drive,
                'fuel' => $matching_post->fuel,
                'status' => $matching_post->status,
                'current_status' => get_post_status($post_id)
            ];
        }
    }
}

function create_rd_inventory_post($post_data)
{
    $post_title = sanitize_text_field($post_data['title']);
    $vehicleData = $post_data['rd_inventory_features'] ?? []; // Your vehicle data from the API

    // Format the data for additional_info
    $formattedVehicleData = [];
    foreach ($vehicleData as $key => $value) {
        $formattedVehicleData[$key] = $value;
    }
    echo $formattedVehicleData;
    // Create the post array
    $post_arr = array(
        'post_title'  => $post_title,
        'post_type'   => 'rd_inventory',
        'post_status' => 'draft', // or 'publish'
    );

    // Insert the new post
    $post_id = wp_insert_post($post_arr);

    // Save the formatted vehicle data in additional_info
    update_post_meta($post_id, 'additional_info', maybe_serialize($formattedVehicleData));

    return $post_id;
}



add_action('wp_ajax_rdis_add_inventory', 'rdis_handle_add_inventory_request');

function rdis_handle_add_inventory_request()
{
    $post_data = $_POST['post_data'] ?? [];
    $additional_info = maybe_serialize($post_data['additional_info']);


    // Creating or updating the post
    $post_id = wp_insert_post([
        'post_title' => sanitize_text_field($post_data['title']),
        'post_type' => 'rd_inventory',
        'post_status' => 'draft',
    ]);

    update_post_meta($post_id, 'additional_info', $additional_info);

    wp_send_json_success(['post_id' => $post_id]);
    wp_die();
}

function sanitize_vehicle_data($vehicleData)
{
    $sanitizedData = [];
    foreach ($vehicleData as $key => $value) {
        // Sanitize each value; adjust sanitization method as necessary
        $sanitizedData[$key] = sanitize_text_field($value);
    }
    return $sanitizedData;
}
