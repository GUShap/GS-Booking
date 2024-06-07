/*------------------------ 
Backend related javascript
------------------------*/
const $ = jQuery;
let selectedRoomImages = customVars.selected_room_images
const orderMessages = Object.keys(customVars.order_messages).length ? customVars.order_messages : {};

$(window).on('load', () => {
    setExpirationCountdown();
    setRemoveImage();
    setMessageScheduleOptions();
    setRetreatsManagement();
    // saveStripeConfigs();
    // setRoomsDashboard();
    setDepositLimits();
    hideOrderItemMeta();
    setDownloadCSV();
    setArchiveDepartureDate();
});

/* ORDER */
function setExpirationCountdown() {
    // Function to update countdown
    const updateCountdown = () => {
        let hoursElement = $('#expiration-countdown .hours');
        let minutesElement = $('#expiration-countdown .minutes');
        let secondsElement = $('#expiration-countdown .seconds');

        let hours = parseInt(hoursElement.text());
        let minutes = parseInt(minutesElement.text());
        let seconds = parseInt(secondsElement.text());

        // Check if all values are zero
        if (hours === 0 && minutes === 0 && seconds === 0) {
            // Countdown reached zero, you can handle this case as needed
            clearInterval(countdownInterval);
            return;
        }

        // Subtract one second
        if (seconds > 0) {
            seconds--;
        } else {
            if (minutes > 0) {
                minutes--;
                seconds = 59;
            } else {
                if (hours > 0) {
                    hours--;
                    minutes = 59;
                    seconds = 59;
                }
            }
        }

        // Update the HTML elements
        hoursElement.text(hours < 10 ? '0' + hours : hours);
        minutesElement.text(minutes < 10 ? '0' + minutes : minutes);
        secondsElement.text(seconds < 10 ? '0' + seconds : seconds);
    };

    // Call the updateCountdown function every second
    const countdownInterval = setInterval(updateCountdown, 1000);
}

function hideOrderItemMeta() {
    $('#woocommerce-order-items table.display_meta tr:has(th:contains("room_id"))').hide();
    $('#order_line_items tr.item').each(function () {
        console.log($(this).find('bdi').last().text());
        if ($(this).find('bdi').last().text() == "$0.00") {
            $(this).hide();
        }
    });
}

/* ROOMS CPT */
function uploadGalleryImages() {
    let mediaUploader;
    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
        //button_text set by wp_localize_script()
        title: 'Choose Images',
        library: {
            type: ['image']
        },
        multiple: 'add' //allowing for multiple image selection

    });
    mediaUploader.on('open', function () {

        // if there's a file ID
        if (selectedRoomImages.length) {
            // select the file ID to show it as selected in the Media Library Modal.
            selectedRoomImages.forEach(function (fileID) {
                mediaUploader.uploader.uploader.param('post_id', parseInt(fileID));
                var selection = mediaUploader.state().get('selection');
                selection.add(wp.media.attachment(fileID));
            });
        }
    });
    mediaUploader.on('select', function () {

        var attachments = mediaUploader.state().get('selection').map(

            function (attachment) {

                attachment.toJSON();
                return attachment;

            });
        //loop through the array and do things with each attachment

        $('#gallery-preview').empty();
        selectedRoomImages = [];
        let i;

        for (i = 0; i < attachments.length; ++i) {
            const imageID = attachments[i].id,
                imageUrl = attachments[i].attributes.url;
            //sample function 1: add image preview
            $('#gallery-preview').append(
                `<li class="gallery-item image-item-preview">
                        <button type="button" class="remove-image-button">&#215;</button> 
                        <img src="${imageUrl}" >
                        <input id="image-input-${imageID}" type="hidden" name="gallery[${imageID}]"  value="${imageID}" /> 
                    </li>`
            );
            selectedRoomImages.push(imageID);
            setRemoveImage();
            //sample function 2: add hidden input for each image
            // $('#gallery-preview').after(`<input id="image-input${attachments[i].id}" type="hidden" name="gallery[]"  value="${attachments[i].id}" /> `);

        }

    });

    mediaUploader.open();
}

