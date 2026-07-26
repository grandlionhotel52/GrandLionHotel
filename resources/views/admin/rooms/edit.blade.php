@extends('layouts.admin')

@section('title', 'Edit Room')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Edit {{ $room->name }}</h1>
            <p class="text-secondary mb-0">Update room information shown to customers.</p>
        </div>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Back to rooms</a>
    </div>

    <section class="soft-card p-4">
        <form method="POST" action="{{ route('admin.rooms.update', $room) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $room->name) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <input type="text" class="form-control" name="type" value="{{ old('type', $room->type) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">View type</label>
                <input type="text" class="form-control" name="view_type" value="{{ old('view_type', $room->view_type) }}" placeholder="Nature View, Garden View, etc.">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description', $room->description) }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price per night</label>
                <input type="number" step="0.01" class="form-control" name="price_per_night" value="{{ old('price_per_night', $room->price_per_night) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Standard occupancy</label>
                <input type="text" class="form-control" value="2 guests" disabled>
                <small class="text-secondary">Extra beds are handled during booking.</small>
            </div>
            <div class="col-md-3">
                <label class="form-label">Room status</label>
                <select class="form-select" name="room_status_id">
                    @foreach($roomStatuses as $roomStatus)
                        <option value="{{ $roomStatus->room_status_id }}" @selected((string) old('room_status_id', $room->room_status_id) === (string) $roomStatus->room_status_id)>
                            {{ $roomStatus->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Image URL (optional)</label>
                <input type="url" class="form-control" name="image" value="{{ old('image', $room->image) }}" placeholder="https://example.com/room.jpg">
                <small class="text-secondary">Keep this value to retain the current image.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">Replace with uploaded image</label>
                <input type="file" class="form-control" name="image_upload" accept="image/jpeg,image/png,image/webp">
                <small class="text-secondary">JPG, PNG, or WebP up to 5 MB.</small>
            </div>
            <div class="col-12">
                <img src="{{ $room->image_url }}" alt="Current image for {{ $room->name }}" class="rounded border" style="width: 180px; height: 110px; object-fit: cover;">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Update room</button>
            </div>
        </form>
    </section>
@endsection
