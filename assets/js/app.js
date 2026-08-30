$(function () {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    $('a[href^="#"]').on('click', function (event) {
        const target = $(this.getAttribute('href'));

        if (target.length) {
            event.preventDefault();
            if (reducedMotion) {
                window.scrollTo(0, target.offset().top - 70);
            } else {
                $('html, body').animate({ scrollTop: target.offset().top - 70 }, 500);
            }
        }
    });

    const variantRows = $('#variantRows');
    const addVariantButton = $('#addVariant');

    function updateVariantFields() {
        variantRows.find('.variant-row').each(function (index) {
            $(this).find('[data-variant-field]').each(function () {
                const field = $(this).data('variant-field');
                const id = 'variant_' + field + '_' + index;

                $(this).attr('id', id);
                $(this).attr('name', 'variants[' + index + '][' + field + ']');
                $(this).siblings('label').attr('for', id);
            });
        });
    }

    addVariantButton.on('click', function () {
        const newRow = variantRows.find('.variant-row').first().clone();
        newRow.find('input[type="checkbox"]').prop('checked', false);
        newRow.find('input').val('');
        newRow.find('.variant-existing-image').val('');
        newRow.find('select').val('active');
        newRow.find('.invalid-feedback').remove();
        newRow.find('.form-control, .form-select').removeClass('is-invalid');
        newRow.find('.remove-variant').removeClass('d-none');
        newRow.find('.variant-image-preview').remove();
        variantRows.append(newRow);
        updateVariantFields();
    });

    variantRows.on('click', '.remove-variant', function () {
        if (variantRows.find('.variant-row').length > 1) {
            $(this).closest('.variant-row').remove();
            updateVariantFields();
        }
    });

    updateVariantFields();

    const themeToggle = $('#themeToggle');
    const themeToggleLabel = $('#themeToggleLabel');
    const savedTheme = localStorage.getItem('balochi-dastar-theme');

    function applyTheme(theme) {
        const isDark = theme === 'dark';
        $('body').attr('data-theme', isDark ? 'dark' : 'light');
        themeToggle.attr('aria-pressed', isDark ? 'true' : 'false');
        themeToggleLabel.text(isDark ? 'Light' : 'Dark');
    }

    if (themeToggle.length) {
        applyTheme(savedTheme === 'dark' ? 'dark' : 'light');
        themeToggle.on('click', function () {
            const nextTheme = $('body').attr('data-theme') === 'dark' ? 'light' : 'dark';
            localStorage.setItem('balochi-dastar-theme', nextTheme);
            applyTheme(nextTheme);
        });
    }

    const colorOptions = $('.product-color-option');
    const productPrice = $('#productPrice');
    const productRegularPrice = $('#productRegularPrice');
    const productStock = $('#productStock');
    const productQuantity = $('#quantity');

    function updateProductSelection() {
        if (!productPrice.length) {
            return;
        }

        const selectedColor = colorOptions.filter(':checked');
        const additionalPrice = selectedColor.length ? parseFloat(selectedColor.data('additional-price')) || 0 : 0;
        const stock = selectedColor.length ? parseInt(selectedColor.data('stock'), 10) : parseInt(productStock.data('base-stock'), 10);
        const basePrice = parseFloat(productPrice.data('base-price')) || 0;
        const regularPrice = productRegularPrice.length ? parseFloat(productRegularPrice.data('regular-price')) || 0 : 0;
        productPrice.text('PKR ' + (basePrice + additionalPrice).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        if (productRegularPrice.length) {
            productRegularPrice.text('PKR ' + (regularPrice + additionalPrice).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
        productStock.text(stock > 0 ? stock + ' available' : 'Out of stock');
        productStock.toggleClass('text-danger', stock < 1).toggleClass('text-success', stock > 0);
        productQuantity.attr('max', Math.max(0, stock));
        if (parseInt(productQuantity.val(), 10) > stock) {
            productQuantity.val(Math.max(1, stock));
        }
        productQuantity.prop('disabled', stock < 1);

        const colorImage = selectedColor.length ? selectedColor.data('image') : '';
        if (colorImage) {
            $('#productMainImage').attr('src', colorImage);
            $('#productGalleryTrigger').data('image', colorImage).attr('data-image', colorImage);
        }
    }

    colorOptions.on('change', updateProductSelection);
    updateProductSelection();

    $('.gallery-lightbox-trigger, .gallery-thumb').on('click', function () {
        const image = $(this).data('image');
        if (image) {
            $('#lightboxImage').attr('src', image);
        }
    });

    const revealItems = $('.home-section, .heritage-section, .process-section, .quote-section, .newsletter-section, .contact-cta, .shop-intro');
    if (!reducedMotion && 'IntersectionObserver' in window) {
        revealItems.addClass('reveal-item');
        const revealObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    $(entry.target).addClass('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        revealItems.each(function () { revealObserver.observe(this); });
    }

    const backToTop = $('#backToTop');
    $(window).on('scroll', function () {
        backToTop.toggleClass('is-visible', window.scrollY > 500);
        if (!reducedMotion) {
            $('.hero-art').css('transform', 'translateY(' + Math.min(window.scrollY * 0.08, 35) + 'px)');
        }
    });
    backToTop.on('click', function () {
        window.scrollTo({ top: 0, behavior: reducedMotion ? 'auto' : 'smooth' });
    });

    $('.stats-strip strong').each(function () {
        const target = parseInt($(this).text(), 10);
        if (!Number.isNaN(target) && !reducedMotion) {
            $(this).text('0');
            $({ value: 0 }).animate({ value: target }, { duration: 1000, step: function () { $(this).text(Math.floor(this.value)); } });
        }
    });

    if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
        const cursor = $('<span class="custom-cursor" aria-hidden="true"></span>').appendTo('body');
        $(document).on('mousemove', function (event) { cursor.css({ left: event.clientX, top: event.clientY }); });
        $('a, button, input, select, textarea').on('mouseenter', function () { cursor.addClass('is-hovering'); }).on('mouseleave', function () { cursor.removeClass('is-hovering'); });
    }
});
