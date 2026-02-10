@extends('layouts.app-sidebar')

@section('title', 'Payment Links - ' . config('app.name'))

@section('page-title','Payment Links')

@section('content')
<div id="paymentLinksApp" ng-app="badlicashApp" ng-controller="PaymentLinksController as plc">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard','url'=>route('dashboard')],
        ['label'=>'Payment Links']
    ]" />

    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Payment Links</h2>
            <p class="text-muted">Create and manage payment links for your customers</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLinkModal" ng-click="plc.initModal()">
                <i class="bi bi-plus-circle"></i> Create Payment Link
            </button>
        </div>
    </div>

    @include('merchant.paymentlinks.filters')

    <div class="stat-card">
        <div ng-show="plc.loading" class="loader-overlay position-relative" style="min-height: 400px;">
            <div class="position-absolute top-50 start-50 translate-middle">
                <div class="spinner-violet"></div>
                <p class="mt-2 text-muted text-center">Loading payment links...</p>
            </div>
        </div>
        
        @include('merchant.paymentlinks.grid')
    </div>

    <!-- Create Payment Link Modal -->
    @include('merchant.paymentlinks.create_modal')

    <!-- Toast Notification - Fixed at top right -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; margin-top: 80px;">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" style="min-width: 350px; max-width: 450px; box-shadow: 0 8px 24px rgba(0,0,0,0.2); border: none;">
            <div class="toast-header d-flex align-items-center" 
                 style="border-bottom: none; padding: 12px 16px; font-weight: 600; background-color: #10b981; color: white;">
                <i class="bi bi-check-circle-fill me-2" style="font-size: 18px;"></i>
                <strong class="me-auto" id="toastTitle">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close" style="opacity: 0.9;"></button>
            </div>
            <div class="toast-body" 
                 style="padding: 14px 16px; font-weight: 500; font-size: 15px;">
                <!-- Content will be dynamically inserted by JavaScript -->
            </div>
        </div>
    </div>
</div>
@endsection

@include('merchant.paymentlinks.angular.main_controller')

