const $ = jQuery;
const isMobile = customVars.is_mobile ? true : false,
    isSingle = customVars.is_single ? true : false,
    ajaxUrl = customVars.ajax_url;
$(window).on('load', () => {
    setMonthsSlider();
    setRetreatInfoPopup();
    setRetreatsHover();
});

function setMonthsSlider() {
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
        return isSingle
            ? isElementInView($firstMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width())
            : isElementInView($firstMonth[0]);
    }
    const isLastMonthInView = () => {
        return isSingle
            ? isElementInView($lastMonth[0], $retreatsContentContainer.offset().left, $retreatsContentContainer.offset().left + $retreatsContentContainer.width())
            : isElementInView($lastMonth[0]);
    }
     const setArrowsVisibility = () => {
        const minMonthQuantity = 2;
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
        const distance = isSingle || isMobile
            ? '100% - 10px'
            : '50% - 10px';
        const translateXValue = getTranslateX($retreatsContentWrapper);
        const newTranslateX = `calc(${translateXValue}px - ${distance})`;

        if (!isLastMonthInView()) {
            $retreatsContentWrapper.css('transform', 'translateX(' + newTranslateX + ')');
        }
    });
    $arrowLeft.on('click', function () {
        if (inTransition) return;
        const distance = isSingle || isMobile
            ? '100% + 10px'
            : '50% + 10px';
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
    // setArrowsVisibility();
}

function setRetreatInfoPopup() {
    const $tripDates = $('.retreats-calendar-content .day-wrapper .retreat-single-day:not(.full-booked)');

    $tripDates.on('click', function () {
        const $tripDays = getTripDaysElements($(this));
        const $retreatsInfo = $('.retreat-info-wrapper');
        const $clonedRetreatInfo = $retreatsInfo.filter('.clone');
        const tripId = $(this).data('retreat-id');
        const tripDepartureDate = $(this).data('departure');

        if ($tripDays.hasClass('selected')) return;

        const $monthWrapper = $(this).closest('.month-wrapper');
        const $selectedRetreats = $retreatsInfo.filter(function () { return $(this).data('id') === tripId })
        const $selectedRetreatClone = $selectedRetreats.first().clone();
        const $retreatInfoLink = $selectedRetreatClone.find('a.retreat-link');
        const $lastTripDate = $monthWrapper.find(`.retreat-single-day[data-retreat-id="${tripId}"][data-departure="${tripDepartureDate}"]`).last().closest('.day-wrapper');
        const $infoTargetLocation = $lastTripDate.nextAll('.day-wrapper[data-day="sunday"]').first();
        const setRetreatInfoRender = () => {
            const currentUrl = $retreatInfoLink.prop('href');
            const newUrl = setQueryParams(currentUrl, { 'departure_date': tripDepartureDate });

            $infoTargetLocation.length
                ? $infoTargetLocation.before($selectedRetreatClone)
                : $monthWrapper.find('.dates-wrapper').append($selectedRetreatClone);

            $selectedRetreatClone.addClass('clone').attr({
                'data-selected': true,
                'data-departure': tripDepartureDate
            });
            $retreatInfoLink.prop('href', newUrl);
            $selectedRetreatClone.slideDown(100);
        }

        if ($clonedRetreatInfo.length) {
            $clonedRetreatInfo.slideUp({
                duration: 100,
                complete: () => {
                    $clonedRetreatInfo.remove();
                    setRetreatInfoRender();
                }
            });
        } else setRetreatInfoRender();
        $tripDates.removeClass('selected').removeClass('hovered');
        $tripDays.addClass('selected');
        $(':root').css('--retreat-color', $tripDays.data('color'));
    });
}

function setRetreatsHover() {
    const $retreats = $('.retreats-calendar-content .retreat-single-day:not(.full-booked)');
    $retreats.on('mouseenter', function () {
        const $tripDays = getTripDaysElements($(this));
        $tripDays.addClass('hovered');
       $(':root').css('--retreat-hover-color', $tripDays.data('color')+ '80');
    });
    $retreats.on('mouseleave', function () {
        const $tripDays = getTripDaysElements($(this));
        $tripDays.removeClass('hovered');
    });

}

function formatDateToYYYYMMDD(dateString) {
    const date = new Date(dateString);

    // Check if the date is valid
    if (isNaN(date.getTime())) {
        return 'Invalid date';
    }

    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');

    return year + '-' + month + '-' + day;
}

function getTripDaysElements($tripDay) {
    const tripId = $tripDay.data('retreat-id'),
        departureDate = $tripDay.data('departure');

    return $tripDay.closest('.month-wrapper').find(`.retreat-single-day[data-retreat-id="${tripId}"][data-departure="${departureDate}"]`)
}

function setQueryParams(url, params) {
    const queryString = $.param(params);
    // remove all query params first
    url = url.replace(/(\?.*?)?(#.*)?$/, '$2');
    // add new query params
    return url + (url.indexOf('?') === -1 ? '?' : '&') + queryString;
}