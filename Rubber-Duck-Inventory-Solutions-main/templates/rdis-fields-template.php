<div class="form-group">
    <h3>Stock #: <span id="rd_inventory_id" name="rd_inventory_id" class="regular-text"><?= $inventory_item_id !== null ? $inventory_item_id : $inventory_item_count + 1 ?></span></h3>
    <h3>Status: <?= $status ? $status : "New Inventory Item" ?></h3>
</div>

<!-- Start Gallery Field -->
<div class="rd-inventory-gallery-field form-row">
    <!-- <label for="rd_inventory_gallery">Gallery:</label> -->
    <div class="col-auto">
        <div class="gallery-thumbnails">
            <?php if (!empty($gallery_data)) : ?>
                <?php foreach ($gallery_data as $image_id) : ?>
                    <div class="gallery-thumbnail">
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                        <button type="button" class="remove-image" data-attachment_id="<?php echo esc_attr($image_id); ?>">Remove</button>
                    </div>
                <?php
                endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="add_gallery_image" class="button mb-4">Add Image</button>
        <!-- Hidden field to store the image IDs -->
        <input type="hidden" id="rd_inventory_gallery" name="rd_inventory_gallery" value="<?php echo esc_attr(implode(',', (array) $gallery_data)); ?>">
    </div>
    <?php if (in_array('owner', $user->roles) || in_array('administrator', $user->roles)) : ?>
        <div class="col-auto">
            <h4>Estimate expenses break down:</h4>
            <table class="table-success table-sm mb-4">
                <tbody>
                    <tr>
                        <th scope="row">Auction Price</th>
                        <td><?= $buy_price ? "$" . $buy_price : "please input value above to populate" ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Labor Cost</th>
                        <td><?= $labor ? "$" . $labor . " +" : "please input value above to populate" ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Parts Cost</th>
                        <td><?= $parts ? "$" . $parts . " +" : "please input value above to populate" ?></td>
                    </tr>
                    <tr>
                        <th scope="row">Interest</th>
                        <td><?= $buy_price && $labor && $parts && $interest ? "$" . ($buy_price + $labor + $parts) * ($interest / 100) . " +" : "please input value above to populate" ?></td>
                        <td>Interest break down:</td>
                        <td><?= $buy_price && $labor && $parts && $interest ? "($" . $buy_price . " + $" . $labor . " + $" . $parts . ") x (" . $interest / 100 . ")" : "" ?></td>
                    </tr>
                    <tr style="border-top: 1px solid green">
                        <th scope="row">Total Expense</th>
                        <td class="lead">$<u><?= (($buy_price ? $buy_price : "0") + ($labor ? $labor : 0) + ($parts ? $parts : 0)) + ($buy_price && $labor && $parts && $interest ? ($buy_price + $labor + $parts) * ($interest / 100) : 0) ?><u></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <div class="rd-inventory-field-group col-auto row">
        <?php if (in_array('owner', $user->roles) || in_array('administrator', $user->roles)) : ?>
            <!-- Buy Price Field -->
            <div class="rd-inventory-field col-auto m-3 form-floating">
                <input type="number" id="rd_inventory_buy_price" name="rd_inventory_buy_price" value="<?php echo esc_attr($buy_price); ?>" class="form-control" min="1" required>
                <label class="ml-2 fw-bold" for="rd_inventory_buy_price">Auction Price: $</label>
            </div>
            <div class="rd-inventory-field col-auto m-3 form-floating">
                <input type="number" id="rd_inventory_labor_cost" name="rd_inventory_labor_cost" value="<?php echo esc_attr($labor); ?>" class="form-control" min="1" required>
                <label class="ml-2 fw-bold" for="rd_inventory_labor_cost">Labor Cost: $</label>
            </div>
            <div class="rd-inventory-field col-auto m-3 form-floating">
                <input type="number" id="rd_inventory_parts_cost" name="rd_inventory_parts_cost" value="<?php echo esc_attr($parts); ?>" class="form-control" min="1" required>
                <label class="ml-2 fw-bold" for="rd_inventory_parts_cost">Parts Cost: $</label>
            </div>
            <div class="rd-inventory-field col-auto m-3 form-floating">
                <input type="number" step="0.01" id="rd_inventory_interest_rate" name="rd_inventory_interest_rate" value="<?php echo esc_attr($interest); ?>" class="form-control" min="1" required>
                <label class="ml-2 fw-bold" for="rd_inventory_interest-rate">Interest Rate: %</label>
            </div>
        <?php endif; ?>
        <div class="rd-inventory-field col-auto m-3 form-floating">
            <input type="number" id="rd_inventory_posted_price" name="rd_inventory_posted_price" value="<?php echo esc_attr($posted_price); ?>" class="form-control" min="1" required>
            <label class="ml-2 fw-bold" for="rd_inventory_posted_price">Selling Price: $</label>
        </div>
        <div class="rd-inventory-field col-auto m-3 form-floating">
            <input type="number" id="rd_inventory_quantity" name="rd_inventory_quantity" value="<?= $quantity ? esc_attr($quantity) : 1 ?>" class="form-control" min="1" required readonly>
            <label class="ml-2 fw-bold" for="rd_inventory_quantity">Quantity:</label>
        </div>
    </div>
</div>
<!-- End Gallery Field -->
<!-- Begin Repeater Fields -->
<div id="repeater">
    <div id="repeater_fields" class="form-row">
        <?php
        foreach ($features as $index => $feature) {
            // Convert the feature name to the field name (e.g., "Type" to "type")
            $fieldName = $featureMappings[$feature['name']] ?? '';

            // Skip if no mapping is found
            if (empty($fieldName)) {
                continue;
            }

        ?>
            <div class="repeater_row col-xs-12 col-sm-5 col-md-2 mb-1 mr-1 form-floating">
                <input style="width: -webkit-fill-available;" type="text" id="rd_inventory_<?php echo $fieldName; ?>" name="rd_inventory_<?php echo $fieldName; ?>" value="<?php echo esc_attr($feature['description']); ?>" class="text-secondary form-control" />
                <label for="rd_inventory_<?php echo $fieldName; ?>"><b><?php echo esc_html($feature['name']); ?></b></label>
            </div>
        <?php
        }

        ?>
    </div>
    <!-- End Repeater Fields -->