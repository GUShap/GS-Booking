<?php

function set_retreats_calendar($retreats_data, $week_start, $include_prev_next, $is_single, $enable_info_element)
{
    $calendar_data_months = get_calendar_data_by_months($retreats_data);
    $first_key = array_key_first($calendar_data_months);
    $last_key = array_key_last($calendar_data_months);
    $is_first_month_centered = true;
    $is_last_month_centered = false;
    $centered_month = get_centered_month();
    ?>
    <div class="retreats-calendar-container<?php echo $is_single ? ' single-retreat' : '' ?>"
        data-fow="<?php echo $week_start ?>">
        <div class="retreats-calendar-content">
            <?php
            $month_order = 0;
            foreach ($calendar_data_months as $month_label => $month_dates) {
                $month_name = date('F', strtotime('01-' . $month_label));
                $month_number = date('m', strtotime('01-' . $month_label));
                $year = date('Y', strtotime('01-' . $month_label));
                $month_title = date('F Y', strtotime('01-' . $month_label));
                $is_centered = $centered_month == $month_number;
                $order_class = '';
                if ($month_label == $first_key)
                    $order_class = ' first';
                else if ($month_label == $last_key)
                    $order_class = ' last';
                if ($is_single && $is_centered) {
                    $order_class .= ' centered';
                    $is_first_month_centered = $month_order == 0;
                    $is_last_month_centered = $month_label == $last_key;
                }
                ?>
                <div class="month-wrapper<?php echo $order_class ?>" data-month="<?php echo $month_name ?>"
                    data-year="<?php echo $year ?>" data-order="<?php echo $month_order ?>">
                    <div class="month-heading">
                        <h4 class="month-title">
                            <?php echo $month_title ?>
                        </h4>
                    </div>
                    <div class="month-content">
                        <div class="days-of-week-wrapper">
                            <?php if ($week_start == 'sunday') { ?>
                                <div class="day-wrapper sunday" data-dow="sunday">
                                    <p>Su</p>
                                </div>
                            <?php } ?>
                            <div class="day-wrapper monday" data-dow="monday">
                                <p>Mo</p>
                            </div>
                            <div class="day-wrapper tuesday" data-dow="tuesday">
                                <p>Tu</p>
                            </div>
                            <div class="day-wrapper wednesday" data-dow="wednesday">
                                <p>We</p>
                            </div>
                            <div class="day-wrapper thursday" data-dow="thursday">
                                <p>Th</p>
                            </div>
                            <div class="day-wrapper friday" data-dow="friday">
                                <p>Fr</p>
                            </div>
                            <div class="day-wrapper saturday" data-dow="saturday">
                                <p>Sa</p>
                            </div>
                            <?php if ($week_start == 'monday') { ?>
                                <div class="day-wrapper sunday" data-dow="sunday">
                                    <p>Su</p>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="dates-wrapper">
                            <?php
                            foreach ($month_dates as $day_number => $day_data) {
                                $date_value = $year . '-' . $month_number . '-' . sprintf('%02d', $day_number);
                                $day_str = !empty($day_data['day_str']) ? $day_data['day_str'] : '';
                                $trips = !empty($day_data['trips']) ? $day_data['trips'] : [];
                                $day_args = [
                                    'day' => $day_number,
                                    'day_str' => $day_str,
                                    'date_value' => $date_value,
                                    'trips' => $trips,
                                ];
                                set_single_day($day_args, $is_single, $week_start, $include_prev_next);
                            } ?>
                        </div>
                    </div>
                </div>
                <?php $month_order++; ?>
            <?php } ?>
        </div>
        <div class="arrows-container">
            <div class="arrow-right-container arrow-container<?php echo $is_last_month_centered?' hidden':'' ?>">
                <button type="button" class="calendar-arrow-button right">&#5125;</button>
            </div>
            <div class="arrow-left-container arrow-container<?php echo $is_first_month_centered?' hidden':'' ?>">
                <button type="button" class="calendar-arrow-button left">&#5130;</button>
            </div>
        </div>
        <?php if ($enable_info_element) {
            get_retreat_info_element($retreats_data);
        } ?>
    </div>
    <style>
        :root {
            --months-count:<?php echo count($calendar_data_months) ?>;
            <?php if($is_single){?>
                --retreat-color:<?php echo $retreats_data[array_key_first($retreats_data)]['general_info']['calendar_color'] ?>;
                --retreat-hover-color:<?php echo $retreats_data[array_key_first($retreats_data)]['general_info']['calendar_color'].'80' ?>;
                <?php } ?>
        }
    </style>
    <?php
}

function get_calendar_data_by_months($retreats_data)
{
    $all_retreats_dates = get_all_departure_dates($retreats_data);
    $months_date_devision = get_months_calendar(array_shift($all_retreats_dates), array_pop($all_retreats_dates));

    foreach ($retreats_data as $retreat_id => $retreat_data) {
        $departure_dates = $retreat_data['departure_dates'];
        $retreat_duration = get_retreat_duration($retreat_id);
        $retreat_color = $retreat_data['general_info']['calendar_color'];
        if (empty($departure_dates))
            continue;
        foreach ($departure_dates as $departure_date => $departure_data) {
            $month_key = date('m-Y', strtotime($departure_date));
            $day_key = ltrim(date('d', strtotime($departure_date)), '0');
            $is_available = !empty($departure_data['registration_active']);
            if (!$is_available)
                continue;
            $months_date_devision[$month_key][$day_key]['trips'][] = [
                'retreat_id' => $retreat_id,
                'departure_date' => $departure_date,
                'is_full_booked' => !empty($departure_data['is_full_booked']),
                'is_departure' => true,
                'is_trip' => true,
                'is_return_date' => $retreat_duration < 2,
                'is_last_day_of_month' => is_last_day_of_month($departure_date),
                'is_first_day_of_month' => $day_key == 1,
                'trip_duration' => $retreat_duration,
                'day_of_trip' => 1,
                'color' => $retreat_color,
            ];
            if ($retreat_duration > 1) {
                for ($i = 1; $i < $retreat_duration; $i++) {
                    $next_date = date('Y-m-d', strtotime($departure_date . ' +' . $i . ' days'));
                    $next_month_key = date('m-Y', strtotime($next_date));
                    $next_day_key = ltrim(date('d', strtotime($next_date)), '0');
                    $months_date_devision[$next_month_key][$next_day_key]['trips'][] = [
                        'departure_date' => $departure_date,
                        'retreat_id' => $retreat_id,
                        'is_full_booked' => !empty($departure_data['is_full_booked']),
                        'is_departure' => false,
                        'is_trip' => true,
                        'is_return_date' => $i == $retreat_duration - 1,
                        'is_last_day_of_month' => is_last_day_of_month($next_date),
                        'is_first_day_of_month' => $next_day_key == 1,
                        'trip_duration' => $retreat_duration,
                        'day_of_trip' => $i + 1,
                        'color' => $retreat_color,
                        ''
                    ];
                }
            }
        }
    }
    return $months_date_devision;
}

function get_all_departure_dates($retreats_data)
{
    $all_departure_dates = [];
    foreach ($retreats_data as $retreat_id => $retreat) {
        foreach ($retreat['departure_dates'] as $departure_date => $departure_data) {
            if (!empty($departure_data['registration_active']))
                $all_departure_dates[] = [
                    'date' => $departure_date,
                    'duration' => $retreat['general_info']['retreat_duration']
                ];
        }
    }
    usort($all_departure_dates, function ($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    return $all_departure_dates;
}

function get_month_dates($selected_date)
{
    $dates = array();
    $firstDayOfMonth = new DateTime($selected_date);
    $firstDayOfMonth->modify('first day of this month');

    $lastDayOfMonth = new DateTime($firstDayOfMonth->format('Y-m-t'));

    while ($firstDayOfMonth <= $lastDayOfMonth) {
        $dateNumber = $firstDayOfMonth->format('j'); // Day of the month without leading zeros
        $dayOfWeek = strtolower($firstDayOfMonth->format('l')); // Day of the week in lowercase
        $dates[$dateNumber] = [
            'day_str' => $dayOfWeek,
            'trips' => []
        ];
        $firstDayOfMonth->modify('+1 day');
    }

    return $dates;
}

function get_months_calendar($first_departure, $last_departure)
{
    $months = array();
    $current_date = new DateTime($first_departure['date']);
    $last_retreat_date = !empty($last_departure)?$last_departure['date']: $first_departure['date'];
    $last_retreat_day = new DateTime($last_retreat_date);
    $last_departure_duration = !empty($last_departure)?$last_departure['duration']: $first_departure['duration'];

    if (!empty($last_departure)) {
        while ($current_date <= $last_retreat_day) {
            $month_key = $current_date->format('m-Y');
            $months[$month_key] = get_month_dates($current_date->format('Y-m-d'));
            $current_date->modify('first day of next month');
        }
    }

    // complete the last month
    $last_retreat_day->modify('+' . $last_departure_duration . ' days');
    $last_month_key = $last_retreat_day->format('m-Y');
    $last_month_dates = get_month_dates($last_retreat_day->format('Y-m-d'));
    $months[$last_month_key] = $last_month_dates;
    return $months;
}

function set_single_day($args, $is_single, $week_start, $include_prev_next)
{
    $day = $args['day'];
    $day_str = $args['day_str'];
    $date_value = $args['date_value'];
    $trips = $args['trips'];
    $prev_month_days = [];
    $next_month_days = [];
    $selected_retreat_id = WC()->session->get('retreat_id');
    $selected_departure_date = WC()->session->get('departure_date');
    if ($trips) {
        foreach ($trips as $trip_day_data) {
            $retreat_id = $trip_day_data['retreat_id'];
            $color = $trip_day_data['color'];
            $departure_date = $trip_day_data['departure_date'];
            $is_departure = !empty($trip_day_data['is_departure']);
            $is_return_date = !empty($trip_day_data['is_return_date']);
            $is_last_day_of_month = !empty($trip_day_data['is_last_day_of_month']);
            $is_first_day_of_month = !empty($trip_day_data['is_first_day_of_month']);
            $is_full_booked = !empty($trip_day_data['is_full_booked']);

            $continues_to_next_month = !$is_return_date && $is_last_day_of_month;
            $continues_from_prev_month = !$is_departure && $is_first_day_of_month;

            $day_of_trip = $trip_day_data['day_of_trip'];
            $trip_duration = $trip_day_data['trip_duration'];

            if ($continues_from_prev_month) {
                $days_left = $trip_duration - ($trip_duration - $day_of_trip);
                $prev_month_dates = get_prev_month_days($date_value, $days_left, $color, $retreat_id, $departure_date, $trip_duration, $is_full_booked);
                foreach ($prev_month_dates as $prev_date => $prev_day_data) {
                    $prev_month_days[$prev_date]['day_str'] = $prev_day_data['day_str'];
                    $prev_month_days[$prev_date]['trips'][] = $prev_day_data['trip_data'];
                }
            }

            if ($continues_to_next_month) {
                $days_left = $trip_duration - $day_of_trip;
                $next_month_dates = get_next_month_days($date_value, $days_left, $color, $retreat_id, $departure_date, $trip_duration, $is_full_booked);
                foreach ($next_month_dates as $next_date => $next_day_data) {
                    $next_month_days[$next_date]['day_str'] = $next_day_data['day_str'];
                    $next_month_days[$next_date]['trips'][] = $next_day_data['trip_data'];
                }
            }

        }
    }

    ?>
    <?php if (!empty($prev_month_days && $include_prev_next)) {
        ksort($prev_month_days);
        foreach ($prev_month_days as $prev_date => $prev_day_data) {
            $prev_day_args = [
                'day' => date('n/j', strtotime($prev_date)),
                'day_str' => $prev_day_data['day_str'],
                'date_value' => $prev_date,
                'trips' => $prev_day_data['trips'],
            ];
            set_single_day($prev_day_args, $is_single, $week_start, $include_prev_next);
        }
    } ?>
    <div class="day-wrapper" data-date="<?php echo $date_value ?>" data-day="<?php echo $day_str ?>"
        data-trip="<?php echo !empty(count($trips)) ? 'true' : 'false' ?>">
        <span class="date-number">
            <?php echo $day ?>
        </span>
        <?php if ($trips) {
            ?>
            <div class="retreats-container">
                <?php
                $trip_order = 0;
                foreach ($trips as $trip_day_data) {
                    $trip_order++;
                    $retreat_id = $trip_day_data['retreat_id'];
                    $is_selected = $selected_departure_date == $departure_date && $selected_retreat_id == $retreat_id && $is_single;
                    $is_disabled = !empty($selected_departure_date) && $selected_departure_date != $departure_date && $is_single;
                    $is_full_booked = !empty($trip_day_data['is_full_booked']);
                    $color = $trip_day_data['color'];
                    $departure_date = $trip_day_data['departure_date'];
                    $is_single_trip_on_day = count($trips) == 1;
                    $is_departure = !empty($trip_day_data['is_departure']);
                    $is_return_date = !empty($trip_day_data['is_return_date']);
                    $is_last_day_of_month = !empty($trip_day_data['is_last_day_of_month']);
                    $is_first_day_of_month = !empty($trip_day_data['is_first_day_of_month']);

                    $continues_to_next_month = !$is_return_date && $is_last_day_of_month;
                    $continues_from_prev_month = !$is_departure && $is_first_day_of_month;

                    $last_day_of_week = $week_start == 'sunday' ? 'saturday' : 'sunday';
                    $is_regular_trip_day = !$is_departure && !$is_return_date && !$continues_to_next_month && !$continues_from_prev_month;

                    $style = 'border:1px solid' . $color . ';';
                    $cell_classes = '';
                    if ($is_selected) {
                        $cell_classes .= ' selected';
                    }
                    if ($is_disabled) {
                        $cell_classes .= ' disabled';
                    }
                    if ($is_full_booked) {
                        $cell_classes .= ' full-booked';
                    }

                    if (!$is_single_trip_on_day && $trip_order == 1) {
                        $style .= 'border-bottom:none;';
                    }

                    $remove_border_right = false;
                    if ($day_str != $last_day_of_week) {
                        if ($is_regular_trip_day) {
                            $remove_border_right = true;
                        }
                        if ($is_departure && !$is_last_day_of_month) {
                            $remove_border_right = true;
                        }
                        if ($is_departure && $is_last_day_of_month && $continues_to_next_month && $include_prev_next) {
                            $remove_border_right = true;
                        }
                        if ($include_prev_next && $continues_to_next_month) {
                            $remove_border_right = true;
                        }

                        if (!$is_single_trip_on_day && $trip_order == 1 && $is_return_date) {
                            $remove_border_right = true;
                        }

                    }

                    if ($remove_border_right) {
                        $style .= 'border-right:none;';
                    }
                    ?>
                    <div class="retreat-single-day<?php echo $cell_classes ?>" data-departure="<?php echo $departure_date ?>"
                        data-retreat-id="<?php echo $retreat_id ?>" data-full="<?php echo $is_full_booked ? 'true' : 'false' ?>"
                        data-color="<?php echo $color ?>" style="<?php echo $style ?>">
                    </div>
                    <?php
                }
                ?>
            </div>
        <?php } ?>
    </div>
    <?php if (!empty($next_month_days && $include_prev_next)) {
        ksort($next_month_days);
        foreach ($next_month_days as $next_date => $next_day_data) {
            $next_day_args = [
                'day' => date('n/j', strtotime($next_date)),
                'day_str' => $next_day_data['day_str'],
                'date_value' => $next_date,
                'trips' => $next_day_data['trips'],
            ];
            set_single_day($next_day_args, $is_single, $week_start, $include_prev_next);
        }
    } ?>

<?php }

function get_prev_month_days($date_value, $days_left, $color, $retreat_id, $departure_date, $trip_duration, $is_full_booked)
{
    $prev_month_days = [];
    for ($i = 1; $i < $days_left; $i++) {
        $date = new DateTime($date_value);
        $date->modify('-' . $i . ' days');
        $prev_month_days[$date->format('Y-m-d')] = [
            'day_str' => strtolower($date->format('l')),
            'trip_data' => [
                'retreat_id' => $retreat_id,
                'departure_date' => $departure_date,
                'is_departure' => $i == $days_left,
                'is_return_date' => false,
                'is_last_day_of_month' => false,
                'is_first_day_of_month' => false,
                'trip_duration' => $trip_duration,
                'is_full_booked' => $is_full_booked,
                'day_of_trip' => $i,
                'color' => $color,
            ]
        ];
    }
    // revers dates order using array_reverse

    return $prev_month_days;
}

function get_next_month_days($date_value, $days_left, $color, $retreat_id, $departure_date, $trip_duration, $is_full_booked)
{
    $next_month_days = [];
    for ($i = 1; $i <= $days_left; $i++) {
        $date = new DateTime($date_value);
        $date->modify('+' . $i . ' days');
        $next_month_days[$date->format('Y-m-d')] = [
            'day_str' => strtolower($date->format('l')),
            'trip_data' => [
                'retreat_id' => $retreat_id,
                'departure_date' => $departure_date,
                'is_departure' => false,
                'is_return_date' => $i == $days_left,
                'is_last_day_of_month' => false,
                'is_first_day_of_month' => false,
                'trip_duration' => $trip_duration,
                'is_full_booked' => $is_full_booked,
                'day_of_trip' => $i,
                'color' => $color,
            ]
        ];
    }
    return $next_month_days;
}

function is_last_day_of_month($date_string)
{
    $date = new DateTime($date_string);
    $last_day_of_month = $date->format('t'); // 't' gives the number of days in the month
    return $date->format('d') == $last_day_of_month;
}
function get_matching_retreats($data, $valueToCheck)
{
    $matchingArrays = array();
    $counter = 0;
    foreach ($data as $label => $entry) {
        if (isset($entry['departure_dates']) && array_key_exists($valueToCheck, $entry['departure_dates'])) {
            $matchingArrays[$counter] = $entry;
            $matchingArrays[$counter]['id'] = $label;
            $counter++;
        }
    }

    return $matchingArrays;
}

function get_retreat_info_element($retreats_data)
{
    ?>
    <div class="retreats-info-wrapper">
        <?php foreach ($retreats_data as $retreat_id => $retreat) {
            $product = wc_get_product($retreat_id);
            $short_description = $product->get_short_description();
            $main_image = $product->get_image_id();
            $retreat_duration = $retreat['general_info']['retreat_duration'];
            $retreat_color = $retreat['general_info']['calendar_color'];
            $retreat_url = get_permalink($retreat_id);
            ?>
            <div class="retreat-info-wrapper" data-id="<?php echo $retreat_id ?>" data-name="<?php echo $retreat['name'] ?>"
                data-selected="false" style="border:0px solid <?php echo $retreat_color ?>;">
                <div class="content-container">
                    <div class="retreat-image">
                        <div class="image-overlay" style="background-color:<?php echo $retreat_color ?>;"></div>
                        <?php echo wp_get_attachment_image($main_image, 'full') ?>
                    </div>
                    <div class="retreat-info-content">
                        <div class="info-heading">
                            <h6 class="retreat-name">
                                <?php echo $retreat['name'] ?>
                            </h6>
                            <p class="retreat-duration">Duration: <span>
                                    <?php echo $retreat_duration; ?>
                                </span> Days</p>
                        </div>
                        <div class="retreat-description">
                            <?php echo $short_description ?>
                        </div>
                        <a href="<?php echo $retreat_url ?>" class="retreat-link"
                            style="background-color:<?php echo $retreat_color ?>;">Book Now</a>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php
}
function get_retreat_duration($retreat_id)
{
    $retreat_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true))
        ? get_post_meta($retreat_id, 'retreat_product_data', true)
        : [];

    return $retreat_data['general_info']['retreat_duration'];
}

function is_product_type_in_cart($product_type)
{
    // Get the cart
    $cart = WC()->cart;

    // Loop through cart items
    foreach ($cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];

        // Check if the product type matches
        if ($product->get_type() === $product_type) {
            return true; // Found the product type in the cart
        }
    }

    return false; // Product type not found in the cart
}

function check_available_second_participant($retreat_id, $departure_date, $room_id)
{
    $cart_items = WC()->cart->get_cart();
    $retreat_data = get_post_meta($retreat_id, 'retreat_product_data', true);
    // $departure_date_data = $retreat_data['departure_dates'][$departure_date];
    $guests_availability = $retreat_data['departure_dates'][$departure_date]['guests_availability'];
    $max_second_participants = $retreat_data['departure_dates'][$departure_date]['max_second_participants'];
    $room_data = $retreat_data['departure_dates'][$departure_date]['rooms_list'][$room_id];
    $room_capacity = $room_data['room_capacity'];
    $available_second_participant = true;
    $second_participants_count = 0;
    if (!empty($cart_items)) {
        foreach ($cart_items as $cart_item) {
            $is_second_participant_product = has_term('second-participant', 'product_cat', $cart_item['product_id']);
            if ($is_second_participant_product) {
                $second_participants_count++;
            }
        }
    }

    if ($guests_availability < 2 || $room_capacity < 2 || $second_participants_count >= $max_second_participants) {
        $available_second_participant = false;
    }
    return $available_second_participant;
}

function get_retreat_data()
{
    $retreats_data = [
        get_the_id() => get_post_meta(get_the_id(), 'retreat_product_data', true)
    ];
    return $retreats_data;
}

function get_centered_month()
{
    $session_retreat_id = WC()->session->get('retreat_id');
    $session_departure_date = WC()->session->get('departure_date');
    $is_program_product_page = has_term('programs', 'product_cat', get_the_ID());
    $preselected_departure_date = !empty($_REQUEST['departure_date']) ? $_REQUEST['departure_date'] : '';
    $centered_month = '';
    if ($session_retreat_id && $session_departure_date) {
        $centered_month = date('m', strtotime($session_departure_date));
    } else if ($is_program_product_page && $preselected_departure_date) {
        $centered_month = date('m', strtotime($preselected_departure_date));
    }
    return $centered_month;
}