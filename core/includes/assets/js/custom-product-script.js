const $ = jQuery;
const ajaxUrl = customVars.ajax_url;
const roomsData = customVars.rooms_data;
const productId = customVars.product_id;
const retreatData = customVars.retreat_data;
const retreatType = customVars.retreat_type;
const blockedDates = customVars.blocked_dates;
const isPublished = customVars.is_published ? true : false,
    productUrl = customVars.product_url,
    checkoutUrl = customVars.checkout_url,
    cartUrl = customVars.cart_url,
    addToCartUrl = customVars.add_to_cart_url;

$(window).on('load', () => {
    setDateInputs();
    setRetreatDatesRepeater();
    setRetreatDatesTable();
    setRetreatRoomsTable();
    setRetreatRoomsPrice();

    setQrCodeGenerator();
});

function setDateInputs() {
    const $dateInputs = $('#dates input.date-input');
    $dateInputs.each(function () {
        $(this).datepicker({
            dateFormat: 'MM d, yy',
            beforeShowDay: function (date) {
                const formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
                return [blockedDates.indexOf(formattedDate) === -1];
            },
            minDate: 0,
        });
    });
}
function setRetreatDatesRepeater() {
    const $dateRepeater = $('.date-repeater-container'),
        $datesWrapper = $dateRepeater.find('.dates-wrapper'),
        $addDateBtn = $dateRepeater.find('.add-date-btn'),
        $editDateBtn = $dateRepeater.find('.edit-info-button');

    $addDateBtn.on('click', function () {
        const $newDateEl = getNewDepartureDateTemplate();
        addNewDepartureDateTab($newDateEl);
        setDepartureDateFunctionality($newDateEl);
        setDateInputs();

        $('.date-repeater-container .departure-date-wrapper').attr('data-selected', false);
        $datesWrapper.append($newDateEl);

    });

    $editDateBtn.on('click', function () {
        const $dateEl = $(this).closest('.departure-date-wrapper');
        $dateEl.attr('edit-mode', true);
    });
}

function setRetreatDatesTable() {
    const $datesContainer = $('#dates');
    const $departureDates = $datesContainer.find('.departure-date-wrapper'),
        $dateTabs = $departureDates.find('.date-tab');
    const $dataTabs = $('li.dates_tab');
    $dataTabs.on('click', function () {
        let row = 1;
        $dateTabs.each(function (idx) {
            const $wrapper = $(this).closest('.departure-date-wrapper'),
                $prevTab = $wrapper.prev().find('.date-tab');

            const is4thTab = idx > 1 && idx % 4 == 0 && !isNaN(idx % 4);
            if (is4thTab) {
                row++;
                $(this).closest('.dates-wrapper').css('margin-top', `${row * $(this).height()}px`)
            }
            const topDifference = is4thTab || idx == 0 ? 1 : 0;
            const calculatedTop = (-row * $(this).outerHeight()) + topDifference;

            $(this).css({
                'width': `${$wrapper.width() / 4}px`,
                'z-index': $departureDates.length - row
            });

            $(this).css('top', `${calculatedTop}px`)
            if ($prevTab.length) {
                const prevTabLeft = +$prevTab.css('left').replace(/[^0-9.]/g, ''),
                    calculatedLeft = is4thTab ? 0 : prevTabLeft + $prevTab.width();
                $(this).css('left', `${calculatedLeft}px`)
            }
            $(this).on('click', () => {
                if (!$wrapper.data('selected')) {
                    $wrapper.siblings().attr('data-selected', false);
                    $wrapper.attr('data-selected', true);
                }
            });
        });
    });
    $departureDates.each(function () {
        setDepartureDateFunctionality($(this));
    });
}

function updateRetreatData(data) {
    data.action = 'update_retreat_product_data';
    data.retreat_id = productId;
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data,
        success: function (response) {
            console.log('AJAX success:', response);
            // refresh the page
            location.reload();
        },
        error: function (error) {
            console.error('AJAX error:', error);
        }
    });
}

