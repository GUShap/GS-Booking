const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;

$(window).on('load', () => {
    detectPaymentMethodChange();
    setParticipantsDetails();
    setCompletingProductsRemove();
    thankyouPageItems();
    setCheckoutFormValidation();
});
function setCheckoutLoader() {
    const $loader = $('.blockOverlay');

    const interval = setInterval(() => {
        if (!$.active) {
            clearInterval(interval);
            $loader.remove();
        }
    }, 50);
}
function setCheckoutInfoTable() {
    const $table = $('.shop_table');
    const $rows = $table.find('tr');
    const $productText = $rows.find('td.product-name');

    $productText.each(function () {
        $keys = $(this).find('dt');
        $keys.each(function () {
            $(this).next().andSelf().wrapAll('<div class="item-wrapper"></div>');
        });
    });
}
function setParticipantsDetails() {
    const $participantsConatiner = $('.participants-details-container'),
        $sameDetailsCheckbox = $participantsConatiner.find('input.same-details-checkbox');
    $sameDetailsCheckbox.on('change', function () {
        const $emailInput = $(this).closest('.room-participants-wrapper').find('.participant-details-content').not(':first').find('input.participant_email'),
            $phoneInput = $(this).closest('.room-participants-wrapper').find('.participant-details-content').not(':first').find('input.participant_phone');
        if ($(this).is(':checked')) {
            $emailInput.val('').prop({
                'required': false,
                'disabled': true,
                'placeholder': ''
            });
            $phoneInput.val('').prop({
                'required': false,
                'disabled': true,
                'placeholder': ''
            });

            $emailInput.next('.error').hide();
            $phoneInput.next('.error').hide();
        } else {
            $emailInput.prop({
                'required': true,
                'disabled': false,
                'placeholder': 'Email'
            });
            $phoneInput.prop({
                'required': true,
                'disabled': false,
                'placeholder': 'Phone Number'
            });
            $emailInput.next('.error').show();
            $phoneInput.next('.error').show();
        }
    })
}
function setCompletingProductsRemove() {
    const $removeButton = $('table.shop_table button.remove-upsell');
    $removeButton.on('click', async function (e) {
        e.preventDefault();
        const $this = $(this);
        const retreatItemKey = $this.data('key');
        const retreatID = $this.data('retreat');
        const upsellID = $this.data('product');
        const roomID = $this.data('room');
        $this.addClass('loading');

        const cartUpdated = await removeCompletingProduct(retreatItemKey,retreatID,upsellID,roomID);
        if (cartUpdated) {
            window.location.reload();
        }
    });
}
async function removeCompletingProduct(retreat_item_key,retreat_id,product_id, room_id) {
    return $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'add_product_to_cart_item',
            retreat_item_key,
            quantity: 0,
            product_id,
            retreat_id,
            room_id,
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
function thankyouPageItems() {
    $('.woocommerce-order-details table.order_details ul.wc-item-meta li:contains("room_id")').remove();
}
function setCheckoutFormValidation() {
    const $form = $('form[name="checkout"]');
    $form.validate();
}
function detectPaymentMethodChange() {
    const $radioInputsToDetect = $('input[name="awcdp_deposit_option"], input[name="payment_method"]');

    $radioInputsToDetect.on('change', function () {
        const interval = setInterval(() => {
            if (!$.active) {
                clearInterval(interval);
                setCompletingProductsRemove();
                detectPaymentMethodChange();
            }
        }, 100);
    });
}