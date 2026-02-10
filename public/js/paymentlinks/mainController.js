/**
 * BadliCash Angular App - Payment Links Controller
 * 
 * This controller manages the payment links view with two-way binding,
 * pagination, filtering, and AJAX calls to the Laravel backend.
 */

(function() {
    'use strict';

    // Create Angular app
    var app = angular.module('badlicashApp', []);

    // Payment Links Controller
    app.controller('PaymentLinksController', ['$http', '$window', '$timeout', '$scope', PaymentLinksController]);

    function PaymentLinksController($http, $window, $timeout, $scope) {
        var vm = this;

        // Data properties
        vm.paymentLinks = [];
        vm.pagination = {
            current_page: 1,
            per_page: 10,
            total: 0,
            last_page: 1
        };
        vm.perPage = 10;
        vm.filters = {
            status: 'all',
            search: ''
        };
        vm.loading = false;
        vm.creating = false;

        // New payment link form
        vm.newLink = {
            title: '',
            description: '',
            amount: '',
            currency: 'INR',
            expires_in_hours: 24
        };

        // Toast notification
        vm.toastMessage = '';
        vm.toastType = 'success';

        // Methods
        vm.loadPaymentLinks = loadPaymentLinks;
        vm.loadPage = loadPage;
        vm.getPages = getPages;
        vm.getPaginationPages = getPaginationPages; // Alias for grid template
        vm.createPaymentLink = createPaymentLink;
        vm.copyLink = copyLink;
        vm.initModal = initModal;
        vm.applyFilters = applyFilters;
        vm.clearFilters = clearFilters;

        // Initialize
        init();

        function init() {
            loadPaymentLinks();
        }

        /**
         * Initialize modal - reset form when opening
         */
        function initModal() {
            vm.creating = false;
            vm.newLink = {
                title: '',
                description: '',
                amount: '',
                currency: 'INR',
                expires_in_hours: 24
            };
            
            // Use $timeout to ensure Angular digest cycle
            $timeout(function() {
                $scope.$apply();
            }, 0);
        }

        /**
         * Apply filters and reload data
         */
        function applyFilters() {
            vm.pagination.current_page = 1; // Reset to first page
            loadPaymentLinks();
        }

        /**
         * Clear all filters
         */
        function clearFilters() {
            vm.filters = {
                status: 'all',
                search: ''
            };
            vm.perPage = 10;
            vm.pagination.current_page = 1;
            loadPaymentLinks();
        }

        /**
         * Load payment links from API
         */
        function loadPaymentLinks() {
            vm.loading = true;

            var params = {
                page: vm.pagination.current_page,
                per_page: vm.perPage,
                status: vm.filters.status === 'all' ? '' : vm.filters.status,
                search: vm.filters.search || ''
            };

            $http.get('/merchant/payment-links/data', { params: params })
                .then(function(response) {
                    if (response.data && response.data.success) {
                        vm.paymentLinks = response.data.data || [];
                        vm.pagination = response.data.pagination || vm.pagination;
                    } else {
                        vm.paymentLinks = [];
                        showToast('Failed to load payment links', 'error');
                    }
                    vm.loading = false;
                })
                .catch(function(error) {
                    console.error('Error loading payment links:', error);
                    vm.paymentLinks = [];
                    vm.loading = false;
                    var errorMsg = 'Failed to load payment links';
                    if (error.data && error.data.message) {
                        errorMsg = error.data.message;
                    }
                    showToast(errorMsg, 'error');
                });
        }

        /**
         * Load specific page
         */
        function loadPage(page) {
            if (page < 1 || page > vm.pagination.last_page) {
                return;
            }
            vm.pagination.current_page = page;
            loadPaymentLinks();
        }

        /**
         * Get array of page numbers for pagination
         */
        function getPages() {
            return getPaginationPages();
        }

        /**
         * Get array of page numbers for pagination (used by grid template)
         */
        function getPaginationPages() {
            var pages = [];
            if (!vm.pagination || !vm.pagination.last_page) {
                return pages;
            }
            
            var start = Math.max(1, vm.pagination.current_page - 2);
            var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);

            for (var i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        }

        /**
         * Create new payment link
         */
        function createPaymentLink(event) {
            // Prevent default form submission
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            // Validate form
            if (!vm.newLink.title || !vm.newLink.amount || parseFloat(vm.newLink.amount) <= 0) {
                showToast('Please fill in all required fields', 'error');
                return false;
            }

            vm.creating = true;

            // Get CSRF token
            var csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                vm.creating = false;
                showToast('CSRF token not found', 'error');
                return false;
            }
            csrfToken = csrfToken.getAttribute('content');

            // Prepare data
            var postData = {
                title: vm.newLink.title,
                description: vm.newLink.description || '',
                amount: parseFloat(vm.newLink.amount),
                currency: vm.newLink.currency || 'INR',
                expires_in_hours: parseInt(vm.newLink.expires_in_hours) || 24
            };

            $http.post('/merchant/payment-links', postData, {
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(function(response) {
                vm.creating = false;
                
                if (response.data && response.data.success) {
                    showToast('Payment link created successfully', 'success');
                    
                    // Close modal using Bootstrap 5 API
                    var modalElement = document.getElementById('createLinkModal');
                    if (modalElement) {
                        var modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            // If no instance exists, create one and hide it
                            var newModal = new bootstrap.Modal(modalElement);
                            newModal.hide();
                        }
                    }

                    // Reset form
                    vm.newLink = {
                        title: '',
                        description: '',
                        amount: '',
                        currency: 'INR',
                        expires_in_hours: 24
                    };

                    // Reset to page 1 to show the newly created link (since links are ordered by latest)
                    vm.pagination.current_page = 1;

                    // Reload list immediately without delay
                    loadPaymentLinks();
                } else {
                    var errorMsg = 'Failed to create payment link';
                    if (response.data && response.data.message) {
                        errorMsg = response.data.message;
                    }
                    showToast(errorMsg, 'error');
                }
            })
            .catch(function(error) {
                vm.creating = false;
                console.error('Error creating payment link:', error);
                
                var errorMsg = 'Failed to create payment link';
                if (error.data) {
                    if (error.data.message) {
                        errorMsg = error.data.message;
                    } else if (error.data.errors) {
                        // Handle validation errors
                        var firstError = Object.values(error.data.errors)[0];
                        if (Array.isArray(firstError)) {
                            errorMsg = firstError[0];
                        } else {
                            errorMsg = firstError;
                        }
                    }
                }
                
                showToast(errorMsg, 'error');
            });

            return false;
        }

        /**
         * Copy payment link to clipboard
         */
        function copyLink(link) {
            if (!link || !link.link_token) {
                showToast('Invalid payment link', 'error');
                return;
            }

            var paymentUrl = $window.location.origin + '/pay/' + link.link_token;
            
            // Use modern clipboard API if available
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(paymentUrl).then(function() {
                    showToast('Payment link copied to clipboard', 'success');
                }).catch(function() {
                    fallbackCopy(paymentUrl);
                });
            } else {
                fallbackCopy(paymentUrl);
            }
        }

        /**
         * Fallback copy method for older browsers
         */
        function fallbackCopy(text) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showToast('Payment link copied to clipboard', 'success');
                } else {
                    showToast('Failed to copy link', 'error');
                }
            } catch (err) {
                showToast('Failed to copy link', 'error');
            }
            
            document.body.removeChild(textarea);
        }

        /**
         * Show toast notification
         */
        function showToast(message, type) {
            vm.toastMessage = message;
            vm.toastType = type || 'success';

            // Use $timeout to ensure Angular digest cycle
            $timeout(function() {
                var toastEl = document.getElementById('toast');
                if (toastEl) {
                    var toast = bootstrap.Toast.getInstance(toastEl);
                    if (!toast) {
                        toast = new bootstrap.Toast(toastEl, { delay: 3500 });
                    }
                    toast.show();
                }
            }, 0);
        }
    }
})();

