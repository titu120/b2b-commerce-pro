jQuery(document).ready(function($) {
    'use strict';
    
    // Add tier functionality
    $(document).on('click', '.add-tier', function() {
        var role = $(this).data('role');
        var container = $('.b2b-tiers-container[data-role="' + role + '"]');
        
        var tierRow = '<div class="b2b-tier-row">' +
            '<input type="number" name="b2b_tier_min_qty[' + role + '][]" placeholder="Min Qty" min="1" style="width: 80px;">' +
            '<input type="text" name="b2b_tier_price[' + role + '][]" placeholder="Price" class="wc_input_price" style="width: 100px;">' +
            '<select name="b2b_tier_type[' + role + '][]" style="width: 100px;">' +
            '<option value="fixed">Fixed Price</option>' +
            '<option value="percentage">Percentage</option>' +
            '</select>' +
            '<button type="button" class="button remove-tier" style="margin-left: 5px;">Remove</button>' +
            '</div>';
        
        container.append(tierRow);
    });
    
    // Remove tier functionality
    $(document).on('click', '.remove-tier', function() {
        $(this).closest('.b2b-tier-row').remove();
    });
    
    // Price validation
    $(document).on('blur', '.wc_input_price', function() {
        var value = $(this).val();
        if (value && !isNaN(value)) {
            // Format to 2 decimal places
            $(this).val(parseFloat(value).toFixed(2));
        }
    });
    
    // Show/hide role sections based on product type
    function toggleB2BPricing() {
        var productType = $('#product-type').val();
        if (productType === 'simple' || productType === 'external') {
            $('.b2b-pricing-section').show();
        } else {
            $('.b2b-pricing-section').hide();
        }
    }
    
    // Initial check
    toggleB2BPricing();
    
    // Check on product type change
    $(document).on('change', '#product-type', function() {
        toggleB2BPricing();
    });
    
    // Auto-save warning for unsaved changes
    var hasChanges = false;
    
    $(document).on('change input', '.b2b-pricing-section input, .b2b-pricing-section select', function() {
        hasChanges = true;
    });
    
    // Warn before leaving page with unsaved changes
    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'You have unsaved B2B pricing changes. Are you sure you want to leave?';
        }
    });
    
    // Clear warning when form is submitted
    $('form#post').on('submit', function() {
        hasChanges = false;
    });
    
    // Initialize WooCommerce price fields
    if (typeof wc_admin_meta_boxes !== 'undefined') {
        wc_admin_meta_boxes.init_price_fields();
    }
});
