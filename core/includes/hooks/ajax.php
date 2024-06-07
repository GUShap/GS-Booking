<?php

function update_retreat_product_data_callback()
{
    // Get data from AJAX request
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
    $prev_departure_date = isset($_POST['prev_departure_date']) ? $_POST['prev_departure_date'] : '';
    // Your logic to update product datas
    if ($retreat_id && $departure_date) {
        $retreat_product_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true)) ? get_post_meta($retreat_id, 'retreat_product_data', true) : [];
        // Add/update data for the selected date
        if (!empty($prev_departure_date)) {
            if ($prev_departure_date != $departure_date) {
                $retreat_product_data['departure_dates'][$departure_date] = $retreat_product_data['departure_dates'][$prev_departure_date];
                unset($retreat_product_data['departure_dates'][$prev_departure_date]);
            }
            foreach ($_POST as $key => $value) {
                if (isset($retreat_product_data['departure_dates'][$departure_date][$key])) {
                    $prev_value = $retreat_product_data['departure_dates'][$departure_date][$key];
                    if ($key == 'rooms_list') {
                        $rooms_data = get_rooms_data($value, $prev_value, $retreat_product_data['rooms'], $departure_date);
                        $booked_rooms = array_filter($rooms_data, function ($room) {
                            return isset($room['is_booked']) && $room['is_booked'] === true;
                        });
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $rooms_data;
                        $retreat_product_data['departure_dates'][$departure_date]['rooms_availability'] = count($rooms_data) - count($booked_rooms);
                    } else if ($key == 'registration_active') {
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $value == 'true';
                        $retreat_product_data['departure_dates'][$departure_date]['status_tags'][] = 'active';
                    } else if ($key == 'max_participants') {
                        $retreat_product_data['departure_dates'][$departure_date][$key] = intval($value);
                        $retreat_product_data['departure_dates'][$departure_date]['guests_availability'] += intval($value) - $prev_value;
                    } else {
                        $retreat_product_data['departure_dates'][$departure_date][$key] = $value;
                        $key = array_search('active', $retreat_product_data['departure_dates'][$departure_date]['status_tags']);
                        unset($retreat_product_data['departure_dates'][$departure_date]['status_tags'][$key]);
                    }
                }
            }
        } else {
            $rooms_data = get_rooms_data($_POST['rooms_list'], [], $retreat_product_data['rooms'], $departure_date);
            $retreat_product_data['departure_dates'][$departure_date] = array(
                'is_available' => true,
                'is_full_booked' => '',
                'registration_active' => true,
                'max_participants' => $_POST['max_participants'],
                'rooms_availability' => count($rooms_data),
                'rooms_list' => $rooms_data,
                'guests_availability' => $_POST['max_participants'],
                'waitlist' => array(),
                'expired_reservations' => array(),
                'status_tags' => ['running'],
                'retreat_id' => $retreat_id,
                'max_second_participants' => $_POST['max_second_participants'],
                'second_participants_count' => 0,
            );
        }

        // sort by date
        ksort($retreat_product_data['departure_dates']);
        // Update product metadata
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_product_data);
    }

    // Send a response back to the AJAX request
    wp_send_json_success($retreat_product_data);
    wp_die();
}
add_action('wp_ajax_update_retreat_product_data', 'update_retreat_product_data_callback');

function remove_retreat_departure_date_callback()
{
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';

    if ($retreat_id && $departure_date) {
        $retreat_product_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true)) ? get_post_meta($retreat_id, 'retreat_product_data', true) : [];
        if (isset($retreat_product_data['departure_dates'][$departure_date])) {
            unset($retreat_product_data['departure_dates'][$departure_date]);
        }
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_product_data);
    }
    wp_send_json_success($retreat_product_data);
    wp_die();
}
add_action('wp_ajax_remove_retreat_departure_date', 'remove_retreat_departure_date_callback');

function get_available_rooms_callback()
{
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'rooms_nonce')) {

        $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
        $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $rooms_data = get_available_rooms_for_date($retreat_id, $departure_date, true);

        wp_send_json_success($rooms_data);
    } else {
        wp_send_json_error();
    }
    wp_die();
}
add_action('wp_ajax_get_available_rooms', 'get_available_rooms_callback');
add_action('wp_ajax_nopriv_get_available_rooms', 'get_available_rooms_callback');

