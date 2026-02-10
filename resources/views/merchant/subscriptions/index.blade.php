@extends('layouts.app-sidebar')

@section('title', 'Subscriptions - ' . config('app.name'))

@section('page-title','Subscriptions')

@section('content')
<div id="merchantSubscriptionsApp">
    <x-breadcrumbs :items="[
        ['label'=>'Dashboard','url'=>route('dashboard')],
        ['label'=>'Subscriptions']
    ]" />

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Available Plans</h5>
                    <div class="input-group" style="max-width: 260px;">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="planSearch" placeholder="Search plans...">
                    </div>
                </div>
                <div id="plansContainer">
                    <div class="text-center py-5" id="plansLoading">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted">Loading plans...</p>
                    </div>
                    <div class="list-group list-group-flush d-none" id="plansList"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">Your Subscriptions</h5>
                </div>
                <div id="subsContainer">
                    <div class="text-center py-5" id="subsLoading">
                        <div class="spinner-violet"></div>
                        <p class="mt-2 text-muted">Loading subscriptions...</p>
                    </div>
                    <div class="list-group list-group-flush d-none" id="subsList"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto" id="toastTitle">Info</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toastBody"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
    'use strict';

    var plansLoading = document.getElementById('plansLoading');
    var plansList = document.getElementById('plansList');
    var planSearch = document.getElementById('planSearch');

    var subsLoading = document.getElementById('subsLoading');
    var subsList = document.getElementById('subsList');

    function showToast(title, body, type) {
        var toastEl = document.getElementById('toast');
        var toastTitle = document.getElementById('toastTitle');
        var toastBody = document.getElementById('toastBody');
        if (toastTitle) toastTitle.textContent = title || 'Info';
        if (toastBody) toastBody.textContent = body || '';
        if (toastEl) {
            var t = bootstrap.Toast.getInstance(toastEl) || new bootstrap.Toast(toastEl, { delay: 3500 });
            t.show();
        }
    }

    function fetchPlans(query) {
        plansLoading.classList.remove('d-none');
        plansList.classList.add('d-none');
        plansList.innerHTML = '';

        var url = new URL(window.location.origin + '/merchant/plans/data');
        if (query) url.searchParams.set('search', query);

        fetch(url).then(r => r.json()).then(function(res){
            plansLoading.classList.add('d-none');
            plansList.classList.remove('d-none');
            if (!res || !res.success) {
                plansList.innerHTML = '<div class="p-3 text-muted">Failed to load plans</div>';
                return;
            }
            var data = res.data.data || [];
            if (data.length === 0) {
                plansList.innerHTML = '<div class="p-3 text-muted">No plans found</div>';
                return;
            }
            data.forEach(function(plan){
                var item = document.createElement('div');
                item.className = 'list-group-item d-flex align-items-center justify-content-between';
                item.innerHTML = '<div>'+
                    '<div class="fw-semibold">'+ plan.name +' <span class="badge bg-secondary ms-2">'+ plan.interval + (plan.interval_count > 1 ? (' x'+plan.interval_count) : '') +'</span></div>'+
                    '<div class="text-muted small">'+ Number(plan.amount).toFixed(2) +' '+ plan.currency +'</div>'+
                '</div>'+
                '<button class="btn btn-sm btn-primary">Subscribe</button>';
                var btn = item.querySelector('button');
                btn.addEventListener('click', function(){
                    createSubscription(plan.id, btn);
                });
                plansList.appendChild(item);
            });
        }).catch(function(){
            plansLoading.classList.add('d-none');
            plansList.classList.remove('d-none');
            plansList.innerHTML = '<div class="p-3 text-muted">Failed to load plans</div>';
        });
    }

    function fetchSubscriptions() {
        subsLoading.classList.remove('d-none');
        subsList.classList.add('d-none');
        subsList.innerHTML = '';

        fetch('/merchant/subscriptions/data').then(r => r.json()).then(function(res){
            subsLoading.classList.add('d-none');
            subsList.classList.remove('d-none');
            if (!res || !res.success) {
                subsList.innerHTML = '<div class="p-3 text-muted">Failed to load subscriptions</div>';
                return;
            }
            var data = res.data.data || [];
            if (data.length === 0) {
                subsList.innerHTML = '<div class="p-3 text-muted">No subscriptions yet</div>';
                return;
            }
            data.forEach(function(sub){
                var item = document.createElement('div');
                item.className = 'list-group-item d-flex align-items-center justify-content-between';
                var statusBadge = '<span class="badge bg-' + (sub.status === 'active' ? 'success' : (sub.status === 'canceled' ? 'secondary' : 'warning')) + '">'+ sub.status +'</span>';
                item.innerHTML = '<div>'+
                    '<div class="fw-semibold">'+ (sub.plan ? sub.plan.name : ('Plan #'+ sub.plan_id)) +' '+ statusBadge +'</div>'+
                    '<div class="text-muted small">Current period: '+ (sub.current_period_start ?? '-') +' → '+ (sub.current_period_end ?? '-') +'</div>'+
                '</div>'+
                '<button class="btn btn-sm btn-outline-secondary">Cancel at Period End</button>';
                var btn = item.querySelector('button');
                if (sub.cancel_at_period_end) {
                    btn.disabled = true;
                    btn.textContent = 'Will cancel at period end';
                }
                btn.addEventListener('click', function(){
                    updateSubscription(sub.id, { cancel_at_period_end: true }, btn);
                });
                subsList.appendChild(item);
            });
        }).catch(function(){
            subsLoading.classList.add('d-none');
            subsList.classList.remove('d-none');
            subsList.innerHTML = '<div class="p-3 text-muted">Failed to load subscriptions</div>';
        });
    }

    function createSubscription(planId, btn) {
        if (btn) btn.disabled = true;
        fetch('/merchant/subscriptions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ plan_id: planId })
        }).then(r => r.json()).then(function(res){
            if (res && res.success) {
                showToast('Success', 'Subscription created');
                fetchSubscriptions();
            } else {
                showToast('Error', (res && res.message) ? res.message : 'Failed to create subscription');
            }
        }).catch(function(){
            showToast('Error', 'Failed to create subscription');
        }).finally(function(){
            if (btn) btn.disabled = false;
        });
    }

    function updateSubscription(id, payload, btn) {
        if (btn) btn.disabled = true;
        fetch('/merchant/subscriptions/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        }).then(r => r.json()).then(function(res){
            if (res && res.success) {
                showToast('Success', 'Subscription updated');
                fetchSubscriptions();
            } else {
                showToast('Error', (res && res.message) ? res.message : 'Failed to update subscription');
            }
        }).catch(function(){
            showToast('Error', 'Failed to update subscription');
        }).finally(function(){
            if (btn) btn.disabled = false;
        });
    }

    // Bind search with debounce
    var t;
    planSearch && planSearch.addEventListener('input', function(){
        clearTimeout(t);
        t = setTimeout(function(){ fetchPlans(planSearch.value.trim()); }, 250);
    });

    // Initial load
    fetchPlans('');
    fetchSubscriptions();
})();
</script>
@endpush


