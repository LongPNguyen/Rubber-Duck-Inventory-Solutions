<?php
//outdated needs to be updated 12/13/23

//CREATE
function rd_inventory_create_item($post_data, $additional_info) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    // Validation and Sanitization
    $name = sanitize_text_field($post_data['name']);
    $price = floatval($post_data['price']);
    $quantity = intval($post_data['quantity']);
    $additional_info_serialized = maybe_serialize($additional_info);

    // Insert into custom inventory table
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'additional_info' => $additional_info_serialized // Storing serialized data
        ),
        array('%s', '%f', '%d', '%s')
    );

    if ($wpdb->last_error) {
        return new WP_Error('db_insert_error', $wpdb->last_error);
    }

    return $wpdb->insert_id;
}

//READ
function rd_inventory_get_item($inventory_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($inventory_id)), ARRAY_A);

    if (!$item) {
        return new WP_Error('no_item', 'Invalid inventory ID');
    }

    $item['additional_info'] = maybe_unserialize($item['additional_info']);

    return $item;
}


//UPDATE
function rd_inventory_update_item($inventory_id, $post_data, $additional_info) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    $name = sanitize_text_field($post_data['name']);
    $price = floatval($post_data['price']);
    $quantity = intval($post_data['quantity']);
    $additional_info_serialized = maybe_serialize($additional_info);

    $result = $wpdb->update(
        $table_name,
        array(
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'additional_info' => $additional_info_serialized // Storing serialized data
        ),
        array('id' => intval($inventory_id)),
        array('%s', '%f', '%d', '%s')
    );

    if ($result === false) {
        return new WP_Error('db_update_error', $wpdb->last_error);
    }

    return true;
}

//DELETE
function rd_inventory_delete_item($inventory_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    $result = $wpdb->delete($table_name, array('id' => intval($inventory_id)), array('%d'));

    if ($result === false) {
        return new WP_Error('db_delete_error', $wpdb->last_error);
    }

    return true;
}