function removeRetreatDate(date) {
    const data = {
        action: 'remove_retreat_departure_date',
        retreat_id: productId,
        departure_date: date
    };
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data,
        success: function (response) {
            console.log('AJAX success:', response);
            location.reload();
        },
        error: function (error) {
            console.error('AJAX error:', error);
        }
    });

}

function getNewDepartureDateTemplate() {
    const $wrapper = $('<div class="departure-date-wrapper" data-date="" data-selected="true" edit-mode="true"></div>'),
        $dateTab = $('<div class="date-tab"><label>Select Departure Date</label></div>'),
        $dateInput = $('<input type="text" class="date-input" name="departure_dates[]">').datepicker({
            dateFormat: 'MM d, yy',
            beforeShowDay: function (date) {
                const formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
                return [blockedDates.indexOf(formattedDate) === -1];
            },
            minDate: 0,
        }),
        $content = $(
            `<div class="date-content-wrapper" data-blocked="true">
                <div class="maximum-participants">
                    <label for="max-participants-input">Maximum Participants:</label>
                    <input type="number" class="max-participants-input">
                </div>
                <div class="maximum-second-participants">
                    <label for="max-second-participants-input">Maximum Second Participants:</label>
                    <input type="number" class="max-second-participants-input">
                </div>
                <div class="rooms-list-wrapper">
                    <p>Choose Rooms</p>
                    <ul class="select-room-list">
                        ${roomsData.map(room => {
                            const id = room.name.toLowerCase().replace(/ /g, '_');
                            return `
                            <li class="select-room-item">
                                    <div class="checkbox-wrapper">
                                        <input type="checkbox" id="${id}" data-product="${room.id}" checked>
                                        <label for="${id}">${room.name}</label>
                                    </div>
                                    <p class="room-capacity">(room capacity: ${room.data.max_room_capacity})</p>
                            </li>`
                        }).join('')}
                    </ul>
                </div>
                <div class="buttons-wrapper">
                    <button class="save-info-button" type="button">Save</button>
                    <button class="cancel-info-button" type="button">Cancel</button>
                </div>
            </div>`
        );
    $dateTab.append($dateInput);
    $wrapper.append($dateTab);
    $wrapper.append($content);
    return $wrapper;
}

function addNewDepartureDateTab($newDateEl) {
    const $dateInput = $newDateEl.find('.date-tab input.date-input');
    $dateInput.on('change', function () {
        $(this).addClass('has-date')
    });
}

function setDepartureDateFunctionality($dateEl) {
    const $dateInput = $dateEl.find('input.date-input'),
        $maxParticipantsInput = $dateEl.find('.max-participants-input'),
        $maxSecondParticipantsInput = $dateEl.find('.max-second-participants-input'),
        $roomItems = $dateEl.find('.select-room-list li.select-room-item'),
        $roomCheckboxes = $dateEl.find('.select-room-list li.select-room-item input[type="checkbox"]'),
        $activateRegistrationToggle = $dateEl.find('input.activate-registration-switch');
    const $saveBtn = $dateEl.find('.save-info-button'),
        $removeBtn = $dateEl.find('.remove-date-button');
    let prev_departure_date =getFormattedDepartueDate($dateInput.val());

    const getDepartureDate = () => {
        return getFormattedDepartueDate($dateInput.val());
    };

    const getRoomsList = () => {
        const rooms_list = {};

        $roomCheckboxes.each(function () {
            if ($(this).is(':checked')) {
                const currentRoomData = roomsData.filter(room => room.id === $(this).data('product'))[0];
                const room_capacity = currentRoomData.data.max_room_capacity,
                    room_name = currentRoomData.name,
                    price = currentRoomData.data.price;

                rooms_list[$(this).data('product')] = {
                    room_name,
                    room_capacity,
                    price
                }
            }
        });
        return rooms_list;
    };

    const setActivationStatus = () => {
        return $activateRegistrationToggle.on('change', function () {
            const registration_active = $(this).is(':checked'),
                departure_date = getDepartureDate();
            updateRetreatData({ departure_date, prev_departure_date, registration_active });
        });
    };

    $dateInput.on('change', function () {
        const selectedDate = new Date($(this).val());
        const frontFormattedDate = selectedDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        if ($(this).siblings('p').length) {
            $(this).siblings('p').text(frontFormattedDate);
        } else {
            $(this).after(`<p class="updated-new-date">${frontFormattedDate}</p>`);
        }
        // $(this).hide().siblings('p').show();
    });

    $saveBtn.on('click', function () {
        const $departureDateContent = $(this).closest('.departure-date-wrapper').find('.date-content-wrapper');
        const departure_date = getDepartureDate(),
            rooms_list = getRoomsList(),
            max_participants = $maxParticipantsInput.val()
                ? $maxParticipantsInput.val()
                : Object.keys(rooms_list).reduce((sum, key) => sum + +rooms_list[key].room_capacity, 0),
            max_second_participants = $maxSecondParticipantsInput.val() ?
                $maxSecondParticipantsInput.val()
                : Object.keys(rooms_list).reduce((sum, key) => sum + +rooms_list[key].room_capacity - 1, 0);
        updateRetreatData({ departure_date, prev_departure_date, rooms_list, max_participants, max_second_participants });
        $(this).closest('.departure-date-wrapper').attr('edit-mode', false);
        $departureDateContent.attr('edit-mode', false);
        prev_departure_date = departure_date;
    });

    $removeBtn.on('click', function () {
        const departure_date = getDepartureDate();
        removeRetreatDate(departure_date);
        $(this).closest('.departure-date-wrapper').remove();
    });

    $roomItems.find('label, input').on('click', (e) => { e.stopPropagation() });

    setActivationStatus();
}

