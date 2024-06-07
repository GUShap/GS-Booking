<?php

class GS_Booking_Woocommerce
{
    // Constructor to initialize the class
    public function __construct()
    {
        $this->initialize_woocommerce_hooks();
        // $this->register_custom_order_statuses();
    }

    // Method to initialize WooCommerce hooks
    public function initialize_woocommerce_hooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'gs_bookings_enqueue_woocommerce_scripts'));

        add_action('admin_init', array($this, 'create_product_categories'));
        add_action('wp_footer', array($this, 'enqueue_payment_gateway_scripts'));

        add_action('woocommerce_product_data_panels', array($this, 'custom_product_types_tabs_content'));
        add_action('woocommerce_process_product_meta', array($this, 'save_custom_product_data'));

        add_filter('woocommerce_product_data_tabs', array($this, 'set_custom_product_types_tabs'));


        // Add 'Privacy Statement' to the list of order meta data
        add_action('add_meta_boxes_product', array($this, 'add_privacy_statement_metabox'));
        add_action('save_post', array($this, 'save_privacy_statement_metabox'));

        add_action('woocommerce_remove_cart_item', array($this, 'update_retreat_remove_from_cart'), 10, 2);

        add_action('woocommerce_before_order_notes', array($this, 'set_participants_details'), 10);
        add_action('woocommerce_checkout_process', array($this, 'validate_custom_checkout_field'));
        // add_action('woocommerce_review_order_before_payment', array($this, 'set_deposit_payment_options'), 11);

        add_filter('woocommerce_get_item_data', array($this, 'set_cart_item_custom_data'), 10, 2);
        add_filter('woocommerce_cart_item_name', array($this, 'set_cart_item_name'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_item_price'), 10, 1);
        add_filter('woocommerce_cart_item_visible', array($this, 'hide_completing_products_from_cart'), 10, 3);
        add_filter('woocommerce_widget_cart_item_visible', array($this, 'hide_completing_products_from_cart'), 10, 3);
        add_filter('woocommerce_checkout_cart_item_visible', array($this, 'hide_completing_products_from_cart'), 10, 3);
        // add_filter('woocommerce_order_item_visible', array($this, 'hide_completing_products_from_order'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'change_cart_product_price_text'), 10, 3);

        add_action('woocommerce_cart_calculate_fees', array($this, 'calculate_fee_for_stripe'));
        add_action('woocommerce_review_order_before_payment', array($this, 'set_payment_method_update'));

        // add_filter('wc_order_is_editable', array($this, 'custom_order_status_editable'), 9999, 2);

        add_filter('woocommerce_cart_item_quantity', array($this, 'customize_cart_quantity_display'), 10, 3);

        add_action('woocommerce_checkout_create_order_line_item', array($this, 'custom_retreat_data_order_line_item'), 10, 4);

        add_action('woocommerce_thankyou', array($this, 'set_post_order_actions'), 9, 1);
        add_action('woocommerce_thankyou', array($this, 'reset_custom_session_items'), 10, 1);

        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'add_custom_order_columns'));


        add_action('woocommerce_order_status_changed', array($this, 'set_order_status_change'), 10, 4);

        // add_action('woocommerce_before_cart', array($this,'limit_booking_retreat_product_in_cart'));
        // add_action('woocommerce_before_checkout', array($this,'limit_booking_retreat_product_in_cart'));

    }

    function gs_bookings_enqueue_woocommerce_scripts($hook)
    {
        global $post_type;

        // Check if the current page is the WooCommerce product edit page
        if (($hook == 'post.php' || $hook == 'post-new.php') && $post_type == 'product') {
            $product = wc_get_product(get_the_id());
            $retreat_data = get_post_meta($product->get_id(), 'retreat_product_data', true);
            $retreat_duration = !empty($retreat_data['general_info']['retreat_duration']) ? $retreat_data['general_info']['retreat_duration'] : 0;
            $rooms_data = [];
            $blocked_dates = [];
            if (!empty($retreat_data['rooms'])) {
                foreach ($retreat_data['rooms'] as $room_id => $price) {
                    $rooms_data[] = [
                        'name' => get_the_title($room_id),
                        'data' => get_post_meta($room_id, 'package_product_data', true),
                        'id' => $room_id,
                        'price' => $price,
                    ];
                }
            }
            if (!empty($retreat_data['departure_dates'])) {
                foreach ($retreat_data['departure_dates'] as $date => $date_data) {
                    $blocked_dates[] = $date;
                    for ($i = 1; $i < $retreat_duration; $i++) {
                        $date_obj = new DateTime($date);
                        $date_obj->modify('+' . $i . ' days');
                        $blocked_dates[] = $date_obj->format('Y-m-d');
                    }
                }
            }
            wp_enqueue_style('gsbooking-product-type-styles', GSBOOKING_PLUGIN_URL . 'core/includes/assets/css/custom-products-style.css', array(), time(), 'all');
            wp_enqueue_script('gsbooking-product-type-scripts', GSBOOKING_PLUGIN_URL . 'core/includes/assets/js/custom-product-script.js', array('jquery'), time(), false);
            wp_localize_script(
                'gsbooking-product-type-scripts',
                'customVars',
                array(
                    'plugin_name' => __(GSBOOKING_NAME, 'gs-booking'),
                    'rooms_data' => $rooms_data,
                    'product_id' => $product->get_id(),
                    'product_type' => $product->get_type(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'retreat_data' => $retreat_data,
                    'is_published' => $product->get_status() === 'publish',
                    'product_url' => get_permalink($product->get_id()),
                    'checkout_url' => wc_get_checkout_url(),
                    'cart_url' => wc_get_cart_url(),
                    'add_to_cart_url' => $product->add_to_cart_url(),
                    'blocked_dates' => $blocked_dates,
                )
            );

            wp_enqueue_script('qr_generator', 'https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js', array('jquery'), '1.5.0', false);
        }
    }

    function create_product_categories()
    {
        $args = array(
            'description' => 'This is a custom product category for completing products',
            'slug' => 'programs',
            'parent' => 0,
        );
        if (!term_exists('programs', 'product_cat')) {
            wp_insert_term('Programs', 'product_cat', $args);
        }
        $args = array(
            'description' => 'This is a custom product category for completing products',
            'slug' => 'completing-products',
            'parent' => 0,
        );
        if (!term_exists('completing-products', 'product_cat')) {
            wp_insert_term('Completing Products', 'product_cat', $args);
        }
        // child category "second participant" of the parent category "completing-products"
        $args = array(
            'description' => 'This is a custom product category for completing products',
            'slug' => 'second-participant',
            'parent' => get_term_by('slug', 'completing-products', 'product_cat')->term_id,
        );
        if (!term_exists('second-participant', 'product_cat')) {
            wp_insert_term('Second Participant', 'product_cat', $args);
        }

        // child category "extra days" of the parent category "completing-products"
        $args = array(
            'description' => 'This is a custom product category for completing products',
            'slug' => 'extra-days',
            'parent' => get_term_by('slug', 'completing-products', 'product_cat')->term_id,
        );
        if (!term_exists('extra-days', 'product_cat')) {
            wp_insert_term('Extra Days', 'product_cat', $args);
        }

    }

    function set_custom_product_types_tabs($tabs)
    {
        global $product_object;
        $product_id = $product_object->get_id();
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);
        $is_program_product = has_term('programs', 'product_cat', $product_id);

        if (empty($product_object))
            return;
        if ($is_program_product) {
            unset($tabs['shipping']);
            unset($tabs['attribute']);
            unset($tabs['variations']);
            unset($tabs['advanced']);
            unset($tabs['inventory']);

            $tabs['general_info'] = array(
                'label' => __('General Info', 'woocommerce'),
                'target' => 'general_info',
                'priority' => 20, // Adjust the priority to control the tab order
            );
            $tabs['dates'] = array(
                'label' => __('Retreat Dates', 'woocommerce'),
                'target' => 'dates',
                'priority' => 21, // Adjust the priority to control the tab order
            );
            $tabs['rooms'] = array(
                'label' => __('Available Rooms', 'woocommerce'),
                'target' => 'rooms',
                'priority' => 22, // Adjust the priority to control the tab order
            );
        }

        if ($is_completing_product) {
            unset($tabs['shipping']);
            unset($tabs['attribute']);
            unset($tabs['variations']);

            $tabs['completing_product'] = array(
                'label' => __('Completing Product Options', 'woocommerce'),
                'target' => 'completing_product',
                'priority' => 21, // Adjust the priority to control the tab order
            );
        }

        $tabs['procut_qr'] = array(
            'label' => __('QR Code', 'woocommerce'),
            'target' => 'procut_qr',
            'priority' => 25, // Adjust the priority to control the tab order
        );
        return $tabs;
    }

    function custom_product_types_tabs_content()
    {
        global $post;
        $product_id = $post->ID;

        $product = wc_get_product($product_id);
        $product_type = $product->get_type();
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);
        $is_program_product = has_term('programs', 'product_cat', $product_id);

        if ($is_program_product) {
            $this->set_retreat_product_type_tab_content($product_id);
        }
        if ($is_completing_product) {
            $this->set_completing_product_tab_content($product_id);
        }
        $this->set_qr_code_tab_content($product_id);
    }

    private function set_qr_code_tab_content($product_id)
    {
        $product_page_qr_image_url = get_post_meta($product_id, 'product_page_qr_image_url', true);
        $atc_qr_image_url = get_post_meta($product_id, 'atc_qr_image_url', true);
        $active_str = !empty($atc_qr_image_url) ? 'true' : 'false';
        ?>
        <div id="procut_qr" class="panel woocommerce_options_panel custom-tab-content">
            <div class="product-page-wrapper qr-code-wrapper"
                data-active="<?php echo !empty($product_page_qr_image_url) ? 'true' : 'false' ?>">
                <h4>Product Page QR Code</h4>
                <div class="button-wrapepr">
                    <button class="create-qr-code-btn" id="create-product-page-qr-btn" type="button">Create QR Code for Product
                        Page</button>
                </div>
                <div class="qr-image-wrapper product">
                    <?php if (!empty($product_page_qr_image_url)) { ?>
                        <img src="<?php echo $product_page_qr_image_url ?>" alt="Product Page QR Code">
                    <?php } ?>
                </div>
                <div class="action-buttons-wrapper">
                    <button class="download-btn" id="download-product-page-qr-code" type="button">Download QR Code</button>
                    <button class="save-btn" id="save-product-page-qr-code" type="button">Save QR Code</button>
                </div>
            </div>
            <div class="atc-wrapper qr-code-wrapper" data-active="<?php echo $active_str ?>">
                <h4>Add To Cart QR Code</h4>
                <div class="button-wrapepr">
                    <select name="atc_redirect" id="atc-redirect-select">
                        <option value="checkout">Checkout Page</option>
                        <option value="cart">Cart Page</option>
                    </select>
                    <button class="create-qr-code-btn" id="create-atc-qr-btn" type="button">Create Add To Cart QR Code</button>
                </div>
                <div class="qr-image-wrapper atc">
                    <?php if (!empty($atc_qr_image_url)) { ?>
                        <img src="<?php echo $atc_qr_image_url ?>" alt="Add To Cart QR Code">
                    <?php } ?>
                </div>
                <div class="action-buttons-wrapper">
                    <button class="download-btn" id="download-atc-qr-code" type="button">Download QR Code</button>
                    <button class="save-btn" id="save-atc-qr-code" type="button">Save QR Code</button>
                </div>
            </div>
        </div>
    <?php }

    private function set_retreat_product_type_tab_content($product_id)
    {
        $retreat_data = get_post_meta($product_id, 'retreat_product_data', true);
        $general_data = !empty($retreat_data['general_info']) ? $retreat_data['general_info'] : '';
        $rooms_data = !empty($retreat_data['rooms']) ? $retreat_data['rooms'] : [];
        $dates_data = !empty($retreat_data['departure_dates']) ? $retreat_data['departure_dates'] : '';

        $this->set_retreat_general_tab_content($general_data, $rooms_data);
        $this->set_retreat_rooms_tab_content($rooms_data);
        $this->set_retreat_dates_tab_contents($dates_data, $rooms_data, $general_data);
    }

    private function set_retreat_general_tab_content($general_data, $rooms_ids)
    {
        $max_group_size = 0;
        foreach ($rooms_ids as $room_id => $room_price) {
            $room_metadata = get_post_meta($room_id, 'package_product_data', true);
            if (!empty($room_metadata['max_room_capacity'])) {
                $max_group_size += $room_metadata['max_room_capacity'];
            }
        }
        ?>
        <div id="general_info" class="panel woocommerce_options_panel custom-tab-content">
            <div class="general-info-heading">
                <h4 class="general-info-title custom-tab-title">General Info</h4>
            </div>
            <div class="general-info-content custom-tab-data">
                <div class="retreat-duration-wrapper general-info-wrapper">
                    <label for="retreat-duration-input">Retreat Duration</label>
                    <input type="number" name="general_info[retreat_duration]" id="retreat-duration-input"
                        value="<?php echo !empty($general_data['retreat_duration']) ? $general_data['retreat_duration'] : '' ?>">
                </div>
                <div class="group-size-wrapper general-info-wrapper">
                    <p>Group Size Range:</p>
                    <input type="number" name="general_info[min_group_size]" id="min-group-size-input" placeholder="min"
                        value="<?php echo !empty($general_data['min_group_size']) ? $general_data['min_group_size'] : '' ?>">
                    <span>to</span>
                    <input type="number" name="" id="max-group-size-input" disabled placeholder="max"
                        value="<?php echo $max_group_size ?>">
                    <input type="hidden" name="general_info[max_group_size]" value="<?php echo $max_group_size ?>">
                </div>
                <div class="retreat-location-wrapper general-info-wrapper">
                    <p>Retreat Location</p>
                    <input type="text" name="general_info[retreat_address]" id="retreat-address-input"
                        value="<?php echo !empty($general_data['retreat_address']) ? $general_data['retreat_address'] : '' ?>"
                        placeholder="Retrear Address">
                    <input type="text" name="general_info[retreat_location_url]" id="retreat-location-input"
                        value="<?php echo !empty($general_data['retreat_location_url']) ? $general_data['retreat_location_url'] : '' ?>"
                        placeholder="Maps Url">
                </div>
                <div class="calendar-color-wrapper general-info-wrapper">
                    <label for="calendar-color">Color For Calendar</label>
                    <input type="color" name="general_info[calendar_color]" id="calendar-color"
                        value="<?php echo !empty($general_data['calendar_color']) ? $general_data['calendar_color'] : '' ?>">
                </div>
            </div>
        </div>
        <?php
    }

    private function set_retreat_rooms_tab_content($available_rooms = [])
    {
        $all_rooms_ids = get_all_rooms_ids();
        ?>
        <div id="rooms" class="panel woocommerce_options_panel custom-tab-content">
            <div class="rooms-heading">
                <h4 class="rooms-title custom-tab-title">Available Rooms</h4>
            </div>
            <div class="rooms-content custom-tab-data">
                <div class="rooms-checkboxes-wrapper">
                    <?php foreach ($all_rooms_ids as $room_id) {
                        $room_name = get_the_title($room_id);
                        $room_metadata = get_post_meta($room_id, 'package_product_data', true);
                        $is_available_room = array_key_exists($room_id, $available_rooms);
                        $room_price = $is_available_room ? $available_rooms[$room_id] : '';
                        ?>
                        <div class="room-wrapper">
                            <div class="room-checkbox-wrapper">
                                <label for="<?php echo $room_id ?>">
                                    <?php echo $room_name ?>
                                </label>
                                <input type="checkbox" class="available-room-checkbox"
                                    name="rooms[<?php echo $room_id ?>][is_available]" id="<?php echo $room_id ?>" <?php echo $is_available_room ? 'checked' : '' ?>>
                            </div>
                            <div class="room-info">
                                <p class="capacity">
                                    Room Capacity: <span class="number">
                                        <?php echo $room_metadata['max_room_capacity'] ?>
                                    </span> Guests
                                </p>
                            </div>
                            <div class="room-price-wrapper" edit-mode="false">
                                <label for="room-price-<?php echo $room_id ?>">Room Price: </label>
                                <input type="number" class="room-price-input" name="rooms[<?php echo $room_id ?>][price]"
                                    id="room-price-<?php echo $room_id ?>" value="<?php echo $room_price ?>" <?php echo $is_available_room ? 'required' : '' ?>>
                                <p><span class="currency">
                                        <?php echo get_woocommerce_currency_symbol(); ?>
                                    </span><span class="room-price">
                                        <?php echo $room_price ?>
                                    </span></p>
                                <div class="buttons-wrapper">
                                    <button type="button" class="set-room-price-button">Set</button>
                                    <button type="button" class="edit-room-price-button">Edit</button>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function set_retreat_dates_tab_contents($dates_data, $rooms_data, $general_data)
    { ?>
        <div id="dates" class="panel woocommerce_options_panel custom-tab-content">
            <div class="dates-heading">
                <h4 class="dates-title custom-tab-title">Departure Dates</h4>
            </div>
            <div class="dates-content custom-tab-data">
                <div class="date-repeater-container">
                    <div class="dates-wrapper">
                        <?php
                        if (!empty($dates_data)) {
                            $count = 0;
                            foreach ($dates_data as $departure_label => $departure_data) {
                                $dt = new DateTime($departure_label);
                                $tab_date_formatted = $dt->format('F j, Y');
                                $selected_rooms = $departure_data['rooms_list'];
                                ?>
                                <div class="departure-date-wrapper" data-date="<?php echo $departure_label ?>"
                                    data-selected="<?php echo $count == 0 ? 'true' : 'false' ?>" edit-mode="false">
                                    <div class="date-tab">
                                        <input class="has-date date-input" type="text"
                                            name="departure_date[<?php echo $departure_label ?>]"
                                            value="<?php echo $tab_date_formatted ?>" />
                                        <p>
                                            <?php echo $tab_date_formatted; ?>
                                        </p>
                                    </div>
                                    <div class="date-content-wrapper">
                                        <div class="availability-wrapper">
                                            <div class="max-participants-wrapper">
                                                <p>Maximum Participants: <span>
                                                        <?php echo $departure_data['max_participants'] ?>
                                                    </span></p>
                                                <div class="set-max-participants-wrapper edit-info-wrapper">
                                                    <input type="number" name="max_participants" min="1"
                                                        class="max-participants-input edit-input"
                                                        value="<?php echo $departure_data['max_participants'] ?>">
                                                </div>
                                            </div>
                                            <div class="max-second-participants-wrapper">
                                                <p>Maximum Second Participants: <span>
                                                        <?php echo $departure_data['max_second_participants'] ?>
                                                    </span></p>
                                                <div class="set-max-second-participants-wrapper edit-info-wrapper">
                                                    <input type="number" name="max_second_participants"
                                                        class="max-second-participants-input edit-input"
                                                        value="<?php echo $departure_data['max_second_participants'] ?>">
                                                </div>
                                            </div>
                                            <div class="guests-availability-wrapper">
                                                <p>Guests Availability: <span>
                                                        <?php echo $departure_data['guests_availability'] ?>
                                                    </span></p>
                                            </div>
                                            <div class="rooms-availability-wrapper">
                                                <p>Rooms Availability: <span>
                                                        <?php echo $departure_data['rooms_availability'] ?>
                                                    </span></p>
                                            </div>
                                        </div>
                                        <div class="info-wrapper">
                                            <?php if (!empty($departure_data['guests_info'])) { ?>
                                                <div class="guests-list-wrapper">
                                                    <ul class="guests-list">
                                                        <?php foreach ($departure_data['guests_info'] as $guest) { ?>
                                                            <li class="guest-item">
                                                                <?php echo $guest['name'] ?>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php } ?>
                                            <div class="rooms-list-wrapper">
                                                <p>Available Rooms:</p>
                                                <?php if (!empty($selected_rooms)) { ?>
                                                    <ul class="rooms-list">
                                                        <?php foreach ($selected_rooms as $room_id => $room_data) {
                                                            $room_name = get_the_title($room_id);
                                                            $is_booked = !empty($room_data['is_booked']);
                                                            $guests = $room_data['guests'];
                                                            $guests_count = isset($guests) ? count($guests) : 0;
                                                            $room_capacity = $room_data['room_capacity'];
                                                            ?>
                                                            <li class="room-item" data-booked="<?php echo $is_booked ? "true" : "false" ?>">
                                                                <p class="room-name">
                                                                    <?php echo $room_name ?>
                                                                </p>
                                                                <p class="max-capacity">Room Capacity:
                                                                    <span>
                                                                        <?php echo $room_capacity ?>
                                                                    </span>
                                                                </p>
                                                                <p class="number-of-guests">Guests Count:
                                                                    <span>
                                                                        <?php echo $guests_count; ?>
                                                                    </span>
                                                                </p>
                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                <?php } ?>
                                                <ul class="select-room-list">
                                                    <?php
                                                    foreach ($rooms_data as $room_id => $room_price) {
                                                        $room_name = get_the_title($room_id);
                                                        $room_general_data = get_post_meta($room_id, 'package_product_data', true);
                                                        $is_selected = in_array($room_id, array_keys($selected_rooms));
                                                        $is_checked_attr = $is_selected ? 'checked' : '';
                                                        $room_el_id = str_replace(' ', '_', strtolower($room_name));
                                                        $room_price_str = 'price: ' . get_woocommerce_currency_symbol() . number_format($room_price);
                                                        $room_capacity_str = 'room capacity:' . $room_general_data['max_room_capacity'];
                                                        ?>
                                                        <li class="select-room-item">
                                                            <div class="checkbox-wrapper">
                                                                <input type="checkbox" id="<?php echo $room_el_id ?>"
                                                                    data-product="<?php echo $room_id ?>" <?php echo $is_checked_attr; ?>>
                                                                <label for="<?php echo $room_el_id; ?>">
                                                                    <?php echo $room_name ?>
                                                                </label>
                                                            </div>
                                                            <p class="room-capacity">
                                                                <?php $room_capacity_str ?>
                                                            </p>
                                                            <p class="room-price">
                                                                <?php echo $room_price_str ?>
                                                            </p>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="footing-wrapper">
                                            <div class="registration-activation-wrapper">
                                                <span>Activate Registration:</span>
                                                <div class="switch-button-wrapper">
                                                    <input type="checkbox" class="activate-registration-switch"
                                                        id="switch-<?php echo $departure_label ?>" <?php echo !empty($departure_data['registration_active']) ? 'checked' : '' ?> />
                                                    <label for="switch-<?php echo $departure_label ?>"></label>
                                                </div>
                                            </div>
                                            <div class="buttons-wrapper">
                                                <button type="button" class="edit-info-button">Change</button>
                                                <button type="button" class="save-info-button">Save</button>
                                            </div>
                                            <div class="delete-date-wrapper">
                                                <button type="button" class="remove-date-button"
                                                    id="remove_<?php echo $departure_label ?>">Remove Date</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $count++;
                            }
                        } ?>
                    </div>
                    <button type="button" class="add-date-btn">Add Date</button>
                </div>
            </div>
        </div>
        <?php
    }

    private function set_completing_product_tab_content($product_id)
    {
        $enable_multiple_items = get_post_meta($product_id, '_enable_multiple_items', true);
        $limit_quantity = get_post_meta($product_id, '_limit_quantity', true);
        $max_items_limit = get_post_meta($product_id, '_max_items_limit', true);
        ?>
        <div id="completing_product" class="panel woocommerce_options_panel custom-tab-content">
            <div class="completing-product-heading">
                <h4 class="completing-product-title custom-tab-title">Completing Product Options</h4>
            </div>
            <div class="completing-product-content custom-tab-data">
                <div class="enable-multiple-items-wrapper completing-product-wrapper">
                    <input type="checkbox" id="enable_multiple_items" name="enable_multiple_items" value="1" <?php echo checked(1, $enable_multiple_items, false) ?>>
                    <label for="enable_multiple_items">Enable Multiple Items</label>
                </div>
                <div class="limit-quantity-wrapper completing-product-wrapper">
                    <input type="checkbox" id="limit_quantity" name="limit_quantity" value="1" <?php echo checked(1, $limit_quantity, false) ?>>
                    <label for="limit_quantity">Limit Quantity</label>
                </div>
                <div class="max-items-limit-wrapper completing-product-wrapper">
                    <input type="number" id="max_items_limit" name="max_items_limit" min="1"
                        value="<?php echo esc_attr($max_items_limit) ?>">
                    <label for="max_items_limit">Max Items Limit</label>
                </div>
            </div>
        </div>
    <?php }

    function save_custom_product_data($post_id)
    {
        $is_completing_product = has_term('completing-products', 'product_cat', $post_id);
        $is_program_product = has_term('programs', 'product_cat', $post_id);

        if ($is_program_product) {
            $this->save_retreat_custom_data($post_id);
        }

        if ($is_completing_product) {
            $this->save_completing_product_data($post_id);
        }
    }

    private function save_retreat_custom_data($product_id)
    {
        $retreat_data = !empty(get_post_meta($product_id, 'retreat_product_data', true))
            ? get_post_meta($product_id, 'retreat_product_data', true)
            : [];

        if (!empty($_POST['general_info'])) {
            $retreat_data['general_info'] = $_POST['general_info'];
        }
        if (!empty($_POST['rooms'])) {
            $retreat_data['rooms'] = [];
            foreach ($_POST['rooms'] as $room_id => $room_val) {
                if (!empty($room_val['is_available']))
                    $retreat_data['rooms'][$room_id] = $room_val['price'];
            }

        }
        if (!empty($_POST['_participants_info'])) {
            $retreat_data['participants_info'] = wp_kses_post($_POST['_participants_info']);
        }
        if (empty($retreat_data)) {
            $retreat_data['archived_departure_dates'] = [];
        }
        update_post_meta($product_id, 'retreat_product_data', $retreat_data);
    }

    function add_privacy_statement_metabox()
    {
        global $post;

        $product = wc_get_product($post->ID);
        $is_program_product = has_term('programs', 'product_cat', $post->ID);


        if ($product && $is_program_product) {
            add_meta_box(
                'privacy_statement_metabox_id',     // Unique ID for the meta box
                'Privacy Statement',                 // Title of the meta box
                array($this, 'display_privacy_statement_metabox'), // Callback function to display the meta box content
                'product',                           // Post type where the meta box will be added
                'normal',                            // Context (e.g., 'normal', 'advanced', 'side')
                'high'                               // Priority (e.g., 'high', 'core', 'default', 'low')
            );
        }
    }

    function save_privacy_statement_metabox($post_id)
    {
        // Check if the nonce is set
        if (!isset($_POST['privacy_statement_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['privacy_statement_nonce'], 'privacy_statement_nonce')) {
            return;
        }

        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['_privacy_statement'])) {
            $privacy_statement = wp_kses_post($_POST['_privacy_statement']);
            update_post_meta($post_id, '_privacy_statement', $privacy_statement);
        }
    }

    function save_completing_product_data($post_id)
    {
        $enable_multiple_items = isset($_POST['enable_multiple_items']) ? 1 : 0;
        $limit_quantity = isset($_POST['limit_quantity']) ? 1 : 0;
        $max_items_limit = isset($_POST['max_items_limit']) ? absint($_POST['max_items_limit']) : 0;

        update_post_meta($post_id, '_enable_multiple_items', $enable_multiple_items);
        update_post_meta($post_id, '_limit_quantity', $limit_quantity);
        update_post_meta($post_id, '_max_items_limit', $max_items_limit);
    }

    function enable_stock_tab()
    {

        if ('product' != get_post_type()):
            return;
        endif;

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                //for Inventory tab
                jQuery('.inventory_options').addClass('show_if_simple_rental').show();

                jQuery('#inventory_product_data ._sold_individually_field').parent().addClass('show_if_simple_rental').show();
                jQuery('#inventory_product_data ._sold_individually_field').addClass('show_if_simple_rental').show();
            });
        </script>
        <?php

    }
    function register_custom_order_statuses()
    {

        register_post_status(
            'wc-deposit-paid',
            array(
                'label' => 'Deposit Paid',
                'public' => true,
                'show_in_admin_status_list' => true,
                'show_in_admin_all_list' => true,
                'exclude_from_search' => false,
                'label_count' => _n_noop('Deposit Paid (%s)', 'Deposit Paid (%s)')
            )
        );

        register_post_status(
            'wc-deposit-expired',
            array(
                'label' => _x('Deposit Expired', 'Order status', 'text_domain'),
                'public' => true,
                'exclude_from_search' => false,
                'show_in_admin_all_list' => true,
                'show_in_admin_status_list' => true,
                'label_count' => _n_noop('Deposit Expired <span class="count">(%s)</span>', 'Deposit Expired <span class="count">(%s)</span>', 'text_domain')
            )
        );
    }

    function add_custom_order_status($statuses)
    {
        $new_statuses = array();

        foreach ($statuses as $key => $status) {
            $new_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_statuses['wc-deposit-paid'] = 'Deposit Paid';
                $new_statuses['wc-deposit-expired'] = 'Deposit Expired';
                // $new_statuses['wc-deposit-underbooked'] = 'Deposit Underbooked';
            }
        }
        return $new_statuses;
    }
    /*********/

    /****CART****/

    function add_data_to_retreat_cart_item($cart_item_data, $product_id, $variation_id, $quantity)
    {
        $is_program_product = has_term('programs', 'product_cat', $product_id);

        if ($is_program_product) {
            $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
            $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
            $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

            if (!$retreat_id || !$departure_date || !$room_id) {
                return $cart_item_data;
            }

            $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
            $room_price = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['price'];
            $additional_json = isset($_POST['additional']) ? $_POST['additional'] : '';
            $completing_products = $additional_json ? json_decode(stripslashes($additional_json), true) : [];
            $additional = [];
            if (!empty($completing_products)) {
                $upsell_item_data = [
                    'departure_date' => $departure_date,
                    'room_id' => $room_id,
                    'retreat_id' => $retreat_id,
                    'is_hidden' => true,
                ];
                foreach ($completing_products as $upsell_id => $upsell_quantity) {
                    $is_second_participant = has_term('second-participant', 'product_cat', $upsell_id);
                    $upsell_cart_key = WC()->cart->add_to_cart($upsell_id, $upsell_quantity, 0, array(), $upsell_item_data);
                    $additional[$upsell_id] = [
                        'quantity' => $upsell_quantity,
                        'related_item_key' => $upsell_cart_key
                    ];
                    if ($is_second_participant) {
                        $cart_item_data['is_second_participant'] = true;
                    }
                }
            }
            $cart_item_data['retreat_id'] = $retreat_id;
            $cart_item_data['departure_date'] = $departure_date;
            $cart_item_data['room_id'] = $room_id;
            $cart_item_data['room_price'] = $room_price;
            $cart_item_data['additional'] = $additional;
            $cart_item_data['is_deposit'] = $_POST['awcdp_deposit_option'] == 'yes';
            WC()->session->set('is_retreat', true);
            WC()->session->set('retreat_id', $retreat_id);
            WC()->session->set('departure_date', $departure_date);
        }

        return $cart_item_data;
    }

    function update_retreat_remove_from_cart($removed_cart_item_key, $cart)
    {
        $items = $cart->get_cart();
        $is_retreat = false;
        $retreat_id = 0;
        $departure_date = '';
        $removed_item = WC()->cart->get_cart_item($removed_cart_item_key);
        $is_program_product = has_term('programs', 'product_cat', $removed_item['product_id']);
        if ($is_program_product) {
            $additional = $removed_item['additional'];
            if (!empty($additional)) {
                foreach ($additional as $upsell_id => $upsell_data) {
                    $related_item_key = $upsell_data['related_item_key'];
                    WC()->cart->remove_cart_item($related_item_key);
                }
            }
        }
        if (!empty($items)) {
            foreach ($items as $cart_item_key => $cart_item) {
                $is_item_program_product = has_term('programs', 'product_cat', $cart_item['product_id']) && $cart_item_key != $removed_cart_item_key;
                if ($is_item_program_product) {
                    $is_retreat = true;
                    $retreat_id = $cart_item['product_id'];
                    $departure_date = $cart_item['departure_date'];
                    break;
                }
            }
        }
        WC()->session->set('is_retreat', $is_retreat);
        WC()->session->set('retreat_id', $retreat_id);
        WC()->session->set('departure_date', $departure_date);
    }
    function set_cart_item_custom_data($item_data, $cart_item_data)
    {
        if (!empty($cart_item_data['departure_date'])) {
            $date = new DateTime($cart_item_data['departure_date']);
            $formattedDate = $date->format('F j, Y');
            $item_data[] = array(
                'key' => 'Departure Date',
                'value' => $formattedDate
            );
        }
        if (!empty($cart_item_data['room_id'])) {
            $item_data[] = array(
                'key' => 'Room',
                'value' => get_the_title($cart_item_data['room_id'])
            );
        }
        if (!empty($cart_item_data['additional'])) {
            foreach ($cart_item_data['additional'] as $upsell_id => $upsell_data) {
                $product = wc_get_product($upsell_id);
                $product_price = $product->get_price();
                $product_link = get_permalink($upsell_id);
                $product_name = get_the_title($upsell_id);
                $product_name_html = '<a class="completing-product-link" href="' . $product_link . '">' . $product_name . '</a>';
                $quantity = $upsell_data['quantity'];
                // $related_item_key = $upsell_data['related_item_key'];
                // $related_item =  $cart->get_cart_item( $related_item_key );
                $room_id = $cart_item_data['room_id'];
                $retreat_id = $cart_item_data['product_id'];
                $final_price = $product_price * $quantity;
                $cart_item_key = $cart_item_data['key'];

                if (strpos($product_name, '2nd Participant') !== false) {
                    $product_name_html = preg_replace('/<a[^>]*>\K.*?(?=<\/a>)/', '2nd Participant', $product_name_html);
                }

                if ($quantity > 1) {
                    $product_name_html .= ' <span>x</span>' . $quantity;
                }

                $item_data[] = array(
                    'key' => $product_name_html,
                    'value' => get_woocommerce_currency_symbol() . number_format($final_price) . ' <button type="button" class="remove-upsell" data-retreat="' . $retreat_id . '" data-key="' . $cart_item_key . '"  data-product="' . $upsell_id . '" data-room="' . $room_id . '">&#215;</button>'
                );
            }
        }
        if (!empty($cart_item_data['order_id'])) {
            $item_data[] = array(
                'key' => 'Added to order',
                'value' => '#' . $cart_item_data['order_id']
            );
        }
        return $item_data;
    }
    function set_cart_item_name($sprintf, $cart_item, $cart_item_key)
    {
        $is_completing_product = has_term('completing-products', 'product_cat', $cart_item['product_id']);
        $quantity = $cart_item['quantity'];
        $current_name = $cart_item['data']->get_name();

        if ($is_completing_product && $quantity > 1) {
            $new_name = $current_name . ' x ' . $quantity;
            $new_html = preg_replace('/<a[^>]*>\K.*?(?=<\/a>)/', $new_name, $sprintf);
            return $new_html;
        }

        return $sprintf;
    }

    function update_cart_item_price($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['room_price'])) {
                $price = $cart_item['room_price'];
                $cart_item['data']->set_price($price);
            }
        }
    }

    function hide_completing_products_from_cart($visible, $cart_item, $cart_item_key)
    {
        $is_hidden = !empty($cart_item['is_hidden']) ? true : false;
        if ($is_hidden)
            $visible = false;
        return $visible;
    }

    function hide_completing_products_from_order($visible, $order_item)
    {
        $is_hidden = !empty($order_item->get_meta_data('_hidden_item')) ? true : false;
        if ($is_hidden) {
            $visible = false;
        }
        return $visible;
    }

    function change_cart_product_price_text($product_price, $cart_item, $cart_item_key)
    {
        $is_program_product = has_term('programs', 'product_cat', $cart_item['product_id']);
        $cart = WC()->cart;
        if (!$is_program_product)
            return $product_price;

        $completing_products = !empty($cart_item['additional']) ? $cart_item['additional'] : [];
        if (empty($completing_products))
            return $product_price;
        $product_price = $cart_item['room_price'];
        foreach ($completing_products as $upsell_id => $upsell_data) {
            $cart_item_key = $upsell_data['related_item_key'];
            $cart_item_price = $cart->get_cart_item($cart_item_key)['line_subtotal'];
            $product_price += $cart_item_price;
        }

        return get_woocommerce_currency_symbol() . number_format($product_price);
    }
    function set_cart_subtotal($cart_object)
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $init_action = isset($_GET['init_action']) ? sanitize_text_field($_GET['init_action']) : '';
        if (empty($order_id) || empty($init_action) || $init_action !== 'deposit')
            return;

        $order = wc_get_order($order_id);
        $deposit_paid = $order->get_meta('deposit_payment');

        $cart_object->subtotal = $cart_object->get_cart_contents_total() + $deposit_paid;

    }
    function limit_booking_retreat_product_in_cart($cart)
    {
        // Check if the 'booking_retreat' product type is already in the cart
        $is_booking_retreat_in_cart = false;

        foreach ($cart->get_cart() as $cart_item) {
            $product = wc_get_product($cart_item['product_id']);
            $is_program_product = has_term('programs', 'product_cat', $product->get_id());
            if ($product && $is_program_product) {
                $is_booking_retreat_in_cart = true;
                break;
            }
        }

        // If 'booking_retreat' is already in the cart, prevent adding another one
        if ($is_booking_retreat_in_cart) {
            wc_add_notice(__('Only one Retreat at a time.', 'eleusinia'), 'error');
        }
    }

    /****CHECKOUT*****/
    function set_participants_details($checkout)
    {
        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $items = WC()->cart->get_cart();
        $prticipants = [];
        $completing_product_participants = [];
        foreach ($items as $item_key => $item) {
            $product = wc_get_product($item['product_id']);
            $is_program_product = has_term('programs', 'product_cat', $item['product_id']);
            $is_completing_product = has_term('completing-products', 'product_cat', $item['product_id']);
            $is_completing_product_second_participant = has_term('second-participant', 'product_cat', $item['product_id']);
            if ($is_program_product) {
                $room_id = $item['room_id'];
                $prticipants[$room_id] = [
                    'quantity' => 1,
                    'cart_item_key' => $item_key,
                ];

                if (!empty($item['is_second_participant'])) {
                    $prticipants[$room_id]['quantity']++;
                }
            }
            if ($is_completing_product && $is_completing_product_second_participant) {
                if (!empty($item['order_id'])) {
                    $completing_product_participants[$item['order_id']] = [
                        'room_id' => $item['room_id'],
                        'cart_item_key' => $item_key,
                    ];
                }
            }
        }
        if (empty($prticipants) && empty($completing_product_participants))
            return;
        ?>
        <div class="participants-details-container">
            <h5 class="participants-heading">Set Participants Details</h5>
            <div class="participants-content">
                <?php if (!empty($prticipants)) { ?>
                    <?php foreach ($prticipants as $room_id => $details) {
                        $room_name = get_the_title($room_id);
                        $checkbox_id = 'checkbox_' . $room_id;
                        $quantity = $details['quantity'];
                        $cart_item_key = $details['cart_item_key'];
                        ?>
                        <div class="room-participants-wrapper">
                            <div class="room-participants-heading">
                                <p class="room-participants-title">
                                    <strong>
                                        <?php echo $room_name ?>
                                    </strong> Room Geuests
                                </p>

                            </div>
                            <?php for ($i = 0; $i < $quantity; $i++) { ?>
                                <div class="participant-details-content">
                                    <?php if ($i > 0) { ?>
                                        <div class="input-wrapper type-checkbox">
                                            <input type="checkbox" class="same-details-checkbox"
                                                name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][same_details]" value="1"
                                                id="<?php echo $checkbox_id ?>">
                                            <label for="<?php echo $checkbox_id ?>">Same Email & Phone</label>
                                        </div>
                                    <?php } ?>
                                    <p>details for guest
                                        <?php echo $i + 1 ?>
                                    </p>
                                    <div class="input-group-wrapper">
                                        <div class="input-wrapper type-text participant-name-wrapper">
                                            <input type="text" class="participant_name"
                                                name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][name]"
                                                placeholder="Full Name" required data-room="<?php echo $room_id ?>"
                                                data-idx="<?php echo $i ?>">
                                        </div>
                                        <div class="input-wrapper type-text participant-email-wrapper">
                                            <input type="email" class="participant_email"
                                                name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][email]"
                                                placeholder="Email" required data-room="<?php echo $room_id ?>" data-idx="<?php echo $i ?>">
                                        </div>
                                        <div class="input-wrapper type-text participant-phone-wrapper">
                                            <input type="text" class="participant_phone"
                                                name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][phone]"
                                                placeholder="Phone Number" required data-room="<?php echo $room_id ?>"
                                                data-idx="<?php echo $i ?>">
                                        </div>
                                        <input type="hidden" name="participants[<?php echo $cart_item_key ?>][<?php echo $i ?>][room_id]"
                                            value="<?php echo $room_id ?>">
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php }
                } ?>
                <?php if (!empty($completing_product_participants)) { ?>
                    <?php foreach ($completing_product_participants as $order_id => $item_data) { ?>
                        <div class="room-participants-wrapper">
                            <div class="room-participants-heading">
                                <p class="room-participants-title">
                                    additional guest's details for
                                    <strong>
                                        <?php echo get_the_title($item_data['room_id']) ?>
                                    </strong> Room on order #<?php echo $order_id ?>
                                </p>
                            </div>
                            <div class="participant-details-content">
                                <div class="input-group-wrapper">
                                    <div class="input-wrapper type-text participant-name-wrapper">
                                        <input type="text" class="participant_name"
                                            name="participants[<?php echo $item_data['cart_item_key'] ?>][1][name]"
                                            placeholder="Full Name" required data-room="<?php echo $room_id ?>" data-idx="0">
                                    </div>
                                    <div class="input-wrapper type-text participant-email-wrapper">
                                        <input type="email" class="participant_email"
                                            name="participants[<?php echo $item_data['cart_item_key'] ?>][1][email]" placeholder="Email"
                                            required data-room="<?php echo $room_id ?>" data-idx="0">
                                    </div>
                                    <div class="input-wrapper type-text participant-phone-wrapper">
                                        <input type="text" class="participant_phone"
                                            name="participants[<?php echo $item_data['cart_item_key'] ?>][1][phone]"
                                            placeholder="Phone Number" required data-room="<?php echo $room_id ?>" data-idx="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    <?php }

    function validate_custom_checkout_field()
    {
        $cart_items = WC()->cart->get_cart();
        // $all_keys = array_keys($cart_items);
        $participants = $_POST['participants'];
        $errors = [];
        $prev_item_key = '';
        foreach ($participants as $cart_item_key => $participant) {
            $room_id = $cart_items[$cart_item_key]['room_id'];
            $room_name = get_the_title($room_id);
            foreach ($participant as $idx => $data) {
                $name = $data['name'];
                $email = $data['email'];
                $phone = $data['phone'];
                $is_same_details = !empty($data['same_details']);

                if (empty($name)) {
                    $errors[] = 'Please fill the name field for ' . $room_name . ' Room Guest ' . ($idx + 1);
                }
                if (!$is_same_details) {
                    if (empty($email) || empty($phone)) {
                        $errors[] = 'Please fill all the fields for ' . $room_name . ' Room Guest ' . ($idx + 1);
                    }
                }
            }
            $prev_item_key = $cart_item_key;
        }
        foreach ($cart_items as $cart_item) {
            $product_id = $cart_item['product_id'];
            $is_program_product = has_term('programs', 'product_cat', $product_id);

            if ($is_program_product) {
                $room_id = $cart_item['room_id'];
                $departure_date = $cart_item['departure_date'];
                $retreat_data = get_post_meta($product_id, 'retreat_product_data', true);
                $is_room_available = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] === 'available';
                if (!$is_room_available) {
                    $errors[] = 'Sorry, is seems that the room "' . get_the_id($room_id) . '" is no longer available for this date';
                }
            }
        }
        if (!empty($errors)) {
            wc_add_notice(implode('<br>', $errors), 'error');
        }
    }
    function customize_cart_quantity_display($product_quantity, $cart_item_key, $cart_item)
    {
        // Check if the product type is 'booking_retreat'
        $is_program_product = has_term('programs', 'product_cat', $cart_item['product_id']);
        $is_completing_product = has_term('completing-products', 'product_cat', $cart_item['product_id']);
        $hide_quantity = $is_program_product || $is_completing_product;
        // if ($is_completing_product) {
        //     $enable_multiple_items = !empty (get_post_meta($cart_item['product_id'], '_enable_multiple_items', true));
        //     if(!$enable_multiple_items) $hide_quantity = true;

        // }
        if ($hide_quantity) {
            return '';
        }

        // For other product types, return the default quantity display
        return $product_quantity;
    }
    /*****ORDER*****/
    function custom_order_status_editable($allow_edit, $order)
    {
        if ($order->get_status() === 'deposit-paid') {
            $allow_edit = true;
            $expiration_time = $order->get_meta('expiration_time');
            $has_reservation_expired = strtotime($expiration_time) - strtotime('now') <= 0;
            if ($has_reservation_expired) {
                // $order->update_status('processing');
                // $allow_edit = false;
            }
        }
        return $allow_edit;
    }
    function custom_retreat_data_order_line_item($item, $cart_item_key, $values, $order)
    {
        $product_id = $item->get_product_id();
        $order_rooms = !empty($order->get_meta('rooms')) ? $order->get_meta('rooms') : [];
        $order_guests = !empty($order->get_meta('guests')) ? $order->get_meta('guests') : [];
        $order_id = $order->get_id();
        $is_program_product = has_term('programs', 'product_cat', $product_id);
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);
        $deposit_data = $item->get_meta('awcdp_deposit_meta');
        $is_room_deposit = !empty($deposit_data);
        if ($is_program_product) {
            $billing_email = $order->get_billing_email();
            $room_id = $values['room_id'];
            $room_price = $values['room_price'];
            $departure_date = $values['departure_date'];
            $participants_data = $_POST['participants'][$cart_item_key];
            $additional_products = $values['additional'];
            $retreat_data = get_post_meta($product_id, 'retreat_product_data', true);
            $duration = $retreat_data['general_info']['retreat_duration'];
            $retreat_dates = format_date_range($departure_date, $duration);

            $item->add_meta_data('Room', get_the_title($room_id));
            $item->add_meta_data('Retreat Dates', $retreat_dates);
            if (!empty($additional_products)) {
                $added_items_str = '';
                foreach ($additional_products as $additional_product_id => $additional_product_data) {
                    $additional_product = wc_get_product($additional_product_id);
                    $additional_product_quantity = $additional_product_data['quantity'];
                    $product_name = $additional_product->get_name();
                    empty($added_items_str)
                        ? $added_items_str .= $product_name
                        : $added_items_str .= ', ' . $product_name;
                    $item->add_meta_data($product_name, $additional_product_quantity);
                }
                $item->add_meta_data('Addons', $added_items_str);
            }

            foreach ($participants_data as $participant_idx => $participant_data) {
                $participant_data['order_id'] = $order_id;
                $participant_data['main_participant'] = $participant_data['email'] === $billing_email;
                $participant_data['retreat_id'] = $product_id;
                $participant_data['departure_date'] = $departure_date;
                $participant_data['order'] = $participant_idx;
                $participants_data[$participant_idx] = $participant_data;
                $order_guests[] = $participant_data;
                $item->add_meta_data('Guest ' . ($participant_idx + 1), $participant_data['name']);
            }
            $order_rooms[] = [
                'room_id' => $room_id,
                'room_price' => $room_price,
                'additional' => $additional_products,
                'is_deposit' => $is_room_deposit,
                'deposit_data' => $deposit_data,
                'payment_completed' => false,
                'guests' => $participants_data,
            ];

            $order->update_meta_data('rooms', $order_rooms);
            $order->update_meta_data('guests', $order_guests);
            $order->update_meta_data('retreat_id', $product_id);
            $order->update_meta_data('departure_date', $departure_date);
            $order->update_meta_data('retreat_data_updated', false);
            $order->update_meta_data('scheduled_order_messages', false);
            $order->update_meta_data('scheduled_retreat_messages', false);
        }
        if ($is_completing_product) {
            $is_hidden = !empty($values['is_hidden']);
            $is_second_participant = has_term('second-participant', 'product_cat', $product_id);
            $addon_room_id = $values['room_id'];
            $addon_departure_date = $values['departure_date'];
            $addon_data = [
                'room_id' => $addon_room_id,
                'departure_date' => $addon_departure_date,
                'is_previous_order' => !$is_hidden,
                'is_second_participant' => $is_second_participant,
            ];
            if (!$is_hidden) {
                $addon_data['order_id'] = $values['order_id'];
                $addon_data['retreat_id'] = $values['retreat_id'];
                $item->add_meta_data('Addon for Order', '#' . $values['order_id']);
                $item->add_meta_data('Retreat', get_the_title($values['retreat_id']));
                if ($is_second_participant) {
                    $addon_guest_details = $_POST['participants'][$cart_item_key][1];
                    $addon_guest_details['retreat_id'] = $values['retreat_id'];
                    $addon_guest_details['departure_date'] = $addon_departure_date;
                    $addon_guest_details['main_participant'] = false;
                    $addon_data['guest'] = $addon_guest_details;
                    $order_guests[] = $addon_guest_details;
                    $order->update_meta_data('guests', $order_guests);
                }
            }
            $item->add_meta_data('Room', get_the_title($addon_room_id));
            $item->add_meta_data('Departure Date', date('F j, Y', strtotime($addon_departure_date)));
            $item->add_meta_data('addon_data', $addon_data);
        }
        $order->save();
    }
    function set_post_order_actions($order_id)
    {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        $parent_id = $order_data['parent_id'];
        $ref_order_id = !empty($parent_id) ? $parent_id : $order_id;
        $ref_order = wc_get_order($ref_order_id);
        $is_retreat_order = !empty($ref_order->get_meta('retreat_id'));
        $addons_updated = !empty($order->get_meta('addons_updated'));
        $is_invoice_sent = !empty($order->get_meta('invoice_sent'));
        $scheduled_retreat_message = !empty($ref_order->get_meta('scheduled_retreat_messages'));
        $deposit_payment_det = !empty($order->get_meta('_awcdp_deposits_payment_det'))
            ? $order->get_meta('_awcdp_deposits_payment_det')
            : [];

        if ($is_retreat_order) {
            $scheduled_order_message = !empty($ref_order->get_meta('scheduled_order_messages'));
            $retreat_data_updated = !empty($ref_order->get_meta('retreat_data_updated'));
            $is_deposit_payment = $ref_order->get_status() === 'deposit-paid' || $ref_order->get_status() === 'partially-paid';
            if ($is_deposit_payment) {
                $this->set_deposit_order_limits($ref_order_id);
            }
            if (!$scheduled_order_message) {
                $this->set_order_emails($ref_order_id);
                schedule_order_emails($ref_order_id);
                $ref_order->update_meta_data('scheduled_order_messages', true);
            }
            if (!$scheduled_retreat_message) {
                schedule_retreat_emails($ref_order_id);
                $ref_order->update_meta_data('scheduled_retreat_messages', true);
            }
            if (!$retreat_data_updated) {
                update_retreat_data($ref_order_id, $deposit_payment_det);
                $ref_order->update_meta_data('retreat_data_updated', true);
            }
            $ref_order->update_meta_data('is_deposit_payment', $is_deposit_payment);
        } else {
            if (!$is_invoice_sent) {
                WC()->mailer()->customer_invoice($order);
                $order->update_meta_data('invoice_sent', true);
            }
            if (!$addons_updated) {
                update_order_addons($ref_order_id);
                $ref_order->update_meta_data('addons_updated', true);
            }
        }
        $ref_order->save();
    }
    function set_deposit_order_limits($order_id)
    {
        $deposit_limits = get_option('reservation_limits');
        if (empty($deposit_limits))
            return;

        $order = wc_get_order($order_id);
        $order_time = $order->get_date_created();
        $departure_date = $order->get_meta('departure_date');
        $order_date_time = new DateTime($order_time->date('Y-m-d'));
        $departure_date_time = new DateTime($departure_date);
        $order_messages = !empty($order->get_meta('order_messages')) ? $order->get_meta('order_messages') : [];

        foreach ($deposit_limits as $idx => $limit_item) {
            $conditions = $limit_item['conditions'];
            $action = $limit_item['action'];
            $messages = $limit_item['messages'];
            $is_valid = false;
            $logical_operator = '';
            $conditions_validation_arr = [];
            foreach ($conditions as $condition) {
                $difference = intval($departure_date_time->diff($order_date_time, true)->format('%R%a'));
                $units_number = $condition['condition_units'];
                $units_type = $condition['condition_type'];
                $operator = $condition['condition_operator'];
                $logical_operator = isset($condition['condition_logic']) && empty($logical_operator)
                    ? $condition['condition_logic']
                    : $logical_operator;

                switch ($units_type) {
                    case 'hours':
                        $difference *= 24;
                        break;
                    case 'weeks':
                        $difference /= 7;
                        break;
                    case 'months':
                        $difference /= 30;
                }

                switch ($operator) {
                    case '<':
                        $conditions_validation_arr[] = $difference < $units_number;
                        break;
                    case '>':
                        $conditions_validation_arr[] = $difference > $units_number;
                        break;
                    case '=':
                        $conditions_validation_arr[] = $difference == $units_number;
                        break;
                    case '<=':
                        $conditions_validation_arr[] = $difference <= $units_number;
                        break;
                    case '>=':
                        $conditions_validation_arr[] = $difference >= $units_number;
                        break;
                }
            }
            $is_valid = $logical_operator == '&&'
                ? every($conditions_validation_arr, true)
                : some($conditions_validation_arr, true);
            if (!$is_valid)
                continue;
            $time_units_number = $action['time_units'];
            $time_units_type = $action['time_type'];
            $time_reference = $action['time_reference'];
            $expiration_time = '';

            switch ($time_reference) {
                case 'order':
                    $expiration_time = $order_time->modify('+' . $time_units_number . ' ' . $time_units_type);
                    break;
                case 'departure':
                    $expiration_time = $departure_date_time->modify('-' . $time_units_number . ' ' . $time_units_type);
                    break;
            }

            $order->update_meta_data('expiration_time', $expiration_time->format('Y-m-d H:i:s'));
            if (empty($messages))
                continue;

            foreach ($messages as $message_id => $message_data) {
                $is_checked = !empty($message_data['is_checked']);
                if (!$is_checked)
                    continue;
                $send_on_expire = !empty($message_data['send_on_expire']);
                if ($send_on_expire) {
                    $order_messages[$message_id][] = $expiration_time->getTimestamp();
                }
                $schedule_before_expire = !empty($message_data['schedule_before_expire']);
                if ($schedule_before_expire) {
                    $days_before_expire = $message_data['days_before_expire'];
                    $hour = $message_data['time_before_expire'];
                    $scheduled_time = strtotime('-' . $days_before_expire . ' days ' . $hour, $expiration_time->getTimestamp());
                    $order_messages[$message_id][] = $scheduled_time;
                }
            }

        }
        $order->update_meta_data('order_messages', $order_messages);
        $order->save();
        schedule_order_expiration($order_id);
    }
    function set_order_emails($order_id)
    {
        $order = wc_get_order($order_id);
        $order_messages = !empty($order->get_meta('order_messages')) ? $order->get_meta('order_messages') : [];
        $all_order_messages_ids = get_all_order_messages_ids();
        $departure_date = $order->get_meta('departure_date');
        $order_status = $order->get_status();
        foreach ($all_order_messages_ids as $message_id) {
            $message_data = get_post_meta($message_id, 'order_message_data', true);
            $message_statuses = $message_data['order_statuses'];
            if (empty($message_statuses))
                continue;
            $is_message_for_order = in_array($order_status, $message_statuses) || in_array('wc-' . $order_status, $message_statuses);
            $schedule = $message_data['schedule'];

            if (!$is_message_for_order)
                continue;

            foreach ($schedule as $event => $time) {
                $relating_time = date('Y-m-d H:i:s');
                $calc = '+';
                $is_scheduled = !empty($time['is_scheduled']);
                $days = !empty($time['days']) ? $time['days'] : '';
                $hour = !empty($time['time']) ? $time['time'] : '';
                if (!$is_scheduled)
                    continue;

                switch ($event) {
                    case 'booking_event':
                        $relating_time = strtotime($order->get_date_created());
                        break;
                    case 'booking':
                        $relating_time = strtotime($order->get_date_created());
                        break;
                    case 'before':
                        $calc = '-';
                        $relating_time = strtotime($departure_date);
                        break;
                }

                $scheduled_time = strtotime($calc . $days . ' days ' . $hour, $relating_time);
                if ($scheduled_time < time()) {
                    $scheduled_time = time();
                }
                if ($event === 'booking_event') {
                    send_order_message_template($order_id, $message_id, $order->get_order_key());
                } else {
                    $order_messages[$message_id][] = $scheduled_time;
                }
            }
        }
        $order->update_meta_data('order_messages', $order_messages);
        $order->save();
    }
    function set_order_status_change($order_id, $old_status, $new_status, $order)
    {
        $is_payment_completed = $new_status === 'processing' || $new_status === 'completed';
        $is_second_payment = $is_payment_completed && $old_status === 'partially-paid';
        $is_cancelled = $new_status === 'cancelled';
        $was_cancelled = $old_status === 'cancelled';
        $set_order_actions = false;

        if ($is_second_payment || $is_cancelled || $was_cancelled) {
            unschedule_order_emails($order_id);
            unschedule_order_expiration($order_id);
            $order->update_meta_data('retreat_data_updated', false);
            $order->update_meta_data('scheduled_order_messages', false);
            $order->update_meta_data('scheduled_retreat_messages', false);
            $order->update_meta_data('expiration_time', '');
            $order->update_meta_data('is_deposit_payment', false);
            $order->update_meta_data('order_messages', []);
            $order->save();
            $set_order_actions = true;
        }

        if ($new_status == 'on-hold' || $new_status == 'pending' || $new_status == 'failed' || $new_status == 'refunded') {
            $set_order_actions = false;
        }

        if ($set_order_actions) {
            // $this->set_post_order_actions($order_id);
        }
    }
    function reset_custom_session_items($order_id)
    {
        // check if cart is empty
        if (WC()->cart->is_empty()) {
            WC()->session->__unset('is_retreat');
            WC()->session->__unset('retreat_id');
            WC()->session->__unset('departure_date');
        }
    }
    function calculate_fee_for_stripe()
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        if ($chosen_payment_method == 'stripe_cc') {
            $total_amount = WC()->cart->subtotal;
            $fee = $total_amount * 0.029 + 0.3;

            WC()->cart->add_fee(__('Service Fee', 'eleusinia'), $fee);
        }
        if ($chosen_payment_method == 'stripe_googlepay') {
            $total_amount = WC()->cart->subtotal;
            $fee = $total_amount * 0.029 + 0.3;

            WC()->cart->add_fee(__('Service  Fee', 'eleusinia'), $fee);
        }
    }
    function set_payment_method_update()
    { ?>
        <script type="text/javascript">
            (function ($) {
                $('form.checkout').on('change', 'input[name^="payment_method"]', function () {
                    $('body').trigger('update_checkout');
                });
            })(jQuery);
        </script>
        <?php
    }

    function add_custom_order_columns($order)
    {
        $is_deposit_payment = $order->get_meta('is_deposit_payment');
        ?>
        </div>
        <?php if (!empty($is_deposit_payment)) {
            $time_to_limit_reservation = $order->get_meta('time_to_limit_reservation');
            $expiration_time = $order->get_meta('expiration_time');
            $has_reservation_countdown_started = strtotime($time_to_limit_reservation) - strtotime('now') <= 0;
            $has_reservation_expired = strtotime($expiration_time) - strtotime('now') <= 0;
            ?>
            <div class="order_data_columm deposit-data-container">
                <h3>Deposit Payment</h3>
                <div class="payments-wrapper">
                    <p class="amount-paid"><strong>Amount Paid:</strong>
                        <?php echo get_woocommerce_currency_symbol() . number_format($order->get_meta('_awcdp_deposits_deposit_amount')); ?>
                    </p>
                    <p><strong>Remaining Amount:</strong>
                        <?php echo get_woocommerce_currency_symbol() . number_format($order->get_meta('_awcdp_deposits_second_payment')); ?>
                    </p>
                    <p><strong>Payment Link:</strong> <a href="<?php echo $order->get_checkout_payment_url() ?>" target="_blank">
                            <?php echo $order->get_checkout_payment_url() ?>
                        </a>
                    <p><strong>Reservation Date:</strong>
                        <?php echo $order->get_date_created()->format('F j, Y') ?>
                    </p>
                    <?php if (!$has_reservation_expired) { ?>
                        <p><strong>Expires At:</strong>
                            <?php echo date('F j, Y H:i', strtotime($expiration_time)) ?>
                        </p>
                    <?php } ?>
                    <?php if ($has_reservation_countdown_started && !$has_reservation_expired) { ?>
                        <p><strong>Time Until Expired:</strong>
                        <div id="expiration-countdown">
                            <?php echo calculate_time_left_with_html(date('Y-m-d H:i:s'), $expiration_time) ?>
                        </div>
                        </p>
                    <?php } else { ?>
                        <p><strong>Reservation Has Expired On:</strong>
                            <?php echo date('F j, Y H:i', strtotime($expiration_time)) ?>
                        </p>
                    <?php } ?>
                </div>
            </div>
        <?php }
    }

    function display_privacy_statement_metabox($post)
    {
        // Retrieve and display your privacy statement content or custom fields here
        $privacy_statement = get_post_meta($post->ID, '_privacy_statement', true);

        // Output the WYSIWYG editor
        wp_editor(
            $privacy_statement,          // Current content of the editor
            '_privacy_statement',        // Name of the textarea and the associated meta key
            array(
                'textarea_name' => '_privacy_statement', // Important for saving the data
                'media_buttons' => true,                  // Display media upload buttons
                'teeny' => false,                         // Use the full editor
                'textarea_rows' => 10,                    // Number of rows for the textarea
            )
        );

        // Add nonce field for security
        wp_nonce_field('privacy_statement_nonce', 'privacy_statement_nonce');
    }
    function enqueue_payment_gateway_scripts()
    {
        wp_enqueue_script('wc-checkout');
        wp_enqueue_scripts('wc-payment-gateway');
    }
}