function setRemoveImage() {
    $('.remove-image-button').click(function (e) {
        e.preventDefault();
        const siblingItems = $(this).closest('.gallery-item').siblings();
        selectedRoomImages = [];
        siblingItems.each(function () {
            const imageID = $(this).find('input').val();
            selectedRoomImages.push(imageID);
        });
        $(this).parent().remove();
    });
}

/* MESSAGES CPT */

function uploadFileForMail() {

    let mediaUploader;

    // If the uploader object has already been created, reopen the dialog
    if (mediaUploader) {
        mediaUploader.open();
        return;
    }

    // Create the media uploader
    mediaUploader = wp.media.frames.file_frame = wp.media({
        title: 'Choose File',
        button: {
            text: 'Choose File'
        },
        multiple: false  // Set to true if you want to allow multiple file uploads
    });

    mediaUploader.on('open', function () {
        // if there's a file ID
        if ($('input#attachment-id').val()) {
            // select the file ID to show it as selected in the Media Library Modal.
            mediaUploader.uploader.uploader.param('post_id', parseInt($('input#attachment-id').val()));
            const selection = mediaUploader.state().get('selection');
            selection.add(wp.media.attachment($('input#attachment-id').val()));
        }
    });
    // When a file is selected, grab the ID and use it to get the URL
    mediaUploader.on('select', function () {
        const attachment = mediaUploader.state().get('selection').first().toJSON();

        // Use attachment.id to get the attachment ID
        const attachment_id = attachment.id;

        // Get the URL using the attachment ID
        const attachment_url = wp.media.attachment(attachment_id).get('url');
        $('button#upload-file-button').text('Change File');
        $('input#attachment-id').val(attachment_id);
        $('input#attachment-url').val(attachment_url);
        $('p#attachment-name').text(attachment.filename);
        // You can now use attachment_url as needed
        // alert('File selected: ' + attachment_url);
    });

    // Open the uploader dialog
    mediaUploader.open();
}

function removeAttachment(){
    // $('button#remove-attachment-button').click(function (e) {
        $('button#upload-file-button').text('Upload File');
        $('input#attachment-id').val('');
        $('input#attachment-url').val('');
        $('p#attachment-name').text('');
    // });

}

function setMessageScheduleOptions() {
    const $optionsContainer = $('#retreat-message-options'),
        $scheduleOptions = $optionsContainer.find('.timing-wrapper');

    const setScheduleOption = ($option) => {
        const $activateCheckbox = $option.find('input[role="activate"]'),
            $daysInput = $option.find('input[role="days"]'),
            $timeInput = $option.find('input[role="time"]');

        $daysInput.prop('required', $activateCheckbox.is(':checked'));
        $timeInput.prop('required', $activateCheckbox.is(':checked'));
    }
    $scheduleOptions.each(function () {
        const $activateCheckbox = $scheduleOptions.find('input[role="activate"]');
        setScheduleOption($(this));

        $activateCheckbox.on('change', () => {
            setScheduleOption($(this));
        });
    });
}