function setRetreatRoomsTable() {
    const $roomsContainer = $('#rooms');
    const $roomCheckboxes = $roomsContainer.find('.room-wrapper input[type="checkbox"]');

    $roomCheckboxes.on('change', function () {
        const isChecked = $(this).is(':checked');
        const $roomWrapper = $(this).closest('.room-wrapper'),
            $roomPriceWrapper = $roomWrapper.find('.room-price-wrapper'),
            $roomPriceInput = $roomPriceWrapper.find('input[type="number"]');
        $roomPriceWrapper.attr('edit-mode', true);
        isChecked
            ? $roomPriceInput.prop('required', true).focus()
            : $roomPriceInput.prop('required', false);
    });
}

function setRetreatRoomsPrice() {
    const $roomsContainer = $('.rooms-checkboxes-wrapper');
    const $editRoomsPriceBtn = $roomsContainer.find('.edit-room-price-button'),
        $setRoomsPriceBtn = $roomsContainer.find('.set-room-price-button');

    $editRoomsPriceBtn.on('click', function () {
        const $roomPriceWrapper = $(this).closest('.room-price-wrapper'),
            $roomPriceInput = $roomPriceWrapper.find('input[type="number"]');
        $roomPriceWrapper.attr('edit-mode', true);
        $roomPriceInput.focus();
    })
    $setRoomsPriceBtn.on('click', function () {
        const $roomPriceWrapper = $(this).closest('.room-price-wrapper'),
            $roomPriceInput = $roomPriceWrapper.find('input[type="number"]'),
            $roomPrice = $roomPriceWrapper.find('.room-price');

        if (!$roomPriceInput.val()) {
            const errorHtml = $('<div class="price-error-message"><p>Please enter a price</p></div>');
            $roomPriceInput.after(errorHtml)
            setTimeout(() => {
                errorHtml.hide({
                    duration: 400,
                    complete: function () { $(this).remove() }
                });
            }, 2000);
            return;
        }
        $roomPriceWrapper.attr('edit-mode', false);
        $roomPrice.text($roomPriceInput.val());
    })
}

function getFormattedDepartueDate(dateStr) {
    if (!dateStr) return;
    const date = new Date(dateStr);
    return formatDate(date);
}

function formatDate(date) {
    if (!date) return;
    var dd = String(date.getDate()).padStart(2, '0');
    var mm = String(date.getMonth() + 1).padStart(2, '0');
    var yyyy = date.getFullYear();
    return yyyy + '-' + mm + '-' + dd;
}

