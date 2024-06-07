<?php
function is_booking_retreat_product_page()
{
    // Check if we are on a single product page
    if (is_product()) {
        // Get the current product ID
        $product_id = get_the_ID();
        // Check if the product is of type 'booking_retreat'
        $product = wc_get_product($product_id);
        if ($product && $product->get_type() === 'booking_retreat') {
            return true;
        }
    }

    return false;
}
function get_rooms_data($departure_rooms_data = [], $prev_departure_rooms_data = [], $general_rooms_data = [], $departure_date = '')
{
    $data = [];
    if (!empty($departure_rooms_data)) {
        foreach ($departure_rooms_data as $room_id => $room_data) {
            if (array_key_exists($room_id, $prev_departure_rooms_data)) {
                $data[$room_id] = $prev_departure_rooms_data[$room_id];
                if (!isset($data[$room_id]['price'])) {
                    $data[$room_id]['price'] = $general_rooms_data[$room_id];
                }
            } else {
                $room_metadata = get_post_meta($room_id, 'package_product_data', true);
                $data[$room_id] = $room_data;
                $data[$room_id]['is_booked'] = false;
                $data[$room_id]['guests'] = [];
                $data[$room_id]['price'] = $general_rooms_data[$room_id];
                $data[$room_id]['expired_orderes_ids'] = [];
                $data[$room_id]['status'] = 'available';
                $data[$room_id]['payments_collected'] = 0;
                $data[$room_id]['room_capacity'] = $room_metadata['max_room_capacity'];
            }
        }
    }
    return $data;
}

function format_date_range($departure_date, $duration)
{
    // Create DateTime object from the departure date
    $start_date = new DateTime($departure_date);

    // Calculate end date based on duration
    $end_date = clone $start_date;
    $end_date->modify('+' . ($duration - 1) . ' days');

    // Format dates
    $start_month = $start_date->format('F');
    $start_day = $start_date->format('j');
    $end_month = $end_date->format('F');
    $end_day = $end_date->format('j');

    // Check if the end date is in a different month
    $date_range = ($start_month === $end_month)
        ? "$start_month $start_day - $end_day, " . $end_date->format('Y')
        : "$start_month $start_day - $end_month $end_day, " . $end_date->format('Y');

    return $date_range;
}

function calculate_time_left_with_html($start_time, $end_time)
{
    // Convert the time strings to DateTime objects
    $startDateTime = strtotime($start_time);
    $endDateTime = strtotime($end_time);

    // Calculate the interval between the two dates
    $timeDifference = $endDateTime - $startDateTime;
    // Format the result
    $hours = $timeDifference > 0 ? floor($timeDifference / 3600) : '00';
    $minutes = $timeDifference > 0 ? floor(($timeDifference % 3600) / 60) : '00';
    $seconds = $timeDifference > 0 ? $timeDifference % 60 : '00';

    // Return the formatted result with HTML
    return "<span class='hours'>$hours</span>:<span class='minutes'>$minutes</span>:<span class='seconds'>$seconds</span>";
}

function is_room_booked($room)
{
    return isset($room['is_booked']) && $room['is_booked'] == true;
}

function get_pages_ids()
{
    $args = [
        'post_type' => 'page',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_all_rooms_ids()
{
    $args = [
        'post_type' => 'retreat_rooms',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_all_retreats_ids()
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids', // Retrieve only post IDs
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => 'programs', // Slug of the category
            ),
        ),
    );
    return get_posts($args);

}

