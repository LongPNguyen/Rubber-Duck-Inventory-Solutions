<?php
if (defined('ABSPATH')) {
    $custom_logo_id = get_theme_mod('custom_logo');
    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
}

add_action('save_post', 'rd_inventory_save_postdata');
function rd_inventory_save_postdata($post_id)
{
    // Check if this is an autosave, if so, don't update
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions and nonce here for security

    // Get the post object to check the post status
    $post = get_post($post_id);

    // Check for the post status
    if ('publish' === $post->post_status) {
        // The post is being published, enforce stricter validation

        // Validation for Quantity
        if (isset($_POST['rd_inventory_quantity']) && $_POST['rd_inventory_quantity'] < 1) {
            // Handle error - quantity must be at least 1
            // You can set an admin notice or revert the post status to draft
            remove_action('save_post', 'rd_inventory_save_postdata'); // Avoid infinite loop
            wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            add_action('save_post', 'rd_inventory_save_postdata');

            // Optionally add an admin notice here
            return;
        }
    }

    // Other validations and saving metadata can go here

}
?>
<div id="rdis-upload-container" class="p-4">
    <img class="rdis-scanner-logo m-5" src="<?= esc_url($logo[0]) ? esc_url($logo[0]) : 'https://rubberducktech.com/wp-content/uploads/2023/06/cropped-Rubber-Duck_final-file.png' ?>" />

    <!-- Pick Process  -->
    <div id="pick-scanner" class="text-center">
        <p class="lead mb-5">Welcome to the Rubber Duck Inventory Flow Manager, please choose how you would like to proceed</p>
        <button id="scanner-add-inventory" class="mr-3">Add Item</button><button id="scanner-sell-inventory">Sell Item</button>
    </div>

    <div id="rdis-matching" style="display: none;">
        <div id="rdis-scanner-form">
            <input type="file" name="rdis_image" id="rdis-image-input" accept="image/*" style="display: none;">
            <label for="rdis-image-input" id="rdis-drop-zone" class="text-center">
                <large class="font-weight-bold text-lg">Add an image of the item identification number into the drop box then click the next button to begin the sales process.</large>
                <large class="font-weight-bold">Make sure to crop the image so only the identification number is visible.</large>
                <small class="font-weight-bold">(ie: barcode number, vin number, etc.)</small>
            </label>
        </div>
        <div id="image-cropping-container" style="display:none;">
            <img id="image-to-crop" src="" alt="Image to Crop" />
        </div>
        <div id="rdis-ocr-results" class="p-3"></div>

        <button class="rdis-scanner-submit">Next →</button>
    </div>
    <div id="matched-item" style="display:none;">
        <h1 class="text-center">Is this the correct inventory item?</h1>
        <div id="inventory-item-details" class="mb-4 bg-light">
            <div id="upper-fold" class="text-center"></div>
            <div id="lower-fold"></div>
        </div>
        <div class="ml-4"><button id="confirm-match">Yes</button><button id="deny-match">No</button></div>
    </div>
    <div id="sale-price-div" class="container align-items-center" style="display:none;">
        <p class="pt-3 pl-3 pr-3 lead">Please input the <b>Sales Price.</b></p>
        <div id="sale-price" class="form-floating">
            <input type="number" id="sale-price-input" class="form-control" value="$" placeholder="$ Price" required>
            <label for="sale-price-input">$</label><br />
        </div>
        <div id="sale-qty" class="d-none">
            <label for="sale-qty-input">Qty:</label><br />
            <input type="number" id="sale-qty-input" value="1" class="ml-3">
        </div>
        <button id="sale-price-next">Next →</button>
    </div>
    <form id="customer-info" class="d-none" method="post" enctype="multipart/form-data">
        <div class="buyer-info mb-4">
            <h3>BUYER INFO</h3>
            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control" placeholder="*First Name" id="cust-first" name="cust-first" required>
                    <label for="cust-first">*First Name:</label>

                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control" placeholder="*Last Name" id="cust-last" name="cust-last" required>
                    <label for="cust-last">*Last Name:</label>

                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="Middle Name" id="cust-middle" name="cust-middle">
                    <label for="cust-middle">Middle Name:</label>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Address" id="cust-address" name="cust-address" required>
                    <label for="cust-address">*Address:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*City" id="cust-city" name="cust-city" required>
                    <label for="cust-city">*City:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <select id="cust-state" class="form-control form-control-sm" placeholder="*State" name="cust-state" required>
                        <option selected>Choose...</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="DC">District Of Columbia</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                    <label for="cust-state">*State:</label>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Zipcode" id="cust-zipcode" name="cust-zipcode" required>
                    <label for="cust-zipcode">*Zipcode:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Country" id="cust-country" name="cust-country" value="United States" required>
                    <label for="cust-country">*Country:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="date" class="form-control form-control-sm" placeholder="*Date" id="cust-dob" name="cust-dob" required>
                    <label for="cust-dob">*DOB:</label>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="tel" class="form-control form-control-sm" placeholder="*Phone" id="cust-phone" name="cust-phone" required>
                    <label for="cust-phone">*Phone:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="email" class="form-control form-control-sm" placeholder="*Email" id="cust-email" name="cust-email" required>
                    <label for="cust-email">*Email:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Drivers License" id="cust-dl" name="cust-dl" required>
                    <label for="cust-dl">*Drivers License #:</label>
                </div>
            </div>
            <div class="form-row">
                <div class="col-auto">
                    <label for="cust-dlp" class="form-label">*Drivers License Photo:</label>
                    <input type="file" class="form-control drivers-license-photo" placeholder="*DLP" id="cust-dlp" name="cust-dlp" data-role="buyer" required>
                </div>
            </div>
        </div>
        <div class="form-check d-flex align-items-center mb-4">
            <input type="checkbox" id="co-buyer-bool">
            <label class="form-check-label mb-1 ml-1" for="co-buyer-bool">CO BUYER?</label>
        </div>
        <div id="co-buyer-info" class="d-none">
            <h3>CO BUYER INFO</h3>
            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control" placeholder="*First Name" id="co-cust-first" name="co-cust-first">
                    <label for="co-cust-first">*First Name:</label>

                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control" placeholder="*Last Name" id="co-cust-last" name="co-cust-last">
                    <label for="co-cust-last">*Last Name:</label>

                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="Middle Name" id="co-cust-middle" name="co-cust-middle">
                    <label for="co-cust-middle">Middle Name:</label>
                </div>
            </div>
            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Address" id="co-cust-address" name="co-cust-address">
                    <label for="co-cust-address">*Address:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*City" id="co-cust-city" name="co-cust-city">
                    <label for="co-cust-city">*City:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <select id="co-cust-state" class="form-control form-control-sm" placeholder="*State" name="co-cust-state">
                        <option selected>Choose...</option>
                        <option value="AL">Alabama</option>
                        <option value="AK">Alaska</option>
                        <option value="AZ">Arizona</option>
                        <option value="AR">Arkansas</option>
                        <option value="CA">California</option>
                        <option value="CO">Colorado</option>
                        <option value="CT">Connecticut</option>
                        <option value="DE">Delaware</option>
                        <option value="DC">District Of Columbia</option>
                        <option value="FL">Florida</option>
                        <option value="GA">Georgia</option>
                        <option value="HI">Hawaii</option>
                        <option value="ID">Idaho</option>
                        <option value="IL">Illinois</option>
                        <option value="IN">Indiana</option>
                        <option value="IA">Iowa</option>
                        <option value="KS">Kansas</option>
                        <option value="KY">Kentucky</option>
                        <option value="LA">Louisiana</option>
                        <option value="ME">Maine</option>
                        <option value="MD">Maryland</option>
                        <option value="MA">Massachusetts</option>
                        <option value="MI">Michigan</option>
                        <option value="MN">Minnesota</option>
                        <option value="MS">Mississippi</option>
                        <option value="MO">Missouri</option>
                        <option value="MT">Montana</option>
                        <option value="NE">Nebraska</option>
                        <option value="NV">Nevada</option>
                        <option value="NH">New Hampshire</option>
                        <option value="NJ">New Jersey</option>
                        <option value="NM">New Mexico</option>
                        <option value="NY">New York</option>
                        <option value="NC">North Carolina</option>
                        <option value="ND">North Dakota</option>
                        <option value="OH">Ohio</option>
                        <option value="OK">Oklahoma</option>
                        <option value="OR">Oregon</option>
                        <option value="PA">Pennsylvania</option>
                        <option value="RI">Rhode Island</option>
                        <option value="SC">South Carolina</option>
                        <option value="SD">South Dakota</option>
                        <option value="TN">Tennessee</option>
                        <option value="TX">Texas</option>
                        <option value="UT">Utah</option>
                        <option value="VT">Vermont</option>
                        <option value="VA">Virginia</option>
                        <option value="WA">Washington</option>
                        <option value="WV">West Virginia</option>
                        <option value="WI">Wisconsin</option>
                        <option value="WY">Wyoming</option>
                    </select>
                    <label for="co-cust-state">*State:</label>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Zipcode" id="co-cust-zipcode" name="co-cust-zipcode">
                    <label for="co-cust-zipcode">*Zipcode:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Country" id="co-cust-country" name="co-cust-country" value="United States">
                    <label for="co-cust-country">*Country:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="date" class="form-control form-control-sm" placeholder="*Date" id="co-cust-dob" name="co-cust-dob">
                    <label for="co-cust-dob">*DOB:</label>
                </div>
            </div>

            <div class="form-row">
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="tel" class="form-control form-control-sm" placeholder="*Phone" id="co-cust-phone" name="co-cust-phone">
                    <label for="co-cust-phone">*Phone:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="email" class="form-control form-control-sm" placeholder="*Email" id="co-cust-email" name="co-cust-email">
                    <label for="co-cust-email">*Email:</label>
                </div>
                <div class="col-sm-12 col-md-4 mb-3 form-floating">
                    <input type="text" class="form-control form-control-sm" placeholder="*Drivers License" id="co-cust-dl" name="co-cust-dl">
                    <label for="co-cust-dl">*Drivers License #:</label>
                </div>
            </div>
            <div class="form-row">
                <div class="col-auto mb-3">
                    <label for="co-cust-dlp" class="form-label ">*Drivers License Photo:</label>
                    <input type="file" class="form-control drivers-license-photo" placeholder="*DLP" id="co-cust-dlp" name="co-cust-dlp" data-role="cobuyer">
                </div>
            </div>
        </div>
        <button type="submit" id="cust-info-next">Next →</button>
    </form>
</div>
<div id="manualInputDiv" class="d-none flex-column align-items-center mt-3 container">
    <p></p>
    <h3>Enter the last 6 of the vin</h3>
    <input type="text" id="manualInputField" class="mb-3">
    <button class="rdis-scanner-submit">Next →</button>
</div>
<!-- End Sales Process  -->
</div>