function add_retreat_to_cart_callback()
{
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'add_to_cart_nonce')) {
        $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
        $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

        if (empty($retreat_id) || empty($departure_date) || empty($room_id)) {
            wp_send_json_error();
        }

        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $is_booked = !empty($retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked']);

        if ($is_booked) {
            wp_send_json_error();
        }

        $cart_item_data = [];
        foreach ($_POST as $key => $value) {
            if ($key !== 'nonce' && $key !== 'action') {
                $cart_item_data[$key] = $value;
            }
            if ($key == 'additional') {
                $completing_products_data = [];
                foreach ($value as $upsell_id => $quantity) {
                    $is_second_participant = has_term('second-participant', 'product_cat', $upsell_id);
                    $upsell_item_data = [
                        'departure_date' => $departure_date,
                        'room_id' => $room_id,
                        'retreat_id' => $retreat_id,
                        'is_hidden' => true,
                    ];
                    if ($is_second_participant) {
                        $cart_item_data['is_second_participant'] = true;
                    }
                    $upsell_cart_key = WC()->cart->add_to_cart($upsell_id, $quantity, 0, array(), $upsell_item_data);

                    $completing_products_data[$upsell_id] = [
                        'quantity' => $quantity,
                        'related_item_key' => $upsell_cart_key
                    ];
                }
                $cart_item_data['additional'] = $completing_products_data;
            }
        }
        WC()->cart->add_to_cart($retreat_id, 1, 0, array(), $cart_item_data);
        $redirect_after_atc_id = get_option('redirect_after_atc', get_the_id());
        $redirect_url = get_permalink($redirect_after_atc_id);
        $res = [
            'room_id' => $room_id,
            'room_name' => get_the_title($room_id),
            'departure_date' => $departure_date
        ];

        WC()->session->set('retreat_id', $retreat_id);
        WC()->session->set('departure_date', $departure_date);
        WC()->session->set('is_retreat', true);

        wp_send_json_success($res);
    } else {
        wp_send_json_error();
    }
    wp_die();
}
add_action('wp_ajax_add_retreat_to_cart', 'add_retreat_to_cart_callback');
add_action('wp_ajax_nopriv_add_retreat_to_cart', 'add_retreat_to_cart_callback');

function add_to_retreat_waitlist_callback()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add_to_cart_nonce')) {
        wp_send_json_error('Auth Is Not Good');

    }
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    if ($name && $email) {
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $waitlist = $retreat_data['departure_dates'][$departure_date]['waitlist'];
        if (!empty($waitlist)) {
            foreach ($waitlist as $user_idx => $user_details) {
                if (in_array($email, $user_details)) {
                    wp_send_json_error('You Are Already on Waitlist for ' . get_the_title($retreat_id) . ' Retreat, on ' . date('F j, Y', strtotime($departure_date)));
                }
            }
        }
        $waitlist[] = [
            'name' => $name,
            'email' => $email,
            'emails_recieved' => []
        ];
        $retreat_data['departure_dates'][$departure_date]['waitlist'] = $waitlist;
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
        schedule_retreat_waitlist_emails($retreat_id, $departure_date);
        wp_send_json_success('Registration for Waitlist is Completed');
    }
    wp_die();

}
add_action('wp_ajax_add_to_retreat_waitlist', 'add_to_retreat_waitlist_callback');
add_action('wp_ajax_nopriv_add_to_retreat_waitlist', 'add_to_retreat_waitlist_callback');

// In your theme's functions.php or a custom plugin file
function upload_product_qr_file_callback()
{
    // Get the SVG content, target, and product_id from the AJAX request
    $target = sanitize_text_field($_POST['target']);
    $product_id = intval($_POST['product_id']);
    $file = $_FILES['qr_file'];

    $upload_result = upload_qr_image_file_handle($file, $target, $product_id);

    if (!is_wp_error($upload_result)) {
        $image_url = $upload_result['url'];
        update_post_meta($product_id, $target . '_qr_image_url', $image_url);
        wp_send_json_success(array('file_url' => $image_url));
    }
}
add_action('wp_ajax_upload_product_qr_file', 'upload_product_qr_file_callback');

function send_order_emails_callback()
{
    $action = isset($_POST['email_action']) ? sanitize_text_field($_POST['email_action']) : '';
    $message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    if ($action == 'retreat_message') {
        send_retreat_message_template($order_id, $message_id, $email, $name);
    }
    if ($action == 'order_message') {
        send_order_message_template($order_id, $message_id, '');
    }

    wp_send_json_success('success');
    wp_die();

}
add_action('wp_ajax_send_order_emails', 'send_order_emails_callback');

