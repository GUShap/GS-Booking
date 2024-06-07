const $ = jQuery;
const isMobile = customVars.is_mobile ? true : false,
    ajaxUrl = customVars.ajax_url,
    roomNonce = customVars.rooms_nonce,
    addToCartNonce = customVars.add_to_cart_nonce;
const retreatId = customVars.retreat_id;
const retreatColor = customVars.retreat_color;
const selectedRoomsInDates = customVars.selected_rooms_in_dates;
const addToCartData = {
    action: 'add_retreat_to_cart',
    retreat_id: retreatId,
    room_id: '',
    departure_date: '',
    room_price: 0,
    awcdp_deposit_option: '',
    nonce: addToCartNonce,
    additional: {}
};
const addToWaitlistData = {
    action: 'add_to_retreat_waitlist',
    retreat_id: retreatId,
    departure_date: '',
    nonce: addToCartNonce,
    name: '',
    email: ''
};
const isRetreatInCart = customVars.is_retreat_in_cart ? true : false;
const isRedirectedFromCalendar = customVars.is_redirect_from_calendar ? true : false;
let retreatItemData = customVars.retreat_item_data;
const retreatItemDepartureDate = customVars.departure_date;
const loadRetreatData = customVars.load_retreat_item_data;

$(window).on('load', () => {
    setCalendarMonthsSlider();
    setInstructinos();
    setDepositSelection();
    setRoomAddToCart();
    setAdditionalProductSelection();
    if (loadRetreatData) {
        setRetreatInCart();
    } else {
        setRetreatDateSelect();
        setQueryData();
    }
});

function setCalendarMonthsSlider() {
    const $retreatsContentContainer = $('.retreats-calendar-container'),
        $retreatsContentWrapper = $('.retreats-calendar-container .retreats-calendar-content'),
        $allMonths = $('.retreats-calendar-container .month-wrapper'),
        $lastMonth = $('.month-wrapper.last'),
        $firstMonth = $('.month-wrapper.first'),
        $arrowRight = $('.arrows-container .arrow-right-container button'),
        $arrowLeft = $('.arrows-container .arrow-left-container button'),
        $allBtns = $('.arrows-container  .calendar-arrow-button');
    let inTransition = false;

    const getTranslateX = (element) => {
        return element.css('transform') != 'none'
            ? element.css('transform').split(",")[4].trim()
            : 0;
    }
    const isElementInView = (element, leftOffset = 0, rightOffset = $retreatsContentContainer.width()) => {
        const rect = element.getBoundingClientRect();
        return rect.x >= leftOffset && rect.x < rightOffset;
    }
    const isFirstMonthInView = () => {
        return isElementInView($firstMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width());
    }
    const isLastMonthInView = () => {
        return isElementInView($lastMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width());
    }
    const setArrowsVisibility = () => {
        const minMonthQuantity = 1;
        if ($allMonths.length <= minMonthQuantity) {
            $arrowLeft.hide();
            $arrowRight.hide();
            return;
        }
        isFirstMonthInView()
            ? $arrowLeft.parent().addClass('hidden')
            : $arrowLeft.parent().removeClass('hidden');

        isLastMonthInView()
            ? $arrowRight.parent().addClass('hidden')
            : $arrowRight.parent().removeClass('hidden');

    }

    $arrowRight.on('click', function () {
        if (inTransition) return;
        const distance = '100%';
        const translateXValue = getTranslateX($retreatsContentWrapper);
        const newTranslateX = `calc(${translateXValue}px - ${distance})`;

        if (!isLastMonthInView()) {
            $retreatsContentWrapper.css('transform', 'translateX(' + newTranslateX + ')');
        }
    });
    $arrowLeft.on('click', function () {
        if (inTransition) return;
        const distance = '100%';
        const translateXValue = getTranslateX($retreatsContentWrapper);
        const newTranslateX = `calc(${translateXValue}px + ${distance})`;

        if (!isFirstMonthInView()) {
            $retreatsContentWrapper.css('transform', 'translateX(' + newTranslateX + ')');
        }
    });
    $allBtns.on('click', function () {
        inTransition = true;
        setTimeout(() => {
            setArrowsVisibility();
            inTransition = false
        }, 340);
    });
}

function setInstructinos() {
    const $instructionsTextWrapper = $('.instruction-text-wrapper'),
        $icon = $instructionsTextWrapper.find('.instructions-icon'),
        $text = $instructionsTextWrapper.find('.instructions-text');

    $icon.on('click', function () {
        $text.slideToggle(200);
    });
}

