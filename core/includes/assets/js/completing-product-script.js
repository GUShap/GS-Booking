const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;
const productID = customVars.product_id;

$(document).ready(function () {
    setAddProductToExistingOrder();
    setAddProductToCartItems();
});

function setAddProductToExistingOrder() {
    const $exsitingOrderIdInput = $('#order-number'),
        $checkOrderButton = $('button.check-order-button'),
        $buttonWrapper = $checkOrderButton.parent(),
        $quantityInput = $('input#quantity-existing-order'),
        $addToCartButton = $('button#add-to-existing-order-btn');

    $exsitingOrderIdInput.on('focusout', async function () {
        const orderID = $(this).val();
        const $loader = $('<span class="custom-loader"></span>');
        const $resultMessage = $('<div class="result-message"></div>');
        const $optionWrapper = $(this).closest('.option-wrapper');
        const $roomsListWrapper = $optionWrapper.find('.rooms-list-wrapper');

        $checkOrderButton.hide();
        $buttonWrapper.append($loader);
        $buttonWrapper.find('.result-message').remove();
        $roomsListWrapper.attr('data-active', 'false');
        const existingOrderOptions = await getExistingOrderOptions(orderID);
        const isEditable = existingOrderOptions && existingOrderOptions.data.is_editable;
        $loader.remove();
        $buttonWrapper.append($resultMessage);

        if (isEditable) {
            $resultMessage.html('<span>&#10003;</span>').addClass('success');
            setItemsCheckboxes($roomsListWrapper, existingOrderOptions.data.items_html);
            $(this).attr('data-active', 'true');
        } else {
            $resultMessage.html(`<span>&#215;</span><p class="error-message">${existingOrderOptions.data}</p>`).addClass('error');
            $addToCartButton.attr('data-order', '');
            $(this).attr('data-active', 'false');
        }

    });
    $quantityInput.on('input', function () {
        const quantity = $(this).val();
        $addToCartButton.attr('data-quantity', quantity);
    });
}

function getExistingOrderOptions(orderId) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'check_order_editable',
            order_id: orderId,
            product_id: productID,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        return response;
    }).catch(() => {
        return false;
    });
}

function setItemsCheckboxes($roomsListWrapper, itemsHtml) {
    const  $list = $roomsListWrapper.find('.input-group-wrapper');
    $list.empty();
    itemsHtml.forEach(itemHtml => {
        const $item = $(itemHtml);
        const $quantityInput = $item.find('input.quantity');
        const $addToCartBtn = $item.find('button.add-to-cart');

        $list.append($item);
        $quantityInput.on('change', function () {
            const quantity = $(this).val();
            $addToCartBtn.attr('data-quantity', quantity);
        });
        $addToCartBtn.on('click', async function () {
            const orderID = $(this).data('order');
            const roomID = $(this).data('room');
            const quantity = $(this).attr('data-quantity');

            const $quantityInput = $(this).siblings('input.quantity');
            const $loader = $('<span class="custom-loader"></span>');
            const $resultMessage = $('<div class="result-message"></div>');

            $(this).hide();
            $(this).after($loader);
            $quantityInput.prop('disabled', true);
            const cartUpdated = await addProductToExistingOrder(orderID, roomID, quantity);
            $quantityInput.prop('disabled', true);
            if (cartUpdated) {
                $resultMessage.html('<span>&#10003;</span>').addClass('success');
            } else {
                $resultMessage.html('<span>&#215;</span>').addClass('error');
            }

            $loader.remove();
            $(this).after($resultMessage);
        })

    });
    $roomsListWrapper.attr('data-active', 'true');
}

function addProductToExistingOrder(order_id, room_id, quantity) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_existing_order',
            order_id,
            room_id,
            quantity,
            product_id: productID,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response.data;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}

function setAddProductToCartItems() {
    const $conatinaer = $('.option-wrapper.cart-option'),
        $optionWrapper = $conatinaer.find('.item-option-wrapper'),
        $quantityInput = $optionWrapper.find('input.quantity'),
        $addToCartButton = $optionWrapper.find('button.add-to-cart');

    $quantityInput.on('input', function () {
        const $siblingAddToCartButton = $(this).siblings('button.add-to-cart');
        const quantity = $(this).val();
        $siblingAddToCartButton.attr('data-quantity', quantity);
    });
    $addToCartButton.on('click', async function () {
        const $siblingQuantityInput = $(this).siblings('input.quantity');
        const retreat_id = $(this).data('retreat'),
            room_id = $(this).data('room'),
            departure_date = $(this).data('departure'),
            cart_item_key = $(this).data('key'),
            is_deposit = $(this).data('deposit');
        const quantity = $(this).attr('data-quantity');

        const btnWidth = $(this).width();
        const $loader = $(`<div class="loader-wrapper" style="width:${btnWidth}px"><span class="custom-loader"></span></div>`);
        const $resultMessage = $('<div class="result-message"></div>');

        $(this).hide();
        $(this).after($loader);

        const cartUpdated = await updateCart(cart_item_key, retreat_id, room_id, departure_date, is_deposit, quantity);
        $siblingQuantityInput.prop('disabled', true);
        if (cartUpdated) {
            $resultMessage.html('<span>&#10003;</span>').addClass('success');
            location.reload();
        } else {
            $resultMessage.html('<span>&#215;</span>').addClass('error');
        }

        $loader.remove();
        $(this).after($resultMessage);
    });
}

async function updateCart(retreat_item_key, retreat_id, room_id, departure_date, is_deposit, quantity) {
    // write ajax request for action 'update_cart', the code passes productID to the server
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_cart_item',
            retreat_id,
            room_id,
            retreat_item_key,
            departure_date,
            quantity,
            product_id: productID,
            awcdp_deposit_option: is_deposit,
            security: nonce
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response.data;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}
