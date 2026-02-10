@extends('layouts.app-sidebar')

@section('title', 'Merchant Accounts - Admin - ' . config('app.name'))
@section('page-title', 'Merchants Management')

@section('content')
<div ng-app="badlicashApp" ng-controller="AdminMerchantAccountsController as amac">
    <x-breadcrumbs :items="[
        ['label'=>'Home','url'=>route('admin.dashboard')],
        ['label'=>'Merchants']
    ]" />

    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Merchants Management</h2>
            <p class="text-muted">List of Merchants</p>
        </div>
    </div>

    <!-- Status Filter Buttons -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-secondary': amac.filters.approval_status !== 'all', 'btn-success': amac.filters.approval_status === 'all'}"
                        ng-click="amac.setApprovalStatus('all')">All</button>
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-secondary': amac.filters.approval_status !== 'approved', 'btn-success': amac.filters.approval_status === 'approved'}"
                        ng-click="amac.setApprovalStatus('approved')">Approved</button>
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-secondary': amac.filters.approval_status !== 'test_approved', 'btn-success': amac.filters.approval_status === 'test_approved'}"
                        ng-click="amac.setApprovalStatus('test_approved')">Test Approved</button>
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-secondary': amac.filters.approval_status !== 'not_approved', 'btn-success': amac.filters.approval_status === 'not_approved'}"
                        ng-click="amac.setApprovalStatus('not_approved')">Not Approved</button>
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-secondary': amac.filters.approval_status !== 'rejected', 'btn-success': amac.filters.approval_status === 'rejected'}"
                        ng-click="amac.setApprovalStatus('rejected')">Rejected</button>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-primary': amac.filters.merchant_type === 'merchant', 'btn-outline-primary': amac.filters.merchant_type !== 'merchant'}"
                        ng-click="amac.setMerchantType('merchant')">Merchants</button>
                <button type="button" class="btn btn-sm" 
                        ng-class="{'btn-primary': amac.filters.merchant_type === 'vendor_merchant', 'btn-outline-primary': amac.filters.merchant_type !== 'vendor_merchant'}"
                        ng-click="amac.setMerchantType('vendor_merchant')">Vendor Merchants</button>
            </div>
        </div>
    </div>

    <!-- Action Buttons and Controls -->
    <div class="stat-card mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <label class="form-label me-2">Show</label>
                <select class="form-select form-select-sm d-inline-block" style="width: auto;" ng-model="amac.pagination.per_page" ng-change="amac.loadMerchants()">
                    <option value="5">5 entries</option>
                    <option value="10">10 entries</option>
                    <option value="25">25 entries</option>
                    <option value="50">50 entries</option>
                </select>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-secondary" ng-click="amac.clearFilters()">
                    <i class="bi bi-funnel"></i> Clear Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary" ng-click="amac.loadMerchants()">
                    <i class="bi bi-arrow-clockwise"></i> Reload
                </button>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-eye"></i> Columns
                    </button>
                    <ul class="dropdown-menu">
                        <li ng-repeat="(key, col) in amac.visibleColumns">
                            <a class="dropdown-item" href="#" ng-click="amac.toggleColumn(key)">
                                <i class="bi" ng-class="col ? 'bi-check-square' : 'bi-square'"></i> @{{ col.label }}
                            </a>
                        </li>
                    </ul>
                </div>
                <button class="btn btn-sm btn-outline-secondary" ng-click="amac.resetView()">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </button>
                <button class="btn btn-sm btn-primary" ng-click="amac.openNewModal()">
                    <i class="bi bi-plus-lg"></i> + New
                </button>
                <button class="btn btn-sm btn-outline-primary" ng-click="amac.duplicateSelected()" ng-disabled="!amac.selectedMerchant">
                    <i class="bi bi-files"></i> Duplicate Merchant
                </button>
            </div>
        </div>
    </div>

    <!-- Main Table -->
    <div class="stat-card">
        <div ng-show="amac.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading merchants...</p>
            </div>
        </div>

        <div ng-hide="amac.loading">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" ng-model="amac.selectAll" ng-change="amac.toggleSelectAll()">
                            </th>
                            <th ng-show="amac.visibleColumns.id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant ID.</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="amac.sortBy('id')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_id" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.merchant_unique_id.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Unique ID</span>
                                </div>
                            </th>
                            <th ng-show="amac.visibleColumns.name.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Name</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="amac.sortBy('name')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_name" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.email.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Email</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="amac.sortBy('email')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_email" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.phone.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Phone</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="amac.sortBy('phone')"></i>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_phone" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.status.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Approval Status</span>
                                    <i class="bi bi-arrow-up-down" style="cursor: pointer;" ng-click="amac.sortBy('approval_status')"></i>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="amac.filters.filter_status" ng-change="amac.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="approved">Approved</option>
                                    <option value="test_approved">Test Approved</option>
                                    <option value="not_approved">Not Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </th>
                            <th>
                                <div class="d-flex align-items-center gap-2">
                                    <span>Account Status</span>
                                </div>
                                <select class="form-select form-select-sm mt-1">
                                    <option value="all">All</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </th>
                            <th ng-show="amac.visibleColumns.partner.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Partner Names</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_partner" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.organization.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Organization Name</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_organization" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.category.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Merchant Category</span>
                                </div>
                                <select class="form-select form-select-sm mt-1" ng-model="amac.filters.filter_category" ng-change="amac.applyFilters()">
                                    <option value="all">All</option>
                                    <option value="B2B">B2B</option>
                                    <option value="Education">Education</option>
                                    <option value="Insurance">Insurance</option>
                                    <option value="Utilities">Utilities</option>
                                    <option value="E-commerce">E-commerce</option>
                                    <option value="Travel & Hospitality">Travel & Hospitality</option>
                                    <option value="Telecom">Telecom</option>
                                    <option value="High Risk">High Risk</option>
                                    <option value="Grocery">Grocery</option>
                                    <option value="NBFC">NBFC</option>
                                    <option value="Government">Government</option>
                                    <option value="Others">Others</option>
                                    <option value="Forex">Forex</option>
                                    <option value="Real Estate">Real Estate</option>
                                    <option value="Housing Society">Housing Society</option>
                                    <option value="Housing Board">Housing Board</option>
                                    <option value="Govt E-Tendering">Govt E-Tendering</option>
                                </select>
                            </th>
                            <th ng-show="amac.visibleColumns.acquirer.visible">
                                <span>Acquirer</span>
                            </th>
                            <th ng-show="amac.visibleColumns.registration_date.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Registration Date</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="MM/DD/YYYY" ng-model="amac.filters.filter_registration_date" ng-change="amac.applyFilters()">
                            </th>
                            <th ng-show="amac.visibleColumns.challan_urn.visible">
                                <div class="d-flex align-items-center gap-2">
                                    <span>Challan URN</span>
                                </div>
                                <input type="text" class="form-control form-control-sm mt-1" placeholder="Filter..." ng-model="amac.filters.filter_challan_urn" ng-change="amac.applyFilters()">
                            </th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr ng-if="amac.merchants.length === 0">
                            <td colspan="14" class="text-center text-danger py-4">No matching records found</td>
                        </tr>
                        <tr ng-repeat="merchant in amac.merchants track by $index" 
                            ng-click="amac.selectMerchant(merchant)" 
                            ng-class="{'table-active': amac.selectedMerchant && amac.selectedMerchant.id === merchant.id}">
                            <td>
                                <input type="checkbox" ng-model="merchant.selected" ng-click="$event.stopPropagation()">
                            </td>
                            <td ng-show="amac.visibleColumns.id.visible">@{{ merchant.id }}</td>
                            <td ng-show="amac.visibleColumns.merchant_unique_id.visible">
                                <strong class="text-primary">@{{ merchant.merchant_unique_id || '-' }}</strong>
                            </td>
                            <td ng-show="amac.visibleColumns.name.visible">@{{ merchant.name }}</td>
                            <td ng-show="amac.visibleColumns.email.visible">@{{ merchant.email }}</td>
                            <td ng-show="amac.visibleColumns.phone.visible">@{{ merchant.phone || '-' }}</td>
                            <td ng-show="amac.visibleColumns.status.visible">
                                <!-- Approval Status Dropdown -->
                                <select class="form-select form-select-sm" 
                                        ng-model="merchant.approval_status" 
                                        ng-change="amac.updateApprovalStatus(merchant)"
                                        ng-click="$event.stopPropagation()">
                                    <option value="approved">Approved</option>
                                    <option value="test_approved">Test Approved</option>
                                    <option value="not_approved">Not Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </td>
                            <td>
                                <!-- Account Status Dropdown -->
                                <select class="form-select form-select-sm" 
                                        ng-model="merchant.status" 
                                        ng-change="amac.updateAccountStatus(merchant)"
                                        ng-click="$event.stopPropagation()">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </td>
                            <td ng-show="amac.visibleColumns.partner.visible">@{{ merchant.partner_name || '-' }}</td>
                            <td ng-show="amac.visibleColumns.organization.visible">@{{ merchant.organization_name || merchant.company_name || '-' }}</td>
                            <td ng-show="amac.visibleColumns.category.visible">@{{ merchant.merchant_category || '-' }}</td>
                            <td ng-show="amac.visibleColumns.acquirer.visible">@{{ (merchant.acquirer_account && merchant.acquirer_account.acquirer_name) ? merchant.acquirer_account.acquirer_name : '—' }}</td>
                            <td ng-show="amac.visibleColumns.registration_date.visible">@{{ merchant.registration_date | date:'MM/dd/yyyy' }}</td>
                            <td ng-show="amac.visibleColumns.challan_urn.visible">@{{ merchant.challan_urn || '-' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary me-1" ng-click="amac.openEditModal(merchant); $event.stopPropagation();" title="Edit merchant">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" ng-click="amac.viewMerchant(merchant); $event.stopPropagation();" title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    Showing @{{ (amac.pagination.current_page - 1) * amac.pagination.per_page + 1 }} to @{{ Math.min(amac.pagination.current_page * amac.pagination.per_page, amac.pagination.total) }} of @{{ amac.pagination.total }} entries
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="amac.changePage(amac.pagination.current_page - 1)" 
                            ng-disabled="amac.pagination.current_page === 1">
                        Previous
                    </button>
                    <span class="mx-2">...</span>
                    <button class="btn btn-sm btn-outline-secondary" 
                            ng-click="amac.changePage(amac.pagination.current_page + 1)" 
                            ng-disabled="amac.pagination.current_page === amac.pagination.last_page">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- New Merchant Modal -->
    @include('admin.merchants.partials.new-merchant-modal')

    <!-- View Merchant Details Modal -->
    <div class="modal fade" id="viewMerchantModal" tabindex="-1" aria-labelledby="viewMerchantModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewMerchantModalLabel">
                        <i class="bi bi-eye"></i> Merchant Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row" ng-if="amac.selectedMerchant">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;"><strong>Merchant ID:</strong></td>
                                            <td>@{{ amac.selectedMerchant.id }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Name:</strong></td>
                                            <td>@{{ amac.selectedMerchant.name }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Legal Name:</strong></td>
                                            <td>@{{ amac.selectedMerchant.legal_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Email:</strong></td>
                                            <td>@{{ amac.selectedMerchant.email }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Phone:</strong></td>
                                            <td>@{{ amac.selectedMerchant.phone || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Organization:</strong></td>
                                            <td>@{{ amac.selectedMerchant.organization_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Category:</strong></td>
                                            <td>@{{ amac.selectedMerchant.merchant_category || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Website:</strong></td>
                                            <td>@{{ amac.selectedMerchant.website_link || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Status & Financial Information -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-check-circle"></i> Status & Financial</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;"><strong>Approval Status:</strong></td>
                                            <td>
                                                <span class="badge" ng-class="{
                                                    'bg-success': amac.selectedMerchant.approval_status === 'approved',
                                                    'bg-info': amac.selectedMerchant.approval_status === 'test_approved',
                                                    'bg-warning': amac.selectedMerchant.approval_status === 'not_approved',
                                                    'bg-danger': amac.selectedMerchant.approval_status === 'rejected'
                                                }">
                                                    <span ng-bind="amac.formatStatus(amac.selectedMerchant.approval_status)"></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Account Status:</strong></td>
                                            <td>
                                                <span class="badge" ng-class="{
                                                    'bg-success': amac.selectedMerchant.status === 'active',
                                                    'bg-secondary': amac.selectedMerchant.status === 'inactive'
                                                }">
                                                    <span ng-bind="(amac.selectedMerchant.status || 'inactive') | uppercase"></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Test Mode:</strong></td>
                                            <td>
                                                <span class="badge" ng-class="amac.selectedMerchant.test_mode ? 'bg-warning' : 'bg-success'">
                                                    @{{ amac.selectedMerchant.test_mode ? 'YES' : 'NO' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Fee Percentage:</strong></td>
                                            <td>@{{ amac.selectedMerchant.fee_percentage || 0 }}%</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Fee Flat:</strong></td>
                                            <td>₹@{{ amac.selectedMerchant.fee_flat || 0 }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Settlement Cycle (Domestic):</strong></td>
                                            <td>
                                                <span class="badge bg-info">T+@{{ amac.selectedMerchant.settlement_cycle_domestic || 1 }}</span>
                                                <small class="text-muted ms-2">(@{{ (amac.selectedMerchant.settlement_cycle_domestic || 1) }} day(s) after transaction)</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Settlement Cycle (International):</strong></td>
                                            <td>
                                                <span class="badge bg-info">T+@{{ amac.selectedMerchant.settlement_cycle_international || 7 }}</span>
                                                <small class="text-muted ms-2">(@{{ (amac.selectedMerchant.settlement_cycle_international || 7) }} day(s) after transaction)</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Registration Date:</strong></td>
                                            <td>@{{ amac.selectedMerchant.registration_date || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Created At:</strong></td>
                                            <td>@{{ amac.selectedMerchant.created_at || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Business Details -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-building"></i> Business Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;"><strong>PAN Number:</strong></td>
                                            <td>@{{ amac.selectedMerchant.merchant_pan_number || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>GSTIN:</strong></td>
                                            <td>@{{ amac.selectedMerchant.gst_identification_no || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>TAN No:</strong></td>
                                            <td>@{{ amac.selectedMerchant.tan_no || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Address:</strong></td>
                                            <td>@{{ amac.selectedMerchant.address_line_1 || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>City:</strong></td>
                                            <td>@{{ amac.selectedMerchant.business_city || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>State:</strong></td>
                                            <td>@{{ amac.selectedMerchant.business_state || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Postal Code:</strong></td>
                                            <td>@{{ amac.selectedMerchant.business_postal_code || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Bank Details -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-bank"></i> Bank Details</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td class="text-muted" style="width: 40%;"><strong>Account Holder:</strong></td>
                                            <td>@{{ amac.selectedMerchant.bank_account_holder_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Account Number:</strong></td>
                                            <td>@{{ amac.selectedMerchant.bank_account_number || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Bank Name:</strong></td>
                                            <td>@{{ amac.selectedMerchant.bank_name || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>IFSC Code:</strong></td>
                                            <td>@{{ amac.selectedMerchant.bank_ifsc_code || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Branch:</strong></td>
                                            <td>@{{ amac.selectedMerchant.bank_branch || '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Account Type:</strong></td>
                                            <td>@{{ amac.selectedMerchant.account_type || '-' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" ng-click="amac.openSettlementSettingsModal(amac.selectedMerchant)">
                        <i class="bi bi-gear"></i> Edit Settlement Settings
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Settlement Settings Modal -->
    <div class="modal fade" id="settlementSettingsModal" tabindex="-1" aria-labelledby="settlementSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="settlementSettingsModalLabel">
                        <i class="bi bi-clock-history"></i> Settlement Settings
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" ng-if="amac.settlementSettingsMerchant">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Configure settlement cycles for <strong>@{{ amac.settlementSettingsMerchant.name }}</strong>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                <strong>Settlement Cycle - Domestic (T+X)</strong>
                                <small class="text-muted d-block">Number of days after transaction for domestic settlements</small>
                            </label>
                            <select class="form-select" ng-model="amac.settlementSettings.settlement_cycle_domestic">
                                <option value="1">T+1 (1 day)</option>
                                <option value="2">T+2 (2 days)</option>
                                <option value="3">T+3 (3 days)</option>
                                <option value="4">T+4 (4 days)</option>
                                <option value="5">T+5 (5 days)</option>
                                <option value="6">T+6 (6 days)</option>
                                <option value="7">T+7 (7 days)</option>
                            </select>
                            <small class="text-muted">Transactions will be settled @{{ amac.settlementSettings.settlement_cycle_domestic || 1 }} day(s) after completion</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <strong>Settlement Cycle - International (T+X)</strong>
                                <small class="text-muted d-block">Number of days after transaction for international settlements</small>
                            </label>
                            <select class="form-select" ng-model="amac.settlementSettings.settlement_cycle_international">
                                <option value="1">T+1 (1 day)</option>
                                <option value="2">T+2 (2 days)</option>
                                <option value="3">T+3 (3 days)</option>
                                <option value="4">T+4 (4 days)</option>
                                <option value="5">T+5 (5 days)</option>
                                <option value="6">T+6 (6 days)</option>
                                <option value="7">T+7 (7 days)</option>
                            </select>
                            <small class="text-muted">International transactions will be settled @{{ amac.settlementSettings.settlement_cycle_international || 7 }} day(s) after completion</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <strong>Fee Percentage (%)</strong>
                            </label>
                            <input type="number" class="form-control" ng-model="amac.settlementSettings.fee_percentage" step="0.01" min="0" max="100">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                <strong>Flat Fee (INR)</strong>
                            </label>
                            <input type="number" class="form-control" ng-model="amac.settlementSettings.fee_flat" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" ng-click="amac.saveSettlementSettings()" ng-disabled="amac.savingSettlementSettings">
                        <span ng-if="amac.savingSettlementSettings" class="spinner-border spinner-border-sm me-1"></span>
                        <i class="bi bi-check-lg" ng-if="!amac.savingSettlementSettings"></i> Save Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('admin.merchants.angular.accounts_controller')