function setDepositSelection() {
    const $acowebDepositRadioInput = $('input[name="awcdp_deposit_option"]');
    addToCartData.awcdp_deposit_option = $acowebDepositRadioInput.val();
    $acowebDepositRadioInput.on('change', function () {
        addToCartData.awcdp_deposit_option = $(this).val();
    });
}

function setRetreatInCart() {
    const $monthsContainer = $('.retreats-calendar-content');
    const $nextMonthBtn = $('button.calendar-arrow-button.right');
    const $ceneterdMonth = $('.month-wrapper.centered');
    const $selectedDay = $(`.day-wrapper[data-date="${retreatItemDepartureDate}"]`);
    const $cartRetreatDays = $(`.retreat-single-day[data-departure="${retreatItemDepartureDate}"]`);
    const $unselectedRetreatDays = $(`.retreat-single-day:not([data-departure="${retreatItemDepartureDate}"])`);

    renderAvailableRooms(retreatItemDepartureDate, false);

    if ($ceneterdMonth.length && !$ceneterdMonth.hasClass('first')) {
        $month_order = +$ceneterdMonth.data('order');
        $monthsContainer.css('transform', `translateX(calc(-100% * ${$month_order}))`);
    }
    $cartRetreatDays.addClass('selected');
    if (isRetreatInCart) {
        $unselectedRetreatDays.addClass('full-booked').data('full', true);
    }
}

