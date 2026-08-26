@extends('layouts.admin')

@section('title', 'Promo Codes')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><p class="ta-eyebrow mb-1">Discounts</p><h1 class="h3 mb-0">Promo Codes</h1></div>
    </div>

    <section class="soft-card p-4 mb-4">
        <h2 class="h5 mb-3">Create promo code</h2>
        <form method="POST" action="{{ route('admin.promo-codes.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3"><label class="form-label">Code</label><input name="code" class="form-control" maxlength="40" pattern="[A-Za-z0-9_-]+" value="{{ old('code') }}" required></div>
            <div class="col-md-2"><label class="form-label">Discount %</label><input type="number" name="discount_percent" class="form-control" min="0.01" max="100" step="0.01" value="{{ old('discount_percent') }}" required></div>
            <div class="col-md-2"><label class="form-label">Starts</label><input type="date" name="starts_at" class="form-control" value="{{ old('starts_at') }}"></div>
            <div class="col-md-2"><label class="form-label">Ends</label><input type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}"></div>
            <div class="col-md-1 d-flex align-items-end"><div class="form-check mb-2"><input type="checkbox" name="is_active" value="1" id="promo_active" class="form-check-input" checked><label for="promo_active" class="form-check-label">Active</label></div></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-ta w-100">Create</button></div>
        </form>
    </section>

    <section class="soft-card p-3">
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Code</th><th>Discount</th><th>Validity</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse($promoCodes as $promo)
            <tr>
                <td><input form="promo-update-{{ $promo->getKey() }}" name="code" class="form-control" value="{{ $promo->code }}" required></td>
                <td><input form="promo-update-{{ $promo->getKey() }}" type="number" name="discount_percent" class="form-control" min="0.01" max="100" step="0.01" value="{{ $promo->discount_percent }}" required></td>
                <td><div class="d-flex gap-1"><input form="promo-update-{{ $promo->getKey() }}" type="date" name="starts_at" class="form-control" value="{{ $promo->starts_at?->toDateString() }}"><input form="promo-update-{{ $promo->getKey() }}" type="date" name="ends_at" class="form-control" value="{{ $promo->ends_at?->toDateString() }}"></div></td>
                <td><div class="form-check"><input form="promo-update-{{ $promo->getKey() }}" type="checkbox" name="is_active" value="1" class="form-check-input" @checked($promo->is_active)><label class="form-check-label">Active</label></div></td>
                <td><div class="d-flex gap-2"><form id="promo-update-{{ $promo->getKey() }}" method="POST" action="{{ route('admin.promo-codes.update', $promo) }}">@csrf @method('PUT')<button class="btn btn-sm btn-ta">Save</button></form><form method="POST" action="{{ route('admin.promo-codes.destroy', $promo) }}" data-confirm="Delete this promo code?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form></div></td>
            </tr>
        @empty<tr><td colspan="5" class="text-center text-secondary py-4">No promo codes yet.</td></tr>@endforelse
        </tbody></table></div>
        @if($promoCodes->hasPages())<div class="mt-3">{{ $promoCodes->links() }}</div>@endif
    </section>
@endsection
