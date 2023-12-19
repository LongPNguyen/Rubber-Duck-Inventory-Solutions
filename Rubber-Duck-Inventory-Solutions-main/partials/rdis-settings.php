<?php
// Retrieve the current defaults
$defaults = get_option('rd_inventory_feature_defaults', array());

// Output any settings errors registered by add_settings_error()
settings_errors('rdts_settings');
?>
<tr>
    <th scope="row"><label for="rd_inventory_feature_defaults">Default Labels</label></th>
    <td>
        <fieldset id="feature-name-fields">
            <legend class="screen-reader-text"><span>Default Labels</span></legend>
            <!-- Hidden template row for cloning -->
            <p class="template-feature-row" style="display: none;">
                <input type="text" name="" value="" class="regular-text" />
                <button type="button" class="remove-feature button">&times;</button>
            </p>

            <?php foreach ($defaults as $index => $value) : ?>
                <p>
                    <input type="text" name="rd_inventory_feature_defaults[<?= $index ?>]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
                    <button type="button" class="remove-feature button">&times;</button>
                </p>
            <?php endforeach; ?>
            <p>
                <button type="button" id="add-feature-name" class="button">Add Label</button>
            </p>
            <p class="description">Enter the default labels that will be used for new inventory items. Click "Add Label" to add more fields.</p>
        </fieldset>
    </td>
</tr>