function check_order_editable_callback()
{
    // check nonce of $_POST['security']
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }
    if (!isset($_POST['order_id'])) {
        wp_send_json_error('Invalid order id');
    }

    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
    $product_id = isset($_POST['product_id']) ? $_POST['product_id'] : 0;
    $order = wc_get_order($order_id);
    if(empty($order)){
        wp_send_json_error('Order not found');
    }
    $order_departure_date = $order->get_meta('departure_date');
    $cart_departure_date = WC()->session->get('departure_date');
    $order_retreat_id = $order->get_meta('retreat_id');
    $cart_retreat_id = WC()->session->get('retreat_id');
    $include_cart = $order_departure_date == $cart_departure_date && $order_retreat_id == $cart_retreat_id;
    $is_second_participant = has_term('second-participant', 'product_cat', $product_id);
    $is_second_participant_available = is_second_participant_available_for_date($order_retreat_id, $order_departure_date, $include_cart);
    $is_editable = check_order_editable($order_departure_date);
    $items_html = [];
    if ($is_editable) {
        $order = wc_get_order($order_id);
        $rooms = $order->get_meta('rooms');
        $cart_items = WC()->cart->get_cart();
        if (!empty($rooms)) {
            foreach ($rooms as $room) {
                $room_id = $room['room_id'];
                $additional = $room['additional'];
                $allowed_quantity = get_allowed_completing_product_quantity($product_id,$order_retreat_id, $order_departure_date, $room_id,$additional,$cart_items);
                $is_disabled = $allowed_quantity <= 0;
                $disabled_str = $is_disabled ? 'disabled' : '';
                $html_str = '<div class="item-option-wrapper">';
                $html_str .= '<div class="input-wrapper type-checkbox room-item-wrapper">';
                $html_str .= '<input type="checkbox" name="room[' . $room_id . ']" id="order-' . $order_id . '-' . $room_id . '" data-order="' . $order_id . '" data-room="' . $room_id . '" value="1" ' . $disabled_str . ' >';
                $html_str .= '<label for="order-' . $order_id . '-' . $room_id . '"><strong>' . get_the_title($room_id) . '</strong> on order #' . $order_id . '</label>';
                $html_str .= '</div>';
                if (!$is_disabled) {
                    $html_str .= '<div class="quantity-atc-wrapper">';
                    if ($allowed_quantity > 1) {
                        $html_str .= '<input type="number" name="quantity" class="quantity" id="quantity-' . $room_id . '" value="1" min="1" max="' . $allowed_quantity . '">';
                    }
                    $html_str .= '<button class="add-to-cart" data-source="order" data-room="' . $room_id . '" data-order="' . $order_id . '" data-quantity="1">Add to Cart</button>';
                    $html_str .= '</div>';
                    $html_str .= '</div>';
                }
                $items_html[] = $html_str;
            }
        }
    }
    if ($is_second_participant && !$is_second_participant_available) {
        $is_editable = false;
        $items_html = 'Second participant is not available for this date.';
    }
    $res = [
        'is_editable' => $is_editable,
        'items_html' => $items_html
    ];
    wp_send_json_success($res);
    wp_die();
}
add_action('wp_ajax_check_order_editable', 'check_order_editable_callback');
add_action('wp_ajax_nopriv_check_order_editable', 'check_order_editable_callback');

function add_product_to_cart_item_callback()
{
    // add security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = WC()->session->get('departure_date');
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $retreat_item_key = isset($_POST['retreat_item_key']) ? $_POST['retreat_item_key'] : '';
    $retreat_item = WC()->cart->get_cart_item($retreat_item_key);
    $additional = $retreat_item['additional'];
    $is_second_participant_prod = has_term('second-participant', 'product_cat', $product_id);
    $is_second_participant_available = is_second_participant_available_for_date($retreat_id, $departure_date, true);
    if (!$room_id) {
        wp_send_json_error('Invalid room id');
    }
    if (!$retreat_item) {
        wp_send_json_error('Invalid retreat item key');

    }
    if ($is_second_participant_prod && !$is_second_participant_available && $quantity) {
        wp_send_json_error('Second participant is not available');
    }
    if ($quantity) {
        $additional[$product_id]['quantity'] = $quantity;
        if ($is_second_participant_prod) {
            $retreat_item['is_second_participant'] = true;
        }

    } else {
        unset($additional[$product_id]);
        if ($is_second_participant_prod)
            $retreat_item['is_second_participant'] = false;
    }

    WC()->cart->remove_cart_item($retreat_item_key);

    if (!empty($additional)) {
        foreach ($additional as $upsell_id => $upsell_data) {
            $upsell_item_data = [
                'retreat_id' => $retreat_id,
                'room_id' => $room_id,
                'departure_date' => $departure_date,
                'is_hidden' => true
            ];
            $upsell_quantity = $upsell_data['quantity'];

            $new_related_item_key = WC()->cart->add_to_cart($upsell_id, $upsell_quantity, 0, array(), $upsell_item_data);
            $additional[$upsell_id]['related_item_key'] = $new_related_item_key;
        }
    }

    $retreat_item['additional'] = $additional;
    unset($retreat_item['key']);

    WC()->cart->add_to_cart($retreat_id, 1, 0, array(), $retreat_item);
    WC()->session->set('is_retreat', true);
    WC()->session->set('retreat_id', $retreat_id);
    WC()->session->set('departure_date', $departure_date);

    wp_send_json_success($retreat_item);
    wp_die();

}
add_action('wp_ajax_add_product_to_cart_item', 'add_product_to_cart_item_callback');
add_action('wp_ajax_nopriv_add_product_to_cart_item', 'add_product_to_cart_item_callback');

