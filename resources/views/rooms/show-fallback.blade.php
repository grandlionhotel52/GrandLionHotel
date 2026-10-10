@extends('layouts.app')

@section('title', $room->name.' | Room Details')

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <article class="soft-card overflow-hidden">
                <img
                    src="{{ $room->image_url }}"
                    alt="{{ $room->name }}"
                    class="w-100 d-block"
                    style="height: min(56vw, 520px); object-fit: cover;"
                >

                <div class="p-4 p-lg-5">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <span class="room-type-chip">Room details</span>
                            <h1 class="mt-2 mb-1">{{ $room->name }}</h1>
                            <p class="text-secondary mb-0">
                                {{ $room->type }}
                                @if(filled($room->view_type))
                                    &middot; {{ $room->view_type }}
                                @endif
                                &middot; {{ \App\Models\Room::standardGuestCapacity() }} guests
                            </p>
                        </div>
                        <div class="price-tag">&#8369;{{ \App\Support\Money::display($room->price_per_night) }} <small class="fs-6">per night</small></div>
                    </div>

                    <p class="text-secondary my-4">{{ $room->description ?: 'A comfortable room for a relaxing stay.' }}</p>

                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach($room->amenities as $amenity)
                            <span class="badge rounded-pill text-bg-light border p-2">
                                <i class="bi {{ $amenity['icon'] }} me-1" aria-hidden="true"></i>{{ $amenity['label'] }}
                            </span>
                        @endforeach
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @guest
                            <a href="{{ route('login', ['intended' => request()->fullUrl()]) }}" class="btn btn-ta">Sign in to book</a>
                        @else
                            <a href="{{ route('bookings.create', $room) }}" class="btn btn-ta">Continue to booking</a>
                        @endguest
                        <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">Back to rooms</a>
                    </div>
                </div>
            </article>
        </div>
    </div>
@endsection