function get_all_retreat_messages_ids()
{
    $args = [
        'post_type' => 'retreat_messages',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_all_order_messages_ids()
{
    $args = [
        'post_type' => 'order_messages',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ];
    return get_posts($args);
}

function get_departure_date_guests_list($retreat_id, $departure_date, $is_archive)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $guests = [];
    $rooms_list = $is_archive ? $retreat_data['archived_departure_dates'][$departure_date]['rooms_list'] : $retreat_data['departure_dates'][$departure_date]['rooms_list'];
    foreach ($rooms_list as $room_id => $room_data) {
        $rooms_guests = $room_data['guests'];
        if (!empty($rooms_guests)) {
            foreach ($rooms_guests as $idx => $guest) {
                $rooms_guests[$idx]['room_name'] = get_the_title($room_id);
                $rooms_guests[$idx]['departure_date'] = $departure_date;
                if (empty($guest['order_id'])) {
                    $rooms_guests[$idx]['order_id'] = $room_data['order_id'];
                }
            }
            $guests = array_merge($guests, $rooms_guests);
        }
    }
    return $guests;
}

function get_departure_date_rooms_list($retreat_id, $departure_date, $is_archive)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $rooms_list = $is_archive
        ? $retreat_data['archived_departure_dates'][$departure_date]['rooms_list']
        : $retreat_data['departure_dates'][$departure_date]['rooms_list'];
    $rooms = [];
    if (!empty($rooms_list)) {
        foreach ($rooms_list as $room_id => $room_data) {
            $addons = [];
            if (!empty($room_data['addons'])) {
                foreach ($room_data['addons'] as $addon_id => $addon_quantity) {
                    $addon_name = get_the_title($addon_id);
                    $addons[] = [
                        'name' => $addon_name,
                        'quantity' => $addon_quantity
                    ];
                }
            }
            $room_data['addons'] = $addons;
            $rooms[] = $room_data;
        }
    }
    return $rooms;
}

function update_retreat_data($order_id, $deposit_payment_det)
{
    $order = wc_get_order($order_id);
    $order_status = $order->get_status();
    $retreat_id = $order->get_meta('retreat_id');
    $departure_date = $order->get_meta('departure_date');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $departure_date_data = $retreat_data['departure_dates'][$departure_date];
    $rooms = $order->get_meta('rooms');
    $rooms_availability = $departure_date_data['rooms_availability'];
    $guests_availability = $departure_date_data['guests_availability'];
    $is_deposit_payment = $order_status === 'partially-paid';
    $is_cancelled = $order_status === 'cancelled';
    $is_full_booked = false;
    $payment_type = !empty($deposit_payment_det['type']) ? $deposit_payment_det['type'] : '';
    $is_first_payment = $payment_type == 'deposit';
    $is_second_payment = $payment_type == 'second_payment';
    if (!$is_cancelled) {
        foreach ($rooms as $room_idx => $order_room_data) {
            $room_id = $order_room_data['room_id'];
            $room_price = $order_room_data['room_price'];
            $room_guests = $order_room_data['guests'];
            $is_deposit = !empty($order_room_data['is_deposit']);
            $room_status = $is_deposit_payment && $is_deposit ? 'deposit' : 'booked';
            $addons = [];
            $additionals = $order_room_data['additional'];
            $payment_collected = $room_price;
            if (!$is_deposit || $is_first_payment) {
                $prev_order_id = !empty($departure_date_data['rooms_list'][$room_id]['order_id'])
                    ? $departure_date_data['rooms_list'][$room_id]['order_id']
                    : '';
                if ($is_deposit) {
                    $payment_collected = $order_room_data['deposit_data']['deposit'];
                    schedule_payment_link_expiration($order_id);
                }
                $departure_date_data['rooms_list'][$room_id]['guests'] = $room_guests;
                $departure_date_data['rooms_list'][$room_id]['order_id'] = $order_id;
                $guests_availability -= count($room_guests);
                $rooms_availability -= 1;
                $departure_date_data['second_participants_count'] += count($room_guests) - 1;
                if ($prev_order_id) {
                    $expired_orders_ids = !empty($departure_date_data['rooms_list'][$room_id]['expired_orderes_ids'])
                        ? $departure_date_data['rooms_list'][$room_id]['expired_orderes_ids']
                        : [];
                    $expired_orders_ids[] = $prev_order_id;
                    $departure_date_data['rooms_list'][$room_id]['expired_orderes_ids'] = $expired_orders_ids;
                    $prev_order = wc_get_order($prev_order_id);
                    $deactivated_payment_links = get_option('deactivated_payment_links', []);
                    $deactivated_payment_links[] = $prev_order->get_checkout_payment_url();
                    update_option('deactivated_payment_links', $deactivated_payment_links);
                }
                if (!empty($additionals)) {
                    foreach ($additionals as $additional_product_id => $additional_product_data) {
                        $additional_product_quantity = $additional_product_data['quantity'];
                        $addons[$additional_product_id] = $additional_product_quantity;
                    }
                    $departure_date_data['rooms_list'][$room_id]['addons'] = $addons;
                }
            } else if ($is_second_payment) {
                $payment_collected = $is_deposit
                    ? $order_room_data['deposit_data']['remaining']
                    : 0;
            }
            $departure_date_data['rooms_list'][$room_id]['payments_collected'] += $payment_collected;
            $departure_date_data['rooms_list'][$room_id]['is_booked'] = true;
            $departure_date_data['rooms_list'][$room_id]['status'] = $room_status;
        }
        $is_full_booked = !$guests_availability || !$rooms_availability;
    } else {
        foreach ($departure_date_data['rooms_list'] as $room_id => $room_data) {
            $room_guests = $room_data['guests'];
            $is_booked = $room_data['status'] == 'booked' || $room_data['status'] == 'deposit';
            $departure_date_data['rooms_list'][$room_id]['guests'] = [];
            $departure_date_data['rooms_list'][$room_id]['is_booked'] = false;
            $departure_date_data['rooms_list'][$room_id]['status'] = 'available';
            $departure_date_data['rooms_list'][$room_id]['order_id'] = '';
            $departure_date_data['rooms_list'][$room_id]['payments_collected'] = 0;
            $guests_availability += count($room_guests);
            $rooms_availability += $is_booked ? 1 : 0;
            foreach ($room_guests as $guest) {
                unschedule_retreat_emails($guest);
            }
        }
        $is_full_booked = false;
    }
    $departure_date_data['is_full_booked'] = $is_full_booked;
    $departure_date_data['full_booked_time'] = $is_full_booked ? time() : '';
    $departure_date_data['guests_availability'] = $guests_availability;
    $departure_date_data['rooms_availability'] = $rooms_availability;
    $retreat_data['departure_dates'][$departure_date] = $departure_date_data;
    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
}

