<?php
// Vin Decoder Api Handler
function decode_vin_with_vpic($vin)
{
    $api_url = "https://vpic.nhtsa.dot.gov/api/vehicles/decodevin/" . $vin . "?format=json";
    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        // Handle error appropriately
        return 'Error: ' . $response->get_error_message();
    }

    $body = wp_remote_retrieve_body($response);
    $decoded_body = json_decode($body, true);

    return $decoded_body;
}

//API Process Start
function process_and_query_vin_api($result)
{
    $vin_info = decode_vin_with_vpic($result);

    // Assuming $vin_info is the data you want to return
    if ($vin_info) {
        wp_send_json_success($vin_info);
    } else {
        wp_send_json_error('No data found for the provided VIN.');
    }
}
