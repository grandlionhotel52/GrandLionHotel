@extends('layouts.admin')

@section('title', 'Create Room')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Create room</h1>
            <p class="text-secondary mb-0">Add room details, pricing, status, and an optional photo.</p>
        </div>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Back to rooms</a>
    </div>

    <section class="soft-card p-4">
        <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <input type="text" class="form-control" name="type" value="{{ old('type') }}" list="room_type_options" placeholder="Standard, Deluxe, Suite..." required>
                <datalist id="room_type_options">
                    @foreach(['Standard', 'Deluxe', 'Family', 'Suite', 'Executive', 'Penthouse'] as $type)
                        <option value="{{ $type }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="col-md-4">
                <label class="form-label">View type</label>
                <input type="text" class="form-control" name="view_type" value="{{ old('view_type') }}" list="room_view_options" placeholder="Garden View, Pool View...">
                <datalist id="room_view_options">
                    @foreach(['Nature View', 'Garden View', 'Pool View', 'Mountain View', 'Courtyard View'] as $view)
                        <option value="{{ $view }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price per night</label>
                <input type="number" step="0.01" class="form-control" name="price_per_night" value="{{ old('price_per_night') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Standard occupancy</label>
                <input type="text" class="form-control" value="2 guests" disabled>
                <small class="text-secondary">Extra beds are handled during booking.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Room status</label>
                <select class="form-select" name="room_status_id">
                    <option value="">Default: Clean</option>
                    @foreach($roomStatuses as $roomStatus)
                        <option value="{{ $roomStatus->room_status_id }}" @selected((string) old('room_status_id') === (string) $roomStatus->room_status_id)>
                            {{ $roomStatus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Image URL (optional)</label>
                <input type="url" class="form-control" name="image" value="{{ old('image') }}" placeholder="https://example.com/room.jpg">
                <small class="text-secondary">Use a remote image, or upload one below.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload room image (optional)</label>
                <input type="file" class="form-control" name="image_upload" accept="image/jpeg,image/png,image/webp">
                <small class="text-secondary">JPG, PNG, or WebP up to 5 MB. Upload takes priority over URL.</small>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Create room</button>
            </div>
        </form>
    </section>
@endsection
