@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Configuration</p>
            <h1 class="h3 mb-0">System Settings</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <section class="soft-card p-4">
                <h2 class="h5 mb-2">Hotel name</h2>
                <p class="text-secondary mb-4">This title appears in the website header, browser titles, receipts, emails, and reports.</p>

                <form method="POST" action="{{ route('admin.settings.update') }}" data-submit-lock>
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="form-label" for="hotel_name">System title</label>
                        <input
                            id="hotel_name"
                            type="text"
                            name="hotel_name"
                            class="form-control @error('hotel_name') is-invalid @enderror"
                            value="{{ old('hotel_name', $hotelName) }}"
                            maxlength="100"
                            autocomplete="organization"
                            required
                        >
                        @error('hotel_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-ta">
                        <i class="bi bi-check2-circle me-1"></i> Save title
                    </button>
                </form>
            </section>
        </div>
    </div>
@endsection
