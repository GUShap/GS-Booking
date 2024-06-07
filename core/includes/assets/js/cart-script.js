const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const nonce = customVars.completing_product_nonce;


$(document).ready(function () {
    setCompletingProductsRemove();
});

function setCompletingProductsRemove() {
    const $removeButton = $('form.woocommerce-cart-form button.remove-upsell');
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

async function removeCompletingProduct( retreat_item_key,retreat_id,product_id, room_id) {
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