add_filter('woocommerce_loop_add_to_cart_link', 'ij_replace_add_to_cart_button', 10, 2);
function ij_replace_add_to_cart_button($button, $product)
{

    $productid = $product->id;
    $productslug = $product->slug;
    $productname = $product->name;

    if (is_product_category() || is_shop()) {

        $button_text = __("More Info", "woocommerce");
        $button_link = $product->get_permalink();
        $button = '<a href="' . $button_link . '" >' . $button_text . ' </a>';
        return $button;
    }
}


add_action('woocommerce_before_shop_loop_item', 'bbloomer_customize_single_upsells');

function bbloomer_customize_single_upsells()
{
    global $woocommerce_loop;
    if ($woocommerce_loop['name'] == 'up-sells') {
        // remove add to cart button
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }
}

function my_text_strings($translated_text, $text, $domain)
{
    $is_cart_page = str_replace('/', '', $_SERVER['REQUEST_URI']) == 'cart';
    if ($is_cart_page) {
        $is_first_appearance = empty($_SESSION['is_first_appearance']);
        if ($text == 'Subtotal' && $is_first_appearance) {
            $translated_text = __('Room Subtotal', 'woocommerce');
            $_SESSION['is_first_appearance'] = true;
        }
    } else {
        $translated_text = $text;
    }
    return $translated_text;
}
add_filter('gettext', 'my_text_strings', 20, 3);
