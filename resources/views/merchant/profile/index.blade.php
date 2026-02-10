@extends('layouts.app-sidebar')

@section('title', 'Profile - Merchant - ' . config('app.name'))
@section('page-title', 'Profile')

@push('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, var(--primary-violet) 0%, var(--primary-violet-dark) 100%);
        color: white;
        padding: 32px;
        border-radius: 16px;
        margin-bottom: 24px;
    }

    .profile-avatar-section {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .profile-avatar-big {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        border: 3px solid rgba(255, 255, 255, 0.3);
    }

    .profile-info h4 {
        margin: 0;
        font-weight: 600;
    }

    .profile-info p {
        margin: 4px 0 0 0;
        opacity: 0.9;
        font-size: 14px;
    }

    .profile-section {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }

    .profile-section-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .profile-section-title i {
        color: var(--primary-violet);
    }

    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-violet);
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25);
    }

    .btn-save {
        background: linear-gradient(135deg, var(--primary-violet) 0%, var(--primary-violet-dark) 100%);
        border: none;
        padding: 12px 32px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-section">
                <div class="profile-avatar-big">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div class="profile-info">
                    <h4>{{ $user->name }}</h4>
                    <p>{{ $user->email }}</p>
                    <p style="margin-top: 8px; font-size: 13px;">
                        <i class="bi bi-building"></i> Merchant ID: <strong>{{ $merchant->id }}</strong>
                        @if($merchant->company_name)
                        | <i class="bi bi-briefcase"></i> {{ $merchant->company_name }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('merchant.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Account Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-person-circle"></i> Account Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Leave blank to keep current password">
                        @error('password')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" name="password_confirmation" placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <!-- Business Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-briefcase"></i> Business Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name" value="{{ old('company_name', $merchant->company_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Type</label>
                        <select class="form-select" name="business_type">
                            <option value="">Select Business Type</option>
                            <option value="Sole Proprietorship" {{ old('business_type', $merchant->business_type) == 'Sole Proprietorship' ? 'selected' : '' }}>Sole Proprietorship</option>
                            <option value="Partnership" {{ old('business_type', $merchant->business_type) == 'Partnership' ? 'selected' : '' }}>Partnership</option>
                            <option value="Private Limited" {{ old('business_type', $merchant->business_type) == 'Private Limited' ? 'selected' : '' }}>Private Limited</option>
                            <option value="Public Limited" {{ old('business_type', $merchant->business_type) == 'Public Limited' ? 'selected' : '' }}>Public Limited</option>
                            <option value="LLP" {{ old('business_type', $merchant->business_type) == 'LLP' ? 'selected' : '' }}>LLP</option>
                            <option value="Other" {{ old('business_type', $merchant->business_type) == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Registration Number</label>
                        <input type="text" class="form-control" name="business_registration_number" value="{{ old('business_registration_number', $merchant->business_registration_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Business Website</label>
                        <input type="url" class="form-control" name="business_website" value="{{ old('business_website', $merchant->business_website) }}" placeholder="https://example.com">
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-telephone"></i> Contact Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Name</label>
                        <input type="text" class="form-control" name="contact_name" value="{{ old('contact_name', $merchant->contact_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="{{ old('phone', $merchant->phone) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Mobile</label>
                        <input type="text" class="form-control" name="contact_mobile" value="{{ old('contact_mobile', $merchant->contact_mobile) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Landline</label>
                        <input type="text" class="form-control" name="contact_landline" value="{{ old('contact_landline', $merchant->contact_landline) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="contact_email" value="{{ old('contact_email', $merchant->contact_email) }}">
                    </div>
                </div>
            </div>

            <!-- Address Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-geo-alt"></i> Address Information
                </div>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" class="form-control" name="address_line_1" value="{{ old('address_line_1', $merchant->address_line_1) }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" name="address_line_2" value="{{ old('address_line_2', $merchant->address_line_2) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="business_city" value="{{ old('business_city', $merchant->business_city) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">State</label>
                        <input type="text" class="form-control" name="business_state" value="{{ old('business_state', $merchant->business_state) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" class="form-control" name="business_country" value="{{ old('business_country', $merchant->business_country) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Postal Code</label>
                        <input type="text" class="form-control" name="business_postal_code" value="{{ old('business_postal_code', $merchant->business_postal_code) }}">
                    </div>
                </div>
            </div>

            <!-- Tax & Legal Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-file-earmark-text"></i> Tax & Legal Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">PAN Number</label>
                        <input type="text" class="form-control" name="merchant_pan_number" value="{{ old('merchant_pan_number', $merchant->merchant_pan_number) }}" maxlength="10">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Name on PAN Card</label>
                        <input type="text" class="form-control" name="name_on_pan_card" value="{{ old('name_on_pan_card', $merchant->name_on_pan_card) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GST Identification Number</label>
                        <input type="text" class="form-control" name="gst_identification_no" value="{{ old('gst_identification_no', $merchant->gst_identification_no) }}" maxlength="15">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GSTIN State</label>
                        <input type="text" class="form-control" name="gstin_state" value="{{ old('gstin_state', $merchant->gstin_state) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax ID</label>
                        <input type="text" class="form-control" name="tax_id" value="{{ old('tax_id', $merchant->tax_id) }}">
                    </div>
                </div>
            </div>

            <!-- Bank Account Information -->
            <div class="profile-section">
                <div class="profile-section-title">
                    <i class="bi bi-bank"></i> Bank Account Information
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" class="form-control" name="bank_account_holder_name" value="{{ old('bank_account_holder_name', $merchant->bank_account_holder_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" name="bank_account_number" value="{{ old('bank_account_number', $merchant->bank_account_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" class="form-control" name="bank_ifsc_code" value="{{ old('bank_ifsc_code', $merchant->bank_ifsc_code) }}" maxlength="11">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Name</label>
                        <input type="text" class="form-control" name="bank_name" value="{{ old('bank_name', $merchant->bank_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank Branch</label>
                        <input type="text" class="form-control" name="bank_branch" value="{{ old('bank_branch', $merchant->bank_branch) }}">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-save">
                    <i class="bi bi-check-circle me-2"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