function get_messages_for_retreat_guests($order_id)
{
    $order = wc_get_order($order_id);
    $retreat_id = $order->get_meta('retreat_id');
    $all_messages = get_all_retreat_messages_ids();
    $retreat_messages = [];
    $guests_scheduled_messages = [];
    foreach ($all_messages as $message_id) {
        $message_data = get_post_meta($message_id, 'retreat_message_data', true);
        $is_message_for_retreat_guest = in_array($retreat_id, $message_data['retreats']) && in_array('guests', $message_data['recipients']);
        if (!$is_message_for_retreat_guest)
            continue;
        $retreat_messages[$message_id] = $message_data;
    }
    if (empty($retreat_messages))
        return;
    foreach ($retreat_messages as $message_id => $message_data) {
        $schedule = $message_data['schedule'];
        foreach ($schedule as $event => $time) {
            $relating_time = date('Y-m-d H:i:s');
            $calc = '+';
            $days = $time['days'];
            $hour = $time['time'];
            switch ($event) {
                case 'booking':
                    $relating_time = strtotime($order->get_date_created());
                    break;
                case 'before':
                    $calc = '-';
                    $departure_date = $order->get_meta('departure_date');
                    $relating_time = strtotime($departure_date);
                    break;
                case 'during':
                    $departure_date = $order->get_meta('departure_date');
                    $relating_time = strtotime($departure_date);
                    break;
                case 'after':
                    $departure_date = $order->get_meta('departure_date');
                    $duration = get_post_meta($retreat_id, 'retreat_product_data', true)['general_info']['retreat_duration'] - 1;
                    $relating_time = strtotime('+' . $duration . ' days', strtotime($departure_date));
                    break;
            }
            $scheduled_time = strtotime($calc . $days . ' days ' . $hour, $relating_time);
            if ($scheduled_time < time()) {
                $scheduled_time = strtotime('+2 minutes', time());
            }
            $guests_scheduled_messages[$message_id][$event] = $scheduled_time;
        }
    }
    return $guests_scheduled_messages;
}

function get_all_messages_for_retreat_waitlist($retreat_id)
{
    $all_messages = get_all_retreat_messages_ids();
    $waitlist_scheduled_messages = [];
    foreach ($all_messages as $message_id) {
        $message_data = get_post_meta($message_id, 'retreat_message_data', true);
        $schedule = $message_data['schedule'];
        $is_message_for_waitlist = in_array($retreat_id, $message_data['retreats']) && in_array('waitlist', $message_data['recipients']);
        if (!$is_message_for_waitlist || empty($schedule))
            continue;
        foreach ($schedule as $event => $time) {
            $days = $time['days'];
            $hour = $time['time'];
            $scheduled_time = strtotime('+' . $days . ' days ' . $hour, time());
            $waitlist_scheduled_messages[$message_id][$event] = $scheduled_time;
        }

    }
    return $waitlist_scheduled_messages;

}

