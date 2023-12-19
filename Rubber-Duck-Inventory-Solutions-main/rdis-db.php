<?php
function rd_inventory_create_table($sql, $table_name)
{
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Table doesn't exist, so create it
    dbDelta($sql);



    if ($wpdb->last_error) {
        error_log("Error creating {$table_name} table: " . $wpdb->last_error);
        // Additional error handling can be added here
    }
}

function rd_inventory_create_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Main Inventory Table
    $table_name = $wpdb->prefix . 'inventory';

    // Ensure 'post_id' in 'wp_inventory' is indexed
    $wpdb->query("ALTER TABLE $table_name ADD INDEX IF NOT EXISTS(post_id)");

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        name varchar(255) NOT NULL,
        status varchar(255) DEFAULT 'pending',
        type text NULL,
        receipt_date text NULL,
        age text NULL,
        vin text NULL,
        year text NULL,
        make text NULL,
        model text NULL,
        body text NULL,
        series text NULL,
        odometer text NULL,
        color text NULL,
        interior text NULL,
        key_number text NULL,
        engine text NULL,
        transmission text NULL,
        drive text NULL,
        fuel text NULL,
        buy_price float NULL,
        labor_cost float NULL,
        parts_cost float NULL,
        interest_rate float NULL,
        posted_price float NULL,
        quantity int NOT NULL,
        date_created datetime DEFAULT CURRENT_TIMESTAMP,
        date_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    rd_inventory_create_table($sql, $table_name);
}

function rd_inventory_upgrade_tables()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'inventory';

    $sql = "ALTER TABLE wp_inventory ADD INDEX(post_id);";
    $wpdb->query($sql);

    rd_inventory_create_table($sql, $table_name);
}

function rd_inventory_create_sales_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'inventory_sales';
    $inventory_table_name = $wpdb->prefix . 'inventory';
    $cust_table_name = $wpdb->prefix . 'inventory_customer_info';

    $sql = "CREATE TABLE $table_name (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        post_id MEDIUMINT(9) NOT NULL,
        drivers_license VARCHAR(50) NULL,
        sold_price FLOAT NOT NULL,
        quantity INT NOT NULL,
        total_profit FLOAT NOT NULL,
        sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (post_id) REFERENCES $inventory_table_name (post_id)
        FOREIGN KEY (drivers_license) REFERENCES $cust_table_name (drivers_license)
    ) $charset_collate;";
    rd_inventory_create_table($sql, $table_name);
}

function rd_inventory_customer_info_table()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'inventory_customer_info';
    $inventory_table_name = $wpdb->prefix . 'inventory';

    $sql = "CREATE TABLE $table_name (
        id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(255) NULL,
        last_name VARCHAR(255) NULL,
        middle_name VARCHAR(255) NULL,
        address VARCHAR(255) NULL,
        city VARCHAR(255) NULL,
        state VARCHAR(255) NULL,
        zipcode VARCHAR(20) NULL,
        country VARCHAR(255) NULL,
        date_of_birth DATE NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(255) NULL,
        drivers_license VARCHAR(50) NULL,
        drivers_license_photo INT NULL,
        co_signer TINYINT(1) NULL,
        co_first_name VARCHAR(255) NULL,
        co_last_name VARCHAR(255) NULL,
        co_middle_name VARCHAR(255) NULL,
        co_address VARCHAR(255) NULL,
        co_city VARCHAR(255) NULL,
        co_state VARCHAR(255) NULL,
        co_zipcode VARCHAR(20) NULL,
        co_country VARCHAR(255) NULL,
        co_date_of_birth DATE NULL,
        co_phone VARCHAR(20) NULL,
        co_email VARCHAR(255) NULL,
        co_drivers_license VARCHAR(50) NULL,
        co_drivers_license_photo INT NULL,
        PRIMARY KEY (id),
    ) $charset_collate;";
    rd_inventory_create_table($sql, $table_name);
}
