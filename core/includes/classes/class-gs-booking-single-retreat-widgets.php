<?php

class GS_Booking_Retreat_Duration_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'retreat_duration_widget';
    }

    public function get_title()
    {
        return esc_html__('Retreat Duration', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-clock-o";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'duration'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'before-after',
            [
                'label' => esc_html__('Prefix & Sufix', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'prefix',
            [
                'label' => esc_html__('Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Duration:', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'suffix',
            [
                'label' => esc_html__('Suffix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('days', 'elementor-addon'),
            ]
        );
        $this->end_controls_section();

        // Content Tab End

        // Style Tab Start

        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .retreat-duration-wrapper' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .retreat-duration-wrapper',
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreat_duration = get_retreat_duration(get_the_id());
        ?>
        <div class="retreat-duration-wrapper">
            <p class="retreat-duration" style="margin:0;">
                <span class="prefix">
                    <?php echo $settings['prefix'] ?>
                </span>
                <?php echo $retreat_duration ?>
                <span class="suffix">
                    <?php echo $settings['suffix'] ?>
                </span>
            </p>
        </div>

        <?php
    }
}

class GS_Booking_Group_Size_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'group_size_widget';
    }

    public function get_title()
    {
        return esc_html__('Group Size', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-shape";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['group', 'size'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'before-after',
            [
                'label' => esc_html__('Prefix & Sufix', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        $this->add_control(
            'prefix',
            [
                'label' => esc_html__('Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Group Size:', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'suffix',
            [
                'label' => esc_html__('Suffix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('participants', 'elementor-addon'),
            ]
        );
        $this->end_controls_section();

        // Content Tab End

        // Style Tab Start

        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .group-size-wrapper' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .group-size-wrapper',
            ]
        );
        $this->end_controls_section();

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreat_id = get_the_id();
        $retreat_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true))
            ? get_post_meta($retreat_id, 'retreat_product_data', true)
            : [];
        $general_info = $retreat_data['general_info'];
        ?>
            <div class="group-size-wrapper">
                <p class="group-size" style="margin:0;">
                    <span class="prefix">
                        <?php echo $settings['prefix'] ?>
                    </span>
                    <?php echo $general_info['min_group_size'] . '-' . $general_info['max_group_size']; ?>
                    <span class="suffix">
                        <?php echo $settings['suffix'] ?>
                    </span>
                </p>
            </div>
        <?php
    }
}

class GS_Booking_Retreat_Location_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'retreat_location_widget';
    }

    public function get_title()
    {
        return esc_html__('Retreat Location', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-map-pin";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'location'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Text', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .retreat-location' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'content_typography',
                'selector' => '{{WRAPPER}} .retreat-location',
            ]
        );

        $this->end_controls_section();

    }

    protected function render()
    {
        $retreat_id = get_the_id();
        $retreat_data = !empty(get_post_meta($retreat_id, 'retreat_product_data', true))
            ? get_post_meta($retreat_id, 'retreat_product_data', true)
            : [];
        $retreat_loaction_url = !empty($retreat_data) ? $retreat_data['general_info']['retreat_location_url'] : '#';
        $retreat_address = !empty($retreat_data) ? $retreat_data['general_info']['retreat_address'] : '';
        ?>
        <div class="retreat-location-wrapper">
            <a href="<?php echo $retreat_loaction_url ?>" target="_blank" class="retreat-location">
                <?php echo $retreat_address; ?>
            </a>
        </div>

        <?php
    }
}

class GS_Booking_Single_Retreat_Calendar extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'single_retreat_calendar';
    }

    public function get_title()
    {
        return esc_html__('Single Retreat Calendar', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-preview-medium";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['retreat', 'calendar'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'text_content',
            [
                'label' => esc_html__('Text Content', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'title',
            [
                'label' => esc_html__('Title', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                // 'default' => esc_html__( 'All Retreas', 'elementor-addon' ),
            ]
        );
        $this->add_control(
            'instruction_text',
            [
                'label' => esc_html__('Instruction Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Select a date to book your retreat', 'elementor-addon'),
            ]
        );
        $this->add_control(
            'retreat_in_cart_text',
            [
                'label' => esc_html__('Retreat In Cart Message', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Please complete retreat purchase before adding another', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'room_title_text',
            [
                'label' => esc_html__('Rooms List Title', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Available Rooms for:', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'price_prefix',
            [
                'label' => esc_html__('Room Price Prefix', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Price for Room:', 'elementor-addon'),
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => esc_html__('Button Text', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('BOOK NOW', 'elementor-addon'),
            ]
        );

        $this->end_controls_section();
        /**************/
        $this->start_controls_section(
            'details',
            [
                'label' => esc_html__('Calendar Details', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        // content tab type radio of which day the week starts - sunday or monday
        $this->add_control(
            'week_start',
            [
                'label' => esc_html__('Week Start', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'sunday',
                'options' => [
                    'sunday' => esc_html__('Sunday', 'elementor-addon'),
                    'monday' => esc_html__('Monday', 'elementor-addon'),
                ],
            ]
        );
        $this->add_control(
            'include_prev_next',
            [
                'label' => esc_html__('Include Previous and Next Month Dates', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => '1',
                'label_on' => esc_html__('Yes', 'elementor-addon'),
                'label_off' => esc_html__('No', 'elementor-addon'),
                'return_value' => '1',
            ]
        );

        $this->end_controls_section();

        // // Content Tab End

        // // Style Tab Start
        $this->start_controls_section(
            'section_title_style',
            [
                'label' => esc_html__('Title', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        // Title Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'calendar_title_typography',
                'label' => esc_html__('Title Typography', 'elementor-addon'),
                'selector' => '{{WRAPPER}} .retreats-calendar-title',
            ]
        );

        // Title Color
        $this->add_control(
            'calendar_title_color',
            [
                'label' => esc_html__('Calendar Title Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .retreats-calendar-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        // Title Alignment
        $this->add_responsive_control(
            'calendar_title_alignment',
            [
                'label' => esc_html__('Alignment', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'elementor-addon'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'elementor-addon'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'elementor-addon'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .retreats-calendar-title' => 'text-align: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'months_style',
            [
                'label' => esc_html__('Months', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'month_title_typography',
                'label' => esc_html__('Month Title Typography', 'elementor-addon'),
                'selector' => '{{WRAPPER}} .month-title',
            ]
        );

        $this->add_control(
            'months_title_color',
            [
                'label' => esc_html__('Month Title Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .month-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'month_bg_color',
            [
                'label' => esc_html__('Month Background Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f1f1f1',
                'selectors' => [
                    '{{WRAPPER}} .month-wrapper ' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .retreat-single-day ' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        $this->end_controls_section();

        $this->start_controls_section(
            'days_style',
            [
                'label' => esc_html__('Days', 'elementor-addon'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'days_of_week_typography',
                'label' => esc_html__('Days Of Week Typography', 'elementor-addon'),
                'selector' => '{{WRAPPER}} .days-of-week-wrapper .day-wrapper p',
            ]
        );

        $this->add_control(
            'days_of_week_color',
            [
                'label' => esc_html__('Days Of Week Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .days-of-week-wrapper .day-wrapper p' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'day_title_typography',
                'label' => esc_html__('Calendar Days Typography', 'elementor-addon'),
                'selector' => '{{WRAPPER}} .day-wrapper .date-number',
            ]
        );

        $this->add_control(
            'days_title_color',
            [
                'label' => esc_html__('Calendar Days Color', 'elementor-addon'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .day-wrapper .date-number' => 'color: {{VALUE}}',
                ],
            ]
        );
        $this->end_controls_section();
    }
	public function get_style_depends() {
		return [ 'single-retreat-style' ];
	}
    protected function render()
    {
        $has_retreat_in_cart = WC()->session->get('is_retreat');
        $settings = $this->get_settings_for_display();
        $retreat_id = get_the_id();
        $retreat_data = get_retreat_data();
        if (empty($retreat_data) || empty($retreat_id) || empty($retreat_data[$retreat_id]))
            return;
        $retreat_duration = $retreat_data[$retreat_id]['general_info']['retreat_duration'];
        $product = wc_get_product($retreat_id);
        $upsell_ids = !empty($product) ? $product->get_upsell_ids() : [];
        $departure_date = !empty(WC()->session->get('is_retreat')) ? WC()->session->get('departure_date') : '';
        ?>
        <div class="book-retreat-container">
            <div class="calendar-container">
                <div class="retreats-calendar-heading">
                    <h5 class="retreats-calendar-title">
                        <?php echo $settings['title'] ?>
                    </h5>
                </div>
                <?php set_retreats_calendar($retreat_data, $settings['week_start'], !empty($settings['include_prev_next']), true, false); ?>
            </div>
            <div class="add-retreat-to-cart-container" date-selected="false" room-selected="false">
                <div class="instruction-text-wrapper">
                    <svg class="instructions-icon" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" fill="#595959"
                        stroke="#595959">
                        <g id="SVGRepo_iconCarrier">
                            <path d="M248,64C146.39,64,64,146.39,64,248s82.39,184,184,184,184-82.39,184-184S349.61,64,248,64Z"
                                style="fill:none;stroke:#707070;stroke-miterlimit:10;stroke-width:32px"></path>
                            <polyline points="220 220 252 220 252 336"
                                style="fill:none;stroke:#707070;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px">
                            </polyline>
                            <line x1="208" y1="340" x2="296" y2="340"
                                style="fill:none;stroke:#707070;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px">
                            </line>
                            <path d="M248,130a26,26,0,1,0,26,26A26,26,0,0,0,248,130Z"></path>
                        </g>
                    </svg>
                    <p class="instructions-text">
                        <?php echo $has_retreat_in_cart ? $settings['retreat_in_cart_text'] : $settings['instruction_text'] ?>
                    </p>
                </div>
                <div class="retreat-dates-container">
                    <p class="dates-prefix">
                        <?php echo $settings['room_title_text'] ?>
                    </p>
                    <p class="retreat-dates-range"></p>
                </div>
                <div class="rooms-list-container" data-active="false" data-duration="<?php echo $retreat_duration ?>">
                    <div class="rooms-dropdown-wrapper">
                        <select class="rooms-options" id="rooms-options-select">
                        </select>
                        <div class="show-rooms-list-wrapper"><button type="button" class="open-list-popup-button">Show Rooms
                                List</button></div>
                        <div class="rooms-list-popup" data-active="false">
                            <div class="list-wrapper">
                                <div class="close-button-wrapper">
                                    <button type="button" class="close-button">&#215;</button>
                                </div>
                                <ul class="rooms-list"></ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="room-price-wrapper">
                    <p class="price-prefix">
                        <?php echo $settings['price_prefix'] ?>
                    </p>
                    <p class="price-text">
                        <span class="currency">
                            <?php echo get_woocommerce_currency_symbol() ?>
                        </span><span class="price"></span>
                    </p>
                    <?php if (!empty($upsell_ids)) {
                        ?>
                        <div class="completing-products">
                            <?php
                            foreach ($upsell_ids as $upsell_id) {
                                $upsell_product = wc_get_product($upsell_id);
                                $upsell_product_name = $upsell_product->get_name();
                                $upsell_product_price = $upsell_product->get_price();
                                $el_id = str_replace(' ', '-', strtolower($upsell_product_name));
                                $enable_multiple_items = !empty(get_post_meta($upsell_id, '_enable_multiple_items', true));
                                $limit_quantity = !empty(get_post_meta($upsell_id, '_limit_quantity', true));
                                $max_items_limit = get_post_meta($upsell_id, '_max_items_limit', true);
                                $max_attr_txt = $limit_quantity ? 'max="' . $max_items_limit . '"' : '';
                                $is_second_participant = has_term('second-participant', 'product_cat', $upsell_id);
                                ?>
                                <div class="completing-product-wrapper <?php echo $el_id ?>-wrapper">
                                    <div class="<?php echo $el_id ?>-checkbox-wrapper checkbox-wrapper">
                                        <input type="checkbox" class="upsell-checkbox" name="additional[<?php echo $upsell_id ?>]"
                                            id="<?php echo $upsell_id ?>" value="1"
                                            data-second-participant="<?php echo $is_second_participant ? 'true' : 'false' ?>">
                                        <label for="<?php echo $el_id ?>">Add
                                            <?php echo $upsell_product_name ?>
                                        </label>
                                    </div>
                                    <p class="<?php echo $el_id ?>-price completing-product-price">for
                                        <span class="currency">
                                            <?php echo get_woocommerce_currency_symbol() ?>
                                        </span>
                                        <span class="upsell-price">
                                            <?php echo number_format($upsell_product_price) ?>
                                        </span>
                                    </p>

                                    <?php if ($enable_multiple_items) { ?>
                                        <div class="quantity-wrapper">
                                            <input type="number" class="quantity-input" name="quantity[<?php echo $upsell_id ?>]"
                                                id="<?php echo $upsell_id ?>" min="1" <?php echo $max_attr_txt ?> value="1">
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
                <?php $this->deposit_radio_options($retreat_id, $departure_date) ?>
                <div class="book-retreat-wrapper">
                    <button type="button" class="book-retreat-button" role="add-to-cart" disabled>
                        <?php echo $settings['button_text'] ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    function deposit_radio_options($retreat_id, $departure_date)
    {
        $deposit_type = get_post_meta($retreat_id, '_awcdp_deposit_type', true);
        $deposit_amount = get_post_meta($retreat_id, '_awcdp_deposits_deposit_amount', true);
        $text_settings = get_option('awcdp_text_settings');
        $default_selection = get_option('awcdp_general_settings')['default_selected'];
        $deposit_amount_text = $deposit_amount;
        $deposit_enabled = is_deposit_enabled($retreat_id, $departure_date);
        if ($deposit_type == 'percent')
            $deposit_amount_text .= '%';
        if ($deposit_type == 'fixed')
            $deposit_amount_text = get_woocommerce_currency_symbol() . $deposit_amount;
        ?>
        <?php if ($deposit_enabled) { ?>
            <div class="awcdp-deposits-wrapper " data-product_id="<?php echo $retreat_id ?>">
                <div class="awcdp-deposits-option ">
                    <div class="awcdp-radio pay-deposit">
                        <div>
                            <input id="awcdp-option-pay-deposit" name="awcdp_deposit_option" type="radio" value="yes" <?php echo $default_selection == 'deposit' ? 'checked' : '' ?> class="awcdp-deposit-radio">
                            <label for="awcdp-option-pay-deposit"
                                class="awcdp-radio-label"><?php echo $text_settings['pay_deposit_text'] ?></label>
                        </div>
                        <div class="awcdp-deposits-description"><?php echo $text_settings['deposit_text'] ?> <span
                                id="awcdp-deposit-amount"><?php echo $deposit_amount_text ?></span> <span
                                id="deposit-suffix"></span>
                        </div>
                    </div>
                    <div class="awcdp-radio pay-full">
                        <input id="awcdp-option-pay-full" name="awcdp_deposit_option" value="no" type="radio" <?php echo $default_selection == 'full' ? 'checked' : '' ?> class="awcdp-deposit-radio">
                        <label for="awcdp-option-pay-full"
                            class="awcdp-radio-label"><?php echo $text_settings['pay_full_text'] ?></label>
                    </div>
                </div>
            </div>
        <?php } else { ?>
            <input id="awcdp-option-pay" name="awcdp_deposit_option" value="no" type="hidden">
        <?php } ?>
    <?php
    }
}