function schedule_retreat_emails($order_id)
{
    $order = wc_get_order($order_id);
    $retreat_id = $order->get_meta('retreat_id');
    $departure_date = $order->get_meta('departure_date');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $departure_date_data = $retreat_data['departure_dates'][$departure_date];
    $order_rooms = $order->get_meta('rooms');
    $rooms_ids = array_map(function ($room) {
        return $room['room_id'];
    }, $order_rooms);
    $messages_for_retreat_guests = get_messages_for_retreat_guests($order_id);

    foreach ($rooms_ids as $room_id) {
        $room_data = $departure_date_data['rooms_list'][$room_id];
        $room_guests = $room_data['guests'];
        foreach ($room_guests as $guest_idx => $guest) {
            if (!empty($guest['is_messages_scheduled']) || empty($guest['email']))
                continue;
            $guest['scheduled_messages'] = $messages_for_retreat_guests;
            $guest['is_messages_scheduled'] = true;
            $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'][$guest_idx] = $guest;
            foreach ($messages_for_retreat_guests as $message_id => $message_data) {
                foreach ($message_data as $event => $ts) {
                    wp_schedule_single_event($ts, 'scheduled_retreat_message_template', array($order_id, $message_id, $guest['email'], $guest['name']));
                }
            }
        }
    }
    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
}

function schedule_order_emails($order_id)
{
    $order = wc_get_order($order_id);
    $order_messages = $order->get_meta('order_messages');
    foreach ($order_messages as $message_id => $message_data) {
        foreach ($message_data as $idx => $ts) {
            if ($ts < time())
                $ts = time();
            wp_schedule_single_event($ts, 'scheduled_order_message_template', array($order_id, $message_id, $order->get_order_key()));
        }
    }
}

function unschedule_order_emails($order_id)
{
    $order = wc_get_order($order_id);
    $order_messages = $order->get_meta('order_messages');
    foreach ($order_messages as $message_id => $message_data) {
        foreach ($message_data as $idx => $ts) {
            wp_unschedule_event($ts, 'scheduled_order_message_template', array($order_id, $message_id, $order->get_order_key()));
        }
    }

}
function schedule_order_expiration($order_id)
{
    $order = wc_get_order($order_id);
    $expiration_time = $order->get_meta('expiration_time');
    if (empty($expiration_time))
        return;
    $ts = strtotime($expiration_time) > time() ? strtotime($expiration_time) : time();
    wp_schedule_single_event($ts, 'scheduled_reservation_expired', array($order_id));
}
function unschedule_order_expiration($order_id)
{
    $order = wc_get_order($order_id);
    $expiration_time = $order->get_meta('expiration_time');
    $ts = strtotime($expiration_time) > time() ? strtotime($expiration_time) : time();
    wp_unschedule_event($ts, 'scheduled_reservation_expired', array($order_id));
}
function schedule_payment_link_expiration($order_id)
{
    $order = wc_get_order($order_id);
    $departure_date = $order->get_meta('departure_date');
    $ts = strtotime($departure_date);
    wp_schedule_single_event($ts, 'schedule_payment_link_expiration', array($order_id));
}

function schedule_retreat_waitlist_emails($retreat_id, $departure_date)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $waitlist = $retreat_data['departure_dates'][$departure_date]['waitlist'];
    $waitlist_messages = get_all_messages_for_retreat_waitlist($retreat_id);

    if (empty($waitlist) || empty($waitlist_messages))
        return;

    foreach ($waitlist as $user_idx => $user_details) {
        $email = $user_details['email'];
        $scheduled_messages = $user_details['scheduled_messages'];
        $name = $user_details['name'];
        if (!empty($scheduled_messages))
            continue;
        $retreat_data['departure_dates'][$departure_date]['waitlist'][$user_idx]['scheduled_messages'] = $waitlist_messages;
        foreach ($waitlist_messages as $message_id => $message_data) {
            foreach ($message_data as $event => $ts) {
                if ($ts < time())
                    $ts = time();
                wp_schedule_single_event($ts, 'scheduled_retreat_message_template', array('', $message_id, $email, $name));
            }
        }
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    }

}

