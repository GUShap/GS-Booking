<?php class GS_Booking_Calendar_Widget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'gs_booking_calendar_widget';
    }

    public function get_title()
    {
        return esc_html__('GS Booking Calendar', 'elementor-addon');
    }

    public function get_icon()
    {
        return "eicon-calendar";
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }

    public function get_keywords()
    {
        return ['calendar', 'retreat', 'booking'];
    }

    protected function register_controls()
    {

        // Content Tab Start

        $this->start_controls_section(
            'content_section',
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

        $this->end_controls_section();

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

        // Style Tab End

    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $retreats_data = $this->get_retreats_data();
        ?>
        <div class="retreats-calendar-heading">
            <h1 class="retreats-calendar-title"><?php echo $settings['title'] ?></h1>
        </div>
        <?php set_retreats_calendar($retreats_data, $settings['week_start'], !empty($settings['include_prev_next']), false, true);
    }


    private function get_retreats_data()
    {
        $all_retreats_id = get_all_retreats_ids();

        $retreats_data = [];

        foreach ($all_retreats_id as $retreat_id) {
            $retreats_data[$retreat_id] = get_post_meta($retreat_id, 'retreat_product_data', true);
            $retreats_data[$retreat_id]['name'] = get_the_title($retreat_id);
        }
        return $retreats_data;
    }
}