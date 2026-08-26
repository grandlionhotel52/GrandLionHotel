@extends('layouts.admin')

@section('title', 'Rooms')

@push('head')
    <style>
        .admin-room-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: var(--admin-surface);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .admin-room-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .admin-room-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-rooms-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-room-search-wrap {
            position: relative;
        }
        .admin-room-search-wrap .form-control {
            padding-left: 2.35rem;
            padding-right: 2.3rem;
        }
        .admin-room-search-icon {
            position: absolute;
            top: 50%;
            left: 0.82rem;
            color: #7b8492;
            transform: translateY(-50%);
            pointer-events: none;
        }
        .admin-room-search-clear {
            position: absolute;
            top: 50%;
            right: 0.5rem;
            display: inline-grid;
            width: 1.7rem;
            height: 1.7rem;
            place-items: center;
            border: 0;
            border-radius: 999px;
            background: transparent;
            color: #687386;
            transform: translateY(-50%);
        }
        .admin-room-search-clear:hover,
        .admin-room-search-clear:focus-visible {
            background: #eef2f7;
            color: #1f2937;
        }
        .admin-discount-shell {
            border-radius: 14px;
            border: 1px solid #ddcfba;
            background: #fffaf1;
            box-shadow: var(--admin-shadow);
        }
        .admin-discount-shell .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #7b6650;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
    </style>
@endpush