function setRetreatDateSelect() {
    const $tripDates = $('.retreats-calendar-content .day-wrapper .retreat-single-day');

    $tripDates.on('mouseenter', function () {
        if ($(this).hasClass('selected')) return;
        const $allTripDays = getTripDaysElements($(this));
        $allTripDays.addClass('hovered');
    });
    $tripDates.on('mouseleave', function () {
        if ($(this).hasClass('selected')) return;
        const $allTripDays = getTripDaysElements($(this));
        $allTripDays.removeClass('hovered');
    });
    $tripDates.on('click', function () {
        if ($(this).hasClass('selected')) return;
        const $allTripDays = getTripDaysElements($(this));
        const departureDate = $(this).data('departure');
        const isFullBooked = $(this).hasClass('full-booked');
        $tripDates.removeClass('selected').removeClass('hovered');
        $allTripDays.addClass('selected');
        if (isFullBooked || isRetreatInCart) {
            setDepartureDateWaitList(departureDate, isFullBooked);
            setAddToWaitlist(departureDate);
        } else {
            setAvailableRooms(departureDate);
        }
    });
}
async function setAvailableRooms(departureDate) {
    retreatItemData = await getAvailableRooms(retreatId, departureDate);
    if (!retreatItemData) return;
    renderAvailableRooms(departureDate, true);
}
async function getAvailableRooms(retreat_id, departure_date) {
    try {
        const response = await $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_available_rooms',
                departure_date,
                retreat_id,
                nonce: roomNonce
            },
        });
        if (!response.success) {
            throw new Error('Network response was not ok');
        }
        return response.data;
    } catch (error) {
        console.error('Error:', error.message);
        return null;
    }
}
function renderAvailableRooms(departureDate, triggerPopup = true) {
    const $widgetContainer = $('.add-retreat-to-cart-container'),
        $openRoomsPopupBtn = $('button.open-list-popup-button'),
        $closeRoomsPopupBtn = $widgetContainer.find('button.close-button'),
        $instructionsText = $widgetContainer.find('.instructions-text'),
        $datesRange = $('.retreat-dates-range'),
        $roomsDropdown = $('select.rooms-options'),
        $roomsPopup = $('.rooms-list-popup'),
        $popupList = $roomsPopup.find('ul.rooms-list'),
        $roomsListContainer = $('.rooms-list-container'),
        $depositWrapper = $widgetContainer.find('.awcdp-deposits-wrapper ');

    const retreatDuration = $roomsListContainer.data('duration'),
        depositEnabled = retreatItemData.deposit_enabled,
        secondParticipantAvailable = retreatItemData.second_participant_available;

    $widgetContainer.attr({ 'date-selected': true, 'departure-date': departureDate });
    $datesRange.text(formatDateRange(departureDate, retreatDuration));
    $roomsDropdown.empty();
    $popupList.empty();
    $widgetContainer.find('.waitlist-form-wrapper').remove();
    for (const key in retreatItemData.rooms_list) {
        setRoomPopupItem($popupList, retreatItemData.rooms_list[key], key, retreatDuration, depositEnabled);
        setSelectDropdownItems($roomsDropdown, retreatItemData.rooms_list[key], key);
    }
    setRoomSelection();
    $roomsDropdown.prepend('<option value="" disabled selected>Select Room</option>');
    $openRoomsPopupBtn.on('click', function () {
        $('.rooms-list-popup').attr('data-active', true);
    });
    $closeRoomsPopupBtn.on('click', function () {
        $('.rooms-list-popup').attr('data-active', false);
    });
    if (triggerPopup) {
        setTimeout(() => {
            $openRoomsPopupBtn.trigger('click');
        }, 100);
    }
    if (!depositEnabled) {
        addToCartData.awcdp_deposit_option = 'no';
        $depositWrapper.remove();
    }
    if (!secondParticipantAvailable) {
        const unavailableParticipantMessage = retreatItemData.unavailable_second_participant_message;
        $instructionsText.text(unavailableParticipantMessage);
    }
}
function setRoomSelection() {
    const $widgetContainer = $('.add-retreat-to-cart-container'),
        $priceWrapper = $('.room-price-wrapper'),
        $roomPrice = $priceWrapper.find('.price'),
        $roomSelectInput = $('.rooms-options'),
        $secondParticipantWrapper = $widgetContainer.find('.completing-product-wrapper:has(input[data-second-participant="true"])'),
        $addToCartBtn = $('.book-retreat-button'),
        $mainGalleryContainer = $('.main-gallery-container'),
        $gallerySlider = $mainGalleryContainer.find('.woocommerce-product-gallery__wrapper'),
        $galleryList = $mainGalleryContainer.find('ol');

    const getImageCleanUrl = (inputString) => {
        const lastIndex = inputString.lastIndexOf('.');
        return inputString.substring(0, lastIndex);
    };
    const getImageFormat = (inputString) => {
        const lastIndex = inputString.lastIndexOf('.');
        return inputString.substring(lastIndex);
    };
    const equalizeItems = ($items, length) => {
        if ($items.length == length) return;

        if ($items.length < length) {
            const lastSlide = $items.last();
            for (let i = $items.length; i < length; i++) {
                const clonedSlide = lastSlide.clone();
                $items.last().after(clonedSlide);
            }
        } else {
            $items.slice(length).remove();
        }
    }
    const equalizeGalleryItems = (length) => {
        const $slides = $gallerySlider.find('.woocommerce-product-gallery__image'),
            $items = $galleryList.find('li');
        equalizeItems($slides, length);
        equalizeItems($items, length);

    };
    const setSelectedRoomGallery = (imagesSrc) => {
        imagesSrc.forEach((src, idx) => {
            const $matchingSlide = $gallerySlider.find('.woocommerce-product-gallery__image').eq(idx);
            const originalSrc = getImageCleanUrl($matchingSlide.find('a').attr('href'));

            const prevSrcSet = $matchingSlide.find('a img').attr('srcset');
            if (prevSrcSet) {
                const format = getImageFormat(src);
                const formatPattern = new RegExp('.jpg|.jpeg|.png|.svg', 'g');
                const unformattedSrcSet = prevSrcSet.replace(new RegExp(originalSrc, 'g'), getImageCleanUrl(src));
                const srcSet = unformattedSrcSet.replace(formatPattern, format);
                $matchingSlide.find('a img').attr({
                    'srcset': srcSet,
                    'data-large_image': src
                });
            }

            $matchingSlide.attr('data-thumb', src);
            $matchingSlide.find('a').attr('href', src);
            $matchingSlide.find('img').attr({ 'src': src, 'data-src': src });
            $galleryList.find('img').eq(idx).attr('src', src);
        });
    };

    $roomSelectInput.on('change', function () {
        if (!$(this).val()) return;

        const $selectedOption = $(this).find('option:selected'),
            $currespondingPopupItem = $(`.room-item-popup#${$(this).val()}`);
        const price = $selectedOption.data('price');
        const imagesSrc = Array.from($currespondingPopupItem.find('.room-gallery li img')).map(img => {
            return img.src;
        });
        const canMultipleGuests = $selectedOption.data('can-multiple') && retreatItemData.second_participant_available;

        $roomPrice.text(price.toLocaleString());
        $widgetContainer.attr('room-selected', true);
        $addToCartBtn.prop('disabled', false);
        $currespondingPopupItem.addClass('selected', true);
        $currespondingPopupItem.find('.book-room-button').text('Selected').prop('disabled', true);
        $currespondingPopupItem.siblings().removeClass('selected');
        $currespondingPopupItem.siblings().find('.book-room-button').text('Book Room').prop('disabled', false);
        canMultipleGuests ? $secondParticipantWrapper.show() : $secondParticipantWrapper.hide();
        if (!imagesSrc.length) return;
        equalizeGalleryItems(imagesSrc.length);
        setSelectedRoomGallery(imagesSrc);
    });
}
function setAdditionalProductSelection() {
    const $widgetContainer = $('.add-retreat-to-cart-container'),
        $upsellCheckbox = $widgetContainer.find('.upsell-checkbox'),
        $quntityInputs = $widgetContainer.find('.completing-product-wrapper input[type="number"]');
    $upsellCheckbox.on('change', function () {
        const isChecked = $(this).is(':checked'),
            id = $(this).prop('id'),
            price = $(this).val();
        if (isChecked) {
            addToCartData.additional[id] = 1;
        } else {
            const closestNumberInput = $(this).closest('.completing-product-wrapper').find('input[type="number"]');
            closestNumberInput.val(1);
            delete addToCartData.additional[id];
        }

    });
    $quntityInputs.on('change', function () {
        const id = $(this).prop('id'),
            quantity = $(this).val();
        const closestCheckbox = $(this).closest('.completing-product-wrapper').find('input[type="checkbox"]');
        const isChecked = closestCheckbox.is(':checked');
        if (isChecked) {
            addToCartData.additional[id] = quantity;
        } else {
            delete addToCartData.additional[id];
        }
    })
}
function setRoomAddToCart() {
    const $widgetContainer = $('.add-retreat-to-cart-container'),
        $roomSelectEl = $('#rooms-options-select'),
        $quantityInputs = $widgetContainer.find('.completing-product-wrapper input.quantity-input'),
        $instructionsWrapper = $widgetContainer.find('.instruction-text-wrapper'),
        $addToCartBtn = $('.book-retreat-button');

    $addToCartBtn.on('click', async function () {
        const room_id = $roomSelectEl.val(),
            departure_date = $widgetContainer.attr('departure-date'),
            price = $roomSelectEl.find('option:selected').data('price');
        const $loader = $('<span class="custom-loader"></span>'),
            $overlay = $('<div class="overlay"></div>');

        addToCartData.room_id = room_id;
        addToCartData.departure_date = departure_date;
        addToCartData.room_price = price;
        $widgetContainer.addClass('processing').attr('date-selected', false).append($overlay, $loader);

        const atcRes = await addToCart();
        if (atcRes.success) {
            const roomId = atcRes.data.room_id,
                roomName = atcRes.data.room_name,
                departureDate = atcRes.data.departure_date,
                retreatDates = $widgetContainer.find('.retreat-dates-range').text();
            const $afterATCoptions = $(`<div class="after-atc-options"><div class="options-title"><p class="top"><strong>"${roomName}"</strong> Room added to cart! </p><p class="bottom">What\'s Next?</p></div></div>`),
                $buttonsContainer = $('<div class="buttons-container"></div>'),
                $checkOutBtn = $(`<a href="/checkout" class="checkout-button">${shoppingCartSavg} Proceed To Checkout</a>`);
            $anotherRoomBtn = $(`<a href="javascript:void(0);" class="another-room-button">${plusSignSvg} Another Room For This Date</a>`),
                $anotherDateBtn = $(`<a href="javascript:void(0);" onclick="window.location.reload(true);" class="another-date-button">${calendarSvg} Check Other Dates</a>`),
                $buttonsContainer.append($checkOutBtn, $anotherRoomBtn);
            $afterATCoptions.append($buttonsContainer);
            $loader.fadeOut(400, function () {
                // $(this).remove();
                $widgetContainer.append($afterATCoptions);
            });
            $roomSelectEl.find(`option[value="${roomId}"]`).prop('disabled', true);
            $roomSelectEl.val('').trigger('change');
            $anotherRoomBtn.on('click', function () {
                location.reload();
            });
            $quantityInputs.val(1);
        }
    });
}
async function addToCart() {
    try {
        const response = await $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: addToCartData,
            beforeSend: function (xhr) {
                const $upsellCheckboxes = $('.upsell-checkbox');
                $upsellCheckboxes.each(function () {
                    const isChecked = $(this).is(':checked'),
                        isSecondParticipant = $(this).data('second-participant') == 'true';

                    if (isChecked && isSecondParticipant) {
                        retreatItemData.second_participants_count++;
                    }
                    if (retreatItemData.second_participants_count >= +retreatItemData.max_second_participants) {
                        retreatItemData.second_participant_available = false;
                    }
                });
            },
        });

        if (!response.success) {
            throw new Error('Network response was not ok');
        }
        return response;
    } catch (error) {
        console.error('Error:', error.message);
    }
}
function setAddToWaitlist(departureDate) {
    const $widgetContainer = $('.add-retreat-to-cart-container'),
        $instructionsTextWrapper = $widgetContainer.find('.instruction-text-wrapper'),
        $waitlistFormWrapper = $widgetContainer.find('.waitlist-form-wrapper'),
        $waitlistForm = $('#waitlist-form'),
        $nameInput = $waitlistForm.find('#waitlist-name'),
        $emailInput = $waitlistForm.find('#waitlist-email');
    const $loader = $('<span class="custom-loader"></span>'),
        $overlay = $('<div class="overlay"></div>'),
        $closeButton = $('<button class="close-overlay-button">&#215;</button>');
    addToWaitlistData.departure_date = departureDate;
    $widgetContainer.find('.instruction-text-wrapper').hide();
    $nameInput.on('change', function () {
        addToWaitlistData.name = $(this).val();
    })
    $emailInput.on('change', function () {
        addToWaitlistData.email = $(this).val();
    })
    $waitlistForm.on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: addToWaitlistData,
            beforeSend: function (xhr) {
                $widgetContainer.addClass('processing').append($overlay, $loader);
            },
            success: (response) => {
                const message = response.data;
                $loader.fadeOut(400);
                $overlay.append($closeButton, `<p class="waitlist-message">${message}</p>`);
                $closeButton.on('click', function () {
                    $widgetContainer.removeClass('processing');
                    $waitlistFormWrapper.empty();
                    $overlay.remove();
                    $loader.remove();
                    $instructionsTextWrapper.show(200);
                });
            }
        });
    });
}
function formatDateRange(departureDate, retreatDuration) {
    // Parse the departureDate string to a Date object
    const departure = new Date(departureDate);

    // Calculate the return date based on retreatDuration
    const returnDate = new Date(departure);
    returnDate.setDate(returnDate.getDate() + retreatDuration - 1);

    // Format months and days
    const departureMonth = departure.toLocaleString('en-US', { month: 'long' });
    const returnMonth = returnDate.toLocaleString('en-US', { month: 'long' });

    // Check if the return date is in the same month
    if (departure.getMonth() === returnDate.getMonth()) {
        return `${departureMonth} ${departure.getDate()} - ${returnDate.getDate()}, ${departure.getFullYear()}`;
    } else {
        return `${departureMonth} ${departure.getDate()} - ${returnMonth} ${returnDate.getDate()}, ${departure.getFullYear()}`;
    }
}
function setSelectDropdownItems($roomsDropdown, room, roomId) {
    const roomName = room.room_name,
        isBooked = room.is_booked,
        isSelected = room.is_selected,
        isFullBooked = retreatItemData.status == 'full',
        isDisabled = isBooked || isSelected || isFullBooked,
        canMultipleGuests = room.can_multiple_guests,
        price = +room.price;

    const $option = $(`<option class="${isSelected ? 'selected' : ''}" value="${roomId}" data-booked="${isBooked}" data-can-multiple="${canMultipleGuests}" data-price="${price}" ${isDisabled ? "disabled" : ""}>${roomName}</option>`);
    $roomsDropdown.append($option);

}
function setRoomPopupItem($popupList, room, roomId, retreatDuration, depositEnabled) {
    const roomName = room.room_name,
        isBooked = room.is_booked,
        isSelected = room.is_selected,
        isFullBooked = retreatItemData.status == 'full',
        price = +room.price,
        beds = {},
        amenities = {},
        imageSrc = room.image_src,
        gallery = room.gallery,
        details = room.details;

    for (const bedKey in details.beds) {
        const bed = details.beds[bedKey];
        if (bed.has_beds == 'on') beds[bedKey] = +bed.number;
    }
    for (const amenityKey in details.amenities) {
        const amenity = details.amenities[amenityKey];
        if (amenity == 'on') {
            let amenityText = '';
            switch (amenityKey) {
                case 'bathroom':
                    amenityText = 'Private Bathroom';
                    break;
                case 'fireplace':
                    amenityText = 'Fireplace';
                    break;
                case 'activities':
                    amenityText = 'Includes All Retreat Activities';
                    break;
                case 'meals':
                    amenityText = 'Includes All Meals';
                    break;
                case 'pickup':
                    amenityText = 'Includes Airport Pickup';
                    break;
            }
            amenities[amenityKey] = amenityText;
        }
    }
    let $bookRoomEl = '';
    const $item = $(`
    <li id="${roomId}" class="room-item-popup${isSelected ? ' selected' : ''}" data-booked="${isBooked}">
        <div class="room-heading">
            <h4 class="room-name">${roomName}</h4>
            <div class="right">
                <div class="price-wrapper">
                    <p class="room-price">$${price.toLocaleString()}</p>
                    ${depositEnabled ? `<p class="room-deposit">Deposit: $${(price * 0.15).toLocaleString()}</p>` : ''}
                </div>
            </div>
        </div>
        <div class="room-content-wrapper">
            <div class="room-image-wrapper">
                <img class="room-image" src="${imageSrc}" alt="${roomName} image">
            </div>
            <div class="room-details-wrapper">
                <ul class="room-details-list">
                    <li class="detail-item">${retreatDuration} Days & ${retreatDuration - 1} Nights</li>
                    ${Object.keys(beds).map(roomId => `<li>${beds[roomId]} x ${roomId} Bed${beds[roomId] > 1 ? 's' : ''}</li>`).join('')}
                    ${Object.keys(amenities).map(roomId => `<li>${amenities[roomId]}</li>`).join('')}
                </ul>
            </div>
        </div>
    </li>`
    );
    if (isBooked) {
        $bookRoomEl = $(`<p class="booked-room-alert">Booked for Current Dates</p>`);
    } else if (isSelected) {
        $bookRoomEl = $(`<button type="button" data-room="${roomId}" class="book-room-button" disabled>Selected</button>`);
    } else if (isFullBooked) {
        $bookRoomEl = $(`<p class="booked-room-alert" data-room="${roomId}" class="book-room-button" disabled>Can't Add To Order</p>`);
    } else {
        $bookRoomEl = $(`<button type="button" data-room="${roomId}" class="book-room-button">Book Now</button>`);
    }
    $item.find('.right').append($bookRoomEl);
    if (gallery.length) {
        const $gallery = $(`
    <div class="room-gallery-wrapper">
        <button type="button" class="room-gallery-button">more images</button>
        <button type="button" class="next-image-button">&#5589;</button>
        <div class="gallery-slider-wrapper">
            <ul class="room-gallery">
                ${gallery.map(image => `<li class="room-gallery-item"><img src="${image}" alt="${roomName} image"></li>`).join('')}
            </ul>
        </div>
`)
        $item.append($gallery);
    }

    const $bookRoomBtn = $item.find('.book-room-button'),
        $roomsPopupContainer = $('.rooms-list-popup'),
        $roomSelectEl = $('#rooms-options-select'),
        $galleryWrapper = $item.find('.room-gallery-wrapper');

    $bookRoomBtn.on('click', function () {
        const roomId = $(this).data('room');
        $roomSelectEl.val(roomId).trigger('change');
        $roomsPopupContainer.attr('data-active', false);
    });

    if ($galleryWrapper.length) setRoomGallerySlider($galleryWrapper);
    $popupList.append($item);
}
function setRoomGallerySlider($galleryWrapper) {
    const $wrapper = $galleryWrapper.find('.gallery-slider-wrapper'),
        $gallery = $galleryWrapper.find('.room-gallery'),
        $imageItem = $gallery.find('.room-gallery-item'),
        $showMoreBtn = $galleryWrapper.find('.room-gallery-button'),
        $nextBtn = $galleryWrapper.find('.next-image-button');
    let offset = 0;
    let inTransition = false;
    let breakpoint = isMobile ? 2 : 4;
    if ($(window).width() > 768 && $(window).width() < 1024) breakpoint = 3;
    let isAboveBreakpoint = $imageItem.length >= breakpoint;
    $showMoreBtn.on('click', function () {
        $wrapper.slideToggle(400);
        if (isAboveBreakpoint) $nextBtn.slideToggle(400);

        $(this).text() == 'more images'
            ? $(this).text('show less')
            : $(this).text('more images');
    });

    $nextBtn.on('click', function () {
        if (inTransition) return;
        inTransition = true;
        offset = offset ? offset : $imageItem.outerWidth() + 15;
        const currentOffset = $gallery.css('transform') != 'none'
            ? +$gallery.css('transform').split(",")[4].trim()
            : 0;
        const $firstItem = $gallery.find('.room-gallery-item').first(),
            $firstItemClone = $firstItem.clone();
        $gallery.css({
            'transform': `translateX(${currentOffset - offset}px)`,
            'transition': 'all .3s linear'
        }).append($firstItemClone);
        setTimeout(() => {
            $firstItem.remove();
            $gallery.css({
                'transform': `translateX(0)`,
                'transition': 'none'
            });
            inTransition = false;
        }, 300);
    });
}
function handleScroll() {
    if (isMobile) return;
    const $widgetContainer = $('.book-retreat-container');
    const $cartContainer = $('.add-retreat-to-cart-container');
    const headerHeight = $('header').outerHeight(), // Get the height of the header
        adminBarHeight = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0, // Get the height of the admin bar
        initialOffset = $cartContainer.offset().top - headerHeight,
        contantHeight = $widgetContainer.outerHeight();


    $(window).scroll(function () {
        const scrollPosition = $(window).scrollTop(),
            heightTreshold = headerHeight;
        if (scrollPosition >= initialOffset) {
            $cartContainer.css({
                'position': 'fixed',
                'top': heightTreshold + 'px', // Set top to the height of the header
                'z-index': '1000'
            });
            $widgetContainer.height(contantHeight);
        } else {
            $cartContainer.css({
                'position': 'relative',
                'top': 0
            });
        }
    });
}
function setDepartureDateWaitList(departureDate, isFullBooked) {
    const $addToCartContainer = $('.add-retreat-to-cart-container');
    const inputDateObject = new Date(departureDate);
    const formatOptions = { year: 'numeric', month: 'short', day: 'numeric' };
    const formattedDate = inputDateObject.toLocaleDateString('en-US', formatOptions);
    const waitListFormHTML = `
    <div class="waitlist-form-wrapper">
        <h5 class="waitlist-form-heading">Join The ${formattedDate} Retreat Waitlist</h5>
        <p class="waitlist-form-subheading">
            ${isFullBooked
            ? "Please enter your name and email below and we will contact you if a spot opens up."
            : "You can only add one retreat to your cart at a time. Until you decide what to do you can also join the waitlist below =)."
        }
        </p>
        <form class="waitlist-form" id="waitlist-form">
            <div class="input-wrapper">
                <label for="waitlist-name">Name</label>
                <input type="text" name="waitlist-name" id="waitlist-name" required>
            </div>
            <div class="input-wrapper">
                <label for="waitlist-email">Email</label>
                <input type="email" name="waitlist-email" id="waitlist-email" required>
            </div>
            <button type="submit" class="waitlist-submit-button">Join Waitlist</button>`;

    $addToCartContainer.find('.waitlist-form-wrapper').remove();
    $addToCartContainer.append(waitListFormHTML);
}
function setQueryData() {
    const urlParams = new URLSearchParams(window.location.search);
    const departureDate = urlParams.get('departure_date');
    const $nextMonthBtn = $('button.calendar-arrow-button.right');
    if (departureDate) {
        const $selectedDay = $(`.day-wrapper[data-date="${departureDate}"]`);
        $selectedDay.trigger('click');
        const interval = setInterval(() => {
            if (isElementXInView($selectedDay)) {
                clearInterval(interval);
                return;
            } else {
                $nextMonthBtn.click();
            }
        }, 300);
    }
}
function isElementXInView($element) {
    const $monthsWrapper = $('.retreats-calendar-container');
    const containerLeft = $monthsWrapper.offset().left;
    const containerRight = containerLeft + $monthsWrapper.width();
    var elementLeft = $element.offset().left;
    var elementRight = elementLeft + $element.outerWidth();

    return (elementLeft >= containerLeft && elementRight <= containerRight);
}
function getTripDaysElements($tripDay) {
    const tripId = $tripDay.data('retreat-id'),
        departureDate = $tripDay.data('departure');

    return $tripDay.closest('.month-wrapper').find(`.retreat-single-day[data-retreat-id="${tripId}"][data-departure="${departureDate}"]`);
}
const shoppingCartSavg = `<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"/><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M16.5285 6C16.5098 5.9193 16.4904 5.83842 16.4701 5.75746C16.2061 4.70138 15.7904 3.55383 15.1125 2.65C14.4135 1.71802 13.3929 1 12 1C10.6071 1 9.58648 1.71802 8.88749 2.65C8.20962 3.55383 7.79387 4.70138 7.52985 5.75747C7.50961 5.83842 7.49016 5.9193 7.47145 6H5.8711C4.29171 6 2.98281 7.22455 2.87775 8.80044L2.14441 19.8004C2.02898 21.532 3.40238 23 5.13777 23H18.8622C20.5976 23 21.971 21.532 21.8556 19.8004L21.1222 8.80044C21.0172 7.22455 19.7083 6 18.1289 6H16.5285ZM8 11C8.57298 11 8.99806 10.5684 9.00001 9.99817C9.00016 9.97438 9.00044 9.9506 9.00084 9.92682C9.00172 9.87413 9.00351 9.79455 9.00718 9.69194C9.01451 9.48652 9.0293 9.18999 9.05905 8.83304C9.08015 8.57976 9.10858 8.29862 9.14674 8H14.8533C14.8914 8.29862 14.9198 8.57976 14.941 8.83305C14.9707 9.18999 14.9855 9.48652 14.9928 9.69194C14.9965 9.79455 14.9983 9.87413 14.9992 9.92682C14.9996 9.95134 14.9999 9.97587 15 10.0004C15 10.0004 15 11 16 11C17 11 17 9.99866 17 9.99866C16.9999 9.9636 16.9995 9.92854 16.9989 9.89349C16.9978 9.829 16.9957 9.7367 16.9915 9.62056C16.9833 9.38848 16.9668 9.06001 16.934 8.66695C16.917 8.46202 16.8953 8.23812 16.8679 8H18.1289C18.6554 8 19.0917 8.40818 19.1267 8.93348L19.86 19.9335C19.8985 20.5107 19.4407 21 18.8622 21H5.13777C4.55931 21 4.10151 20.5107 4.13998 19.9335L4.87332 8.93348C4.90834 8.40818 5.34464 8 5.8711 8H7.13208C7.10465 8.23812 7.08303 8.46202 7.06595 8.66696C7.0332 9.06001 7.01674 9.38848 7.00845 9.62056C7.0043 9.7367 7.00219 9.829 7.00112 9.89349C7.00054 9.92785 7.00011 9.96221 7 9.99658C6.99924 10.5672 7.42833 11 8 11ZM9.53352 6H14.4665C14.2353 5.15322 13.921 4.39466 13.5125 3.85C13.0865 3.28198 12.6071 3 12 3C11.3929 3 10.9135 3.28198 10.4875 3.85C10.079 4.39466 9.76472 5.15322 9.53352 6Z" fill="#5f4b4d"/> </g></svg>`;
const calendarSvg = `<svg  viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"/><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/><g id="SVGRepo_iconCarrier"> <path d="M3 9H21M17 13.0014L7 13M10.3333 17.0005L7 17M7 3V5M17 3V5M6.2 21H17.8C18.9201 21 19.4802 21 19.908 20.782C20.2843 20.5903 20.5903 20.2843 20.782 19.908C21 19.4802 21 18.9201 21 17.8V8.2C21 7.07989 21 6.51984 20.782 6.09202C20.5903 5.71569 20.2843 5.40973 19.908 5.21799C19.4802 5 18.9201 5 17.8 5H6.2C5.0799 5 4.51984 5 4.09202 5.21799C3.71569 5.40973 3.40973 5.71569 3.21799 6.09202C3 6.51984 3 7.07989 3 8.2V17.8C3 18.9201 3 19.4802 3.21799 19.908C3.40973 20.2843 3.71569 20.5903 4.09202 20.782C4.51984 21 5.07989 21 6.2 21Z" stroke="#5f4b4d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/> </g></svg>`;
const plusSignSvg = `<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="none"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill="#5f4b4d" fill-rule="evenodd" d="M9 17a1 1 0 102 0v-6h6a1 1 0 100-2h-6V3a1 1 0 10-2 0v6H3a1 1 0 000 2h6v6z"></path> </g></svg>`;