function unschedule_retreat_emails($guest)
{
    $scheduled_messages = $guest['scheduled_messages'];
    $order_id = !empty($guest['order_id']) ? $guest['order_id'] : '';
    foreach ($scheduled_messages as $message_id => $message_data) {
        foreach ($message_data as $event => $ts) {
            wp_unschedule_event($ts, 'scheduled_retreat_message_template', array($order_id, $message_id, $guest['email'], $guest['name']));
        }
    }
}
function update_guest_details($retreat_id, $departure_date, $room_id, $guest_order, $new_details)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $guest = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'][$guest_order];
    $order_id = $guest['order_id'];
    $prev_scheduled_messages = $guest['scheduled_messages'];
    $new_scheduled_messages = [];
    $send_missed_scheduled_messages = !empty($new_details['send_missed_scheduled_messages']);

    if (!empty($prev_scheduled_messages)) {
        foreach ($prev_scheduled_messages as $message_id => $message_data) {
            foreach ($message_data as $event => $ts) {
                unschedule_retreat_emails($guest);
                $is_schedule_past = $ts < time();
                if ($is_schedule_past && !$send_missed_scheduled_messages)
                    continue;
                $new_scheduled_messages[$message_id][$event] = $ts;
            }
        }
    }
    $guest['name'] = $new_details['name'];
    $guest['email'] = $new_details['email'];
    $guest['phone'] = $new_details['phone'];
    $guest['scheduled_messages'] = $new_scheduled_messages;
    $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'][$guest_order] = $guest;
    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);

    foreach ($new_scheduled_messages as $message_id => $message_data) {
        foreach ($message_data as $event => $ts) {
            if ($ts < time())
                $ts = time();
            wp_schedule_single_event($ts, 'scheduled_retreat_message_template', array($order_id, $message_id, $new_details['email'], $new_details['name']));
        }
    }
}

