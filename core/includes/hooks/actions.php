<?php
function archive_passed_retreats(){
    $all_retreats_id = get_all_retreats_ids();
    if(empty($all_retreats_id)){
        return;
    }

    if(empty($all_retreats_id)) return;

    foreach($all_retreats_id as $retreat_id){
        $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
        $departure_dates = !empty($retreat_data['departure_dates'])?$retreat_data['departure_dates']:[];
        if(empty($departure_dates)){
            continue;
        }
        $duration = get_option('days_after_departure_to_archive_date','');
        $current_date = new DateTime();

        foreach($departure_dates as $date => $date_data){
            $rooms_list = !empty($date_data['rooms_list'])?$date_data['rooms_list']:[];
            if(empty( $rooms_list)){
                continue;
            }
            $givenDateTime = new DateTime($date);

           $interval = $givenDateTime->diff($current_date);
           $daysDifference = $interval->days;

            if($givenDateTime < $current_date && $daysDifference >= $duration){
                $date_data['status_tag'][] = 'archived';
                $date_data['is_available'] = false;
                $date_data['registration_active'] = false;
                $retreat_data['archived_departure_dates'][$date] = $date_data;
                foreach($rooms_list as $room_id=> $room_data){
                    $room_product_data = get_post_meta($room_id, 'package_product_data', true);
                    $payments_collected = !empty($room_data['payments_collected']) ? $room_data['payments_collected'] : 0;
                     
                    isset($room_product_data['departure_dates'])
                        ? $room_product_data['departure_dates'][] = $date
                        :[];

                    $room_product_data['payments_collected'][$date] = [
                        'retreat_id'=> $retreat_id,
                        'amount' => $payments_collected];

                    update_post_meta( $room_id, 'package_product_data', $room_product_data );
                }

                unset($retreat_data['departure_dates'][$date]);
            }
        }
        update_post_meta($retreat_id, 'retreat_product_data', $retreat_data);
    }
}
add_action('init', 'archive_passed_retreats');

function check_deactivated_payment_links() {
    // Get the current URL
    $current_url = home_url($_SERVER['REQUEST_URI']);

    // Get the deactivated payment links from the options table
    $deactivated_links = get_option('deactivated_payment_links', array());
    // Check if the current URL is in the list of deactivated links
    if (in_array($current_url, $deactivated_links)) {
        $deactivated_payment_link_redirect = get_option('deactivated_payment_link_redirect', '');
        // Perform your desired action here
        // Example: Display a message and exit
        if(!empty($deactivated_payment_link_redirect)){
            wp_redirect($deactivated_payment_link_redirect);
            exit;
        } else{
            wp_die('This payment link is deactivated.');
        }

        // Or you can redirect to another page
        // wp_redirect(home_url('/some-other-page/'));
        // exit;
    }
}
add_action('template_redirect', 'check_deactivated_payment_links');

add_action('init', function(){
    // $order = wc_get_order(14182);
    // dd($order);
    // $x = get_post_meta(14060, 'retreat_product_data', true);
    // $x['departure_dates']['2024-06-12']['rooms_availability'] = 4;
    // $x['departure_dates']['2024-06-12']['guests_availability'] = 10;
// dd($x);
    // foreach($x['departure_dates']['2024-07-03']['rooms_list'][13706]['guests'] as $key => $guest){
    //    if($key > 0){
    //     unset($x['departure_dates']['2024-07-03']['rooms_list'][13706]['guests'][$key]);
    //    }
    // }
    // update_post_meta(14060, 'retreat_product_data', $x);
//     $cart = WC()->cart->get_cart();
//     dd(count($cart));
});