@section('content')
    @php
        $oldTargetScopeRaw = old('target_scope', 'all');
        $oldTargetScope = $oldTargetScopeRaw === 'type' ? 'roomtype' : $oldTargetScopeRaw;
        $oldSelectedRoomIds = collect(old('room_ids', []))->map(fn ($id) => (int) $id)->all();
        $hasBulkDiscountErrors = $errors->hasAny([
            'target_scope',
            'room_type',
            'room_ids',
            'room_ids.*',
            'discount_percent',
            'discount_start',
            'discount_end',
        ]);
        $hasCreateRoomErrors = old('_form_context') === 'create_room' && $errors->hasAny([
            'name', 'type', 'view_type', 'description', 'price_per_night',
            'room_status_id', 'image', 'image_upload',
        ]);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Room Management</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-ta" data-bs-toggle="modal" data-bs-target="#createRoomModal">
                <i class="bi bi-plus-circle me-1"></i>Add Room
            </button>
            <button
                type="button"
                class="btn btn-ta-outline"
                data-bs-toggle="modal"
                data-bs-target="#bulkDateDiscountModal"
            >
                <i class="bi bi-calendar-range me-1"></i>Bulk Date Discount
            </button>
            <a href="{{ route('admin.rooms.date-discounts.index') }}" class="btn btn-ta-outline">
                <i class="bi bi-journal-text me-1"></i>View Date Discounts
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Total Rooms</p>
                <p class="value">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Available</p>
                <p class="value text-success">{{ $stats['available'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Unavailable</p>
                <p class="value text-secondary">{{ $stats['unavailable'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Upcoming Date Discounts</p>
                <p class="value text-warning">{{ $stats['active_discounts'] }}</p>
            </div>
        </div>
    </div>

    <section class="admin-rooms-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.rooms.index') }}" id="adminRoomSearchForm">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label" for="adminRoomQuickSearch">Quick search</label>
                    <div class="admin-room-search-wrap">
                        <i class="bi bi-search admin-room-search-icon" aria-hidden="true"></i>
                        <input type="search" class="form-control" id="adminRoomQuickSearch" name="q" value="{{ request('q') }}" placeholder="Room name, type, or view" autocomplete="off" aria-describedby="adminRoomSearchHelp">
                        <button type="button" class="admin-room-search-clear {{ filled(request('q')) ? '' : 'd-none' }}" id="adminRoomSearchClear" aria-label="Clear room search">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="form-text" id="adminRoomSearchHelp" aria-live="polite">Results update as you type.</div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-select">
                        <option value="all" @selected($availability === 'all')>All</option>
                        <option value="available" @selected($availability === 'available')>Available</option>
                        <option value="unavailable" @selected($availability === 'unavailable')>Unavailable</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Room Status</label>
                    <select name="room_status" class="form-select">
                        <option value="">All</option>
                        @foreach($roomStatuses as $status)
                            <option value="{{ $status->slug }}" @selected($roomStatus === $status->slug)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <caption class="visually-hidden">Hotel rooms, availability, status, and management actions</caption>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Room Type</th>
                        <th>View</th>
                        <th>Standard Occupancy</th>
                        <th>Price</th>
                        <th>Room Status</th>
                        <th>Availability</th>
                        <th class="text-end admin-action-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>{{ $room->id }}</td>
                            <td>{{ $room->name }}</td>
                            <td>{{ $room->type }}</td>
                            <td>{{ $room->view_type ?: '-' }}</td>
                            <td>2 guests</td>
                            <td>
                                <div>&#8369;{{ \App\Support\Money::display($room->price_per_night) }} / night</div>
                            </td>
                            <td>
                                <form action="{{ route('admin.rooms.update-room-status', $room) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="room_status_id" onchange="this.form.submit()" class="form-select form-select-sm py-1" style="width: auto;">
                                        @foreach($roomStatuses as $status)
                                            <option value="{{ $status->id }}" {{ $room->room_status_id == $status->id ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                                @if($room->status_updated_at)
                                    <small class="d-block text-muted">
                                        Updated {{ $room->status_updated_at->diffForHumans() }}
                                        @if($room->statusUpdatedByAdmin)
                                            by {{ $room->statusUpdatedByAdmin->name }}
                                        @endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $room->is_available ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $room->is_available ? 'Available' : 'Unavailable' }}</span>
                            </td>
                            <td class="text-end admin-action-col">
                                <div class="admin-action-group">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-edit-room-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoomModal"
                                        data-room-id="{{ $room->id }}"
                                        data-room-update-url="{{ route('admin.rooms.update', $room) }}"
                                        data-room-name="{{ $room->name }}"
                                        data-room-type="{{ $room->type }}"
                                        data-room-view-type="{{ $room->view_type ?? '' }}"
                                        data-room-description="{{ $room->description ?? '' }}"
                                        data-room-price-night="{{ number_format((float) $room->price_per_night, 2, '.', '') }}"
                                        data-room-image="{{ $room->image ?? '' }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Delete this room? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-action-delete">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">No rooms found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $rooms->links() }}
    </div>

    <div class="modal fade" id="createRoomModal" tabindex="-1" aria-labelledby="createRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_form_context" value="create_room">

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1" id="createRoomModalLabel">Add Room</h5>
                            <p class="text-secondary small mb-0">Enter the room details, nightly price, status, and optional photo.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control {{ $hasCreateRoomErrors && $errors->has('name') ? 'is-invalid' : '' }}" name="name" value="{{ $hasCreateRoomErrors ? old('name') : '' }}" placeholder="Room 101" required autofocus>
                                @if($hasCreateRoomErrors) @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room Type</label>
                                <input type="text" class="form-control {{ $hasCreateRoomErrors && $errors->has('type') ? 'is-invalid' : '' }}" name="type" value="{{ $hasCreateRoomErrors ? old('type') : '' }}" list="create_room_type_options" placeholder="Standard, Deluxe, Suite..." required>
                                <datalist id="create_room_type_options">
                                    @foreach(['Standard', 'Deluxe', 'Family', 'Suite', 'Executive', 'Penthouse'] as $type)
                                        <option value="{{ $type }}"></option>
                                    @endforeach
                                </datalist>
                                @if($hasCreateRoomErrors) @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">View Type</label>
                                <input type="text" class="form-control {{ $hasCreateRoomErrors && $errors->has('view_type') ? 'is-invalid' : '' }}" name="view_type" value="{{ $hasCreateRoomErrors ? old('view_type') : '' }}" list="create_room_view_options" placeholder="Garden View, Pool View...">
                                <datalist id="create_room_view_options">
                                    @foreach(['Nature View', 'Garden View', 'Pool View', 'Mountain View', 'Courtyard View'] as $view)
                                        <option value="{{ $view }}"></option>
                                    @endforeach
                                </datalist>
                                @if($hasCreateRoomErrors) @error('view_type') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control {{ $hasCreateRoomErrors && $errors->has('description') ? 'is-invalid' : '' }}" name="description" rows="3" maxlength="2000">{{ $hasCreateRoomErrors ? old('description') : '' }}</textarea>
                                @if($hasCreateRoomErrors) @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Price per Night</label>
                                <div class="input-group">
                                    <span class="input-group-text">PHP</span>
                                    <input type="number" step="0.01" min="0" class="form-control {{ $hasCreateRoomErrors && $errors->has('price_per_night') ? 'is-invalid' : '' }}" name="price_per_night" value="{{ $hasCreateRoomErrors ? old('price_per_night') : '' }}" required>
                                    @if($hasCreateRoomErrors) @error('price_per_night') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Room Status</label>
                                <select class="form-select {{ $hasCreateRoomErrors && $errors->has('room_status_id') ? 'is-invalid' : '' }}" name="room_status_id">
                                    <option value="">Default: Clean</option>
                                    @foreach($roomStatuses as $status)
                                        <option value="{{ $status->room_status_id }}" @selected($hasCreateRoomErrors && (string) old('room_status_id') === (string) $status->room_status_id)>{{ $status->name }}</option>
                                    @endforeach
                                </select>
                                @if($hasCreateRoomErrors) @error('room_status_id') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Standard Occupancy</label>
                                <div class="static-field" aria-label="Fixed standard occupancy">2 guests &middot; Fixed</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image URL <span class="text-secondary">(optional)</span></label>
                                <input type="url" class="form-control {{ $hasCreateRoomErrors && $errors->has('image') ? 'is-invalid' : '' }}" name="image" value="{{ $hasCreateRoomErrors ? old('image') : '' }}" placeholder="https://example.com/room.jpg">
                                @if($hasCreateRoomErrors) @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload Image <span class="text-secondary">(optional)</span></label>
                                <input type="file" class="form-control {{ $hasCreateRoomErrors && $errors->has('image_upload') ? 'is-invalid' : '' }}" name="image_upload" accept="image/jpeg,image/png,image/webp">
                                <small class="text-secondary">JPG, PNG, or WebP up to 5 MB. Upload takes priority over URL.</small>
                                @if($hasCreateRoomErrors) @error('image_upload') <div class="invalid-feedback">{{ $message }}</div> @enderror @endif
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Create Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editRoomForm" method="POST" action="{{ route('admin.rooms.update', ['room' => '__ROOM__']) }}" class="row g-0">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_room_modal_id" id="edit_room_modal_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_room_name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Room Type</label>
                                <input type="text" class="form-control" name="type" id="edit_room_type" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">View Type</label>
                                <input type="text" class="form-control" name="view_type" id="edit_room_view_type" placeholder="Nature View, Garden View, etc.">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_room_description" rows="3"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Price per night</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_night" id="edit_room_price_night" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Standard occupancy</label>
                                <div class="static-field" aria-label="Fixed standard occupancy">2 guests &middot; Fixed</div>
                                <small class="text-secondary">Extra bedding requests are handled separately.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Image URL</label>
                                <input type="url" class="form-control" name="image" id="edit_room_image" placeholder="https://...">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Update room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDateDiscountModal" tabindex="-1" aria-labelledby="bulkDateDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content admin-discount-shell">
                <form method="POST" action="{{ route('admin.rooms.date-discounts.bulk') }}" id="bulkDateDiscountForm">
                    @csrf

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1" id="bulkDateDiscountModalLabel">Bulk Date Discount</h5>
                            <p class="text-secondary mb-0 small">Apply one discount percent to a date range for all rooms, one room type, or selected rooms.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Target Scope</label>
                                <select name="target_scope" id="discount_target_scope" class="form-select @error('target_scope') is-invalid @enderror">
                                    <option value="all" @selected($oldTargetScope === 'all')>All rooms</option>
                                    <option value="roomtype" @selected($oldTargetScope === 'roomtype')>By room type</option>
                                    <option value="selected" @selected($oldTargetScope === 'selected')>Selected rooms</option>
                                </select>
                                @error('target_scope')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4" id="discount_room_type_wrap">
                                <label class="form-label">Room Type</label>
                                <select name="room_type" id="discount_room_type" class="form-select @error('room_type') is-invalid @enderror">
                                    <option value="">Choose room type</option>
                                    @foreach($roomTypes as $roomType)
                                        <option value="{{ $roomType }}" @selected(old('room_type') === $roomType)>{{ $roomType }}</option>
                                    @endforeach
                                </select>
                                @error('room_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Discount (%)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="100"
                                    name="discount_percent"
                                    id="discount_percent"
                                    value="{{ old('discount_percent', '10') }}"
                                    class="form-control @error('discount_percent') is-invalid @enderror"
                                    required
                                >
                                @error('discount_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input
                                    type="date"
                                    name="discount_start"
                                    id="discount_start"
                                    value="{{ old('discount_start', now()->toDateString()) }}"
                                    min="{{ now()->toDateString() }}"
                                    class="form-control @error('discount_start') is-invalid @enderror"
                                    required
                                >
                                @error('discount_start')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input
                                    type="date"
                                    name="discount_end"
                                    id="discount_end"
                                    value="{{ old('discount_end', now()->addDays(6)->toDateString()) }}"
                                    min="{{ old('discount_start', now()->toDateString()) }}"
                                    class="form-control @error('discount_end') is-invalid @enderror"
                                    required
                                >
                                @error('discount_end')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12" id="discount_room_select_wrap">
                                <label class="form-label">Selected Rooms</label>
                                <input
                                    type="text"
                                    id="discount_room_search"
                                    class="form-control mb-2"
                                    placeholder="Search room by ID, name, or room type"
                                >
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                    <span class="small fw-semibold" id="discount_selected_count">0 rooms selected</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-ta-outline" id="discount_select_visible">Select visible</button>
                                        <button type="button" class="btn btn-sm btn-ta-outline" id="discount_clear_rooms">Clear</button>
                                    </div>
                                </div>
                                <select
                                    name="room_ids[]"
                                    id="discount_room_ids"
                                    class="form-select @error('room_ids') is-invalid @enderror @error('room_ids.*') is-invalid @enderror"
                                    multiple
                                    size="8"
                                >
                                    @foreach($discountRoomOptions as $optionRoom)
                                        <option value="{{ $optionRoom->id }}" @selected(in_array((int) $optionRoom->id, $oldSelectedRoomIds, true))>
                                            #{{ $optionRoom->id }} - {{ $optionRoom->name }} (Room Type: {{ $optionRoom->type }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-secondary d-block mt-1">Click rooms to select them. Use the buttons above to manage search results quickly.</small>
                                @error('room_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('room_ids.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="alert alert-light border mb-0" id="discount_impact_summary" role="status" aria-live="polite"></div>
                                <small class="text-secondary d-block mt-2">Any existing discount that overlaps this room and date range will be replaced.</small>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-ta" id="bulkDateDiscountSubmit">Apply Discount</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const roomSearchForm = document.getElementById('adminRoomSearchForm');
            const roomSearchField = document.getElementById('adminRoomQuickSearch');
            const roomSearchClear = document.getElementById('adminRoomSearchClear');
            const roomSearchHelp = document.getElementById('adminRoomSearchHelp');

            if (roomSearchForm && roomSearchField) {
                let roomSearchTimer;
                const submittedRoomSearch = roomSearchField.value.trim();
                const submitRoomSearch = function () {
                    roomSearchHelp.textContent = 'Searching rooms...';
                    roomSearchForm.requestSubmit();
                };

                roomSearchField.addEventListener('input', function () {
                    window.clearTimeout(roomSearchTimer);
                    const value = roomSearchField.value.trim();
                    roomSearchClear?.classList.toggle('d-none', value === '');
                    roomSearchHelp.textContent = value === '' ? 'Showing all rooms...' : 'Waiting for you to finish typing...';

                    if (value === submittedRoomSearch) {
                        roomSearchHelp.textContent = 'Results update as you type.';
                        return;
                    }

                    roomSearchTimer = window.setTimeout(submitRoomSearch, 450);
                });

                roomSearchClear?.addEventListener('click', function () {
                    window.clearTimeout(roomSearchTimer);
                    roomSearchField.value = '';
                    roomSearchClear.classList.add('d-none');
                    submitRoomSearch();
                });
            }

            const discountScopeSelect = document.getElementById('discount_target_scope');
            const discountRoomTypeWrap = document.getElementById('discount_room_type_wrap');
            const discountRoomTypeField = document.getElementById('discount_room_type');
            const discountRoomSelectWrap = document.getElementById('discount_room_select_wrap');
            const discountRoomSelectField = document.getElementById('discount_room_ids');
            const discountRoomSearchField = document.getElementById('discount_room_search');
            const discountSelectVisibleButton = document.getElementById('discount_select_visible');
            const discountClearRoomsButton = document.getElementById('discount_clear_rooms');
            const discountSelectedCount = document.getElementById('discount_selected_count');
            const discountPercentField = document.getElementById('discount_percent');
            const discountStartField = document.getElementById('discount_start');
            const discountEndField = document.getElementById('discount_end');
            const discountImpactSummary = document.getElementById('discount_impact_summary');
            const bulkDiscountForm = document.getElementById('bulkDateDiscountForm');
            const bulkDiscountSubmit = document.getElementById('bulkDateDiscountSubmit');
            const totalRoomCount = @json($discountRoomOptions->count());
            const roomTypeCounts = @json($discountRoomOptions->groupBy('type')->map->count());

            const normalizeDiscountSearchValue = function (value) {
                return (value || '').toString().trim().toLowerCase();
            };

            const updateDiscountRoomSearchUi = function () {
                if (!discountRoomSelectField) {
                    return;
                }

                const query = normalizeDiscountSearchValue(discountRoomSearchField?.value);
                const options = Array.from(discountRoomSelectField.options);

                options.forEach(function (optionEl) {
                    const optionText = normalizeDiscountSearchValue(optionEl.textContent);
                    optionEl.hidden = query !== '' && !optionText.includes(query);
                });
            };

            const selectedRoomCount = function () {
                return discountRoomSelectField
                    ? Array.from(discountRoomSelectField.options).filter((option) => option.selected).length
                    : 0;
            };

            const updateDiscountImpactSummary = function () {
                const scope = discountScopeSelect?.value || 'all';
                const percent = discountPercentField?.value || '0';
                let rooms = totalRoomCount;

                if (scope === 'roomtype') {
                    rooms = roomTypeCounts[discountRoomTypeField?.value || ''] || 0;
                } else if (scope === 'selected') {
                    rooms = selectedRoomCount();
                }

                let days = 0;
                if (discountStartField?.value && discountEndField?.value) {
                    const start = new Date(discountStartField.value + 'T00:00:00');
                    const end = new Date(discountEndField.value + 'T00:00:00');
                    days = Math.max(0, Math.round((end - start) / 86400000) + 1);
                }

                if (discountSelectedCount) {
                    const selected = selectedRoomCount();
                    discountSelectedCount.textContent = `${selected} room${selected === 1 ? '' : 's'} selected`;
                }
                if (discountImpactSummary) {
                    discountImpactSummary.textContent = `${percent}% discount · ${rooms} room${rooms === 1 ? '' : 's'} · ${days} day${days === 1 ? '' : 's'}`;
                }
            };

            const updateDiscountScopeUi = function () {
                if (!discountScopeSelect) {
                    return;
                }

                const scope = discountScopeSelect.value;
                const showRoomType = scope === 'roomtype';
                const showSelected = scope === 'selected';

                if (discountRoomTypeWrap) {
                    discountRoomTypeWrap.classList.toggle('d-none', !showRoomType);
                }
                if (discountRoomTypeField) {
                    discountRoomTypeField.disabled = !showRoomType;
                }

                if (discountRoomSelectWrap) {
                    discountRoomSelectWrap.classList.toggle('d-none', !showSelected);
                }
                if (discountRoomSelectField) {
                    discountRoomSelectField.disabled = !showSelected;
                }
                if (discountRoomSearchField) {
                    discountRoomSearchField.disabled = !showSelected;
                    if (!showSelected) {
                        discountRoomSearchField.value = '';
                    }
                }

                updateDiscountRoomSearchUi();
                updateDiscountImpactSummary();
            };

            discountScopeSelect?.addEventListener('change', updateDiscountScopeUi);
            discountRoomTypeField?.addEventListener('change', updateDiscountImpactSummary);
            discountRoomSelectField?.addEventListener('change', updateDiscountImpactSummary);
            discountRoomSelectField?.addEventListener('mousedown', function (event) {
                const option = event.target;
                if (!(option instanceof HTMLOptionElement)) {
                    return;
                }

                event.preventDefault();
                option.selected = !option.selected;
                discountRoomSelectField.focus();
                updateDiscountImpactSummary();
            });
            discountPercentField?.addEventListener('input', updateDiscountImpactSummary);
            discountRoomSearchField?.addEventListener('input', updateDiscountRoomSearchUi);
            discountStartField?.addEventListener('change', function () {
                if (discountEndField) {
                    discountEndField.min = discountStartField.value;
                    if (discountEndField.value < discountStartField.value) {
                        discountEndField.value = discountStartField.value;
                    }
                }
                updateDiscountImpactSummary();
            });
            discountEndField?.addEventListener('change', updateDiscountImpactSummary);
            discountSelectVisibleButton?.addEventListener('click', function () {
                Array.from(discountRoomSelectField?.options || []).forEach(function (option) {
                    if (!option.hidden) option.selected = true;
                });
                updateDiscountImpactSummary();
            });
            discountClearRoomsButton?.addEventListener('click', function () {
                Array.from(discountRoomSelectField?.options || []).forEach(function (option) {
                    option.selected = false;
                });
                updateDiscountImpactSummary();
            });
            bulkDiscountForm?.addEventListener('submit', function (event) {
                if (bulkDiscountForm.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }
                bulkDiscountForm.dataset.submitting = '1';
                if (bulkDiscountSubmit) {
                    bulkDiscountSubmit.disabled = true;
                    bulkDiscountSubmit.textContent = 'Applying…';
                }
            });
            updateDiscountScopeUi();

            const bulkDateDiscountModalEl = document.getElementById('bulkDateDiscountModal');
            const shouldOpenBulkFromQuery = new URLSearchParams(window.location.search).get('open_bulk_discount') === '1';
            if (bulkDateDiscountModalEl && typeof bootstrap !== 'undefined' && (@json($hasBulkDiscountErrors) || shouldOpenBulkFromQuery)) {
                const bulkModal = bootstrap.Modal.getOrCreateInstance(bulkDateDiscountModalEl);
                bulkModal.show();
            }

            const createRoomModalEl = document.getElementById('createRoomModal');
            if (createRoomModalEl && typeof bootstrap !== 'undefined' && @json($hasCreateRoomErrors)) {
                bootstrap.Modal.getOrCreateInstance(createRoomModalEl).show();
            }

            const editRoomModalEl = document.getElementById('editRoomModal');
            const formEl = document.getElementById('editRoomForm');
            if (!editRoomModalEl || !formEl || typeof bootstrap === 'undefined') {
                return;
            }

            const oldFormValues = {
                name: @json(old('name')),
                type: @json(old('type')),
                viewType: @json(old('view_type')),
                description: @json(old('description')),
                priceNight: @json(old('price_per_night')),
                image: @json(old('image')),
            };

            const fieldRoomId = document.getElementById('edit_room_modal_id');
            const fieldName = document.getElementById('edit_room_name');
            const fieldType = document.getElementById('edit_room_type');
            const fieldViewType = document.getElementById('edit_room_view_type');
            const fieldDescription = document.getElementById('edit_room_description');
            const fieldPriceNight = document.getElementById('edit_room_price_night');
            const fieldImage = document.getElementById('edit_room_image');

            editRoomModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const roomId = trigger.getAttribute('data-room-id') || '';
                const roomUpdateUrl = trigger.getAttribute('data-room-update-url') || '';
                const name = trigger.getAttribute('data-room-name') || '';
                const type = trigger.getAttribute('data-room-type') || '';
                const viewType = trigger.getAttribute('data-room-view-type') || '';
                const description = trigger.getAttribute('data-room-description') || '';
                const priceNight = trigger.getAttribute('data-room-price-night') || '0';
                const image = trigger.getAttribute('data-room-image') || '';

                if (!roomUpdateUrl) {
                    return;
                }

                formEl.action = roomUpdateUrl;
                fieldRoomId.value = roomId;
                fieldName.value = name;
                fieldType.value = type;
                fieldViewType.value = viewType;
                fieldDescription.value = description;
                fieldPriceNight.value = priceNight;
                fieldImage.value = image;
            });

            const oldModalRoomId = @json(old('_room_modal_id'));
            if (oldModalRoomId) {
                const oldButton = document.querySelector(`.js-edit-room-btn[data-room-id="${oldModalRoomId}"]`);
                if (oldButton) {
                    bootstrap.Modal.getOrCreateInstance(editRoomModalEl).show(oldButton);

                    if (oldFormValues.name !== null) fieldName.value = oldFormValues.name;
                    if (oldFormValues.type !== null) fieldType.value = oldFormValues.type;
                    if (oldFormValues.viewType !== null) fieldViewType.value = oldFormValues.viewType;
                    if (oldFormValues.description !== null) fieldDescription.value = oldFormValues.description;
                    if (oldFormValues.priceNight !== null) fieldPriceNight.value = oldFormValues.priceNight;
                    if (oldFormValues.image !== null) fieldImage.value = oldFormValues.image;
                }
            }
        });
    </script>
@endpush