/* MANAGE RETREATS */
function setRetreatsManagement() {
    const $retreatsManage = $('#retreats-management'),
        $retreats = $retreatsManage.find('.single-retreat'),
        $departureDates = $retreatsManage.find('.departure-date'),
        $rooms = $retreatsManage.find('ul.rooms-list > li'),
        $guests = $retreatsManage.find('ul.guests-list>li:not(.guests-heading)');

    $retreats.each(function (idx) {
        const $heading = $(this).find('.retreat-heading'),
            $content = $(this).find('.retreat-content');

        const currentTopOffset = $heading.offset().top,
            offsetAdjust = ($heading.height() + 15) * idx

        $heading.offset({ 'top': currentTopOffset + offsetAdjust });
        $heading.on('click', (idx, heading) => {
            $(this).attr('data-selected', 'true');
            $(this).siblings().attr('data-selected', 'false');
        });
    });

    $departureDates.each(function () {
        const $heading = $(this).find('.heading'),
            $content = $(this).find('.content');

        $heading.on('click', (e) => {
            const isSelected = $(this).attr('data-selected') == 'true';
            $(this).attr('data-selected', isSelected ? 'false' : 'true');
            $content.slideToggle(300);
            $(this).siblings().find('.content').slideUp(300);
            $(this).siblings().attr('data-selected', 'false');
            $content.offset({ left: $(this).closest('.retreat-content').offset().left + 15 });
        });
    });

    $rooms.on('click', function () {
        const status = $(this).data('status');
        let isSelected = $(this).attr('data-selected') == 'true';
        const $siblings = $(this).siblings();
        if (status == 'available') return;
        $(this).find('.room-content').slideToggle(300);
        $(this).attr('data-selected', isSelected ? 'false' : 'true');
        isSelected = !isSelected;

        $siblings.each(function () {
            $(this).attr('data-selected', 'false')
            $(this).find('.room-content').slideUp(300);
            setRoomItemStyle($(this), false)
        });

        if (isSelected) {
            const $content = $(this).find('.room-content');

            $content.offset({ left: $(this).closest('.retreat-content').offset().left + 30 });
            $content.css({
                width: `calc(${$(this).closest('.content').width() / $(this).width() * 100}% - 13px)`,
                // left:$(this).closest('.retreat-content').offset().left + 'px',
                display: "grid",
                "grid-template-columns": "repeat(2, 1fr)"
            });
        }

        setRoomItemStyle($(this), isSelected);
    });

    $rooms.on('mouseover', function () {
        setRoomItemStyle($(this), true)
    });

    $rooms.on('mouseleave', function () {
        if ($(this).attr('data-selected') == 'true') return;
        setRoomItemStyle($(this), false)
    });

    $guests.on('click', function () {
        $(this).toggleClass('expanded');
    });

    $guests.each(function () {
        const $editDetailsBtn = $(this).find('button.edit-details'),
            $details = $(this).find('.details'),
            $nameText = $details.find('.name-wrapper strong'),
            $emailText = $details.find('.email-wrapper p'),
            $phoneText = $details.find('.phone-wrapper p'),
            $editDetailsForm = $(this).find('form.edit-participant-details'),
            $nameInput = $editDetailsForm.find('input.name-input'),
            $emailInput = $editDetailsForm.find('input.email-input'),
            $phoneInput = $editDetailsForm.find('input.phone-input'),
            $sendMissedMessagesCheckbox = $editDetailsForm.find('input.send-missed-messages-checkbox');

        const originalEmail = $emailInput.val();

        $editDetailsBtn.on('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $details.attr('edit-mode', 'true');
        });
        $editDetailsForm.find('input').on('click', function (e) {
            e.stopPropagation();
        });
        $editDetailsForm.on('submit', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $.ajax({
                url: customVars.ajaxUrl,
                type: 'POST',
                data: $(this).serialize() + '&action=update_guest_details',
                success: function (response) {
                    $details.attr('edit-mode', 'false');
                    $nameText.text($nameInput.val());
                    $emailText.text($emailInput.val());
                    $phoneText.text($phoneInput.val());
                }
            });
        });
        $emailInput.on('input', function () {
            const currentEmail = $(this).val();
            if(currentEmail !== originalEmail){
                $sendMissedMessagesCheckbox.parent().removeClass('hidden')
            } else{
                $sendMissedMessagesCheckbox.prop('checked', false);
                $sendMissedMessagesCheckbox.parent().addClass('hidden');
            }
        });
    });
    setGuestsList($guests);
    setManualRoomBooking($rooms);
}

function setRoomItemStyle($roomItem, isSelected) {
    const $roomHeading = $roomItem.find('.room-heading');
    const color = $roomItem.data('color');

    $roomHeading.css({
        'background-color': isSelected ? color : '',
        'color': isSelected ? '#fff' : '',
        'text-shadow': isSelected ? '1px 1px 1px #000' : 'none'
    });
}

function setManualRoomBooking($rooms) {
    $rooms.each(function () {
        const $manualRoomBookingBtn = $(this).find('.manual-booking-btn'),
            $manualRoomBookingForm = $(this).find('form.book-room-form');

        $manualRoomBookingBtn.on('click', function (e) {
            e.stopPropagation();
            $manualRoomBookingForm.attr('data-active', 'true');
        });
        $manualRoomBookingForm.on('click', function (e) {
            e.stopPropagation();
        });
    });
}