function setQrCodeGenerator() {
    const $createProductPageQRBtn = $('#create-product-page-qr-btn'),
        $saveProductPageQRBtn = $('#save-product-page-qr-code'),
        $createATCQRBtn = $('#create-atc-qr-btn'),
        $saveATCQRBtn = $('#save-atc-qr-code'),
        $atcRedirectSelect = $('#atc-redirect-select'),
        $downloadProductPageQrBtn = $('#download-product-page-qr-code'),
        $downloadATCQrBtn = $('#download-atc-qr-code');
    const createQRfile = (url, $previewEl) => {
        const qrCode = new QRCodeStyling({
            width: 180,
            height: 180,
            data: url,
            image: 'https://wordpress-946789-4130449.cloudwaysapps.com/wp-content/uploads/2024/01/logo-2023@1.7x.png',
            dotsOptions: {
                color: "#000",
                type: "classy-rounded",
                gradient: {
                    type: "radial",
                    colorStops: [
                        { offset: 0, color: "#03739e" },
                        { offset: 1, color: "#268f88" }
                    ]
                }
            },
            backgroundOptions: {
                color: "#fff"
            },
            cornersSquareOptions: {
                type: "extra-rounded",
                gradient: {
                    colorStops: [
                        { offset: 0, color: "#03739e" },
                        { offset: 1, color: "#268f88" }
                    ]
                }
            },
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 5,
                imageSize: 0.5
            },
            typeNumber: 4,
        });
        qrCode.append($previewEl[0]);
    }
    const qrCanvasUploadAjax = (canvasEl, target) => {
        const dataUrl = canvasEl.toDataURL('image/png');
        const blob = dataURLtoBlob(dataUrl);
        const fileName = target + '_qr_code_' + productId + '.png';

        const formData = new FormData();
        formData.append('action', 'upload_product_qr_file');
        formData.append('target', target);
        formData.append('product_id', productId);
        formData.append('qr_file', blob, fileName);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                console.log('AJAX success:', response);
            },
            error: function (error) {
                console.error('AJAX error:', error);
            }
        });
    }
    const downloadQRImageFile = (imageUrl, action) => {
        const downloadLink = $('<a>', {
            href: imageUrl,
            download: action + '_qr.png', // Specify the desired file name
            style: 'display: none;' // Hide the link
        });

        // Append the link to the body
        $('body').append(downloadLink);

        // Trigger a click on the link to start the download
        downloadLink[0].click();

        // Remove the link from the DOM
        downloadLink.remove();
    }

    $createProductPageQRBtn.on('click', function () {
        createQRfile(productUrl, $('.qr-image-wrapper.product'));
    });
    $saveProductPageQRBtn.on('click', function () {
        const canvasEl = $('.qr-image-wrapper.product canvas')[0];
        qrCanvasUploadAjax(canvasEl, 'product_page');
        $(this).closest('.qr-code-wrapper').attr('data-active', true);
    });

    $createATCQRBtn.on('click', function () {
        const baseUrl = $atcRedirectSelect.val() === 'checkout' ? checkoutUrl : cartUrl,
            redirectUrl = baseUrl + addToCartUrl;
        createQRfile(redirectUrl, $('.qr-image-wrapper.atc'));
    });
    $saveATCQRBtn.on('click', function () {
        const canvasEl = $('.qr-image-wrapper.atc canvas')[0];
        qrCanvasUploadAjax(canvasEl, 'atc');
        $(this).closest('.qr-code-wrapper').attr('data-active', true);
    });

    $downloadProductPageQrBtn.on('click', function () {
        const imageUrl = $('.qr-image-wrapper.product img').prop('src');
        downloadQRImageFile(imageUrl, 'product_page');
    });
    $downloadATCQrBtn.on('click', function () {
        const imageUrl = $('.qr-image-wrapper.atc img').prop('src');
        downloadQRImageFile(imageUrl, 'add_to_cart');
    });
}

function dataURLtoBlob(dataURL) {
    var arr = dataURL.split(','), mime = arr[0].match(/:(.*?);/)[1];
    var bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], { type: mime });
}