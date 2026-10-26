@extends('layouts.admin')

@section('title', 'Promo Codes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Discounts</p>
            <h1 class="h3 mb-0">Promo Codes</h1>
            <p class="text-secondary mb-0">Active offers automatically appear in the customer Promos modal.</p>
        </div>
    </div>

    <section class="soft-card p-4 mb-4">
        <h2 class="h5 mb-3">Create promo code</h2>
        <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label" for="promo_code">Code</label>
                <input id="promo_code" name="code" class="form-control text-uppercase" maxlength="40" pattern="[A-Za-z0-9_-]+" value="{{ old('code') }}" placeholder="CHRISTMAS20" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="promo_discount">Discount %</label>
                <input id="promo_discount" type="number" name="discount_percent" class="form-control" min="0.01" max="100" step="0.01" value="{{ old('discount_percent') }}" required>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="promo_description">Customer-facing description</label>
                <input id="promo_description" name="description" class="form-control" maxlength="255" value="{{ old('description') }}" placeholder="Christmas offer for December stays">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="promo_starts">Starts</label>
                <input id="promo_starts" type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="promo_ends">Ends</label>
                <input id="promo_ends" type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="new_customers_only" value="1" id="promo_new_customers" class="form-check-input" @checked(old('new_customers_only'))>
                    <label for="promo_new_customers" class="form-check-label">New customers only</label>
                </div>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_active" value="1" id="promo_active" class="form-check-input" @checked(old('is_active', true))>
                    <label for="promo_active" class="form-check-label">Active</label>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-ta w-100">Create</button>
            </div>
        </form>
    </section>

    <section class="soft-card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Offer</th>
                        <th>Discount</th>
                        <th>Validity</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($promoCodes as $promo)
                        <tr>
                            <td style="min-width: 240px">
                                <input form="promo-update-{{ $promo->getKey() }}" name="code" class="form-control text-uppercase mb-2" value="{{ $promo->code }}" required>
                                <input form="promo-update-{{ $promo->getKey() }}" name="description" class="form-control form-control-sm" maxlength="255" value="{{ $promo->description }}" placeholder="Description shown to customers">
                            </td>
                            <td style="min-width: 120px">
                                <input form="promo-update-{{ $promo->getKey() }}" type="number" name="discount_percent" class="form-control" min="0.01" max="100" step="0.01" value="{{ $promo->discount_percent }}" required>
                            </td>
                            <td style="min-width: 280px">
                                <div class="d-flex gap-1">
                                    <input form="promo-update-{{ $promo->getKey() }}" type="date" name="starts_at" class="form-control" value="{{ $promo->starts_at?->toDateString() }}" aria-label="Start date for {{ $promo->code }}">
                                    <input form="promo-update-{{ $promo->getKey() }}" type="date" name="ends_at" class="form-control" value="{{ $promo->ends_at?->toDateString() }}" aria-label="End date for {{ $promo->code }}">
                                </div>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input form="promo-update-{{ $promo->getKey() }}" type="checkbox" name="new_customers_only" value="1" id="promo-new-{{ $promo->getKey() }}" class="form-check-input" @checked($promo->new_customers_only)>
                                    <label class="form-check-label" for="promo-new-{{ $promo->getKey() }}">New only</label>
                                </div>
                            </td>
                            <td>
                                <div class="form-check">
                                    <input form="promo-update-{{ $promo->getKey() }}" type="checkbox" name="is_active" value="1" id="promo-active-{{ $promo->getKey() }}" class="form-check-input" @checked($promo->is_active)>
                                    <label class="form-check-label" for="promo-active-{{ $promo->getKey() }}">Active</label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <form id="promo-update-{{ $promo->getKey() }}" method="POST" action="{{ route('admin.promo-codes.update', $promo) }}">
                                        @csrf
                                        @method('PUT')
                                        <button class="btn btn-sm btn-ta">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.promo-codes.destroy', $promo) }}" data-confirm="Delete this promo code?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No promo codes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($promoCodes->hasPages())
            <div class="mt-3">{{ $promoCodes->links() }}</div>
        @endif
    </section>
@endsection