function setGuestsList($guests) {
    const guestsMessageAjax = (email, name, order_id, message_id, email_action) => {
        $.ajax({
            url: customVars.ajaxUrl,
            type: 'POST',
            data: {
                action: 'send_order_emails',
                email,
                message_id,
                name,
                order_id,
                email_action
            },
            success: function (response) {
                console.log(response);
            }
        });
    }

    $guests.each(function () {
        const $messageButtons = $(this).find('.actions button');
        $messageButtons.on('click', function (e) {
            e.stopPropagation();
            const email_action = $(this).data('action'),
                email = $(this).data('email'),
                name = $(this).data('name'),
                order_id = $(this).data('order'),
                message_id = $(this).data('message');
            guestsMessageAjax(email, name, order_id, message_id, email_action);
        });
    });

}

function setDownloadCSV() {
    const $container = $('#retreats-management'),
        $downloadBtn = $container.find('.departure-date .csv-button');
    const is_archive = $container.data('archive');
    const getCSVData = async (csv_action, retreat_id, departure_date) => {
        try {
            const response = await $.ajax({
                url: customVars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_csv_data',
                    csv_action,
                    retreat_id,
                    departure_date,
                    is_archive
                }
            });
            return response.data;
        } catch (error) {
            error('Error:', error.message);
            return null;
        }
    }

    $downloadBtn.on('click', async function () {
        const action = $(this).data('action'),
            departure_date = $(this).data('departure'),
            retreat_id = $(this).data('retreat');

        const csvData = await getCSVData(action, retreat_id, departure_date);
        if (action == 'guests') {
            downloadGuestsCSV(csvData, departure_date);
        }
        if (action == 'rooms') {
            downloadRoomsCSV(csvData, departure_date);
        }
    });
}

function downloadGuestsCSV(csvData, departureDate) {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Retreat Date,Room Name,Occupant Name,Email,Phone Number\n";

    csvData.forEach(function (row) {
        csvContent += departureDate + ","
            + row.room_name + ","
            + row.name + ","
            + row.email + ","
            + row.phone + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `guests-list-${departureDate}.csv`);
    document.body.appendChild(link); // Required for FF

    link.click();
}

function downloadRoomsCSV(csvData, departureDate) {
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Room Name,Departure Date,Status,Capacity,Guests,Price,Payments Collected, Addons\n";

    csvData.forEach(function (row) {
        let addonsStr = '',
            guestsStr = '';

        if (row.guests.length) {
            row.guests.forEach(guest => {
                guestsStr += guest.name + ";\t";
            });
            guestsStr = guestsStr.slice(0, -1);
        }
        if (row.addons.length) {
            row.addons.forEach(addon => {
                addonsStr += `${addon.name} x ${addon.quantity};\t`;
            });
            addonsStr = addonsStr.slice(0, -1);
        }

        csvContent += row.room_name + ","
            + departureDate + ","
            + row.status + ","
            + row.room_capacity + ","
            + guestsStr + ","
            + row.price + ","
            + row.payments_collected + ","
            + addonsStr + "\n";
    });
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);

    link.setAttribute("download", `rooms-list-${departureDate}.csv`);
    document.body.appendChild(link); // Required for FF

    link.click();
}

function setArchiveDepartureDate() {
    const $archiveDateBtn = $('#retreats-management .departure-date .archive-date-button'),
        $removeDateFromArchiveBtn = $('#retreats-management .departure-date .remove-archive-date-button');
    $archiveDateBtn.on('click', async function () {
        const $dateContainer = $(this).closest('.departure-date'),
            $dateContent = $dateContainer.find('.content');
        const retreat_id = $(this).data('retreat'),
            departure_date = $(this).data('departure');

        $dateContent.slideUp(300);
        $dateContainer.attr('data-selected', 'false');
        const response = await archiveDepartureDate(retreat_id, departure_date);
        if (response.success) {
            $dateContainer.fadeOut(300);
        }
    });

    $removeDateFromArchiveBtn.on('click', async function () {
        const $dateContainer = $(this).closest('.departure-date'),
            $dateContent = $dateContainer.find('.content');
        const retreat_id = $(this).data('retreat'),
            departure_date_key = $(this).data('departure');

        $dateContent.slideUp(300);
        $dateContainer.attr('data-selected', 'false');
        const response = await removeDateFromArchive(retreat_id, departure_date_key);
        if (response.success) {
            $dateContainer.fadeOut(300);
        }
    });
}