function update_order_addons($order_id)
{
    $order = wc_get_order($order_id);
    $order_items = $order->get_items();
    foreach ($order_items as $item) {
        $quantity = $item->get_quantity();
        $product_id = $item->get_product_id();
        $is_completing_product = has_term('completing-products', 'product_cat', $product_id);
        if (!$is_completing_product)
            continue;
        $addon_data = $item->get_meta('addon_data');
        $different_order = !empty($addon_data['is_previous_order']);
        if (!$different_order)
            continue;
        $prev_order_id = $addon_data['order_id'];
        $prev_order = wc_get_order($prev_order_id);
        $retreat_id = $addon_data['retreat_id'];
        $departure_date = $addon_data['departure_date'];
        $prev_order_rooms = $prev_order->get_meta('rooms');
        $room_id = $addon_data['room_id'];
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $departure_data = $retreat_data['departure_dates'][$departure_date];
        $is_second_participant = $addon_data['is_second_participant'];
        $filtered_room_key = array_keys($filtered_room)[0];
        $filtered_room = array_filter($prev_order_rooms, function ($room) use ($room_id) {
            return isset($room['room_id']) && $room['room_id'] == $room_id;
        });
        $current_quantity = !empty($prev_order_rooms[$filtered_room_key]['additional'][$product_id]['quantity']) ? $prev_order_rooms[$filtered_room_key]['additional'][$product_id]['quantity'] : 0;

        if ($is_second_participant) {
            $guest = $addon_data['guest'];
            $guest['order_id'] = $order_id;
            $guest['main_participant'] = false;
            $guest['scheduled_messages'] = [];
            $guest['room_id'] = $room_id;
            $guest['order'] = 1;
            $departure_data['rooms_list'][$room_id]['guests'][] = $guest;
            $departure_data['second_participants_count'] += 1;
            $departure_data['guests_availability'] -= 1;
        }
        $departure_data['rooms_list'][$room_id]['addons'][$product_id] = $current_quantity + $quantity;
        $retreat_data['departure_dates'][$departure_date] = $departure_data;
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);

        $prev_order_rooms[$filtered_room_key]['additional'][$product_id]['quantity'] = $current_quantity + $quantity;
        $prev_order->update_meta_data('rooms', $prev_order_rooms);
        $prev_order->save();

        schedule_retreat_emails($prev_order_id);
    }
}
function process_variables_values($message, $order_id)
{
    $pattern = '/{{(.*?)}}/im';

    // Callback function to replace matched variables with their values
    $callback = function ($matches) use ($order_id) {
        // $matches[1] contains the content inside the '{{ }}'
        $var = $matches[0];
        return get_variable_value($var, $order_id);
    };

    // Replace all occurrences of variables with their values
    $processed_message = preg_replace_callback($pattern, $callback, $message);

    return $processed_message;
}
function get_variable_value($var, $order_id)
{
    $returned_value = '';
    $order = wc_get_order($order_id);
    $first_name = $order->get_billing_first_name();
    $last_name = $order->get_billing_last_name();
    $full_name = $first_name . ' ' . $last_name;
    $email = $order->get_billing_email();
    $phone = $order->get_billing_phone();

    $retreat_id = $order->get_meta('retreat_id');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);

    $rooms = get_message_rooms_str($order);
    $guests = get_message_guests_str($order);
    $departure_date = $order->get_meta('departure_date');
    $duration = $retreat_data['general_info']['retreat_duration'] - 1;
    $retreat_name = get_the_title($retreat_id);
    $order_date = $order->get_date_created()->format('Y-m-d');

    $order_total = get_woocommerce_currency_symbol() . number_format($order->get_total());
    $deposit_amount = !empty($order->get_meta('_awcdp_deposits_deposit_amount'))
        ? get_woocommerce_currency_symbol() . number_format($order->get_meta('_awcdp_deposits_deposit_amount'))
        : '';
    $remaining_amount = !empty($order->get_meta('_awcdp_deposits_second_payment'))
        ? get_woocommerce_currency_symbol() . number_format($order->get_meta('_awcdp_deposits_second_payment'))
        : '';

    $full_payment_due_date = $order->get_meta('expiration_time');
    $full_payment_url = $order->get_checkout_payment_url();

    switch ($var) {
        case '{{first_name}}':
            $returned_value = $first_name;
            break;
        case '{{last_name}}':
            $returned_value = $last_name;
            break;
        case '{{full_name}}':
            $returned_value = $full_name;
            break;
        case '{{email}}':
            $returned_value = $email;
            break;
        case '{{phone}}':
            $returned_value = $phone;
            break;
        case '{{order_id}}':
            $returned_value = $order_id;
            break;
        case '{{order_date}}':
            $returned_value = $order_date;
            break;
        case '{{order_total}}':
            $returned_value = $order_total;
            break;
        case '{{deposit_amount}}':
            $returned_value = $deposit_amount;
            break;
        case '{{remaining_amount}}':
            $returned_value = $remaining_amount;
            break;
        case '{{full_payment_due_date}}':
            $returned_value = date('F j, Y', strtotime($full_payment_due_date));
            break;
        case '{{full_payment_url}}':
            $returned_value = $full_payment_url;
            break;
        case '{{departure_date}}':
            $returned_value = date('F j, Y', strtotime($departure_date));
            break;
        case '{{return_date}}':
            $returned_value = date('F j, Y', strtotime($departure_date . ' + ' . $duration . ' days'));
            break;
        case '{{retreat_name}}':
            $returned_value = $retreat_name;
            break;
        case '{{rooms}}':
            $returned_value = $rooms;
            break;
        case '{{guests}}':
            $returned_value = $guests;
            break;
        case '{{guests_count}}':
            $returned_value = count($order->get_meta('guests'));
            break;
        default:
            $returned_value = '';
            break;
    }
    return $returned_value;
}
function get_message_rooms_str($order)
{
    $rooms = $order->get_meta('rooms');
    $rooms_str = '';
    foreach ($rooms as $idx => $room) {
        $room_id = $room['room_id'];
        $room_name = get_the_title($room_id);
        if ($idx == count($rooms) - 1)
            $rooms_str .= $room_name;
        else {
            if ($idx == count($rooms) - 2)
                $rooms_str .= $room_name . ' & ';
            else
                $rooms_str .= $room_name . ', ';
        }
    }
    return $rooms_str;
}
function get_message_guests_str($order)
{
    $guests = $order->get_meta('guests');
    $guests_str = '';
    foreach ($guests as $idx => $guest) {
        $guest_name = $guest['name'];
        if ($idx == count($guests) - 1)
            $guests_str .= $guest_name;
        else {
            if ($idx == count($guests) - 2)
                $guests_str .= $guest_name . ' & ';
            else
                $guests_str .= $guest_name . ', ';
        }
    }
    return $guests_str;
}
function check_order_editable($departure_date)
{
    $departure_date_time = DateTime::createFromFormat('Y-m-d', $departure_date);
    $now = new DateTime();

    if (!empty($departure_date) && $departure_date_time < $now)
        return false;


    return true;
}
function get_available_rooms_for_date($retreat_id, $departure_date, $include_cart = false)
{
    global $woocommerce;
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $is_available_for_booking = is_date_available_for_guests($retreat_id, $departure_date, $include_cart);
    $rooms_list = $retreat_data['departure_dates'][$departure_date]['rooms_list'];
    $rooms_data = [
        'rooms_list' => [],
        'status' => $is_available_for_booking ? 'available' : 'full',
        'second_participant_available' => is_second_participant_available_for_date($retreat_id, $departure_date, true),
        'unavailable_second_participant_message' => get_option('unavailable_second_participant_message', ''),
        'deposit_enabled' => is_deposit_enabled($retreat_id, $departure_date)
    ];
    $cart_items = $woocommerce->session->get('cart', array());
    $selected_rooms_in_dates = [];
    if (!empty($cart_items)) {
        foreach ($cart_items as $cart_item) {
            $is_same_retreat = $cart_item['product_id'] == $retreat_id;
            if ($is_same_retreat) {
                $selected_rooms_in_dates[] = [
                    'departure_date' => $cart_item['departure_date'],
                    'room_id' => $cart_item['room_id'],
                ];
            }

        }
    }
    foreach ($rooms_list as $room_id => $room) {
        $room_gallery = get_post_meta($room_id, 'room_gallery', true);
        $rooms_data['rooms_list'][$room_id] = $room;
        $rooms_data['rooms_list'][$room_id]['details'] = get_post_meta($room_id, 'package_product_data', true);
        $rooms_data['rooms_list'][$room_id]['image_src'] = wp_get_attachment_url(get_post_thumbnail_id($room_id));
        $rooms_data['rooms_list'][$room_id]['gallery'] = $room_gallery ? array_values(array_map(function ($image_id) {
            return wp_get_attachment_url($image_id);
        }, $room_gallery)) : [];

        $rooms_data['rooms_list'][$room_id]['can_multiple_guests'] = $room['room_capacity'] > 1;
        unset($rooms_data['rooms_list'][$room_id]['guests']);
        unset($rooms_data['rooms_list'][$room_id]['details']['payments_collected']);
        unset($rooms_data['rooms_list'][$room_id]['payments_collected']);
        unset($rooms_data['rooms_list'][$room_id]['expired_orderes_ids']);
        unset($rooms_data['rooms_list'][$room_id]['order_id']);
        unset($rooms_data['rooms_list'][$room_id]['room_capacity']);
        $rooms_data['rooms_list'][$room_id]['is_selected'] = false;
        foreach ($selected_rooms_in_dates as $selected_room) {
            if ($selected_room['departure_date'] == $departure_date && $selected_room['room_id'] == $room_id) {
                $rooms_data['rooms_list'][$room_id]['is_selected'] = true;
                break;
            }
        }
    }

    return $rooms_data;
}
function is_date_available_for_guests($retreat_id, $departure_date, $include_cart = false)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $departure_date_data = $retreat_data['departure_dates'][$departure_date];
    $is_full_booked = $departure_date_data['is_full_booked'];
    $guests_availabilty = $departure_date_data['guests_availability'];
    $rooms_availability = $departure_date_data['rooms_availability'];
    $max_participants = $departure_date_data['max_participants'];
    $guests_count = 0;
    if ($is_full_booked)
        return false;
    if ($include_cart) {
        $cart_items = WC()->cart->get_cart();
        foreach ($cart_items as $cart_item) {
            $is_program_product = has_term('programs', 'product_cat', $cart_item['product_id']);
            $is_second_participant_product = has_term('second-participant', 'product_cat', $cart_item['product_id']);
            if ($is_program_product) {
                $guests_availabilty--;
                $rooms_availability--;
                $guests_count++;
            }
            if (!$is_second_participant_product)
                continue;
            $session_retreat_id = WC()->session->get('retreat_id');
            $is_same_retreat = $cart_item['retreat_id'] == $session_retreat_id;
            if ($is_second_participant_product && $is_same_retreat) {
                $guests_availabilty--;
                $guests_count++;
            }
        }
    }
    return $guests_availabilty && $rooms_availability && $max_participants > $guests_count;
}
function is_second_participant_available_for_date($retreat_id, $departure_date, $include_cart = false)
{
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $departure_date_data = $retreat_data['departure_dates'][$departure_date];
    $max_second_participants = $departure_date_data['max_second_participants'];
    $second_participants_count = !empty($departure_date_data['second_participants_count']) ? $departure_date_data['second_participants_count'] : 0;
    if ($include_cart) {
        $cart_items = WC()->cart->get_cart();
        foreach ($cart_items as $cart_item) {
            $is_second_participant_product = has_term('second-participant', 'product_cat', $cart_item['product_id']);
            if (!$is_second_participant_product)
                continue;
            $session_retreat_id = WC()->session->get('retreat_id');
            $is_same_retreat = $cart_item['retreat_id'] == $session_retreat_id;
            if ($is_same_retreat && $is_second_participant_product) {
                $second_participants_count++;
            }
        }
    }
    return $max_second_participants > $second_participants_count;
}

