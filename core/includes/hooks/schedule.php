<?php

function scheduled_reservation_expired_callback($order_id)
{
    $order = wc_get_order($order_id);
    $retreat_id = $order->get_meta('retreat_id');
    $departure_date = $order->get_meta('departure_date');
    $rooms = $order->get_meta('rooms');
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    $status = $order->get_status();

    if ($status !== 'partially-paid') {
        return;
    }
    
    foreach($rooms as $room_data){
        $room_id = $room_data['room_id'];
        $is_deposit = !empty($room_data['is_deposit']);
        $current_guests_info = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'];
        $number_of_guests = count($current_guests_info);
        
        if(!$is_deposit) continue;

        $retreat_data['departure_dates'][$departure_date]['expired_reservations'][$order_id] = $current_guests_info;
        $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['guests'] = [];
        $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['is_booked'] = false;
        $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['status'] = 'available';
        $retreat_data['departure_dates'][$departure_date]['guests_availability'] += $number_of_guests;
        $retreat_data['departure_dates'][$departure_date]['rooms_availability'] += 1;
        $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id]['expired_orderes_ids'][] = $order_id;
    }

    update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    $order->save();
}

add_action('scheduled_reservation_expired', 'scheduled_reservation_expired_callback', 10, 1);

function schedule_payment_link_expiration_callback($order_id)
{
    $order = wc_get_order($order_id);
    $payment_url = $order->get_checkout_payment_url();
    $deactivated_payment_links = get_option('deactivated_payment_links', []);
    $deactivated_payment_links[] = $payment_url;
    update_option('deactivated_payment_links', $deactivated_payment_links);
}
add_action('schedule_payment_link_expiration', 'schedule_payment_link_expiration_callback', 10, 1);

function send_retreat_message_template($order_id, $message_id, $email, $name)
{
    $message_data = get_post_meta($message_id, 'retreat_message_data', true);
    $attachment = !empty($message_data['attachment']) ? $message_data['attachment'] : '';
    $attachment_url = !empty($attachment) ? $attachment['url'] : '';
    $subject = !empty($message_data['subject']) ? $message_data['subject'] : get_the_title($message_id);
    $message = get_post_field('post_content', $message_id);
    $headers[] = 'Content-Type: text/html; charset=UTF-8;From: Eleusinia Retreat <info@eleusiniaretreat.com>';

    $fname = explode(' ', $name)[0];
    $lname = count(explode(' ', $name)) > 1 ? explode(' ', $name)[1] : $fname;
    // return;
    $message = str_replace('{{first_name}}', $fname, $message);
    $message = str_replace('{{last_name}}', $lname, $message);
    $message = str_replace('{{name}}', $name, $message);

    $sent = wp_mail($email, $subject, $message, $headers, $attachment_url);
}
add_action('scheduled_retreat_message_template', 'send_retreat_message_template', 10, 4);

function send_order_message_template($order_id, $message_id, $order_key)
{
    // dd('order_message_template');
    $order = wc_get_order($order_id);
    $email = $order->get_billing_email();
    $message_data = get_post_meta($message_id, 'order_message_data', true);
    $headers[] = 'Content-Type: text/html; charset=UTF-8;From: Eleusinia Retreat <info@eleusiniaretreat.com>';
    $subject = !empty($message_data['subject']) ? $message_data['subject'] : get_the_title($message_id);
    $attachment = !empty($message_data['attachment']) ? $message_data['attachment'] : '';
    $attachment_url = !empty($attachment) ? $attachment['url'] : '';
    $message = get_post_field('post_content', $message_id);
    $send_invoice = !empty($message_data['send_invoice']);

    $processed_subject = process_variables_values($subject, $order_id);
    $processed_massage = process_variables_values($message, $order_id);
    
    $sent = wp_mail($email, $processed_subject, $processed_massage, $headers, $attachment_url);
    if($send_invoice){
        WC()->mailer()->customer_invoice( $order );
    }
}
add_action('scheduled_order_message_template', 'send_order_message_template', 10, 3);