async function archiveDepartureDate(retreat_id, departure_date) {
    return $.ajax({
        url: customVars.ajaxUrl,
        type: 'POST',
        data: {
            action: 'archive_departure_date',
            retreat_id,
            departure_date
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}

async function removeDateFromArchive(retreat_id, departure_date_key) {
    return $.ajax({
        url: customVars.ajaxUrl,
        type: 'POST',
        data: {
            action: 'remove_date_from_archive',
            retreat_id,
            departure_date_key
        },
        dataType: 'json'
    }).then(response => {
        if (response.success) {
            return response;
        } else {
            return false;
        }
    }).catch(() => {
        return false;
    });
}

/* ROOMS DASHBOARD */
function setRoomsDashboard() {
    const $roomsDashboard = $('#rooms-stats'),
        $rooms = $roomsDashboard.find('ul.rooms-list> li'),
        $retreats = $rooms.find('.retreats-revenue-wrapper'),
        $dates = $retreats.find('.date-wrapper'),
        $navButtons = $retreats.find('.arrows-container button');

    $rooms.on('click', function () {
        let isSelected = $(this).attr('data-selected') == 'true';
        const $siblings = $(this).siblings();
        $(this).find('.room-content').slideToggle(300);
        $(this).attr('data-selected', isSelected ? 'false' : 'true');
        isSelected = !isSelected;

        $siblings.each(function () {
            $(this).attr('data-selected', 'false')
            $(this).find('.room-content').slideUp(300);
            setRoomItemStyle($(this), false)
        });

        setRoomItemStyle($(this), isSelected);
    });

    $rooms.on('mouseover', function () {
        setRoomItemStyle($(this), true)
    });

    $rooms.on('mouseleave', function () {
        if ($(this).attr('data-selected') == 'true') return;
        setRoomItemStyle($(this), false)
    });

    $rooms.each(function () {
        const $payments = $(this).find('.single-payment-wrapper');
        $payments.on('click', function (e) {
            e.stopPropagation();
            const $content = $(this).find('.content');
            $(this).toggleClass('expanded');
            $content.slideToggle(300);
        });

    })

    $navButtons.on('click', function (e) {
        e.stopPropagation();
        const isNext = $(this).hasClass('next-arrow');
        const $retreatsContent = $(this).closest('.arrows-container').siblings('.retreats-content'),
            $retreatItems = $retreatsContent.find('.retreat-wrapper'),
            $currentRetreat = $retreatsContent.find('.retreat-wrapper[data-current="true"]'),
            $nextRetreat = isNext ? $currentRetreat.next() : $currentRetreat.prev();


        const currentOffsetX = $retreatsContent.css('transform') == 'none' ? 0 : +$retreatsContent.css('transform').split(',')[4],// check value of tranform(translateX), if null set to 0
            translateX = isNext ? currentOffsetX - ($retreatItems.outerWidth() + 20) : currentOffsetX + ($retreatItems.outerWidth() + 20);

        $retreatsContent.css('transform', `translateX(${translateX}px)`);
        $currentRetreat.attr('data-current', 'false');
        $nextRetreat.attr('data-current', 'true');
    });

    $dates.on('click', function (e) {
        e.stopPropagation();
        const $content = $(this).find('.content');
        $(this).toggleClass('active');
        $content.slideToggle(300);
    });
}

/* OPTIONS */

function setDepositLimits() {
    const $depositLimitsContainer = $('#options .limit-deposit-room .input-wrapper.type-repeater'),
        $addLimitBtn = $depositLimitsContainer.find('.add-row-button'),
        $limitsList = $depositLimitsContainer.find('.limits-list'),
        $limitItems = $limitsList.find('.limit-item'),
        $limitSectionsLabels = $limitItems.find('.repeater-section-wrapper .label');

    $addLimitBtn.on('click', function (e) {
        e.preventDefault();
        const limitIdx = $limitsList.find('.limit-item').length,
            itemNamePrefix = `reservation_limits[${limitIdx}]`;
        const $newLimit = singleItemHtml(limitIdx),
            $deleteItemButton = $newLimit.find('.delete-limit-button'),
            $addConditionButton = $newLimit.find('.add-condition-button');
        $limitsList.append($newLimit);
        setDeleteItemButton($deleteItemButton);
        setAddConditionButton($addConditionButton, itemNamePrefix);
    });

    $limitItems.each(function () {
        const $deleteItemButton = $(this).find('.delete-limit-button'),
            $addConditionButton = $(this).find('.add-condition-button'),
            $conditions = $(this).find('.conditions');
        const itemNamePrefix = `reservation_limits[${$(this).data('index')}]`;

        setDeleteItemButton($deleteItemButton);
        setAddConditionButton($addConditionButton, itemNamePrefix);

        $conditions.find('.condition-row').each(function () {
            setDeleteConditionButton($(this).find('.delete-condition-button'));
        });
    });

    $limitSectionsLabels.on('click', function () {
        const $content = $(this).siblings('.content');
        $content.slideToggle(300);
        $(this).closest('.repeater-section-wrapper').toggleClass('active');
    });
}

function singleItemHtml(itemIdx) {
    const itemNamePrefix = `reservation_limits[${itemIdx}]`;
    const deleteItemButton = `<button type="button" class="button-24 delete-limit-button">Delete</button>`,
        addConditionButton = `<button type="button" class="button-4 add-condition-button">Add Condition</button>`;

    const $item = $(`
        <li class="limit-item" data-index=${itemIdx}>
            <div class="heading-wrapper">
                <input type="text" name="${itemNamePrefix}[item_name]" class="item-name" value="">
                <div class="buttons-wrapper">
                    ${deleteItemButton}
                </div>
            </div>
            <div class="content-wrapper">
                <div class="conditions-wrapper">
                    <strong>Conditions</strong>
                    <div class="content">
                        <div class="conditions">
                        </div>
                        ${addConditionButton}
                    </div>
                </div>
                <div class="action-wrapper">
                    <strong>Expiration Time</strong>
                    <div class="time-row">
                        expire room reservation
                        <input type="number" name="${itemNamePrefix}[action][time_units]" class="units" min="0" value="">
                        <select name="${itemNamePrefix}[action][time_type]" class="type">
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                            <option value="weeks">Weeks</option>
                            <option value="months">Months</option>
                        </select>
                        <span class="relation-value">After</span>
                        <select name="${itemNamePrefix}[action][time_reference]" class="reference" required>
                            <option value="" disabled="" selected >Select Reference</option>
                            <option value="departure">Departure Date</option>
                            <option value="order">Order</option>
                        </select>
                    </div>
                </div>                 
                <div class="messages-wrapper">
                    <strong>Messages</strong>
                    <div class="messages-checkbox-wrapper">
                    ${setOrderMessagesHtml(itemNamePrefix)}  
                </div>
            </div>
        </li>
    `);
    return $item;
}

function setOrderMessagesHtml(itemNamePrefix) {
    const messagesPrefix = `${itemNamePrefix}[messages]`;
    let messagesHtml = '';

    const singleMessageHtml = (messagesPrefix, messageId, messageTitle) => {
        const singleMessagePrefix = `${messagesPrefix}[${messageId}]`;
        return `<div class="input-group limit-message">
                    <div class="input-wrapper type-checkbox">
                        <input type="checkbox" role="activate" name="${singleMessagePrefix}[is_checked]" id="message_${messageId}">
                        <label for="message_${messageId}">${messageTitle}</label>
                    </div>
                    <div class="inner">
                        <div class="input-wrapper type-checkbox">
                            <input type="checkbox" name="${singleMessagePrefix}[send_on_expire]" id="send_on_expire_${messageId}">
                            <label for="send_on_expire_${messageId}">Send on Expiration</label>
                        </div>
                        <div class="input-wrapper type-checkbox">
                            <input type="checkbox" role="schedule" name="${singleMessagePrefix}[schedule_before_expire]" id="schedule_before_expire_${messageId}">
                            <label for="schedule_before_expire_${messageId}">Schedule Before Expiration</label>
                        </div>
                        <div class="schedule-message-wrapper">

                            <div class="input-wrapper type-text">
                                <input type="number" name="${singleMessagePrefix}[days_before_expire]" id="days_before_expire_${messageId}" value="">
                                <label for="days_before_expire_${messageId}">Days Before Expiration</label>
                            </div>
                            <div class="input-wrapper type-time">
                                <label for="time_before_expire_${messageId}">at</label>
                                <input type="time" name="${singleMessagePrefix}[time_before_expire]" id="time_before_expire_${messageId}" value="">
                            </div>
                        </div>
                    </div>
                </div>`
    }
    for (const messageId in orderMessages) {
        const messageTitle = orderMessages[messageId];
        messagesHtml += singleMessageHtml(messagesPrefix, messageId, messageTitle);
    }
    return messagesHtml;
}

function setDeleteItemButton($button) {
    $button.on('click', function (e) {
        e.preventDefault();
        const $item = $(this).closest('.limit-item');
        $item.remove();
    });
}

function setAddConditionButton($button, itemNamePrefix) {
    $button.on('click', function (e) {
        e.preventDefault();
        const $conditions = $(this).siblings('.conditions');

        const conditionIdx = $conditions.find('.condition-row').length,
            conditionNamePrefix = `${itemNamePrefix}[conditions][${conditionIdx}]`;
        const $condition = $(`
        <div class="condition-row" data-idx="${conditionIdx}">
            ${conditionIdx
                ? `<select name="${conditionNamePrefix}[condition_logic]" class="condition_logic">
                    <option value="&&">AND</option>
                    <option value="||">OR</option>
                 </select>`
                : ''
            }
                <div class="">
                <button type="button" class="delete-condition-button">&#215;</button>
                <span>Apply if deposit paid</span>
                <select name="${conditionNamePrefix}[condition_operator]" class="operator">
                    <option value=">">More Than</option>
                    <option value="<">Less Than</option>
                    <option value="=">Exactly</option>
                    <option value=">=">More Than or Equal</option>
                    <option value="<=">Less Than or Equal</option>
                </select>
                <input type="number" name="${conditionNamePrefix}[condition_units]" class="units" min="0" value="">
                <select name="${conditionNamePrefix}[condition_type]" class="type">
                    <option value="hours">Hours</option>
                    <option value="days">Days</option>
                    <option value="weeks">Weeks</option>
                    <option value="months">Months</option>
                </select>
                <span>Before Departure</span>
            </div>
        </div>
        `);
        $conditions.append($condition);
        setDeleteConditionButton($condition.find('.delete-condition-button'));
    });
}

function setDeleteConditionButton($button) {
    $button.on('click', function (e) {
        e.preventDefault();
        const $condition = $(this).closest('.condition-row'),
            $nextConditions = $condition.nextAll('.condition-row');
        $condition.remove();
        if (!$nextConditions.length) return;
        // Update the indexes of the next conditions's data-idx and input names
        $nextConditions.each(function () {
            const idx = $(this).data('idx');
            $(this).attr('data-idx', idx - 1);
            $(this).find('input, select').each(function () {
                const name = $(this).attr('name');
                $(this).attr('name', name.replace(/\[(\d+)\]/g, `[${idx - 1}]`));
            });
        });
    });
}

function setEditItemButton($button) {
    $button.on('click', function (e) {
        e.preventDefault();
        const isEditMode = $(this).attr('edit-mode') == 'false';
        const $item = $(this).closest('.limit-item');
        if (isEditMode) {
            $(this).attr('edit-mode', 'true');
            $(this).text('Set');
            $item.find('input, select, textarea').prop({ 'disabled': false, 'readonly': false });
        } else {
            $(this).attr('edit-mode', 'false');
            $(this).text('Edit');
            $item.find('input, select, textarea').prop({ 'disabled': true, 'readonly': true });
        }
    });
}