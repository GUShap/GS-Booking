<?php

class GS_Booking_Backend
{
    // Constructor to initialize the class
    public function __construct()
    {
        // Hook the function to register the custom post type to the init action
        add_action('init', array($this, 'register_retreat_rooms_post_type'), 10, 0);
        add_action('init', array($this, 'register_retreat_messages_post_type'), 11, 0);
        add_action('init', array($this, 'register_order_messages_post_type'), 12, 0);

        // Hook the main menu page function to the admin_menu action
        add_action('admin_menu', array($this, 'gs_bookings_menu_page'));

        add_action('add_meta_boxes', array($this, 'add_retreat_rooms_options_metabox'));
        add_action('add_meta_boxes', array($this, 'add_retreat_rooms_gallery_metabox'));

        add_action('add_meta_boxes', array($this, 'add_retreat_messages_metabox'));
        add_action('add_meta_boxes', array($this, 'add_order_messages_metabox'));

        // Hook the method to save custom data when a "Retreat Room" post is saved
        add_action('save_post', array($this, 'set_cpt_data'));
        add_action('admin_init', array($this, 'save_options_data'));
    }

    // Add the main menu page
    function gs_bookings_menu_page()
    {
        add_menu_page(
            'GS Bookings',              // Page title
            'GS Bookings',              // Menu title
            'manage_options',           // Capability required to access the menu item
            'gs_bookings_page',         // Menu slug (unique identifier)
            array($this, 'display_admin_page'),  // Callback function to display the page
            'dashicons-calendar',       // Icon URL or Dashicon class
            30                          // Position in the menu
        );

        // Add a submenu page for the custom post type "Retreats Archive"
        // $all_future_retreats_ids = get_all_retreats_ids();
        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Retreats Management',                  // Page title
            'Retreats Management',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'retreats-manage',
            function () {
                $this->display_retreats_manage(get_all_retreats_ids(), false);
            },
        );

        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Retreats Archive',                  // Page title
            'Retreats Archive',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'retreat-archive',
            function () {
                $this->display_retreats_manage(get_all_retreats_ids(), true);
            },
        );

        add_submenu_page(
            'gs_bookings_page',         // Parent menu slug
            'Options',                  // Page title
            'Options',         // Menu title
            'manage_options',           // Capability required to access the submenu item
            'options',
            array($this, 'display_options'),  // Callback function to display the page
        );

    }

    // Callback function to display the main menu page
    function display_admin_page()
    {
        // Your main menu page content goes here
        echo '<div class="wrap">';
        echo '<h1>GS Bookings Main Page</h1>';
        echo '<p>Main menu page content goes here.</p>';
        echo '</div>';
    }
    /* Rooms CPT */
    // Register the custom post type "Retreat Rooms"
    function register_retreat_rooms_post_type()
    {
        $labels = array(
            'name' => 'Rooms',
            'singular_name' => 'Retreat Room',
            'menu_name' => 'Rooms',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Retreat Room',
            'edit_item' => 'Edit Retreat Room',
            'new_item' => 'New Retreat Room',
            'view_item' => 'View Retreat Room',
            'search_items' => 'Search Retreat Rooms',
            'not_found' => 'No Retreat Rooms found',
            'not_found_in_trash' => 'No Retreat Rooms found in Trash',
            'parent_item_colon' => 'Parent Retreat Room:',
            'all_items' => 'Rooms',
            'archives' => 'Retreat Room Archives',
            'insert_into_item' => 'Insert into Retreat Room',
            'uploaded_to_this_item' => 'Uploaded to this Retreat Room',
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'public' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'menu_position' => 10,  // Adjust the position as needed
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'query_var' => true,
            'rewrite' => array('slug' => 'retreat_rooms'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-hammer',  // Customize the icon as needed
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('retreat_rooms', $args);
    }
    public function add_retreat_rooms_gallery_metabox()
    {
        add_meta_box(
            'retreat_rooms_gallery_metabox',
            'Gallery',
            array($this, 'retreat_rooms_gallery_metabox_content'),
            'retreat_rooms',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    public function retreat_rooms_gallery_metabox_content($post)
    {
        // Get existing gallery images
        $gallery_images = get_post_meta($post->ID, 'room_gallery', true);
        ?>
                                <p>
                                    <label for="gallery_images">Gallery Images:</label>
                                    <input type="button" class="button button-secondary" value="Select Images" onclick="uploadGalleryImages();">
                                    <input type="hidden" name="gallery_images" id="gallery_images" value="<?php echo esc_attr($gallery_images); ?>">
                                    <ul id="gallery-preview">
                                        <?php if (!empty($gallery_images)) { ?>
                                                            <?php foreach ($gallery_images as $image_label => $image_id) { ?>
                                                                            <li class="gallery-item">
                                                                                <button type="button" class="remove-image-button">&#215;</button>
                                                                                <?php echo wp_get_attachment_image($image_label, 'thumbnail') ?>
                                                                                <input type="hidden" id="image-input-<?php echo $image_label ?>" name="gallery[<?php echo $image_label ?>]"
                                                                                    value="<?php echo $image_label ?>">
                                                                            </li>
                                                            <?php } ?>
                                        <?php } ?>
                                    </ul>
                                </p>
                <?php }

    // Function to add the custom metabox
    public function add_retreat_rooms_options_metabox()
    {
        add_meta_box(
            'retreat_rooms_metabox',
            'Retreat Room Options',
            array($this, 'retreat_rooms_metabox_content'),
            'retreat_rooms',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    public function retreat_rooms_metabox_content($post)
    {
        // Use the post ID to get the product data
        $room_id = $post->ID;
        $this->set_package_product_type_tab_content($room_id);
    }
    public function set_cpt_data($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['post_type']) && $_POST['post_type'] == 'retreat_rooms') {
            $this->save_package_custom_data($post_id);
        }
        if (isset($_POST['post_type']) && $_POST['post_type'] == 'retreat_messages') {
            $this->save_retreat_message_data($post_id);
        }
        if (isset($_POST['post_type']) && $_POST['post_type'] == 'order_messages') {
            $this->save_order_message_data($post_id);
        }
    }

    private function set_package_product_type_tab_content($room_id)
    {
        $product_custom_data = get_post_meta($room_id, 'package_product_data', true);
        $max_room_capacity = !empty($product_custom_data['max_room_capacity']) ? $product_custom_data['max_room_capacity'] : '';
        $room_color = !empty($product_custom_data['room_color']) ? $product_custom_data['room_color'] : '';
        $beds = !empty($product_custom_data['beds']) ? $product_custom_data['beds'] : '';
        $amenities = !empty($product_custom_data['amenities']) ? $product_custom_data['amenities'] : '';
        ?>
                                <div id="package_attr" class="panel woocommerce_options_panel custom-tab-content">
                                    <div class="package-attr-headline">
                                        <h4 class="attr-title custom-tab-title">Package Attributes</h4>
                                    </div>
                                    <div class="package-attr-content custom-tab-data">
                                        <section class="general-info-wrapper">
                                            <?php
                                            $max_guests_select_args = [
                                                'id' => 'max_room_capacity',
                                                'label' => 'Max Guests Capacity',
                                                'wrapper_class' => 'room-capacity-wrapper',
                                                'selected_val' => $max_room_capacity
                                            ];
                                            $this->set_numbers_select($max_guests_select_args, 4);
                                            ?>
                                            <div class="room-color-wrapper">
                                                <label for="room_color">Room Color</label>
                                                <input type="color" name="room_color" id="room_color" value="<?php echo $room_color ?>">
                                            </div>
                                        </section>
                                        <section class="beds-wrapper">
                                            <h5 class="bed-title section-title">Beds Options</h5>
                                            <div class="options-wrapper">
                                                <div class="king-bed-wrapper bed-type-wrapper">
                                                    <div class="checkbox-wrapper">
                                                        <input type="checkbox" name="beds[king][has_beds]" id="king_bed_checkbox" <?php echo !empty($beds['king']['has_beds']) ? 'checked' : '' ?>>
                                                        <label for="king_bed_checkbox">King Beds</label>
                                                    </div>
                                                    <?php
                                                    $king_bed_select_args = [
                                                        'id' => 'beds[king][number]',
                                                        'wrapper_class' => 'number-of-king-bed-wrapper number-of-beds-wrapper',
                                                        'selected_val' => !empty($beds['king']['number']) ? $beds['king']['number'] : ''
                                                    ];
                                                    $this->set_numbers_select($king_bed_select_args, 4);
                                                    ?>
                                                </div>
                                                <div class="queen-bed-wrapper bed-type-wrapper">
                                                    <div class="checkbox-wrapper">
                                                        <input type="checkbox" name="beds[queen][has_beds]" id="queen_bed_checkbox" <?php echo !empty($beds['queen']['has_beds']) ? 'checked' : '' ?>>
                                                        <label for="queen_bed_checkbox">Queen Beds</label>
                                                    </div>
                                                    <?php
                                                    $queen_bed_select_args = [
                                                        'id' => 'beds[queen][number]',
                                                        'wrapper_class' => 'number-of-queen-bed-wrapper number-of-beds-wrapper',
                                                        'selected_val' => !empty($beds['queen']['number']) ? $beds['queen']['number'] : ''

                                                    ];
                                                    $this->set_numbers_select($queen_bed_select_args, 4);
                                                    ?>
                                                </div>
                                                <div class="double-bed-wrapper bed-type-wrapper">
                                                    <div class="checkbox-wrapper">
                                                        <input type="checkbox" name="beds[double][has_beds]" id="double_bed_checkbox" <?php echo !empty($beds['double']['has_beds']) ? 'checked' : '' ?>>
                                                        <label for="double_bed_checkbox">Double Beds</label>
                                                    </div>
                                                    <?php
                                                    $double_bed_select_args = [
                                                        'id' => 'beds[double][number]',
                                                        'wrapper_class' => 'number-of-double-bed-wrapper number-of-beds-wrapper',
                                                        'selected_val' => !empty($beds['double']['number']) ? $beds['double']['number'] : ''
                                                    ];
                                                    $this->set_numbers_select($double_bed_select_args, 4);
                                                    ?>
                                                </div>
                                                <div class="single-bed-wrapper bed-type-wrapper">
                                                    <div class="checkbox-wrapper">
                                                        <input type="checkbox" name="beds[single][has_beds]" id="single_bed_checkbox" <?php echo !empty($beds['single']['has_beds']) ? 'checked' : '' ?>>
                                                        <label for="single_bed_checkbox">Single Beds</label>
                                                    </div>
                                                    <?php
                                                    $single_bed_select_args = [
                                                        'id' => 'beds[single][number]',
                                                        'wrapper_class' => 'number-of-single-bed-wrapper number-of-beds-wrapper',
                                                        'selected_val' => !empty($beds['single']['number']) ? $beds['single']['number'] : ''
                                                    ];
                                                    $this->set_numbers_select($single_bed_select_args, 4);
                                                    ?>
                                                </div>
                                            </div>
                                        </section>
                                        <section class="amenities-wrapper">
                                            <h5 class="amenities-title section-title">Amenities</h5>
                                            <div class="options-wrapper">
                                                <div class="fireplace-checkbox-wrapper checkbox-wrapper">
                                                    <input type="checkbox" name="amenities[fireplace_checkbox]" id="amenities[fireplace_checkbox]" <?php echo !empty($amenities['fireplace']) ? 'checked' : '' ?>>
                                                    <label for="amenities[fireplace_checkbox]">Fireplace</label>
                                                </div>
                                                <div class="bathroom-checkbox-wrapper checkbox-wrapper">
                                                    <input type="checkbox" name="amenities[bathroom_checkbox]" id="amenities[bathroom_checkbox]" <?php echo !empty($amenities['bathroom']) ? 'checked' : '' ?>>
                                                    <label for="amenities[bathroom_checkbox]">Private Bathroom</label>
                                                </div>
                                                <div class="meals-checkbox-wrapper checkbox-wrapper">
                                                    <input type="checkbox" name="amenities[meals_checkbox]" id="amenities[meals_checkbox]" <?php echo !empty($amenities['meals']) ? 'checked' : '' ?>>
                                                    <label for="amenities[meals_checkbox]">Meals</label>
                                                </div>
                                                <div class="activities-checkbox-wrapper checkbox-wrapper">
                                                    <input type="checkbox" name="amenities[activities_checkbox]" id="amenities[activities_checkbox]"
                                                        <?php echo !empty($amenities['activities']) ? 'checked' : '' ?>>
                                                    <label for="amenities[activities_checkbox]">Activities</label>
                                                </div>
                                                <div class="pickup-checkbox-wrapper checkbox-wrapper">
                                                    <input type="checkbox" name="amenities[pickup_checkbox]" id="amenities[pickup_checkbox]" <?php echo !empty($amenities['pickup']) ? 'checked' : '' ?>>
                                                    <label for="amenities[pickup_checkbox]">Airport Pickup</label>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                <?php }

    private function save_package_custom_data($room_id)
    {
        $package_data = !empty(get_post_meta($room_id, 'package_product_data', true))
            ? get_post_meta($room_id, 'package_product_data', true)
            : [];

        $max_room_capacity = $_POST['max_room_capacity'];
        $room_color = $_POST['room_color'];
        $amenities = $_POST['amenities'];
        $beds = $_POST['beds'];

        if (!empty($max_room_capacity)) {
            $package_data['max_room_capacity'] = $max_room_capacity;
        }
        if (!empty($room_color)) {
            $package_data['room_color'] = $room_color;
        }
        if (!empty($beds)) {
            $package_data['beds'] = $beds;
        }
        if (!empty($amenities)) {
            $package_data['amenities'] = [
                'fireplace' => !empty($amenities['fireplace_checkbox']) ? $amenities['fireplace_checkbox'] : '',
                'bathroom' => !empty($amenities['bathroom_checkbox']) ? $amenities['bathroom_checkbox'] : '',
                'meals' => !empty($amenities['meals_checkbox']) ? $amenities['meals_checkbox'] : '',
                'activities' => !empty($amenities['activities_checkbox']) ? $amenities['activities_checkbox'] : '',
                'pickup' => !empty($amenities['pickup_checkbox']) ? $amenities['pickup_checkbox'] : '',
            ];
        }
        if (!empty($_POST['gallery'])) {
            update_post_meta($room_id, 'room_gallery', $_POST['gallery']);
        } else {
            delete_post_meta($room_id, 'room_gallery');
        }

        update_post_meta($room_id, 'package_product_data', $package_data);
    }
    private function set_numbers_select($args, $number_of_options)
    {
        $id = $args['id'];
        $label = !empty($args['label']) ? $args['label'] : '';
        $wrapper_class = !empty($args['wrapper_class']) ? $args['wrapper_class'] : '';
        $selected_val = !empty($args['selected_val']) ? $args['selected_val'] : 0;
        ?>
                            <div class="select-wrapper <?php echo $wrapper_class ?>">
                                <label for="<?php echo $id ?>">
                                    <?php echo $label ?>
                                </label>
                                <select name="<?php echo $id ?>" id="<?php echo $id ?>">
                                    <?php for ($i = 1; $i <= $number_of_options; $i++) { ?>
                                                <option value="<?php echo $i ?>" <?php echo $selected_val == $i ? 'selected' : '' ?>>
                                                    <?php echo $i ?>
                                                </option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php
    }

    /* Retreats Messages CPT */
    function register_retreat_messages_post_type()
    {
        $labels = array(
            'name' => 'Retreats Messages',
            'singular_name' => 'Retreat Message',
            'menu_name' => 'Retreat Emails',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Retreat Message',
            'edit_item' => 'Edit Retreat Message',
            'new_item' => 'New Retreat Message',
            'view_item' => 'View Retreat Message',
            'search_items' => 'Search Retreat Messages',
            'not_found' => 'No Retreat Messages found',
            'not_found_in_trash' => 'No Retreat Messages found in Trash',
            'parent_item_colon' => 'Parent Retreat Message:',
            'all_items' => 'Retreats Emails',
            'archives' => 'Retreat Messages Archives',
            'insert_into_item' => 'Insert into Retreat Message',
            'uploaded_to_this_item' => 'Uploaded to this Retreat Message',
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'public' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'menu_position' => 10,  // Adjust the position as needed
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'query_var' => true,
            'rewrite' => array('slug' => 'retreat_messages'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-email-alt',  // Customize the icon as needed
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('retreat_messages', $args);
    }
    function add_retreat_messages_metabox()
    {
        add_meta_box(
            'retreat_messages_metabox',
            'Message Options',
            array($this, 'retreat_messages_metabox_content'),
            'retreat_messages',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }
    function retreat_messages_metabox_content($post)
    {
        // Use the post ID to get the product data
        $message_id = $post->ID;

        $message_data = get_post_meta($message_id, 'retreat_message_data', true);
        $message_subject = !empty($message_data['subject']) ? $message_data['subject'] : '';
        $retreats_ids = !empty($message_data['retreats']) ? $message_data['retreats'] : [];
        $recipients = !empty($message_data['recipients']) ? $message_data['recipients'] : [];

        $schedule = !empty($message_data['schedule']) ? $message_data['schedule'] : [];
        $schedule_booking = !empty($schedule['booking']) ? $schedule['booking'] : [];
        $schedule_before = !empty($schedule['before']) ? $schedule['before'] : [];
        $schedule_during = !empty($schedule['during']) ? $schedule['during'] : [];
        $schedule_after = !empty($schedule['after']) ? $schedule['after'] : [];

        $attachment = !empty($message_data['attachment']) ? $message_data['attachment'] : [];
        $attachment_id = !empty($attachment['id']) ? $attachment['id'] : '';
        $attachment_url = !empty($attachment['url']) ? $attachment['url'] : '';

        $all_retreats_ids = get_all_retreats_ids();
        ?>
                            <div id="retreat-message-options" class="">
                                <div class="message-options-content">
                                    <div class="input-wrapper subject">
                                        <label for="message_subject">Message Subject</label>
                                        <input type="text" name="subject" id="message_subject" placeholder="<?php echo $post->post_title ?>"
                                            value="<?php echo $message_subject ?>">
                                    </div>
                                    <div class="group-wrapper checkbox choose-retreats">
                                        <h4>Choose Retreats</h4>
                                        <div class="content">
                                            <?php foreach ($all_retreats_ids as $retreat_id) {
                                                $checked_prop = in_array($retreat_id, $retreats_ids) ? ' checked' : '';
                                                ?>
                                                                    <div class="input-wrapper type-checkbox">
                                                                        <input type="checkbox" name="retreats[<?php echo $retreat_id ?>]"
                                                                            id="retreat_<?php echo $retreat_id ?>" value="<?php echo $retreat_id ?>" <?php echo $checked_prop ?>>
                                                                        <label for="retreat_<?php echo $retreat_id ?>">
                                                                            <?php echo get_the_title($retreat_id); ?>
                                                                        </label>
                                                                    </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="group-wrapper checkbox choose-recipients-list">
                                        <h4>Choose Recipients</h4>
                                        <div class="content">
                                            <div class="input-wrapper type-checkbox">
                                                <input type="checkbox" name="recipients[]" id="guests_rec" value="guests" <?php echo in_array('guests', $recipients) ? 'checked' : '' ?>>
                                                <label for="guests_rec">Guests</label>
                                            </div>
                                            <div class="input-wrapper type-checkbox">
                                                <input type="checkbox" name="recipients[]" id="waitlist_rec" value="waitlist" <?php echo in_array('waitlist', $recipients) ? 'checked' : '' ?>>
                                                <label for="waitlist_rec">Waitlist</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="group-wrapper checkbox schedule-message">
                                        <h4>When To Schedule Message?</h4>
                                        <div class="content">
                                            <div class="after-booking timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[booking][is_scheduled]"
                                                        id="schedule_booking" value="true" <?php echo !empty($schedule_booking) ? 'checked' : '' ?>>
                                                    <label for="schedule_booking">After Booking</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[booking][days]" id="days_booking" min="0"
                                                        value="<?php echo !empty($schedule_booking['days']) ? $schedule_booking['days'] : '' ?>">
                                                    <label for="days_booking">Days</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_booking">At: </label>
                                                    <input type="time" role="time" name="schedule[booking][time]" id="time_booking"
                                                        value="<?php echo !empty($schedule_booking['time']) ? $schedule_booking['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                            <div class="before-wrapper timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[before][is_scheduled]"
                                                        id="schedule_before" value="true" <?php echo !empty($schedule_before) ? 'checked' : '' ?>>
                                                    <label for="schedule_before">Before Retreat</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[before][days]" id="days_before" min="1"
                                                        value="<?php echo !empty($schedule_before['days']) ? $schedule_before['days'] : '' ?>">
                                                    <label for="days_before">Days</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_before">At: </label>
                                                    <input type="time" role="time" name="schedule[before][time]" id="time_before"
                                                        value="<?php echo !empty($schedule_before['time']) ? $schedule_before['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                            <div class="during-wrapper timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[during][is_scheduled]"
                                                        id="schedule_during" value="true" <?php echo !empty($schedule_during) ? 'checked' : '' ?>>
                                                    <label for="schedule_during">During Retreat</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[during][days]" id="days_departure" min="0"
                                                        value="<?php echo !empty($schedule_during['days']) ? $schedule_during['days'] : '' ?>">
                                                    <label for="days_departure">Days after departure</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_departure">At: </label>
                                                    <input type="time" role="time" name="schedule[during][time]" id="time_departure"
                                                        value="<?php echo !empty($schedule_during['time']) ? $schedule_during['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                            <div class="after-wrapper timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[after][is_scheduled]" id="schedule_after"
                                                        value="true" <?php echo !empty($schedule_after) ? 'checked' : '' ?>>
                                                    <label for="schedule_after">After Retreat</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[after][days]" id="days_after" min="0"
                                                        value="<?php echo !empty($schedule_after['days']) ? $schedule_after['days'] : '' ?>">
                                                    <label for="days_after">Days</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_after">At: </label>
                                                    <input type="time" role="time" name="schedule[after][time]" id="time_after"
                                                        value="<?php echo !empty($schedule_after['time']) ? $schedule_after['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="input-wrapper type-file attachment"
                                        data-selected="<?php echo !empty($attachment_url) ? 'true' : 'false' ?>">
                                        <p class="choose-attachment">Attachment: </p>
                                        <button class="attachment-button" id="upload-file-button" type="button" onclick="uploadFileForMail()">
                                            <?php echo !empty($attachment_url) ? 'Change' : 'Attache' ?> File
                                        </button>
                                        <button class="attachment-button" id="remove-attachment-button" type="button" onclick="removeAttachment()">
                                            Remove
                                        </button>
                                        <p id="attachment-name">
                                            <?php echo !empty($attachment_url) ? basename($attachment_url) : '' ?>
                                        </p>

                                        <input type="hidden" name="attachment[id]" id="attachment-id" value="<?php echo $attachment_id ?>">
                                        <input type="hidden" name="attachment[url]" id="attachment-url" value="<?php echo $attachment_url ?>">
                                    </div>
                                </div>
                            </div>
                <?php }

    function save_retreat_message_data($message_id)
    {
        $message_data = !empty(get_post_meta($message_id, 'message_data', true))
            ? get_post_meta($message_id, 'message_data', true)
            : [];
        $message_subject = $_POST['subject'];
        $retreats = $_POST['retreats'];
        $recipients = $_POST['recipients'];
        $schedule = $_POST['schedule'];
        $attachment = $_POST['attachment'];

        $message_data['subject'] = $message_subject;
        $message_data['retreats'] = $retreats;
        $message_data['recipients'] = $recipients;
        $message_data['attachment'] = $attachment;

        if (!empty($schedule)) {
            foreach ($schedule as $key => $value) {
                if ($value['is_scheduled'] == 'true') {
                    unset($value['is_scheduled']);
                    $message_data['schedule'][$key] = $value;
                } else {
                    unset($message_data['schedule'][$key]);
                }
            }
        }
        update_post_meta($message_id, 'retreat_message_data', $message_data);
    }

    /* Order Messages CPT */
    function register_order_messages_post_type()
    {
        $labels = array(
            'name' => 'Order Messages',
            'singular_name' => 'Order Message',
            'menu_name' => 'Order Emails',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Order Message',
            'edit_item' => 'Edit Order Message',
            'new_item' => 'New Order Message',
            'view_item' => 'View Order Message',
            'search_items' => 'Search Order Messages',
            'not_found' => 'No Order Messages found',
            'not_found_in_trash' => 'No Order Messages found in Trash',
            'parent_item_colon' => 'Parent Order Message:',
            'all_items' => 'Order Emails',
            'archives' => 'Order Messages Archives',
            'insert_into_item' => 'Insert into Order Message',
            'uploaded_to_this_item' => 'Uploaded to this Order Message',
            'featured_image' => 'Featured Image',
            'set_featured_image' => 'Set featured image',
            'remove_featured_image' => 'Remove featured image',
            'use_featured_image' => 'Use as featured image',
            'public' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'menu_position' => 10,  // Adjust the position as needed
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('category', 'post_tag'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'gs_bookings_page',  // Show in the "Retreat Rooms" submenu
            'query_var' => true,
            'rewrite' => array('slug' => 'order_messages'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_icon' => 'dashicons-email-alt',  // Customize the icon as needed
            'supports' => array('title', 'editor', 'thumbnail'),
        );

        register_post_type('order_messages', $args);
    }

    function add_order_messages_metabox()
    {
        add_meta_box(
            'order_messages_metabox',
            'Message Options',
            array($this, 'order_messages_metabox_content'),
            'order_messages',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
        add_meta_box(
            'order_messages_variables_metabox',
            'Message Varibles',
            array($this, 'order_messages_variables'),
            'order_messages',  // Change to the actual slug of your custom post type
            'normal',
            'high'
        );
    }

    function order_messages_metabox_content($post)
    {
        // Use the post ID to get the product data
        $message_id = $post->ID;
        $message_data = get_post_meta($message_id, 'order_message_data', true);
        $message_subject = !empty($message_data['subject']) ? $message_data['subject'] : '';
        $order_statuses = !empty($message_data['order_statuses']) ? $message_data['order_statuses'] : [];
        $schedule = !empty($message_data['schedule']) ? $message_data['schedule'] : [];
        $schedule_booking_event = !empty($schedule['booking_event']) ? $schedule['booking_event'] : [];
        $schedule_booking = !empty($schedule['booking']) ? $schedule['booking'] : [];
        $schedule_before = !empty($schedule['before']) ? $schedule['before'] : [];
        $send_invoice = !empty($message_data['send_invoice']) ? $message_data['send_invoice'] : '';
        $attachment = !empty($message_data['attachment']) ? $message_data['attachment'] : [];
        $attachment_id = !empty($attachment['id']) ? $attachment['id'] : '';
        $attachment_url = !empty($attachment['url']) ? $attachment['url'] : '';
        $all_order_statuses = wc_get_order_statuses();
        ?>
                            <div id="order-message-options" class="">
                                <div class="message-options-content">
                                    <div class="input-wrapper subject">
                                        <label for="message_subject">Message Subject</label>
                                        <textarea type="text" name="subject" id="message_subject" placeholder="<?php echo $post->post_title ?>"
                                            ><?php echo $message_subject ?></textarea> 
                                    </div>
                                    <div class="group-wrapper checkbox choose-order-statuses">
                                        <h4>Choose Order Statuses</h4>
                                        <div class="content">
                                            <?php foreach ($all_order_statuses as $status => $label) {
                                                if ($status == 'wc-checkout-draft')
                                                    continue;
                                                $checked_prop = in_array($status, $order_statuses) ? ' checked' : '';
                                                ?>
                                                            <div class="input-wrapper type-checkbox">
                                                                <input type="checkbox" name="order_statuses[<?php echo $status ?>]"
                                                                    id="order_status_<?php echo $status ?>" value="<?php echo $status ?>" <?php echo $checked_prop ?>>
                                                                <label for="order_status_<?php echo $status ?>">
                                                                    <?php echo $label ?>
                                                                </label>
                                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="group-wrapper checkbox schedule-message">
                                        <h4>When To Schedule Message?</h4>
                                        <div class="content">
                                            <div class="on-booking timing-wrapper">
                                            <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="event" name="schedule[booking_event][is_scheduled]"
                                                        id="schedule_booking_event" value="true" <?php echo !empty($schedule_booking_event['is_scheduled']) ? 'checked' : '' ?>>
                                                    <label for="schedule_booking_event">On Booking</label>
                                                </div>
                                            </div>
                                            <div class="after-booking timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[booking][is_scheduled]"
                                                        id="schedule_booking" value="true" <?php echo !empty($schedule_booking['is_scheduled']) ? 'checked' : '' ?>>
                                                    <label for="schedule_booking">After Booking</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[booking][days]" id="days_booking" min="0"
                                                        value="<?php echo !empty($schedule_booking['days']) ? $schedule_booking['days'] : '' ?>">
                                                    <label for="days_booking">Days</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_booking">At: </label>
                                                    <input type="time" role="time" name="schedule[booking][time]" id="time_booking"
                                                        value="<?php echo !empty($schedule_booking['time']) ? $schedule_booking['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                            <div class="before-wrapper timing-wrapper">
                                                <div class="input-wrapper type-checkbox">
                                                    <input type="checkbox" role="activate" name="schedule[before][is_scheduled]"
                                                        id="schedule_before" value="true" <?php echo !empty($schedule_before['is_scheduled']) ? 'checked' : '' ?>>
                                                    <label for="schedule_before">Before Retreat</label>
                                                </div>
                                                <div class="input-wrapper type-text">
                                                    <input type="number" role="days" name="schedule[before][days]" id="days_before" min="1"
                                                        value="<?php echo !empty($schedule_before['days']) ? $schedule_before['days'] : '' ?>">
                                                    <label for="days_before">Days</label>
                                                </div>
                                                <div class="input-wrapper type-time">
                                                    <label for="time_before">At: </label>
                                                    <input type="time" role="time" name="schedule[before][time]" id="time_before"
                                                        value="<?php echo !empty($schedule_before['time']) ? $schedule_before['time'] : '12:00' ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="group-wrapper checkbox add-invoice">
                                        <h4>Send Invoice?</h4>
                                        <div class="content">
                                            <div class="input-wrapper type-radio">
                                                <input type="radio" name="send_invoice" id="send_invoice_true" value="1" <?php echo $send_invoice ? 'checked' : '' ?>>
                                                <label for="send_invoice_true">Yes</label>
                                            </div>
                                            <div class="input-wrapper type-radio">
                                                <input type="radio" name="send_invoice" id="send_invoice_false" value="" <?php echo $send_invoice ? '' : 'checked' ?>>
                                                <label for="send_invoice_false">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="input-wrapper type-file attachment"
                                        data-selected="<?php echo !empty($attachment_url) ? 'true' : 'false' ?>">
                                        <p class="choose-attachment">Attachment: </p>
                                        <button class="attachment-button" id="upload-file-button" type="button" onclick="uploadFileForMail()">
                                            <?php echo !empty($attachment_url) ? 'Change' : 'Attache' ?> File
                                        </button>
                                        <button class="attachment-button" id="remove-attachment-button" type="button" onclick="removeAttachment()">
                                            Remove
                                        </button>
                                        <p id="attachment-name">
                                            <?php echo !empty($attachment_url) ? basename($attachment_url) : '' ?>
                                        </p>
                                        <input type="hidden" name="attachment[id]" id="attachment-id" value="<?php echo $attachment_id ?>">
                                        <input type="hidden" name="attachment[url]" id="attachment-url" value="<?php echo $attachment_url ?>">
                                    </div>
                                </div>
                            </div>
                        <?php
    }
    function order_messages_variables($post)
    {
        ?>
                        <div id="order-message-variables" class="">
                            <div class="message-variables-content">
                                <div class="variables-wrapper">
                                    <h4>Available Variables For Order Emails</h4>
                                    <ul class="variables-list">
                                        <li><strong>{{first_name}}</strong>: User first name</li>
                                        <li><strong>{{last_name}}</strong>: User last name</li>
                                        <li><strong>{{full_name}}</strong>: User full name</li>
                                        <li><strong>{{email}}</strong>: User email</li>
                                        <li><strong>{{phone}}</strong>: User phone</li>
                                        <li><strong>{{order_id}}</strong>: Order ID</li>
                                        <li><strong>{{order_date}}</strong>: Order Date</li>
                                        <li><strong>{{order_total}}</strong>: Order Total</li>
                                        <li><strong>{{deposit_amount}}</strong>: Deposit Amount</li>
                                        <li><strong>{{remaining_amount}}</strong>: Remaining Amount</li>
                                        <li><strong>{{full_payment_due_date}}</strong>: Full Payment Due Date</li>
                                        <li><strong>{{full_payment_url}}</strong>: Full Payment URL (place as "href" attribute)</li>
                                        <li><strong>{{departure_date}}</strong>: Departure Date</li>
                                        <li><strong>{{return_date}}</strong>: Return Date</li>
                                        <li><strong>{{retreat_name}}</strong>: Retreat Name</li>
                                        <li><strong>{{rooms}}</strong>: Rooms Names</li>
                                        <li><strong>{{guests}}</strong>: Guests Names</li>
                                        <li><strong>{{guests_count}}</strong>: Guests Count</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php
    }
    function save_order_message_data($message_id)
    {
        $message_data = !empty(get_post_meta($message_id, 'order_message_data', true))
            ? get_post_meta($message_id, 'order_message_data', true)
            : [];
        $message_subject = $_POST['subject'];
        $order_statuses = $_POST['order_statuses'];
        $schedule = $_POST['schedule'];
        $attachment = $_POST['attachment'];
        $send_invoice = $_POST['send_invoice'];

        $message_data['subject'] = $message_subject;
        $message_data['order_statuses'] = $order_statuses;
        $message_data['attachment'] = $attachment;
        $message_data['schedule'] = $schedule;
        $message_data['send_invoice'] = $send_invoice;

        update_post_meta($message_id, 'order_message_data', $message_data);
    }
    /* Retreats Management */
    function display_retreats_manage($retreats_to_display = [], $is_archive = false)
    {
        ?>   
                            <div id="retreats-management" class="retreats-management-container" data-archive="<?php echo $is_archive ? 'true' : 'false' ?>">
                                <h1>
                                <?php echo $is_archive ? 'Retreats Archive' : 'Manage Retreats' ?>    
                                </h1>
                                <div class="retreats-content-wrapper">
                                    <div class="retreats-wrapper">
                                        <?php
                                        foreach ($retreats_to_display as $idx => $retreat_id) {
                                            $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
                                            $departure_dates = $is_archive ? $retreat_data['archived_departure_dates'] : $retreat_data['departure_dates'];
                                            if (empty($departure_dates))
                                                continue;
                                            ?>
                                                        <div class="single-retreat" data-selected="<?php echo $idx == 0 ? 'true' : 'false' ?>">
                                                            <div class="retreat-heading">
                                                                <h3 class="retreat-title">
                                                                    <?php echo get_the_title($retreat_id) ?>
                                                                </h3>
                                                            </div>
                                                            <div class="retreat-content">
                                                                <p class="title-echo">
                                                                    <?php echo get_the_title($retreat_id) ?>
                                                                </p>
                                                                <?php if (!empty($departure_dates)) { ?>
                                                                            <div class="departure-dates">
                                                                                <div class="departure-dates-content">
                                                                                    <?php
                                                                                    foreach ($departure_dates as $date => $date_data) {
                                                                                        $passed_date = $date;
                                                                                        if ($is_archive) {
                                                                                            $parts = explode('-', $date);
                                                                                            $joined_parts = implode('-', array_slice($parts, 0, 3));
                                                                                            $passed_date = $joined_parts;
                                                                                            $same_date_divider = !empty($parts[3]) ? chr(65 + $parts[3]) : '';
                                                                                            $departure_date = date('F j, Y', strtotime($joined_parts)) . ' ' . $same_date_divider;
                                                                                        } else {
                                                                                            $departure_date = date('F j, Y', strtotime($date));
                                                                                        }
                                                                                        $rooms_list = $date_data['rooms_list'];
                                                                                        $guests_list = get_departure_date_guests_list($retreat_id, $date, $is_archive);
                                                                                        ?>
                                                                                                    <div class="departure-date" data-selected="false">
                                                                                                        <div class="heading">
                                                                                                            <h5>
                                                                                                                <?php echo $departure_date ?>
                                                                                                            </h5>
                                                                                                        </div>
                                                                                                        <div class="content">
                                                                                                            <div class="general departure-content-section">
                                                                                                                <?php $this->display_general_data($date_data, $passed_date) ?>
                                                                                                            </div>
                                                                                                            <div class="rooms departure-content-section">
                                                                                                                <?php $this->display_rooms_data($rooms_list) ?>
                                                                                                            </div>
                                                                                                            <div class="guests departure-content-section">
                                                                                                                <?php $this->display_guests_data($retreat_id, $guests_list, $is_archive, $passed_date) ?>                                                                                                    
                                                                                                            </div>
                                                                                                            <div class="footing">
                                                                                                                <div class="actions">
                                                                                                                    <button type="button" data-retreat="<?php echo $retreat_id ?>" data-departure="<?php echo $date ?>" data-action="guests" class="csv-button export-guests-csv departure-date-action">Download Guests CSV</button>
                                                                                                                    <button type="button" data-retreat="<?php echo $retreat_id ?>" data-departure="<?php echo $date ?>" data-action="rooms" class="csv-button export-rooms-csv departure-date-action">Download Rooms CSV</button>
                                                                                                                    <?php if (!$is_archive) { ?>
                                                                                                                                    <button type="button" data-retreat="<?php echo $retreat_id ?>" data-departure="<?php echo $date ?>" data-action="archive" class="archive-button archive-date-button departure-date-action">Move This Date To Archive</button>
                                                                                                                    <?php } else { ?>
                                                                                                                                    <button type="button" data-retreat="<?php echo $retreat_id ?>" data-departure="<?php echo $date ?>" data-action="archive_remove" class="archive-button remove-archive-date-button departure-date-action">Remove This Date From Archive</button>
                                                                                                                    <?php } ?>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                    <?php } ?>
                                                                                </div>
                                                                            </div>
                                                                <?php } ?>
                                                                <div class="retreat-actions">
                                                                    <a href="<?php echo get_edit_post_link($retreat_id) ?>" target="_blank">Edit</a>
                                                                    <a href="<?php echo get_permalink($retreat_id) ?>" target="_blank">View</a>
                                                                </div>
                                                            </div>
                                                        </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php
    }

    function display_general_data($date_data, $departure_date)
    {
        $available_rooms = $date_data['rooms_availability'];
        $total_rooms = !empty($date_data['rooms_list']) ? count($date_data['rooms_list']) : 0;
        $expired_reservations = !empty($date_data['expired_reservations']) ? $date_data['expired_reservations'] : [];
        $expired_reservations_count = count($expired_reservations);
        $expired_reservations_ids = array_keys($expired_reservations);
        $guests_availability = $date_data['guests_availability'];
        $is_full_booked = !empty($date_data['is_full_booked']) ? $date_data['is_full_booked'] : false;
        $full_booked_time = !empty($date_data['full_booked_time']) ? $date_data['full_booked_time'] : 0;
        ?>
                            <h5>details</h5>
                            <p class="available-rooms">
                                available rooms:
                                <?php echo $available_rooms ?> / <?php echo $total_rooms ?>
                            </p>
                            <p class="expired-reservations-count">expired reservations:
                                <?php echo $expired_reservations_count ?>
                            </p>
                            <p class="guests-availability">
                                guests availability:
                                <?php echo $guests_availability ?>
                            </p>
                            <?php if ($is_full_booked) { ?>
                                        <h5 class="full-booked-title">Full Booked</h5>
                                        <p class="full-booked-time">
                                            full booked at:
                                            <?php echo date('F j, Y', $full_booked_time) ?>
                                        </p>
                                        <p>
                                            <?php
                                            // calculate when full booked related to departure date
                                            $full_booked_diff = $full_booked_time - strtotime($departure_date);
                                            $full_booked_diff_days = abs(floor($full_booked_diff / (60 * 60 * 24)));
                                            echo "$full_booked_diff_days days before departure date";

                                            ?>
                                        </p>
                                <?php } ?>
                        <?php
    }

    function display_rooms_data($rooms_list)
    {
        ?>
                <h5>Rooms</h5>
                <ul class="rooms-list">
                    <?php
                    if (!empty($rooms_list)) {
                        foreach ($rooms_list as $room_id => $room) {
                            $room_name = get_the_title($room_id);
                            $room_data = get_post_meta($room_id, 'package_product_data', true);
                            $room_color = !empty($room_data['room_color']) ? $room_data['room_color'] : '#f62355';
                            $item_style = "border:2px solid $room_color;";
                            $is_booked = !empty($room['is_booked']);
                            $guests = !empty($room['guests']) ? $room['guests'] : [];
                            $status = !empty($room['status']) ? $room['status'] : 'available';
                            $payments_collected = !empty($room['payments_collected']) ? $room['payments_collected'] : 0;
                            $addons = !empty($room['addons']) ? $room['addons'] : [];
                            $order_id = !empty($room['order_id']) ? $room['order_id'] : '';
                            $expired_orderes_ids = !empty($room['expired_orderes_ids']) ? $room['expired_orderes_ids'] : [];
                            $has_content = $is_booked || $payments_collected || $status == 'booked' || $status == 'deposit' || $order_id;
                            ?>
                                                <li data-selected="false" data-color="<?php echo $room_color ?>" data-status="<?php echo $status ?>">
                                                    <div class="room-heading" style="<?php echo $item_style ?>">
                                                        <p class="room-name">
                                                            <?php echo $room_name ?>
                                                        </p>
                                                        <p class="status">status: <strong>
                                                                <?php echo $status ?>
                                                            </strong>
                                                        </p>
                                                    </div>
                                                    <div class="room-content" style="<?php echo $item_style ?>">
                                                        <?php if ($status !== 'available') { ?>
                                                                            <div class="order-id room-content-section">
                                                                                <strong>Order ID</strong>
                                                                                <p class="order-id room-section-content">
                                                                                    #<?php echo $order_id ?>
                                                                                </p>
                                                                            </div>
                                                        <?php } ?>
                                                        <?php if (!empty($guests)) { ?>
                                                                            <div class="room-guests room-content-section">
                                                                                <p><strong>Guests</strong></p>
                                                                                <ul class="room-guests-list room-section-content">
                                                                                    <?php foreach ($guests as $guest) {
                                                                                        if (empty($guest['name']))
                                                                                            continue;
                                                                                        ?>
                                                                                                        <li>
                                                                                                            <?php echo $guest['name'] ?>
                                                                                                        </li>
                                                                                    <?php } ?>
                                                                                </ul>
                                                                            </div>
                                                        <?php } ?>
                                                        <?php if (!empty($payments_collected)) { ?>
                                                                                <div class="payments-collected room-content-section">
                                                                                    <p><strong>Payments Collected</strong></p>
                                                                                    <p class="amount room-section-content">
                                                                                        <?php echo get_woocommerce_currency_symbol() . number_format($payments_collected) ?>
                                                                                    </p>
                                                                                </div>
                                                        <?php } ?>
                                                        <?php if (!empty($addons)) { ?>
                                                                            <div class="addons room-content-section">
                                                                                <p><strong>Addons for Room</strong></p>
                                                                                <ul class="addons-list room-section-content">
                                                                                    <?php foreach ($addons as $product_id => $quantity) { ?>
                                                                                                        <li>
                                                                                                            <span class="addon-name"><?php echo get_the_title($product_id) ?></span> X <span class="addon-quantity"><?php echo $quantity ?></span>
                                                                                                        </li>
                                                                                    <?php } ?>
                                                                                </ul>
                                                                            </div>
                                                        <?php } ?>
                                                        <?php if ($status == 'deposit') {
                                                            $order = wc_get_order($order_id);

                                                            ?>
                                                                            <div class="order-expiration room-content-section">
                                                                                <p><strong>Expires On</strong></p>
                                                                                <p class="expiration room-section-content">
                                                                                    <?php echo $order->get_date_created()->add(new DateInterval('P1D'))->format('F j, Y') ?>
                                                                                </p>
                                                                            </div>
                                                                            <div class="deposit-payment-link room-content-section">
                                                                                <p><strong>Deposit Payment Link</strong></p>
                                                                                <p class="payment-link room-section-content">
                                                                                    <a href="<?php echo $order->get_checkout_payment_url() ?>" target="_blank">
                                                                                <?php echo $order->get_checkout_payment_url(); ?>
                                                                                </a>
                                                                                </p>    
                                                                            </div>  
                                                        <?php } ?>
                                                    </div>
                                                </li>
                            <?php }
                    }
                    ?>
                </ul>
            <?php
    }

    function display_guests_data($retreat_id, $guests_list, $is_archive, $departure_date)
    {
        $all_retreat_messages_ids = get_all_retreat_messages_ids();
        $messages_for_guests = [];
        foreach ($all_retreat_messages_ids as $message_id) {
            $message_data = get_post_meta($message_id, 'retreat_message_data', true);
            $enabled_message = in_array($retreat_id, $message_data['retreats']) && in_array('guests', $message_data['recipients']);
            if (!$enabled_message)
                continue;
            $messages_for_guests[] = $message_id;
        }
        ?>
                <h5>Guests</h5>
                <ul class="guests-list">
                    <li class="guests-heading">
                        <p>Details</p>
                        <?php if (!$is_archive) { ?>
                                        <p>Actions</p>
                        <?php } ?>
                    </li>
                    <?php
                    if (!empty($guests_list)):
                        foreach ($guests_list as $idx => $guest) {
                            $name = !empty($guest['name']) ? $guest['name'] : '';
                            $email = !empty($guest['email']) ? $guest['email'] : '';
                            $phone = !empty($guest['phone']) ? $guest['phone'] : '';
                            $room_id = !empty($guest['room_id']) ? $guest['room_id'] : '';
                            $order = !empty($guest['order']) ? $guest['order'] : 0;
                            if (empty($name))
                                continue;
                            $is_main_particiant = !empty($guest['main_participant']);
                            $order_id = !empty($guest['order_id']) ? $guest['order_id'] : 0;
                            ?>
                                            <li>
                                                <div class="details expanding-wrapper" edit-mode="false">
                                                    <div class="name-wrapper">
                                                        <strong class="editable expanding-item-heading">
                                                            <?php echo $guest['name'] ?>
                                                        </strong>
                                                    </div>
                                                    <div class="expanding-details expanding-item-content">
                                                        <div class="email-wrapper">
                                                            <p class="editable">
                                                                <?php echo $email ?>
                                                            </p>
                                                        </div>
                                                        <div class="phone-wrapper">
                                                            <p class="editable">
                                                                <?php echo $phone ?>
                                                            </p>
                                                        </div>
                                                        <?php if (!$is_archive) { ?>
                                                                <div class="details-edit-form-wrapper">
                                                                    <form class="edit-participant-details" action="update_guest_details" method="post">
                                                                        <input class="text-input" type="text" title="Name" value="<?php echo $name ?>"  placeholder="Name" name="details[name]">
                                                                        <input class="email-input" type="email" title="Email" value="<?php echo $email ?>" placeholder="Email" name="details[email]">
                                                                        <input class="phone-input" type="tel" title="Phone" value="<?php echo $phone ?>" placeholder="Phone" name="details[phone]">
                                                                        <div class="checkbox-wrapper hidden">
                                                                            <input type="checkbox" value="1" class="send-missed-messages-checkbox" name="details[send_missed_scheduled_messages]" id="send_missed_scheduled_messages_<?php echo $idx ?>">
                                                                            <label for="send_missed_scheduled_messages_<?php echo $idx ?>">Send Missed Scheduled Messages</label>
                                                                        </div>
                                                                        <input type="hidden" name="retreat_id" value="<?php echo $retreat_id ?>">
                                                                        <input type="hidden" name="departure_date" value="<?php echo $departure_date ?>">
                                                                        <input type="hidden" name="room_id" value="<?php echo $room_id ?>">
                                                                        <input type="hidden" name="order" value="<?php echo $order ?>">
                                                                        <button type="submit" class="details-button save-details">Save</button>                                                        
                                                                    </form>
                                                                </div>
                                                        <?php } ?>
                                                        <div class="room-wrapper">
                                                            <p>Room:
                                                                <?php echo get_the_title($room_id) ?>
                                                            </p>
                                                        </div>
                                                        <?php if (!$is_archive) { ?>
                                                                <div class="details-buttons-wrapper">
                                                                    <button type="button" class="details-button edit-details">Edit</button>
                                                                </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                                <div class="actions expanding-wrapper">
                                                    <?php if (!$is_archive):
                                                        if (!empty($messages_for_guests) && !empty($email)) { ?>
                                                                            <div class="retreat-emails actions-wrapper">
                                                                                <strong class="expanding-item-heading">retreat emails</strong>
                                                                                <div class="expanding-actions expanding-item-content">
                                                                                    <ul class="retreat-messages-list">
                                                                                        <?php foreach ($messages_for_guests as $message_id) { ?>
                                                                                                            <li><?php echo get_the_title($message_id) ?>
                                                                                                                <button type="button"
                                                                                                                    data-message="<?php echo $message_id ?>"
                                                                                                                    data-action="retreat_message"
                                                                                                                    data-name="<?php echo $name ?>"
                                                                                                                    data-email = "<?php echo $email ?>"
                                                                                                                    >
                                                                                                                    Send Message
                                                                                                                </button>
                                                                                                            </li>
                                                                                        <?php } ?>
                                                                                    </ul>
                                                                                </div>
                                                                            </div>
                                                                <?php } ?>
                                                                <?php if ($is_main_particiant) {
                                                                    $order = wc_get_order($order_id);
                                                                    $order_status = $order ? $order->get_status() : '';
                                                                    $all_order_messages = get_all_order_messages_ids();
                                                                    $order_messages_for_guests = [];
                                                                    foreach ($all_order_messages as $idx => $message_id) {
                                                                        $message_data = get_post_meta($message_id, 'order_message_data', true);
                                                                        $enabled_message = !empty($message_data['order_statuses']) && (in_array($order_status, $message_data['order_statuses']) || in_array('wc-' . $order_status, $message_data['order_statuses']));
                                                                        if (!$enabled_message)
                                                                            continue;
                                                                        $order_messages_for_guests[] = $message_id;
                                                                    }
                                                                    if (!empty($order_messages_for_guests)) { ?>
                                                                                    <div class="order-emails actions-wrapper">
                                                                                        <strong class="expanding-item-heading">order emails</strong>
                                                                                        <div class="expanding-actions expanding-item-content">
                                                                                            <ul>
                                                                                                <?php foreach ($order_messages_for_guests as $message_id) { ?>
                                                                                                        <li>
                                                                                                            <button type="button"
                                                                                                                data-message="<?php echo $message_id ?>"
                                                                                                                data-action="order_message"
                                                                                                                data-name="<?php echo $guest['name'] ?>"
                                                                                                                data-email = "<?php echo $guest['email'] ?>"
                                                                                                                data-order = "<?php echo $order_id ?>">
                                                                                                                Send <span style="font-weight:600;font-style:italic;"><?php echo get_the_title($message_id); ?></span> Message
                                                                                                            </button>
                                                                                                        </li>
                                                                                                <?php } ?>
                                                                                            </ul>
                                                                                        </div>
                                                                                    </div>
                                                                        <?php } ?>
                                                                <?php } ?>
                                                    <?php endif; ?>
                                                </div>
                                            </li>
                                <?php }
                    endif; ?>
                </ul>
            <?php
    }

    /* Configurations */
    function display_options()
    {
        $days_after_departure_to_archive_date = get_option('days_after_departure_to_archive_date', '');
        $unavailable_second_participant_message = get_option('unavailable_second_participant_message', '');
        $days_before_deposit_disabled = get_option('days_before_deposit_disabled', '');
        $deposit_payment_info_page_id = get_option('deposit_info_page', '');
        $deactivated_payment_link_redirect = get_option('deactivated_payment_link_redirect', '');
        $deposit_limits = get_option('reservation_limits', []);
        $all_pages_ids = get_pages_ids();
        $all_order_messages = get_all_order_messages_ids();
        ?>
            <div id="options" class="configs-container">
                <h1>Options</h1>
                <form method="post" action="" class="retreat-options-form" id="retreat-options-form">
                    <input type="hidden" name="options" value="1">
                    <div class="retreat-options-wrapper form-section">
                        <h3>Retreat options</h3>
                        <div class="input-wrapper type-text">
                            <label for="days_after_departure_to_archive_date">When to archive date? (Days After Departure)</label>
                            <input type="number" min="0" name="days_after_departure_to_archive_date"
                                id="days_after_departure_to_archive_date"
                                value="<?php echo $days_after_departure_to_archive_date ?>">
                        </div>
                        <div class="input-wrapper type-textarea">
                            <label for="unavailable-second-participant-message">
                                Unavailable Second Participant Message
                            </label>
                            <textarea name="unavailable_second_participant_message" id="unavailable_second_participant_message"
                                ><?php echo $unavailable_second_participant_message ?></textarea>

                        </div>
                    </div>
                    <div class="limit-deposit-room retreat-options-wrapper">
                        <h3>Deposit options</h3>
                        <div class="input-wrapper type-text">
                            <label for="days_before_deposit_disabled">When to disable deposit option? (Days Before
                                Departure)</label>
                            <input type="number" min="0" name="days_before_deposit_disabled" id="days_before_deposit_disabled"
                                value="<?php echo $days_before_deposit_disabled ?>">
                        </div>
                        <div class="input-wrapper type-select">
                            <label for="deposit_info_page">Deposit Payment Info Page</label>
                            <select name="deposit_info_page" id="deposit_info_page">
                                <option value="" selected disabled>Choose Page</option>
                                <?php foreach ($all_pages_ids as $page_id) { ?>
                                                            <option value="<?php echo $page_id ?>" <?php echo $page_id == $deposit_payment_info_page_id ? 'selected' : '' ?>>
                                                                <?php echo get_the_title($page_id) ?>
                                                            </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="input-wrapper type-select">
                            <label for="deactivated_payment_link_redirect">Redirect Deactivated Payment Link To</label>
                            <select name="deactivated_payment_link_redirect" id="deactivated_payment_link_redirect">
                                <option value="" selected disabled>Choose Page</option>
                                <?php foreach ($all_pages_ids as $page_id) { ?>
                                                            <option value="<?php echo $page_id ?>" <?php echo $page_id == $deactivated_payment_link_redirect ? 'selected' : '' ?>>
                                                                <?php echo get_the_title($page_id) ?>
                                                            </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="input-wrapper type-repeater">
                            <h5 class="repeater-title">Reservations Expiration Limits</h5>
                            <div class="repeater-content">
                                <ul class="limits-list">
                                    <?php if (!empty($deposit_limits)): ?>
                                                                <?php foreach ($deposit_limits as $limit_idx => $limit_data) {
                                                                    $item_data_id = 'reservation_limits[' . $limit_idx . ']';
                                                                    $conditions_data = !empty($limit_data['conditions']) ? $limit_data['conditions'] : [];
                                                                    $action_data = !empty($limit_data['action']) ? $limit_data['action'] : [];
                                                                    $messages_data = !empty($limit_data['messages']) ? $limit_data['messages'] : [];
                                                                    ?>
                                                                                        <li class="limit-item" data-index="<?php echo $limit_idx ?>">
                                                                                            <div class="heading-wrapper">
                                                                                                <input type="text" name="<?php echo $item_data_id ?>[item_name]" class="item-name" value="<?php echo $limit_data['item_name'] ?>">
                                                                                                <div class="buttons-wrapper">
                                                                                                    <button type="button" class="button-24 delete-limit-button">Delete</button>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="content-wrapper">
                                                                                                <div class="conditions-wrapper repeater-section-wrapper">
                                                                                                    <strong class="label">Conditions</strong>
                                                                                                    <div class="content">
                                                                                                        <div class="conditions">
                                                                                                            <?php foreach ($conditions_data as $condition_idx => $condition_data) {
                                                                                                                $condition_data_id = $item_data_id . '[conditions][' . $condition_idx . ']';
                                                                                                                $logic = $condition_idx > 0 ? $condition_data['condition_logic'] : '';
                                                                                                                $operator = $condition_data['condition_operator'];
                                                                                                                $units = $condition_data['condition_units'];
                                                                                                                $type = $condition_data['condition_type'];
                                                                                                                ?>
                                                                                                                                    <div class="condition-row" data-idx="<?php echo $condition_idx ?>">
                                                                                                                                        <?php if ($condition_idx > 0) { ?>
                                                                                                                                                                    <select name="<?php echo $condition_data_id ?>[condition_logic]" class="condition_logic">
                                                                                                                                                                        <option value="&&" <?php echo $logic == "&&" ? 'selected' : '' ?>>AND</option>
                                                                                                                                                                        <option value="||" <?php echo $logic == '||' ? 'selected' : '' ?>>OR</option>
                                                                                                                                                                    </select>
                                                                                                                                            <?php } ?>
                                                                                                                                            <div class="">
                                                                                                                                            <button type="button" class="delete-condition-button">&#215;</button>
                                                                                                                                            <span>Apply if deposit paid</span>
                                                                                                                                            <select name="<?php echo $condition_data_id ?>[condition_operator]" class="operator">
                                                                                                                                                <option value=">" <?php echo $operator == ">" ? 'selected' : '' ?>>More Than</option>
                                                                                                                                                <option value="<" <?php echo $operator == "<" ? 'selected' : '' ?>>Less Than</option>
                                                                                                                                                <option value="=" <?php echo $operator == "=" ? 'selected' : '' ?>>Exactly</option>
                                                                                                                                                <option value=">=" <?php echo $operator == ">=" ? 'selected' : '' ?>>More Than or Equal</option>
                                                                                                                                                <option value="<=" <?php echo $operator == "<=" ? 'selected' : '' ?>>Less Than or Equal</option>
                                                                                                                                            </select>
                                                                                                                                            <input type="number" name="<?php echo $condition_data_id ?>[condition_units]" class="units" min="0" value="<?php echo $units ?>">
                                                                                                                                            <select name="<?php echo $condition_data_id ?>[condition_type]" class="type">
                                                                                                                                                <option value="hours" <?php echo $type == "hours" ? 'selected' : '' ?> >Hours</option>
                                                                                                                                                <option value="days" <?php echo $type == "days" ? 'selected' : '' ?> >Days</option>
                                                                                                                                                <option value="weeks" <?php echo $type == "weeks" ? 'selected' : '' ?> >Weeks</option>
                                                                                                                                                <option value="months" <?php echo $type == "months" ? 'selected' : '' ?> >Months</option>
                                                                                                                                            </select>
                                                                                                                                            <span>Before Departure</span>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                            <?php } ?>
                                                                                                        </div>
                                                                                                        <button type="button" class="button-4 add-condition-button">Add Condition</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="action-wrapper repeater-section-wrapper">
                                                                                                    <strong class="label">Expiration Time</strong>
                                                                                                    <div class="content time-row">
                                                                                                        expire room reservation
                                                                                                        <input type="number" name="<?php echo $item_data_id ?>[action][time_units]" class="units" min="0" value="<?php echo $action_data['time_units'] ?>">
                                                                                                        <select name="<?php echo $item_data_id ?>[action][time_type]" class="type">
                                                                                                            <option value="hours" <?php echo $action_data['time_type'] == 'hours' ? "selected" : '' ?>>Hours</option>
                                                                                                            <option value="days" <?php echo $action_data['time_type'] == 'days' ? "selected" : '' ?>>Days</option>
                                                                                                            <option value="weeks" <?php echo $action_data['time_type'] == 'weeks' ? "selected" : '' ?>>Weeks</option>
                                                                                                            <option value="months" <?php echo $action_data['time_type'] == 'months' ? "selected" : '' ?>>Months</option>
                                                                                                        </select>
                                                                                                        <span class="relation-value"><?php echo $action_data['time_reference'] == 'order' ? 'After' : 'Before' ?></span>
                                                                                                        <select name="<?php echo $item_data_id ?>[action][time_reference]" class="reference">
                                                                                                            <option value="" <?php echo empty($action_data['time_reference']) ? 'selected' : '' ?>>Select Reference</option>
                                                                                                            <option value="departure" <?php echo $action_data['time_reference'] == 'departure' ? 'selected' : '' ?>>Departure Date</option>
                                                                                                            <option value="order" <?php echo $action_data['time_reference'] == 'order' ? 'selected' : '' ?>>Order</option>
                                                                                                        </select>
                                                                                                    </div>
                                                                                                </div>                 
                                                                                                <div class="messages-wrapper repeater-section-wrapper">
                                                                                                    <strong class="label">Messages</strong>
                                                                                                    <?php if (!empty($all_order_messages)) { ?>
                                                                                                                                <div class="content">
                                                                                                                                    <div class="messages-checkbox-wrapper">
                                                                                                                                        <?php foreach ($all_order_messages as $order_message_id) {
                                                                                                                                            $message_data = !empty($messages_data[$order_message_id]) ? $messages_data[$order_message_id] : [];
                                                                                                                                            $input_name_id = $item_data_id . '[messages][' . $order_message_id . ']';
                                                                                                                                            $is_checked = !empty($message_data['is_checked']);
                                                                                                                                            $send_on_expire = !empty($message_data['send_on_expire']);
                                                                                                                                            $schedule_before_expire = !empty($message_data['schedule_before_expire']);
                                                                                                                                            $days_before_expire = !empty($message_data['days_before_expire']) ? $message_data['days_before_expire'] : '';
                                                                                                                                            $time_before_expire = !empty($message_data['time_before_expire']) ? $message_data['time_before_expire'] : '';
                                                                                                                                            ?>
                                                                                                                                                                    <div class="input-group limit-message">
                                                                                                                                                                        <div class="input-wrapper type-checkbox">
                                                                                                                                                                            <input type="checkbox" role="activate" name="<?php echo $input_name_id ?>[is_checked]" id="message_<?php echo $order_message_id ?>" <?php echo $is_checked ? 'checked' : '' ?>>
                                                                                                                                                                            <label for="message_<?php echo $order_message_id ?>"><?php echo get_the_title($order_message_id) ?></label>
                                                                                                                                                                        </div>
                                                                                                                                                                        <div class="inner">
                                                                                                                                                                            <div class="input-wrapper type-checkbox">
                                                                                                                                                                                <input type="checkbox" name="<?php echo $input_name_id ?>[send_on_expire]" id="send_on_expire_<?php echo $order_message_id ?>" <?php echo $send_on_expire ? 'checked' : '' ?>>
                                                                                                                                                                                <label for="send_on_expire_<?php echo $order_message_id ?>">Send on Expiration</label>
                                                                                                                                                                            </div>
                                                                                                                                                                            <div class="input-wrapper type-checkbox">
                                                                                                                                                                                <input type="checkbox" role="schedule" name="<?php echo $input_name_id ?>[schedule_before_expire]" id="schedule_before_expire_<?php echo $order_message_id ?>" <?php echo $schedule_before_expire ? 'checked' : '' ?>>
                                                                                                                                                                                <label for="schedule_before_expire_<?php echo $order_message_id ?>">Schedule Before Expiration</label>
                                                                                                                                                                            </div>
                                                                                                                                                                            <div class="schedule-message-wrapper">
                                                                                                                                                                                <div class="input-wrapper type-text">
                                                                                                                                                                                    <input type="number" name="<?php echo $input_name_id ?>[days_before_expire]" id="days_before_expire_<?php echo $order_message_id ?>" value="<?php echo !empty($days_before_expire) ? $days_before_expire : '' ?>">
                                                                                                                                                                                    <label for="days_before_expire_<?php echo $order_message_id ?>">Days Before Expiration</label>
                                                                                                                                                                                </div>
                                                                                                                                                                                <div class="input-wrapper type-time">
                                                                                                                                                                                    <label for="time_before_expire_<?php echo $order_message_id ?>">at</label>
                                                                                                                                                                                    <input type="time" name="<?php echo $input_name_id ?>[time_before_expire]" id="time_before_expire_<?php echo $order_message_id ?>" value="<?php echo !empty($time_before_expire) ? $time_before_expire : '' ?>">
                                                                                                                                                                                </div>
                                                                                                                                                                            </div>
                                                                                                                                                                        </div>
                                                                                                                                                                    </div>
                                                                                                                                        <?php } ?>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                    <?php } ?> 
                                                                                                </div>
                                                                                            </div>
                                                                                        </li>                   
                                                                <?php } ?>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="repeater-buttons">
                                <button type="button" class="button-59 add-row-button">Add Limitation</button>
                            </div>
                        </div>
                    </div>
                    <input type="submit" value="Save">
                </form>
            </div>
        <?php }

    function save_options_data()
    {
        if (!empty($_POST['options'])) {
            $days_after_departure_to_archive_date = !empty($_POST['days_after_departure_to_archive_date']) ? $_POST['days_after_departure_to_archive_date'] : 0;
            $unavailable_second_participant_message = !empty($_POST['unavailable_second_participant_message']) ? $_POST['unavailable_second_participant_message'] : '';
            $days_before_deposit_disabled = !empty($_POST['days_before_deposit_disabled']) ? $_POST['days_before_deposit_disabled'] : 0;
            $deposit_info_page = !empty($_POST['deposit_info_page']) ? $_POST['deposit_info_page'] : '';
            $reservation_limits = !empty($_POST['reservation_limits']) ? $_POST['reservation_limits'] : [];
            $deactivated_payment_link_redirect = !empty($_POST['deactivated_payment_link_redirect']) ? $_POST['deactivated_payment_link_redirect'] : '';

            update_option('days_after_departure_to_archive_date', $days_after_departure_to_archive_date);
            update_option('unavailable_second_participant_message', $unavailable_second_participant_message);
            update_option('days_before_deposit_disabled', $days_before_deposit_disabled);
            update_option('deposit_info_page', $deposit_info_page);
            update_option('reservation_limits', $reservation_limits);
            update_option('deactivated_payment_link_redirect', $deactivated_payment_link_redirect);
        }
    }

}