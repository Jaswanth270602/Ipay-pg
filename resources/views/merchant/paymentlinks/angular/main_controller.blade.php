@push('scripts')
<script>
console.log('=== Payment Links Controller Script Loaded ===');
(function() {
    'use strict';
    console.log('Inside IIFE');
    function registerController() {
        console.log('registerController function called');
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            console.log('Trying to get badlicashApp module...');
            var app = angular.module('badlicashApp');
            console.log('Got badlicashApp module, registering controller...');
            app.controller('PaymentLinksController', ['$http', '$window', '$timeout', '$scope', function($http, $window, $timeout, $scope) {
                console.log('PaymentLinksController initialized');
                var vm = this;
                
                // Initialize all data
                vm.paymentLinks = [];
                vm.pagination = { 
                    current_page: 1, 
                    per_page: 10, 
                    total: 0, 
                    last_page: 1, 
                    from: 0, 
                    to: 0 
                };
                vm.perPage = 10;
                vm.filters = { status: 'all', search: '' };
                vm.loading = false;
                vm.creating = false;
                vm.newLink = { 
                    title: '', 
                    description: '', 
                    amount: '', 
                    currency: 'INR', 
                    allow_partial_payment: false,
                    expires_in_hours: 24
                };
                vm.toastMessage = '';
                vm.toastType = 'success';

                // Initialize modal - RESET STATE
                vm.initModal = function() {
                    // FORCE reset creating flag immediately - use explicit false
                    vm.creating = false;
                    console.log('initModal: Reset creating to false');
                    vm.newLink = { 
                        title: '', 
                        description: '', 
                        amount: '', 
                        currency: 'INR', 
                        allow_partial_payment: false,
                        expires_in_hours: 24
                    };
                    
                    // Reset DOM inputs immediately
                    var titleInput = document.getElementById('linkTitle');
                    var amountInput = document.getElementById('linkAmount');
                    var descriptionInput = document.getElementById('linkDescription');
                    var expiresInput = document.getElementById('linkExpires');
                    var currencySelect = document.getElementById('linkCurrency');
                    var partialPaymentCheckbox = document.getElementById('allowPartialPayment');
                    
                    if (titleInput) titleInput.value = '';
                    if (amountInput) amountInput.value = '';
                    if (descriptionInput) descriptionInput.value = '';
                    if (expiresInput) expiresInput.value = '24';
                    if (currencySelect) currencySelect.value = 'INR';
                    if (partialPaymentCheckbox) partialPaymentCheckbox.checked = false;
                    
                    // Force scope update
                    $timeout(function() {
                        $scope.$apply();
                    }, 0);
                };

                // Load payment links
                vm.loadPaymentLinks = function() {
                    vm.loading = true;
                    
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.perPage,
                        status: vm.filters.status === 'all' ? '' : vm.filters.status,
                        search: vm.filters.search || ''
                    };
                    
                    $http.get('/merchant/payment-links/data', { 
                        params: params,
                        timeout: 30000
                    }).then(function(response) {
                        if (response && response.data && response.data.success) {
                            vm.paymentLinks = response.data.data || [];
                            if (response.data.pagination) {
                                vm.pagination = {
                                    current_page: response.data.pagination.current_page || 1,
                                    last_page: response.data.pagination.last_page || 1,
                                    total: response.data.pagination.total || 0,
                                    from: response.data.pagination.from || 0,
                                    to: response.data.pagination.to || 0,
                                    per_page: response.data.pagination.per_page || 10
                                };
                            }
                        } else {
                            vm.paymentLinks = [];
                            if (response && response.data && response.data.message) {
                                vm.showToast(response.data.message, 'error');
                            }
                        }
                        vm.loading = false;
                    }, function(error) {
                        console.error('Error loading payment links:', error);
                        vm.loading = false;
                        vm.paymentLinks = [];
                        
                        var errorMsg = 'Failed to load payment links';
                        if (error && error.data && error.data.message) {
                            errorMsg = error.data.message;
                        } else if (error && error.status === -1) {
                            errorMsg = 'Request timeout. Please refresh the page.';
                        }
                        vm.showToast(errorMsg, 'error');
                    });
                };

                // Apply filters
                var filterTimeout;
                vm.applyFilters = function() {
                    if (filterTimeout) {
                        $timeout.cancel(filterTimeout);
                    }
                    filterTimeout = $timeout(function() {
                        vm.pagination.current_page = 1;
                        vm.loadPaymentLinks();
                    }, 300);
                };

                // Clear filters
                vm.clearFilters = function() {
                    vm.filters = { status: 'all', search: '' };
                    vm.pagination.current_page = 1;
                    vm.applyFilters();
                };

                // Change page
                vm.loadPage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadPaymentLinks();
                    }
                };

                // Get pagination pages
                vm.getPaginationPages = function() {
                    var pages = [];
                    var start = Math.max(1, vm.pagination.current_page - 2);
                    var end = Math.min(vm.pagination.last_page, vm.pagination.current_page + 2);
                    for (var i = start; i <= end; i++) {
                        pages.push(i);
                    }
                    return pages;
                };

                // Create payment link
                vm.createPaymentLink = function(event) {
                    console.log('=== createPaymentLink CALLED ===', vm.newLink);
                    console.log('Current creating flag:', vm.creating);
                    console.log('Event:', event);
                    
                    // Ensure we have an event
                    if (!event) {
                        event = window.event || {};
                    }
                    
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    // PREVENT DUPLICATE - check immediately
                    if (vm.creating === true) {
                        console.log('Already creating, preventing duplicate');
                        return;
                    }
                    
                    console.log('Starting payment link creation...');
                    
                    // READ FORM VALUES DIRECTLY (always read to ensure fresh data)
                    vm.newLink.title = document.getElementById('linkTitle').value || vm.newLink.title;
                    vm.newLink.amount = document.getElementById('linkAmount').value || vm.newLink.amount;
                    vm.newLink.description = document.getElementById('linkDescription').value || vm.newLink.description;
                    vm.newLink.expires_in_hours = document.getElementById('linkExpires').value || vm.newLink.expires_in_hours;
                    
                    // Get selected value from dropdown
                    var currencySelect = document.getElementById('linkCurrency');
                    vm.newLink.currency = currencySelect.options[currencySelect.selectedIndex].value;
                    
                    // Get partial payment checkbox value
                    var partialPaymentCheckbox = document.getElementById('allowPartialPayment');
                    vm.newLink.allow_partial_payment = partialPaymentCheckbox ? partialPaymentCheckbox.checked : false;
                    
                    console.log('After reading form values:', vm.newLink);
                    
                    // Validate
                    if (!vm.newLink.title || !String(vm.newLink.title).trim()) {
                        vm.showToast('Please enter a title', 'error');
                        return;
                    }

                    if (!vm.newLink.amount) {
                        vm.showToast('Please enter an amount', 'error');
                        return;
                    }

                    var amount = parseFloat(vm.newLink.amount);
                    if (isNaN(amount) || amount <= 0) {
                        vm.showToast('Please enter a valid amount greater than 0', 'error');
                        return;
                    }

                    // Get CSRF
                    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfMeta) {
                        vm.showToast('CSRF token not found. Please refresh the page.', 'error');
                        return;
                    }
                    var csrfToken = csrfMeta.getAttribute('content');

                    // SET CREATING FLAG - then apply scope immediately
                    vm.creating = true;
                    console.log('Setting creating flag to true');
                    // Force immediate UI update - use $timeout to ensure Angular processes it
                    $timeout(function() {
                        $scope.$apply();
                    }, 0);

                    // Payload
                    var payload = {
                        title: String(vm.newLink.title).trim(),
                        description: vm.newLink.description ? String(vm.newLink.description).trim() : '',
                        amount: amount,
                        currency: vm.newLink.currency || 'INR',
                        allow_partial_payment: vm.newLink.allow_partial_payment || false,
                        expires_in_hours: parseInt(vm.newLink.expires_in_hours) || 24,
                        payment_methods: ['card', 'upi', 'netbanking', 'wallet']
                    };

                    // HTTP Request
                    console.log('Sending POST request with payload:', payload);
                    console.log('CSRF Token:', csrfToken);
                    
                    $http.post('/merchant/payment-links', payload, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Content-Type': 'application/json'
                        },
                        timeout: 30000
                    }).then(function(response) {
                        console.log('=== RESPONSE RECEIVED ===', response);
                        
                        if (response && response.data && response.data.success) {
                            // Reset creating flag IMMEDIATELY before doing anything else
                            vm.creating = false;
                            console.log('Success: Reset creating to false');
                            // Force immediate UI update
                            if (!$scope.$$phase) {
                                $scope.$apply();
                            }
                            // Also manually hide creating text immediately
                            setTimeout(function() {
                                var btn = document.getElementById('createLinkButton');
                                if (btn) {
                                    var spans = btn.getElementsByTagName('span');
                                    for (var i = 0; i < spans.length; i++) {
                                        if (spans[i].textContent && spans[i].textContent.includes('Creating')) {
                                            spans[i].style.display = 'none';
                                        }
                                    }
                                }
                            }, 0);
                            
                            vm.showToast('Payment link created successfully!', 'success');
                            
                            // Close modal after a brief delay to ensure UI updates
                            setTimeout(function() {
                                var modalEl = document.getElementById('createLinkModal');
                                if (modalEl) {
                                    var bsModal = bootstrap.Modal.getInstance(modalEl);
                                    if (bsModal) {
                                        bsModal.hide();
                                    } else {
                                        var newModal = new bootstrap.Modal(modalEl);
                                        newModal.hide();
                                    }
                                }
                            }, 100);
                            
                            // Reset form
                            vm.initModal();
                            
                            // Reset to page 1 and reload
                            vm.pagination.current_page = 1;
                            vm.loadPaymentLinks();
                        } else {
                            var msg = response && response.data && response.data.message 
                                ? response.data.message 
                                : 'Failed to create payment link';
                            vm.showToast(msg, 'error');
                        }
                        
                        // ALWAYS reset creating flag after response - do it IMMEDIATELY
                        vm.creating = false;
                        console.log('Reset creating flag to false after response');
                        // Force immediate scope update
                        if (!$scope.$$phase) {
                            $scope.$apply();
                        }
                    }, function(error) {
                        console.error('=== ERROR CREATING PAYMENT LINK ===', error);
                        vm.creating = false;
                        
                        var errorMsg = 'Failed to create payment link';
                        if (error && error.data) {
                            if (error.data.message) {
                                errorMsg = error.data.message;
                            } else if (error.data.errors) {
                                var errors = error.data.errors;
                                var firstKey = Object.keys(errors)[0];
                                if (firstKey && errors[firstKey]) {
                                    errorMsg = Array.isArray(errors[firstKey]) 
                                        ? errors[firstKey][0] 
                                        : String(errors[firstKey]);
                                }
                            }
                        } else if (error && error.status === -1) {
                            errorMsg = 'Request timeout. Please try again.';
                        }
                        
                        vm.showToast(errorMsg, 'error');
                        
                        $timeout(function() {
                            $scope.$apply();
                        }, 0);
                    });
                };

                // Copy link
                vm.copyLink = function(link) {
                    var url = $window.location.origin + '/pay/' + link.link_token;
                    var textarea = document.createElement('textarea');
                    textarea.value = url;
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();
                    
                    try {
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        vm.showToast('Payment link copied to clipboard!', 'success');
                    } catch(e) {
                        document.body.removeChild(textarea);
                        vm.showToast('Failed to copy. Please copy manually.', 'error');
                    }
                };

                // Show toast
                vm.showToast = function(msg, type) {
                    vm.toastMessage = msg || '';
                    vm.toastType = type || 'success';
                    
                    // Force scope update first
                    if (!$scope.$$phase) {
                        $scope.$apply();
                    }
                    
                    $timeout(function() {
                        var toastElement = document.getElementById('toast');
                        if (toastElement && vm.toastMessage) {
                            // Hide any existing toast first to prevent duplication
                            var existingToast = bootstrap.Toast.getInstance(toastElement);
                            if (existingToast) {
                                existingToast.hide();
                            }
                            
                            // Update toast header background color and title
                            var toastHeader = toastElement.querySelector('.toast-header');
                            var toastTitle = document.getElementById('toastTitle');
                            var toastIcon = toastHeader ? toastHeader.querySelector('i') : null;
                            
                            if (toastHeader) {
                                if (vm.toastType === 'success') {
                                    toastHeader.style.backgroundColor = '#10b981';
                                    toastHeader.style.color = 'white';
                                    if (toastTitle) toastTitle.textContent = 'Success';
                                    if (toastIcon) {
                                        toastIcon.className = 'bi bi-check-circle-fill me-2';
                                    }
                                } else {
                                    toastHeader.style.backgroundColor = '#ef4444';
                                    toastHeader.style.color = 'white';
                                    if (toastTitle) toastTitle.textContent = 'Error';
                                    if (toastIcon) {
                                        toastIcon.className = 'bi bi-x-circle-fill me-2';
                                    }
                                }
                            }
                            
                            // Update toast body - completely replace content
                            var toastBody = toastElement.querySelector('.toast-body');
                            
                            if (toastBody) {
                                // Completely clear and rebuild
                                toastBody.innerHTML = '';
                                
                                // Create and add icon
                                var icon = document.createElement('i');
                                icon.className = vm.toastType === 'success' ? 'bi bi-check-circle me-2' : 'bi bi-x-circle me-2';
                                toastBody.appendChild(icon);
                                
                                // Add message text (only once)
                                var messageSpan = document.createElement('span');
                                messageSpan.textContent = vm.toastMessage;
                                toastBody.appendChild(messageSpan);
                                
                                // Update colors
                                if (vm.toastType === 'success') {
                                    toastBody.style.backgroundColor = '#d1fae5';
                                    toastBody.style.color = '#065f46';
                                } else {
                                    toastBody.style.backgroundColor = '#fee2e2';
                                    toastBody.style.color = '#991b1b';
                                }
                            }
                            
                            // Create new toast instance
                            var toastInstance = bootstrap.Toast.getInstance(toastElement);
                            if (!toastInstance) {
                                toastInstance = new bootstrap.Toast(toastElement, {
                                    autohide: true,
                                    delay: 4000
                                });
                            }
                            
                            // Show toast
                            toastInstance.show();
                        }
                    }, 100);
                };

                // Setup modal listeners - FORCE RESET on open
                $timeout(function() {
                    var modalEl = document.getElementById('createLinkModal');
                    if (modalEl) {
                        modalEl.addEventListener('show.bs.modal', function() {
                            // FORCE reset creating flag when modal opens
                            vm.creating = false;
                            console.log('Modal show: Reset creating to false');
                            vm.initModal();
                            // Force scope update
                            $timeout(function() {
                                $scope.$apply();
                                // Also manually hide any "Creating..." text
                                var btn = document.getElementById('createLinkButton');
                                if (btn) {
                                    var spans = btn.getElementsByTagName('span');
                                    for (var i = 0; i < spans.length; i++) {
                                        if (spans[i].textContent && spans[i].textContent.includes('Creating')) {
                                            spans[i].style.display = 'none';
                                        }
                                    }
                                }
                            }, 0);
                        });
                        
                        modalEl.addEventListener('shown.bs.modal', function() {
                            // Double-check creating flag is false when modal is fully shown
                            vm.creating = false;
                            console.log('Modal shown: Reset creating to false');
                            $timeout(function() {
                                $scope.$apply();
                                // Force hide creating text
                                var btn = document.getElementById('createLinkButton');
                                if (btn) {
                                    var spans = btn.getElementsByTagName('span');
                                    for (var i = 0; i < spans.length; i++) {
                                        if (spans[i].textContent && spans[i].textContent.includes('Creating')) {
                                            spans[i].style.display = 'none';
                                        }
                                    }
                                }
                            }, 0);
                        });
                        
                        modalEl.addEventListener('hidden.bs.modal', function() {
                            // FORCE reset when modal closes
                            vm.creating = false;
                            vm.initModal();
                            if (!$scope.$$phase) {
                                $scope.$apply();
                            }
                        });
                    }
                }, 500);

                // FORCE reset creating flag on page load
                vm.creating = false;
                
                // Add direct click handler as fallback
                $timeout(function() {
                    var btn = document.getElementById('createLinkButton');
                    if (btn) {
                        btn.addEventListener('click', function(e) {
                            console.log('Direct button click handler fired');
                            var scope = angular.element(document.getElementById('paymentLinksApp')).scope();
                            if (scope && scope.plc && scope.plc.createPaymentLink) {
                                scope.plc.createPaymentLink(e);
                                scope.$apply();
                            }
                        });
                    }
                }, 1000);
                
                // Initial load
                vm.loadPaymentLinks();
            }]);
        } catch(e) {
            setTimeout(registerController, 50);
        }
    }
    if (typeof angular !== 'undefined') {
        registerController();
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', registerController);
        } else {
            registerController();
        }
    }
})();
</script>
@endpush
