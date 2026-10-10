@extends('layouts.app')

@section('title', $room->name.' | Room Details')

@section('content')
    @php
        $fallbackGallery = collect([[
            'url' => $room->image_url,
            'caption' => $room->name.' main room view',
        ]])->concat($room->defaultDetailImages())->unique('url')->values();
    @endphp

    <div class="row justify-content-center">
        <div class="col-xl-10">
            <article class="soft-card overflow-hidden">
                <div id="roomFallbackCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false" aria-label="{{ $room->name }} photo gallery">
                    <div class="carousel-inner">
                        @foreach($fallbackGallery as $index => $photo)
                            <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                <img
                                    src="{{ $photo['url'] }}"
                                    alt="{{ $photo['caption'] }}"
                                    class="w-100 d-block"
                                    style="height: min(56vw, 520px); object-fit: cover;"
                                    @if($index > 0) loading="lazy" @endif
                                >
                                <div class="carousel-caption"><span class="bg-dark bg-opacity-75 rounded px-3 py-2">{{ $photo['caption'] }}</span></div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#roomFallbackCarousel" data-bs-slide="prev" aria-label="Previous room photo">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#roomFallbackCarousel" data-bs-slide="next" aria-label="Next room photo">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    </button>
                </div>

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
