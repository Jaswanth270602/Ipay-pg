@extends('layouts.app-sidebar')

@section('title', 'Base Rates Management - Admin - ' . config('app.name'))
@section('page-title', 'Base Rates Configuration')

@section('content')
<div ng-app="ipayApp" ng-controller="BaseRatesController as brc">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Base Rates']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Base Rates Management</h2>
            <p class="text-muted">Configure base rates for banks, merchants, receivers, and pricers</p>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="brc.pagination.per_page" ng-change="brc.loadRates()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="brc.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="brc.loadRates()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="brc.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="brc.openNewModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
            </div>
        </div>
    </div>

    <!-- Rates Table -->
    <div class="stat-card">
        <div ng-show="brc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading base rates...</p>
            </div>
        </div>

        <div ng-hide="brc.loading">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Team Id</th>
                            <th>Team Name</th>
                            <th>Payment Mode</th>
                            <th>Bank Code</th>
                            <th>Bank Description</th>
                            <th>Sector</th>
                            <th>Merchant Email</th>
                            <th>Reseller Email</th>
                            <th>Currency</th>
                            <th>Fixed Fee</th>
                            <th>Percent Fee</th>
                            <th>Min Amount</th>
                            <th>Max Amount</th>
                            <th>Min Share</th>
                            <th>Max Share</th>
                            <th>Admin %</th>
                            <th>Reseller %</th>
                            <th>Merchant %</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.team_id" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.team_name" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="brc.filters.payment_mode" ng-change="brc.applyFilters()">
                                    <option value="all">All</option>
                                    <!-- Cards -->
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Commercial Credit Card">Commercial Credit Card</option>
                                    <option value="International Credit Card">International Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="International Debit Card">International Debit Card</option>
                                    <option value="Prepaid Card">Prepaid Card</option>
                                    <option value="Corporate Card">Corporate Card</option>
                                    <option value="EMI">EMI (on Credit Card)</option>
                                    <option value="Cardless EMI">Cardless EMI</option>
                                    <!-- UPI / QR -->
                                    <option value="UPI">UPI</option>
                                    <option value="UPI Intent">UPI Intent</option>
                                    <option value="UPI AutoPay">UPI AutoPay</option>
                                    <option value="Bharat QR">Bharat QR</option>
                                    <option value="Bharat QR(Static)">Bharat QR (Static)</option>
                                    <option value="Bharat QR(Dynamic)">Bharat QR (Dynamic)</option>
                                    <!-- Netbanking / Bank -->
                                    <option value="Netbanking">Netbanking</option>
                                    <option value="Direct Netbanking">Direct Netbanking</option>
                                    <option value="ATM Card">ATM Card</option>
                                    <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                    <!-- Wallets & Others -->
                                    <option value="Wallet">Wallet</option>
                                    <option value="Cash Card">Cash Card</option>
                                    <option value="PayLater">PayLater / BNPL</option>
                                    <option value="NACH">NACH / eMandate</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.bank_code" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.bank_description" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="brc.filters.sector" ng-change="brc.applyFilters()">
                                    <option value="all">All</option>
                                    <!-- Generic / Cross-industry -->
                                    <option value="B2B">B2B</option>
                                    <option value="B2C">B2C</option>
                                    <option value="E-commerce">E-commerce</option>
                                    <option value="Marketplaces">Marketplaces</option>
                                    <option value="Aggregator / PSP">Aggregator / PSP</option>
                                    <option value="Others">Others</option>
                                    <!-- Financial / Risk -->
                                    <option value="Financial Services">Financial Services</option>
                                    <option value="NBFC">NBFC</option>
                                    <option value="Stock Broking">Stock Broking</option>
                                    <option value="Mutual Funds / Investments">Mutual Funds / Investments</option>
                                    <option value="Forex">Forex</option>
                                    <option value="High Risk">High Risk</option>
                                    <!-- Government / Public -->
                                    <option value="Government">Government</option>
                                    <option value="Govt E-Tendering">Govt E-Tendering</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Housing Society">Housing Society</option>
                                    <option value="Housing Board">Housing Board</option>
                                    <option value="Municipal Taxes / Property Tax">Municipal Taxes / Property Tax</option>
                                    <!-- Education / Healthcare -->
                                    <option value="Education">Education</option>
                                    <option value="EdTech">EdTech</option>
                                    <option value="Healthcare / Hospitals">Healthcare / Hospitals</option>
                                    <option value="Pharmacies">Pharmacies</option>
                                    <!-- Travel / Lifestyle -->
                                    <option value="Travel &amp; Hospitality">Travel &amp; Hospitality</option>
                                    <option value="Airlines">Airlines</option>
                                    <option value="Hotels / Accommodation">Hotels / Accommodation</option>
                                    <option value="Tours &amp; Activities">Tours &amp; Activities</option>
                                    <option value="Online Travel Agency (OTA)">Online Travel Agency (OTA)</option>
                                    <!-- Retail & Services -->
                                    <option value="Grocery / Supermarket">Grocery / Supermarket</option>
                                    <option value="Food &amp; Beverages / Restaurants">Food &amp; Beverages / Restaurants</option>
                                    <option value="Retail">Retail (Apparel, Electronics, etc.)</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Logistics / Courier">Logistics / Courier</option>
                                    <option value="Auto / Fuel">Auto / Fuel</option>
                                    <!-- Digital / Tech -->
                                    <option value="Telecom">Telecom</option>
                                    <option value="IT Services / SaaS">IT Services / SaaS</option>
                                    <option value="Gaming">Gaming</option>
                                    <option value="OTT / Digital Content">OTT / Digital Content</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.merchant_email" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.reseller_email" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="brc.filters.currency" ng-change="brc.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="INR">INR</option>
                                    <option value="AED">AED</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="USD">USD</option>
                                    <option value="MUR">MUR</option>
                                    <option value="RWF">RWF</option>
                                    <option value="LKR">LKR</option>
                                    <option value="XOF">XOF</option>
                                    <option value="CDF">CDF</option>
                                    <option value="ZMW">ZMW</option>
                                </select>
                            </th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.flat_fee" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.percentage_fee" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.min_amount" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.max_amount" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.min_share" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.max_share" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.admin_share_pct" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.reseller_share_pct" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th><input type="text" class="form-control form-control-sm" ng-model="brc.filters.merchant_share_pct" ng-change="brc.applyFilters()" placeholder=""></th>
                            <th>
                                <select class="form-select form-select-sm" ng-model="brc.filters.is_active" ng-change="brc.applyFilters()">
                                    <option value="">All</option>
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="rate in brc.rates track by rate.id">
                            <td>@{{ rate.id }}</td>
                            <td>@{{ rate.team_id || '-' }}</td>
                            <td>@{{ rate.team_name || '-' }}</td>
                            <td>@{{ rate.payment_mode || '-' }}</td>
                            <td>@{{ rate.bank_code || '-' }}</td>
                            <td>@{{ rate.bank_description || '-' }}</td>
                            <td>@{{ rate.sector || '-' }}</td>
                            <td>@{{ rate.merchant_email || '-' }}</td>
                            <td>@{{ rate.reseller_email || '-' }}</td>
                            <td>@{{ rate.currency || '-' }}</td>
                            <td>@{{ rate.flat_fee }}</td>
                            <td>@{{ rate.percentage_fee }}</td>
                            <td>@{{ rate.min_amount || '-' }}</td>
                            <td>@{{ rate.max_amount || '-' }}</td>
                            <td>@{{ rate.min_share || '-' }}</td>
                            <td>@{{ rate.max_share || '-' }}</td>
                            <td>@{{ rate.admin_share_pct || '-' }}</td>
                            <td>@{{ rate.reseller_share_pct || '-' }}</td>
                            <td>@{{ rate.merchant_share_pct || '-' }}</td>
                            <td>
                                <span class="badge" ng-class="rate.is_active ? 'bg-success' : 'bg-secondary'">
                                    @{{ rate.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" ng-click="brc.editRate(rate)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" ng-click="brc.deleteRate(rate)" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr ng-if="brc.rates.length === 0 && !brc.loading">
                            <td colspan="21" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 48px;"></i>
                                <p class="mt-2">No base rates found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (brc.pagination.current_page - 1) * brc.pagination.per_page + 1 }} to @{{ Math.min(brc.pagination.current_page * brc.pagination.per_page, brc.pagination.total) }} of @{{ brc.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="brc.changePage(brc.pagination.current_page - 1)" 
                            ng-disabled="brc.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">Page @{{ brc.pagination.current_page }} of @{{ brc.pagination.last_page }}</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="brc.changePage(brc.pagination.current_page + 1)" 
                            ng-disabled="brc.pagination.current_page === brc.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- New/Edit Base Rate Modal -->
    <div class="modal fade" id="baseRateModal" tabindex="-1" aria-labelledby="baseRateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="baseRateModalLabel">
                        <i class="bi bi-percent"></i> @{{ brc.isEditing ? 'Edit' : 'New' }} Base Rate
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="baseRateForm" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Rate Type <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('rate_type')}" ng-model="brc.rateForm.rate_type" ng-change="brc.onRateTypeChange(); brc.validate('rate_type')" ng-blur="brc.validate('rate_type')" required>
                                    <option value="">Select Type</option>
                                    <option value="bank">Bank</option>
                                    <option value="merchant">Merchant</option>
                                    <option value="receiver">Receiver</option>
                                    <option value="pricer">Pricer</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('rate_type')">@{{ brc.firstError('rate_type') }}</div>
                            </div>
                            <div class="col-md-6" ng-if="brc.rateForm.rate_type">
                                <label class="form-label">Entity <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('entity_id')}" ng-model="brc.rateForm.entity_id" ng-disabled="!brc.entities.length" ng-change="brc.validate('entity_id')" ng-blur="brc.validate('entity_id')" required>
                                    <option value="">Select @{{ brc.rateForm.rate_type }}</option>
                                    <option ng-repeat="entity in brc.entities" value="@{{ entity.id }}">@{{ entity.name }}</option>
                                </select>
                                <small class="text-muted" ng-if="!brc.entities.length">Loading entities...</small>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('entity_id')">@{{ brc.firstError('entity_id') }}</div>
                            </div>
                            <div class="col-md-6" ng-if="brc.rateForm.rate_type === 'merchant'">
                                <label class="form-label">Reseller</label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('reseller_id')}" ng-model="brc.rateForm.reseller_id" ng-change="brc.validate('reseller_id')" ng-blur="brc.validate('reseller_id')">
                                    <option value="">Direct merchant (no reseller)</option>
                                    <option ng-repeat="reseller in brc.resellers" value="@{{ reseller.id }}">@{{ reseller.name }} (@{{ reseller.email }})</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('reseller_id')">@{{ brc.firstError('reseller_id') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('payment_method')}" ng-model="brc.rateForm.payment_method" ng-change="brc.validate('payment_method')" ng-blur="brc.validate('payment_method')" required>
                                    <option value="">Select Method</option>
                                    <option value="card">Card</option>
                                    <option value="upi">UPI</option>
                                    <option value="netbanking">Net Banking</option>
                                    <option value="wallet">Wallet</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('payment_method')">@{{ brc.firstError('payment_method') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('payment_mode')}" ng-model="brc.rateForm.payment_mode" ng-change="brc.validate('payment_mode')" ng-blur="brc.validate('payment_mode')">
                                    <option value="">Select Payment Mode</option>
                                    <option value="Credit Card">Credit Card</option>
                                    <option value="Commercial Credit Card">Commercial Credit Card</option>
                                    <option value="International Credit Card">International Credit Card</option>
                                    <option value="Debit Card">Debit Card</option>
                                    <option value="International Debit Card">International Debit Card</option>
                                    <option value="Prepaid Card">Prepaid Card</option>
                                    <option value="Corporate Card">Corporate Card</option>
                                    <option value="EMI">EMI (on Credit Card)</option>
                                    <option value="Cardless EMI">Cardless EMI</option>
                                    <option value="UPI">UPI</option>
                                    <option value="UPI Intent">UPI Intent</option>
                                    <option value="UPI AutoPay">UPI AutoPay</option>
                                    <option value="Bharat QR">Bharat QR</option>
                                    <option value="Bharat QR(Static)">Bharat QR (Static)</option>
                                    <option value="Bharat QR(Dynamic)">Bharat QR (Dynamic)</option>
                                    <option value="Netbanking">Netbanking</option>
                                    <option value="Direct Netbanking">Direct Netbanking</option>
                                    <option value="ATM Card">ATM Card</option>
                                    <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                    <option value="Wallet">Wallet</option>
                                    <option value="Cash Card">Cash Card</option>
                                    <option value="PayLater">PayLater / BNPL</option>
                                    <option value="NACH">NACH / eMandate</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('payment_mode')">@{{ brc.firstError('payment_mode') }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Service Type <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('service_type')}" ng-model="brc.rateForm.service_type" ng-change="brc.validate('service_type')" ng-blur="brc.validate('service_type')" required>
                                    <option value="payment">Payment</option>
                                    <option value="refund">Refund</option>
                                    <option value="chargeback">Chargeback</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('service_type')">@{{ brc.firstError('service_type') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('transaction_type')}" ng-model="brc.rateForm.transaction_type" ng-change="brc.validate('transaction_type')" ng-blur="brc.validate('transaction_type')" required>
                                    <option value="domestic">Domestic</option>
                                    <option value="international">International</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('transaction_type')">@{{ brc.firstError('transaction_type') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Team Id</label>
                                <input type="number" class="form-control" ng-model="brc.rateForm.team_id" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Team Name</label>
                                <input type="text" class="form-control" ng-model="brc.rateForm.team_name">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Bank Code</label>
                                <input type="text" class="form-control" ng-model="brc.rateForm.bank_code">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Description</label>
                                <input type="text" class="form-control" ng-model="brc.rateForm.bank_description">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Sector <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('sector')}" ng-model="brc.rateForm.sector" ng-change="brc.validate('sector')" ng-blur="brc.validate('sector')">
                                    <option value="">Select Sector</option>
                                    <option value="B2B">B2B</option>
                                    <option value="B2C">B2C</option>
                                    <option value="E-commerce">E-commerce</option>
                                    <option value="Marketplaces">Marketplaces</option>
                                    <option value="Aggregator / PSP">Aggregator / PSP</option>
                                    <option value="Financial Services">Financial Services</option>
                                    <option value="NBFC">NBFC</option>
                                    <option value="Stock Broking">Stock Broking</option>
                                    <option value="Mutual Funds / Investments">Mutual Funds / Investments</option>
                                    <option value="Forex">Forex</option>
                                    <option value="High Risk">High Risk</option>
                                    <option value="Government">Government</option>
                                    <option value="Govt E-Tendering">Govt E-Tendering</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="Housing Society">Housing Society</option>
                                    <option value="Housing Board">Housing Board</option>
                                    <option value="Municipal Taxes / Property Tax">Municipal Taxes / Property Tax</option>
                                    <option value="Education">Education</option>
                                    <option value="EdTech">EdTech</option>
                                    <option value="Healthcare / Hospitals">Healthcare / Hospitals</option>
                                    <option value="Pharmacies">Pharmacies</option>
                                    <option value="Travel &amp; Hospitality">Travel &amp; Hospitality</option>
                                    <option value="Airlines">Airlines</option>
                                    <option value="Hotels / Accommodation">Hotels / Accommodation</option>
                                    <option value="Tours &amp; Activities">Tours &amp; Activities</option>
                                    <option value="Online Travel Agency (OTA)">Online Travel Agency (OTA)</option>
                                    <option value="Grocery / Supermarket">Grocery / Supermarket</option>
                                    <option value="Food &amp; Beverages / Restaurants">Food &amp; Beverages / Restaurants</option>
                                    <option value="Retail">Retail (Apparel, Electronics, etc.)</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Logistics / Courier">Logistics / Courier</option>
                                    <option value="Auto / Fuel">Auto / Fuel</option>
                                    <option value="Telecom">Telecom</option>
                                    <option value="IT Services / SaaS">IT Services / SaaS</option>
                                    <option value="Gaming">Gaming</option>
                                    <option value="OTT / Digital Content">OTT / Digital Content</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('sector')">@{{ brc.firstError('sector') }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Currency <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('currency')}" ng-model="brc.rateForm.currency" ng-change="brc.validate('currency')" ng-blur="brc.validate('currency')">
                                    <option value="">Select Currency</option>
                                    <option value="INR">INR</option>
                                    <option value="AED">AED</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                    <option value="USD">USD</option>
                                    <option value="MUR">MUR</option>
                                    <option value="RWF">RWF</option>
                                    <option value="LKR">LKR</option>
                                    <option value="XOF">XOF</option>
                                    <option value="CDF">CDF</option>
                                    <option value="ZMW">ZMW</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('currency')">@{{ brc.firstError('currency') }}</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Percentage Fee (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('percentage_fee')}" ng-model="brc.rateForm.percentage_fee" ng-change="brc.validate('percentage_fee')" ng-blur="brc.validate('percentage_fee')" step="0.001" min="0" max="100" required>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('percentage_fee')">@{{ brc.firstError('percentage_fee') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Flat Fee (INR) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('flat_fee')}" ng-model="brc.rateForm.flat_fee" ng-change="brc.validate('flat_fee')" ng-blur="brc.validate('flat_fee')" step="0.01" min="0" required>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('flat_fee')">@{{ brc.firstError('flat_fee') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Amount</label>
                                <input type="number" class="form-control" ng-model="brc.rateForm.min_amount" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Amount</label>
                                <input type="number" class="form-control" ng-model="brc.rateForm.max_amount" step="0.01" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Min Share (%)</label>
                                <input type="number" class="form-control" ng-model="brc.rateForm.min_share" step="0.0001" min="0" max="100">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Share (%)</label>
                                <input type="number" class="form-control" ng-model="brc.rateForm.max_share" step="0.0001" min="0" max="100">
                            </div>
                            <div class="col-md-4" ng-if="brc.rateForm.rate_type === 'merchant'">
                                <label class="form-label">Admin Share (%)</label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('admin_share_pct')}" ng-model="brc.rateForm.admin_share_pct" ng-change="brc.validateShares()" ng-blur="brc.validateShares()" step="0.0001" min="0" max="100">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('admin_share_pct')">@{{ brc.firstError('admin_share_pct') }}</div>
                            </div>
                            <div class="col-md-4" ng-if="brc.rateForm.rate_type === 'merchant'">
                                <label class="form-label">Reseller Share (%)</label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('reseller_share_pct')}" ng-model="brc.rateForm.reseller_share_pct" ng-change="brc.validateShares()" ng-blur="brc.validateShares()" step="0.0001" min="0" max="100">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('reseller_share_pct')">@{{ brc.firstError('reseller_share_pct') }}</div>
                            </div>
                            <div class="col-md-4" ng-if="brc.rateForm.rate_type === 'merchant'">
                                <label class="form-label">Merchant Share (%)</label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('merchant_share_pct')}" ng-model="brc.rateForm.merchant_share_pct" ng-change="brc.validateShares()" ng-blur="brc.validateShares()" step="0.0001" min="0" max="100">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('merchant_share_pct')">@{{ brc.firstError('merchant_share_pct') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">GST Percentage (%) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" ng-class="{'is-invalid': brc.hasError('gst_percentage')}" ng-model="brc.rateForm.gst_percentage" ng-change="brc.validate('gst_percentage')" ng-blur="brc.validate('gst_percentage')" step="0.01" min="0" max="100">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('gst_percentage')">@{{ brc.firstError('gst_percentage') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" ng-class="{'is-invalid': brc.hasError('is_active')}" ng-model="brc.rateForm.is_active" ng-change="brc.validate('is_active')" ng-blur="brc.validate('is_active')">
                                    <option value="">Select Status</option>
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('is_active')">@{{ brc.firstError('is_active') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective From <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" ng-class="{'is-invalid': brc.hasError('effective_from')}" ng-model="brc.rateForm.effective_from" ng-change="brc.validate('effective_from')" ng-blur="brc.validate('effective_from')">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('effective_from')">@{{ brc.firstError('effective_from') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective To</label>
                                <input type="date" class="form-control" ng-class="{'is-invalid': brc.hasError('effective_to')}" ng-model="brc.rateForm.effective_to" ng-change="brc.validate('effective_to')" ng-blur="brc.validate('effective_to')">
                                <div class="invalid-feedback d-block" ng-if="brc.firstError('effective_to')">@{{ brc.firstError('effective_to') }}</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea class="form-control" ng-model="brc.rateForm.notes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="brc.saveRate()" ng-disabled="brc.saving">
                        <span ng-if="brc.saving" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!brc.saving"></i> @{{ brc.isEditing ? 'Update' : 'Create' }} Rate
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';
    function registerController() {
        if (typeof angular === 'undefined') {
            setTimeout(registerController, 50);
            return;
        }
        try {
            var app = angular.module('ipayApp');
            app.controller('BaseRatesController', ['$http', '$scope', function($http, $scope) {
                var vm = this;
                var csrf = document.querySelector('meta[name="csrf-token"]').content;
                vm.rates = [];
                vm.pagination = { current_page: 1, per_page: 5, total: 0, last_page: 1 };
                vm.loading = false;
                vm.saving = false;
                vm.isEditing = false;
                vm.entities = [];
                vm.resellers = [];
                vm.validationErrors = {};
                vm.touched = {};
                vm.filters = {
                    team_id: '',
                    team_name: '',
                    payment_mode: 'all',
                    bank_code: '',
                    bank_description: '',
                    sector: 'all',
                    currency: 'all',
                    flat_fee: '',
                    percentage_fee: '',
                    min_amount: '',
                    max_amount: '',
                    min_share: '',
                    max_share: '',
                    merchant_email: '',
                    reseller_email: '',
                    admin_share_pct: '',
                    reseller_share_pct: '',
                    merchant_share_pct: '',
                    is_active: ''
                };

                vm.rateForm = {
                    rate_type: '',
                    entity_type: '',
                    entity_id: null,
                    payment_method: 'card',
                    payment_mode: '',
                    service_type: 'payment',
                    transaction_type: 'domestic',
                    percentage_fee: 0,
                    flat_fee: 0,
                    team_id: null,
                    team_name: '',
                    bank_code: '',
                    bank_description: '',
                    sector: '',
                    currency: '',
                    min_amount: null,
                    max_amount: null,
                    min_share: null,
                    max_share: null,
                    reseller_id: '',
                    admin_share_pct: null,
                    reseller_share_pct: null,
                    merchant_share_pct: null,
                    gst_percentage: 18,
                    is_active: true,
                    effective_from: null,
                    effective_to: null,
                    notes: ''
                };

                vm.firstError = function(field) {
                    return vm.validationErrors[field] && vm.validationErrors[field].length ? vm.validationErrors[field][0] : '';
                };
                vm.hasError = function(field) {
                    return !!vm.firstError(field);
                };
                vm.setTouched = function(field) {
                    vm.touched[field] = true;
                };

                vm.validate = function(field) {
                    vm.setTouched(field);
                    vm.validateForm(false);
                };

                vm.validateShares = function() {
                    vm.setTouched('admin_share_pct');
                    vm.setTouched('reseller_share_pct');
                    vm.setTouched('merchant_share_pct');
                    vm.validateForm(false);
                };

                vm.validateForm = function(hard) {
                    // hard=true used before submit; soft validation otherwise.
                    var e = {};

                    function req(field, msg) {
                        if (hard) vm.setTouched(field);
                        if (!vm.touched[field] && !hard) return;
                        var v = vm.rateForm[field];
                        if (v === null || v === undefined || String(v).trim() === '') {
                            e[field] = [msg];
                        }
                    }

                    req('rate_type', 'Rate Type is required.');
                    req('entity_id', 'Entity is required.');
                    req('payment_method', 'Payment Method is required.');
                    req('payment_mode', 'Payment Mode is required.');
                    req('service_type', 'Service Type is required.');
                    req('transaction_type', 'Transaction Type is required.');
                    req('sector', 'Sector is required.');
                    req('currency', 'Currency is required.');
                    req('percentage_fee', 'Percentage Fee is required.');
                    req('flat_fee', 'Flat Fee is required.');
                    req('gst_percentage', 'GST Percentage is required.');
                    req('is_active', 'Status is required.');
                    req('effective_from', 'Effective From is required.');

                    if (vm.rateForm.payment_method === 'card' && (!vm.rateForm.payment_mode || String(vm.rateForm.payment_mode).trim() === '')) {
                        e.payment_mode = ['Payment Mode is required for Card payments.'];
                    }

                    function num(field, min, max, msg) {
                        if (!vm.touched[field] && !hard) return;
                        var v = vm.rateForm[field];
                        if (v === null || v === undefined || v === '') return;
                        var n = parseFloat(v);
                        if (isNaN(n)) {
                            e[field] = [msg || 'Must be numeric.'];
                            return;
                        }
                        if (min !== null && n < min) e[field] = ['Must be ≥ ' + min + '.'];
                        if (max !== null && n > max) e[field] = ['Must be ≤ ' + max + '.'];
                    }

                    num('percentage_fee', 0, 100, 'Percentage Fee must be numeric.');
                    num('flat_fee', 0, null, 'Flat Fee must be numeric.');
                    num('gst_percentage', 0, 100, 'GST Percentage must be between 0–100.');
                    num('min_amount', 0, null, 'Min Amount must be numeric.');
                    num('max_amount', 0, null, 'Max Amount must be numeric.');
                    num('min_share', 0, 100, 'Min Share must be between 0–100.');
                    num('max_share', 0, 100, 'Max Share must be between 0–100.');

                    if (vm.rateForm.min_amount !== null && vm.rateForm.max_amount !== null && vm.rateForm.min_amount !== '' && vm.rateForm.max_amount !== '') {
                        var a = parseFloat(vm.rateForm.min_amount);
                        var b = parseFloat(vm.rateForm.max_amount);
                        if (!isNaN(a) && !isNaN(b) && a > b) {
                            e.min_amount = ['Min Amount must be less than or equal to Max Amount.'];
                        }
                    }

                    // Shares are required and must sum to 100
                    req('admin_share_pct', 'Admin Share (%) is required.');
                    req('reseller_share_pct', 'Reseller Share (%) is required.');
                    req('merchant_share_pct', 'Merchant Share (%) is required.');
                    num('admin_share_pct', 0, 100, 'Admin Share must be between 0–100.');
                    num('reseller_share_pct', 0, 100, 'Reseller Share must be between 0–100.');
                    num('merchant_share_pct', 0, 100, 'Merchant Share must be between 0–100.');

                    var ad = parseFloat(vm.rateForm.admin_share_pct || 0);
                    var rs = parseFloat(vm.rateForm.reseller_share_pct || 0);
                    var mc = parseFloat(vm.rateForm.merchant_share_pct || 0);
                    if (!isNaN(ad) && !isNaN(rs) && !isNaN(mc)) {
                        if (Math.abs((ad + rs + mc) - 100) > 0.0001) {
                            e.admin_share_pct = ['Total share must equal 100%.'];
                            e.reseller_share_pct = ['Total share must equal 100%.'];
                            e.merchant_share_pct = ['Total share must equal 100%.'];
                        }
                    }

                    if ((!vm.rateForm.reseller_id || vm.rateForm.reseller_id === '') && (parseFloat(vm.rateForm.reseller_share_pct || 0) > 0)) {
                        e.reseller_id = ['Select a reseller or set Reseller Share to 0%.'];
                    }

                    // Effective To >= Effective From
                    if (vm.rateForm.effective_to && vm.rateForm.effective_from) {
                        var from = new Date(vm.rateForm.effective_from);
                        var to = new Date(vm.rateForm.effective_to);
                        if (!isNaN(from.getTime()) && !isNaN(to.getTime()) && to < from) {
                            e.effective_to = ['Effective To must be greater than or equal to Effective From.'];
                        }
                    }

                    vm.validationErrors = e;
                    return Object.keys(e).length === 0;
                };

                vm.loadRates = function() {
                    vm.loading = true;
                    var params = {
                        page: vm.pagination.current_page,
                        per_page: vm.pagination.per_page
                    };

                    Object.keys(vm.filters).forEach(function(key) {
                        if (vm.filters[key] !== undefined && vm.filters[key] !== null && vm.filters[key] !== '') {
                            params[key] = vm.filters[key];
                        }
                    });

                    $http.get('/admin/base-rates/data', { params: params }).then(function(response) {
                        vm.rates = response.data.data || [];
                        vm.pagination = {
                            current_page: response.data.pagination.current_page,
                            last_page: response.data.pagination.last_page,
                            total: response.data.pagination.total,
                            per_page: response.data.pagination.per_page
                        };
                        vm.loading = false;
                    }, function(error) {
                        vm.loading = false;
                        console.error('Error loading rates:', error);
                        alert('Failed to load base rates');
                    });
                };

                vm.changePage = function(page) {
                    if (page >= 1 && page <= vm.pagination.last_page) {
                        vm.pagination.current_page = page;
                        vm.loadRates();
                    }
                };

                vm.applyFilters = function() {
                    vm.pagination.current_page = 1;
                    vm.loadRates();
                };

                vm.clearFilters = function() {
                    vm.filters = {
                        team_id: '',
                        team_name: '',
                        payment_mode: 'all',
                        bank_code: '',
                        bank_description: '',
                        sector: 'all',
                        currency: 'all',
                        flat_fee: '',
                        percentage_fee: '',
                        min_amount: '',
                        max_amount: '',
                        min_share: '',
                        max_share: '',
                        merchant_email: '',
                        reseller_email: '',
                        admin_share_pct: '',
                        reseller_share_pct: '',
                        merchant_share_pct: '',
                        is_active: ''
                    };
                    vm.applyFilters();
                };

                vm.loadResellers = function() {
                    $http.get('/admin/merchant-accounts/resellers').then(function(response) {
                        vm.resellers = (response.data && response.data.data) ? response.data.data : [];
                    }, function() {
                        vm.resellers = [];
                    });
                };

                vm.resetView = function() {
                    vm.clearFilters();
                    vm.pagination.current_page = 1;
                };

                vm.onRateTypeChange = function() {
                    if (vm.rateForm.rate_type && (vm.rateForm.rate_type === 'merchant' || vm.rateForm.rate_type === 'bank')) {
                        vm.rateForm.entity_type = vm.rateForm.rate_type;
                        $http.get('/admin/base-rates/entities', { params: { type: vm.rateForm.rate_type } }).then(function(response) {
                            vm.entities = response.data.data || [];
                        });
                    } else {
                        vm.entities = [];
                        vm.rateForm.entity_id = null;
                    }

                    if (vm.rateForm.rate_type === 'merchant') {
                        vm.loadResellers();
                    } else {
                        vm.rateForm.reseller_id = '';
                        vm.rateForm.admin_share_pct = null;
                        vm.rateForm.reseller_share_pct = null;
                        vm.rateForm.merchant_share_pct = null;
                    }
                };

                vm.openNewModal = function() {
                    vm.isEditing = false;
                    vm.validationErrors = {};
                    vm.touched = {};
                    vm.rateForm = {
                        rate_type: '',
                        entity_type: '',
                        entity_id: null,
                        payment_method: 'card',
                        payment_mode: '',
                        service_type: 'payment',
                        transaction_type: 'domestic',
                        percentage_fee: 0,
                        flat_fee: 0,
                        team_id: null,
                        team_name: '',
                        bank_code: '',
                        bank_description: '',
                        sector: '',
                        currency: '',
                        min_amount: null,
                        max_amount: null,
                        min_share: null,
                        max_share: null,
                        reseller_id: '',
                        admin_share_pct: 100,
                        reseller_share_pct: 0,
                        merchant_share_pct: 0,
                        gst_percentage: 18,
                        is_active: true,
                        effective_from: null,
                        effective_to: null,
                        notes: ''
                    };
                    vm.entities = [];
                    var modal = new bootstrap.Modal(document.getElementById('baseRateModal'));
                    modal.show();
                };

                vm.editRate = function(rate) {
                    vm.isEditing = true;
                    vm.validationErrors = {};
                    vm.touched = {};
                    vm.rateForm = {
                        id: rate.id,
                        rate_type: rate.rate_type,
                        entity_type: rate.entity_type,
                        entity_id: rate.entity_id,
                        payment_method: rate.payment_method,
                         payment_mode: rate.payment_mode,
                        service_type: rate.service_type,
                        transaction_type: rate.transaction_type,
                        percentage_fee: parseFloat(rate.percentage_fee),
                        flat_fee: parseFloat(rate.flat_fee),
                        team_id: rate.team_id,
                        team_name: rate.team_name,
                        bank_code: rate.bank_code,
                        bank_description: rate.bank_description,
                        sector: rate.sector,
                        currency: rate.currency,
                        min_amount: rate.min_amount,
                        max_amount: rate.max_amount,
                        min_share: rate.min_share,
                        max_share: rate.max_share,
                        reseller_id: rate.reseller_id ? String(rate.reseller_id) : '',
                        admin_share_pct: rate.admin_share_pct != null ? parseFloat(rate.admin_share_pct) : null,
                        reseller_share_pct: rate.reseller_share_pct != null ? parseFloat(rate.reseller_share_pct) : null,
                        merchant_share_pct: rate.merchant_share_pct != null ? parseFloat(rate.merchant_share_pct) : null,
                        gst_percentage: parseFloat(rate.gst_percentage || 18),
                        is_active: rate.is_active,
                        effective_from: rate.effective_from,
                        effective_to: rate.effective_to,
                        notes: rate.notes || ''
                    };
                    vm.onRateTypeChange();
                    var modal = new bootstrap.Modal(document.getElementById('baseRateModal'));
                    modal.show();
                };

                vm.saveRate = function() {
                    if (!vm.validateForm(true)) {
                        if (typeof showToast === 'function') {
                            showToast('Validation failed', 'error');
                        } else {
                            alert('Validation failed');
                        }
                        return;
                    }
                    if (!vm.rateForm.rate_type || !vm.rateForm.payment_method || !vm.rateForm.service_type) {
                        if (typeof showToast === 'function') {
                            showToast('Please fill in all required fields', 'error');
                        } else {
                            alert('Please fill in all required fields');
                        }
                        return;
                    }

                    vm.saving = true;
                    var url = vm.isEditing ? '/admin/base-rates/' + vm.rateForm.id : '/admin/base-rates';
                    var method = vm.isEditing ? 'POST' : 'POST';

                    $http({
                        method: method,
                        url: url,
                        data: vm.rateForm,
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        vm.saving = false;
                        if (response.data.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('baseRateModal'));
                            modal.hide();
                            var successMsg = vm.isEditing ? 'Base rate updated successfully' : 'Base rate created successfully';
                            if (typeof showToast === 'function') {
                                showToast(successMsg, 'success');
                            } else {
                                alert(successMsg);
                            }
                            vm.loadRates();
                        } else {
                            var errorMsg = response.data.message || 'Failed to save base rate';
                            if (response.data.errors) {
                                var errors = Object.values(response.data.errors).flat();
                                vm.validationErrors = response.data.errors;
                                errorMsg = errors.join(', ');
                            }
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function(error) {
                        vm.saving = false;
                        var errorMsg = 'Failed to save base rate';
                        if (error.data && error.data.message) {
                            errorMsg = error.data.message;
                        } else if (error.data && error.data.errors) {
                            var errors = Object.values(error.data.errors).flat();
                            vm.validationErrors = error.data.errors;
                            errorMsg = errors.join(', ');
                        }
                        if (typeof showToast === 'function') {
                            showToast(errorMsg, 'error');
                        } else {
                            alert(errorMsg);
                        }
                    });
                };

                vm.deleteRate = function(rate) {
                    if (!confirm('Are you sure you want to delete this base rate?')) {
                        return;
                    }

                    $http.delete('/admin/base-rates/' + rate.id, {
                        headers: { 'X-CSRF-TOKEN': csrf }
                    }).then(function(response) {
                        if (response.data.success) {
                            if (typeof showToast === 'function') {
                                showToast('Base rate deleted successfully', 'success');
                            } else {
                                alert('Base rate deleted successfully');
                            }
                            vm.loadRates();
                        } else {
                            var errorMsg = 'Failed to delete base rate: ' + (response.data.message || 'Unknown error');
                            if (typeof showToast === 'function') {
                                showToast(errorMsg, 'error');
                            } else {
                                alert(errorMsg);
                            }
                        }
                    }, function(error) {
                        if (typeof showToast === 'function') {
                            showToast('Failed to delete base rate', 'error');
                        } else {
                            alert('Failed to delete base rate');
                        }
                        console.error('Error:', error);
                    });
                };

                vm.loadRates();
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

