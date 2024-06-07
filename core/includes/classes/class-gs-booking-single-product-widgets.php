<?php

class GS_Booking_Completing_Product_Add_To_Cart extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'completing_product_add_to_cart';
    }

    public function get_title()
    {
        return __('Completing Product Add To Cart', 'text-domain');
    }

    public function get_icon()
    {
        return 'eicon-checkbox';
    }

    public function get_categories()
    {
        return ['eleusinia'];
    }


    public function get_keywords()
    {
        return ['retreat', 'product', 'products', 'completing', 'add', 'to', 'cart', 'duration'];
    }

    protected function register_controls()
    {
        // Register your controls here
    }

    protected function render()
    {
        $product_id = get_the_ID();
        $enable_multiple_items = !empty(get_post_meta($product_id, '_enable_multiple_items', true));
        $limit_quantity = !empty(get_post_meta($product_id, '_limit_quantity', true));
        $max_items_limit = get_post_meta($product_id, '_max_items_limit', true);
        $max_str = $limit_quantity ? 'max="' . $max_items_limit . '"' : '';
        $rooms_in_cart = [];
        $cart = WC()->cart->get_cart();
        $is_second_participant_product = has_term('second-participant', 'product_cat', $product_id);
        foreach ($cart as $item_key => $cart_item) {
            $is_program_product = has_term('programs', 'product_cat', $cart_item['product_id']);
            if ($is_program_product) {
                $available_second_participant_for_room = check_available_second_participant($cart_item['retreat_id'], $cart_item['departure_date'], $cart_item['room_id']);
                $additional = !empty($cart_item['additional']) ? $cart_item['additional'] : [];
                if (!empty($additional)) {
                    foreach ($additional as $key => $value) {
                        if ($key == $product_id)
                            unset($additional[$key]);
                        else {
                            $additional[$key] = $value['quantity'];
                        }
                    }
                }
                $rooms_in_cart[] = [
                    'retreat_id' => $cart_item['retreat_id'],
                    'departure_date' => $cart_item['departure_date'],
                    'room_id' => $cart_item['room_id'],
                    'item_key' => $item_key,
                    'quantity' => !empty($cart_item['additional'][$product_id]['quantity']) ? $cart_item['additional'][$product_id]['quantity'] : 1,
                    'available_second_participant' => $is_second_participant_product && empty($cart_item['is_second_participant']) && $available_second_participant_for_room,
                    'is_deposit' => !empty($cart_item['is_deposit']),
                ];
            }
        }
        ?>
        <div class="completing-product-add-to-cart" id="completing-product-add-to-cart">
            <div class="instructions">
                <p>The Addon Products can only be purchased as addition to the current retreat booking or for a previous retreat
                    reservation, that haven't been departured.</p>
            </div>
            <div class="source-container">
                <div class="source-heading">
                    <h5>Choose "Cart" to add product to a retreat that's in your cart, or choose 'Existing Order'</h5>
                </div>
                <div class="source-content">
                    <div class="input-group-wrapper item-type-radio">
                        <div class="input-wrapper type-radio">
                            <input type="radio" name="source" id="source-cart" value="cart">
                            <label for="source-cart">Cart</label>
                        </div>
                        <div class="input-wrapper type-radio">
                            <input type="radio" name="source" id="source-order" value="order">
                            <label for="source-order">Existing Order</label>
                        </div>
                    </div>
                    <div class="add-to-cart-wrapper">
                        <div class="option-wrapper cart-option">
                            <?php if (!empty($rooms_in_cart)): ?>
                                <p>Choose a room item to add the product to:</p>
                                <?php foreach ($rooms_in_cart as $room_item) {
                                    $retreat_id = $room_item['retreat_id'];
                                    $departure_date = $room_item['departure_date'];
                                    $room_id = $room_item['room_id'];
                                    $item_key = $room_item['item_key'];
                                    $current_quantity = $room_item['quantity'];
                                    $available_second_participant = !empty($room_item['available_second_participant']);
                                    $is_deposit = !empty($room_item['is_deposit']);
                                    $disable_checkbox_str = $is_second_participant_product && !$available_second_participant ? 'disabled' : '';
                                    if (!$enable_multiple_items && $current_quantity > 0 && !$is_second_participant_product)
                                        $disable_checkbox_str = 'disabled';
                                    ?>
                                    <div class="item-option-wrapper">
                                        <div class="input-wrapper type-checkbox">
                                            <input type="checkbox" name="retreat" data-departure="<?php echo $departure_date ?>"
                                                data-room="<?php echo $room_id ?>" id="retreat-<?php echo $retreat_id ?>"
                                                value="<?php echo $retreat_id; ?>" <?php echo $disable_checkbox_str ?>>
                                            <label for="retreat-<?php echo $retreat_id ?>">
                                                <strong>
                                                    <?php echo get_the_title($room_id) ?>
                                                </strong> Room in
                                                <?php echo get_the_title($retreat_id) ?> Retreat, on
                                                <?php echo date('F j, Y', strtotime($departure_date)); ?>
                                            </label>
                                        </div>
                                        <div class="quantity-atc-wrapper">
                                            <?php if ($enable_multiple_items): ?>
                                                <input type="number" name="quantity" class="quantity" id="quantity-<?php echo $item_key ?>"
                                                    value="<?php echo $current_quantity ?>" min="1" <?php echo $max_str ?>>
                                            <?php endif; ?>
                                            <button class="add-to-cart" data-source="cart" data-retreat="<?php echo $retreat_id ?>"
                                                data-departure="<?php echo $departure_date ?>" data-room="<?php echo $room_id ?>"
                                                data-key="<?php echo $item_key ?>"
                                                data-deposit="<?php echo $is_deposit ? 'yes' : 'no' ?>" data-quantity="1">Add to
                                                Cart</button>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php else: ?>
                                <p>No retreats in cart at the moment. <a href="<?php get_home_url() . 'product/5-days/' ?>">Click
                                        here</a> for details on our 5 days program</p>
                            <?php endif; ?>
                        </div>
                        <div class="option-wrapper order-option">
                            <p>Enter the order number of the retreat you want to add the product to:</p>
                            <div class="input-group-wrapper">
                                <input type="text" name="order-number" id="order-number"
                                    placeholder="Type order ID to add Product to" data-active="false">
                                <div class="button-wrapper">
                                    <button type="button" class="check-order-button">
                                        <svg viewBox="0 0 24 24" id="Layer_1" data-name="Layer 1"
                                            xmlns="http://www.w3.org/2000/svg" fill="#000000">
                                            <g id="SVGRepo_iconCarrier">
                                                <circle class="cls-1" cx="9.14" cy="9.14" r="7.64"></circle>
                                                <line class="cls-1" x1="22.5" y1="22.5" x2="14.39" y2="14.39"></line>
                                            </g>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="rooms-list-wrapper" data-active="false">
                                <p>Choose a room item to add the product to:</p>
                                <div class="input-group-wrapper"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php }

    protected function _content_template()
    {
        // Output your widget's template here
    }
}