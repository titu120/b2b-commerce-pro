/**
 * B2B Commerce Pro JavaScript
 * Handles admin interactions, form submissions, and dynamic functionality
 */

(function($) {
    'use strict';

    // B2B Commerce Pro Admin JavaScript
    var B2BCommercePro = {
        
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initDataTables();
            this.initAjaxForms();
            this.initQuoteRequests();
            this.initBulkPricing();
        },

        bindEvents: function() {
            // User approval buttons
            $(document).on('click', '.b2b-approve-user', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var nonce = $(this).data('nonce');
                
                if (confirm('Are you sure you want to approve this user?')) {
                    B2BCommercePro.approveUser(userId, nonce);
                }
            });

            $(document).on('click', '.b2b-reject-user', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var nonce = $(this).data('nonce');
                
                if (confirm('Are you sure you want to reject this user?')) {
                    B2BCommercePro.rejectUser(userId, nonce);
                }
            });

            // Pricing rule form
            $(document).on('submit', '#b2b-pricing-form', function(e) {
                e.preventDefault();
                B2BCommercePro.savePricingRule($(this));
            });

            // Delete pricing rule
            $(document).on('click', '.b2b-delete-pricing', function(e) {
                e.preventDefault();
                var ruleId = $(this).data('rule-id');
                var nonce = (typeof b2b_ajax !== 'undefined' && b2b_ajax.pricing_nonce) ? b2b_ajax.pricing_nonce : '';
                
                if (confirm('Are you sure you want to delete this pricing rule?')) {
                    B2BCommercePro.deletePricingRule(ruleId, nonce);
                }
            });

            // Filter users
            $(document).on('change', '#role, #approval', function() {
                $('#user-filter-form').submit();
            });

            // Bulk actions
            $(document).on('change', '#bulk-action-selector', function() {
                var action = $(this).val();
                if (action) {
                    $('.bulk-action-btn').removeClass('disabled').text('Apply ' + action);
                } else {
                    $('.bulk-action-btn').addClass('disabled').text('Apply');
                }
            });

            $(document).on('click', '.bulk-action-btn', function(e) {
                e.preventDefault();
                if ($(this).hasClass('disabled')) return;
                var operation = $('#bulk-action-selector').val();
                var ids = $('.user-checkbox:checked').map(function(){ return $(this).val(); }).get();
                if (!operation || ids.length === 0) return;
                if (!confirm('Are you sure you want to ' + operation + ' ' + ids.length + ' users?')) return;
                $.ajax({
                    url: b2b_ajax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'b2b_bulk_user_action',
                        operation: operation,
                        user_ids: ids,
                        nonce: (typeof b2b_ajax !== 'undefined' ? b2b_ajax.bulk_user_nonce : '')
                    },
                    success: function(res){
                        if (res && res.success) {
                            location.reload();
                        } else {
                            alert('Bulk action failed: ' + (res && res.data ? res.data : 'Unknown error'));
                        }
                    },
                    error: function(){
                        alert('Request failed.');
                    }
                });
            });

            // Select all users
            $(document).on('change', '#select-all-users', function() {
                $('.user-checkbox').prop('checked', $(this).is(':checked'));
                B2BCommercePro.updateBulkActionButton();
            });

            $(document).on('change', '.user-checkbox', function() {
                B2BCommercePro.updateBulkActionButton();
            });
        },

        approveUser: function(userId, nonce) {
            $.ajax({
                url: b2b_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2b_approve_user',
                    user_id: userId,
                    nonce: nonce || (typeof b2b_ajax !== 'undefined' ? b2b_ajax.approve_nonce : '')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        rejectUser: function(userId, nonce) {
            $.ajax({
                url: b2b_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2b_reject_user',
                    user_id: userId,
                    nonce: nonce || (typeof b2b_ajax !== 'undefined' ? b2b_ajax.reject_nonce : '')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        savePricingRule: function(form) {
            var formData = form.serialize();
            if (form.find('input[name="b2b_pricing_nonce"]').length === 0 && typeof b2b_ajax !== 'undefined' && b2b_ajax.pricing_nonce) {
                formData += '&nonce=' + encodeURIComponent(b2b_ajax.pricing_nonce);
            }

            $.ajax({
                url: b2b_ajax.ajaxurl,
                type: 'POST',
                data: formData + '&action=b2b_save_pricing_rule',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        deletePricingRule: function(ruleId, nonce) {
            $.ajax({
                url: b2b_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'b2b_delete_pricing_rule',
                    rule_id: ruleId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        },

        updateBulkActionButton: function() {
            var checkedCount = $('.user-checkbox:checked').length;
            var action = $('#bulk-action-selector').val();
            
            if (checkedCount > 0 && action) {
                $('.bulk-action-btn').removeClass('disabled').text('Apply ' + action + ' to ' + checkedCount + ' users');
            } else {
                $('.bulk-action-btn').addClass('disabled').text('Apply');
            }
        },

        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                $(this).tooltip({
                    position: { my: 'left+5 center', at: 'right center' }
                });
            });
        },

        initDataTables: function() {
            if ($.fn.DataTable) {
                $('.b2b-admin-table').DataTable({
                    responsive: true,
                    pageLength: 25,
                    order: [[0, 'asc']],
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    }
                });
            }
        },

        initAjaxForms: function() {
            $('.b2b-ajax-form').on('submit', function(e) {
                e.preventDefault();
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var originalText = submitBtn.text();
                
                submitBtn.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            B2BCommercePro.showMessage('Success: ' + response.data, 'success');
                        } else {
                            B2BCommercePro.showMessage('Error: ' + response.data, 'error');
                        }
                    },
                    error: function() {
                        B2BCommercePro.showMessage('An error occurred. Please try again.', 'error');
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });
        },

        showMessage: function(message, type) {
            var alertClass = type === 'success' ? 'notice-success' : 'notice-error';
            var html = '<div class="notice ' + alertClass + ' is-dismissible"><p>' + message + '</p></div>';
            
            $('.b2b-admin-header').after(html);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('.notice').fadeOut();
            }, 5000);
        },

        // Utility functions
        formatCurrency: function(amount, currency) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency || 'USD'
            }).format(amount);
        },

        formatDate: function(date) {
            return new Date(date).toLocaleDateString();
        },

        // Export functionality
        exportData: function(type) {
            var data = {
                action: 'b2b_export_data',
                type: type,
                nonce: b2b_ajax.nonce
            };
            
            $.post(b2b_ajax.ajaxurl, data, function(response) {
                if (response.success) {
                    var link = document.createElement('a');
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.data);
                    link.download = 'b2b_' + type + '_' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                } else {
                    alert('Export failed: ' + response.data);
                }
            });
        },

        // Quote request functionality
        initQuoteRequests: function() {
            // Prevent multiple event bindings
            if (this.quoteRequestsInitialized) return;
            this.quoteRequestsInitialized = true;
            
            // Use event delegation for dynamically added elements
            $(document).on('click', '.b2b-quote-btn', function() {
                var form = $(this).siblings('.b2b-quote-form');
                form.slideToggle();
            });

            $(document).on('click', '.submit-quote', function() {
                var form = $(this).closest('.b2b-quote-form');
                var productId = form.siblings('.b2b-quote-btn').data('product-id');
                var quantity = form.find('input[name="quote_qty"]').val();
                var message = form.find('textarea[name="quote_message"]').val();

                if (!quantity || quantity < 1) {
                    alert('Please enter a valid quantity');
                    return;
                }

                var data = {
                    action: 'b2b_quote_request',
                    product_id: productId,
                    quantity: quantity,
                    message: message,
                    nonce: b2b_ajax.nonce
                };

                $.post(b2b_ajax.ajaxurl, data, function(response) {
                    if (response.success) {
                        alert('Quote request submitted successfully!');
                        form.slideUp();
                        form.find('input, textarea').val('');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $(document).on('click', '.cancel-quote', function() {
                $(this).closest('.b2b-quote-form').slideUp();
            });

            // Product Inquiry functionality
            $(document).on('click', '.b2b-inquiry-btn', function() {
                var form = $(this).siblings('.b2b-inquiry-form');
                form.slideToggle();
            });

            $(document).on('click', '.submit-inquiry', function() {
                var form = $(this).closest('.b2b-inquiry-form');
                var productId = form.siblings('.b2b-inquiry-btn').data('product-id');
                var email = form.find('input[name="inquiry_email"]').val();
                var message = form.find('textarea[name="inquiry_message"]').val();

                if (!email || !message) {
                    alert('Please fill in all required fields.');
                    return;
                }

                var data = {
                    action: 'b2b_product_inquiry',
                    product_id: productId,
                    email: email,
                    message: message,
                    nonce: b2b_ajax.nonce
                };

                $.post(b2b_ajax.ajaxurl, data, function(response) {
                    if (response.success) {
                        alert('Inquiry submitted successfully!');
                        form.slideUp();
                        form.find('input, textarea').val('');
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $(document).on('click', '.cancel-inquiry', function() {
                $(this).closest('.b2b-inquiry-form').slideUp();
            });
        },

        // Bulk pricing calculator functionality
        initBulkPricing: function() {
            // Prevent multiple event bindings
            if (this.bulkPricingInitialized) return;
            this.bulkPricingInitialized = true;
            
            $(document).on('click', '.calculate-bulk-price', function() {
                var calculator = $(this).closest('.b2b-bulk-calculator');
                var quantity = calculator.find('.bulk-qty-input').val();
                // Prefer product id on calculator container, fallback to theme button data attribute
                var productId = calculator.data('product-id') || calculator.closest('.product').find('.add_to_cart').data('product_id');

                if (!quantity || quantity < 1) {
                    alert('Please enter a valid quantity');
                    return;
                }

                var data = {
                    action: 'b2b_calculate_bulk_price',
                    product_id: productId,
                    quantity: quantity,
                    nonce: b2b_ajax.nonce
                };

                $.post(b2b_ajax.ajaxurl, data, function(response) {
                    if (response.success) {
                        calculator.find('.bulk-price-display').html(
                            'Unit Price: ' + response.data.unit_price + '<br>' +
                            'Total Price: ' + response.data.total_price + '<br>' +
                            'Discount: ' + response.data.discount
                        );
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Prevent multiple initializations
        if (window.B2BCommerceProInitialized) return;
        window.B2BCommerceProInitialized = true;
        
        B2BCommercePro.init();
    });

    // Make it globally available
    window.B2BCommercePro = B2BCommercePro;

})(jQuery); 