function add_product_to_existing_order_callback()
{
    // add security
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'completing_product_nonce')) {
        wp_send_json_error('Invalid security token');
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = intval($_POST['quantity']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
    $order = wc_get_order($order_id);
    $departure_date = $order->get_meta('departure_date');
    $retreat_id = $order->get_meta('retreat_id');
    $item_data = [
        'order_id' => $order_id,
        'room_id' => $room_id,
        'retreat_id' => $retreat_id,
        'departure_date' => $departure_date,
        'is_hidden' => false,
    ];

    if ($order_id) {
        WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $item_data);
        wp_send_json_success('true');
    } else {
        wp_send_json_error('Product not found in order');
    }
    wp_die();


}
add_action('wp_ajax_add_product_to_existing_order', 'add_product_to_existing_order_callback');
add_action('wp_ajax_nopriv_add_product_to_existing_order', 'add_product_to_existing_order_callback');

function get_csv_data_callback()
{
    $action = isset($_POST['csv_action']) ? $_POST['csv_action'] : '';
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
    $is_archive = !empty($_POST['is_archive']);

    $csv_data = [];
    if ($action == 'guests') {
        $csv_data = get_departure_date_guests_list($retreat_id, $departure_date,$is_archive);
    }
    if ($action == 'rooms') {
        $csv_data = get_departure_date_rooms_list($retreat_id, $departure_date,$is_archive);
    }
    wp_send_json_success($csv_data);
    wp_die();

}
add_action('wp_ajax_get_csv_data', 'get_csv_data_callback');

function archive_departure_date_callback()
{
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date = isset($_POST['departure_date']) ? $_POST['departure_date'] : '';
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $archived_departures = !empty($retreat_data['archived_departure_dates']) ? $retreat_data['archived_departure_dates'] : [];

    $departure_date_key = $departure_date;
    // for ($i = 0; $i < count($archived_departures); $i++) {
    //     if (array_key_exists($departure_date . '-' . $i, $archived_departures))
    //         continue;
    //     else
    //         $departure_date_key = $departure_date . '-' . $i;
    // }
    $retreat_data['departure_dates'][$departure_date]['status_tags'][] = 'archived';
    $retreat_data['departure_dates'][$departure_date]['is_available'] = false;
    $retreat_data['departure_dates'][$departure_date]['registration_active'] = false;
    $retreat_data['archived_departure_dates'][$departure_date_key] = $retreat_data['departure_dates'][$departure_date];
    
    unset($retreat_data['departure_dates'][$departure_date]);
    ksort($retreat_data['archived_departure_dates']);

    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    wp_send_json_success('success');
    wp_die();
}
add_action('wp_ajax_archive_departure_date', 'archive_departure_date_callback');


function remove_date_from_archive_callback()
{
    $retreat_id = isset($_POST['retreat_id']) ? intval($_POST['retreat_id']) : 0;
    $departure_date_key = isset($_POST['departure_date_key']) ? $_POST['departure_date_key'] : '';
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $archived_departures = !empty($retreat_data['archived_departure_dates']) ? $retreat_data['archived_departure_dates'] : [];

    if (isset($archived_departures[$departure_date_key])) {
        unset($archived_departures[$departure_date_key]);
    }
    $retreat_data['archived_departure_dates'] = $archived_departures;
    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    wp_send_json_success('success');
    wp_die();
}
add_action('wp_ajax_remove_date_from_archive', 'remove_date_from_archive_callback');

function update_guest_details_callback()
{
    $retreat_id = (int) $_POST['retreat_id'];
    $departure_date = $_POST['departure_date'];
    $room_id = !empty($_POST['room_id']) ? (int) $_POST['room_id'] : '';
    $guest_order = !empty($_POST['order']) ? (int) $_POST['order'] : 0;
    $new_details = $_POST['details'];

    update_guest_details($retreat_id, $departure_date, $room_id, $guest_order, $new_details);
    // $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    // $prev_details = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'][$guest_order];
    // // foreach($retreats as $retreat_id => $partial_data){
    //     dd($new_details);
    //     dd($prev_details);
    // // }
    wp_send_json_success('success');
}
add_action('wp_ajax_update_guest_details', 'update_guest_details_callback');