function get_allowed_completing_product_quantity($product_id, $retreat_id, $departure_date, $room_id, $additional, $cart_items)
{
    $enable_multiple_items = !empty(get_post_meta($product_id, '_enable_multiple_items', true));
    $limit_quantity = !empty(get_post_meta($product_id, '_limit_quantity', true)) || !$enable_multiple_items;
    $max_items_limit = get_post_meta($product_id, '_max_items_limit', true);

    $allowed_quantity = $limit_quantity ? $max_items_limit : 999;
    if (!$enable_multiple_items)
        $allowed_quantity = 1;

    foreach ($additional as $upsell_id => $upsell_data) {
        if ($upsell_id == $product_id) {
            $quantity = $upsell_data['quantity'];
            $allowed_quantity -= $quantity;
        }
    }

    if (!empty($cart_items)) {
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $is_same_product_id = $cart_item['product_id'] == $product_id;
            $is_same_retreat_id = $cart_item['retreat_id'] == $retreat_id;
            $is_same_departure_date = $cart_item['departure_date'] == $departure_date;
            $is_same_room_id = $cart_item['room_id'] == $room_id;
            if ($is_same_product_id && $is_same_retreat_id && $is_same_departure_date && $is_same_room_id) {
                $allowed_quantity -= $cart_item['quantity'];
            }
        }
    }
    return $allowed_quantity;
}
function is_deposit_enabled($retreat_id, $departure_date)
{
    $deposit_enabled = get_post_meta($retreat_id, '_awcdp_deposit_enabled', true) == 'yes';
    $days_before_deposit_disabled = get_option('days_before_deposit_disabled', '');
    if ($days_before_deposit_disabled && $departure_date && $deposit_enabled) {
        $current_date = new DateTime();
        $departure_date_time = new DateTime($departure_date);
        $interval = $current_date->diff($departure_date_time);
        $days_difference = $interval->days;
        if ($days_difference < $days_before_deposit_disabled) {
            $deposit_enabled = false;
        }
    }
    return $deposit_enabled;

}

function upload_qr_image_file_handle($file, $target, $product_id)
{
    // Set the uploads directory
    $upload_dir = wp_upload_dir();
    $user_images_dir = trailingslashit($upload_dir['basedir']) . 'products_qr_codes/';

    // Create the directory if it doesn't exist
    if (!file_exists($user_images_dir)) {
        wp_mkdir_p($user_images_dir);
    }

    $file_name = wp_unique_filename($user_images_dir, $target . '_qr_code_' . $product_id . '.png');
    $file_path = $user_images_dir . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $url = home_url('/wp-content/uploads/products_qr_codes/') . $file_name;
        $upload_result = array(
            'file' => $file_path,
            'url' => $url
        );

        return $upload_result;
    } else {
        return new WP_Error('upload_error', 'Error moving uploaded file');
    }
}

function some(array $array, $value)
{
    foreach ($array as $element) {
        if ($element === $value) {
            return true;
        }
    }
    return false;
}

function every(array $array, $value)
{
    foreach ($array as $element) {
        if ($element !== $value) {
            return false;
        }
    }
    return true;
}

function array_every(array $array, callable $callback)
{
    foreach ($array as $element) {
        if (!$callback($element)) {
            return false;
        }
    }
    return true;
}

function dd($data)
{
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}