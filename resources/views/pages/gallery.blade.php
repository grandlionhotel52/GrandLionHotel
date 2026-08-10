@extends('layouts.app')

@section('title', 'Gallery')

@push('head')
    <style>
        .gallery-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .gallery-filters {
            display: flex;
            gap: 0.55rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }
        .gallery-filter {
            flex: 0 0 auto;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: #fff;
            color: var(--ink);
            padding: 0.5rem 0.9rem;
            font-weight: 700;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }
        .gallery-filter:hover,
        .gallery-filter:focus-visible,
        .gallery-filter.is-active {
            border-color: var(--ta);
            background: var(--ta);
            color: #fff;
        }
        .gallery-card {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            border: 1px solid var(--line);
        }
        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 34px rgba(14, 19, 31, 0.16);
            border-color: #d2c2a8;
        }
        .gallery-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }
        .gallery-preview {
            display: block;
            width: 100%;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: zoom-in;
        }
        .gallery-preview:focus-visible {
            outline: 3px solid var(--ta);
            outline-offset: -3px;
        }
        .gallery-zoom-label {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            z-index: 1;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.76);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            pointer-events: none;
        }
        .gallery-details {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            color: #fff;
            padding: 0.9rem 1rem;
            background: linear-gradient(180deg, rgba(16, 24, 40, 0.04) 0%, rgba(16, 24, 40, 0.9) 100%);
        }
        .gallery-room-name {
            margin: 0;
            font-size: 1rem;
            line-height: 1.2;
            color: #fff;
            font-weight: 800;
        }
        .gallery-room-meta {
            margin: 0.18rem 0 0;
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.88);
        }
        .gallery-room-price {
            margin: 0.2rem 0 0;
            font-size: 0.86rem;
            font-weight: 700;
            color: #f9e9ca;
        }
        .gallery-room-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.45rem;
            color: #fff;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .gallery-link:hover .gallery-room-action,
        .gallery-link:focus-visible .gallery-room-action {
            color: #f9e9ca;
        }
        .gallery-link:focus-visible {
            outline: 3px solid #fff;
            outline-offset: -5px;
            border-radius: 0 0 18px 18px;
        }
        .gallery-empty {
            border: 1px dashed var(--line);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.78);
        }
        .gallery-dialog {
            width: min(1000px, calc(100vw - 2rem));
            max-height: calc(100vh - 2rem);
            padding: 0;
            overflow: hidden;
            border: 0;
            border-radius: 18px;
            background: #101827;
            color: #fff;
            box-shadow: 0 28px 80px rgba(0, 0, 0, 0.4);
        }
        .gallery-dialog::backdrop {
            background: rgba(4, 9, 18, 0.82);
            backdrop-filter: blur(4px);
        }
        .gallery-dialog-image {
            display: block;
            width: 100%;
            max-height: calc(100vh - 8rem);
            object-fit: contain;
            background: #080d16;
        }
        .gallery-dialog-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.85rem 1rem;
        }
        .gallery-dialog-close {
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 999px;
            background: transparent;
            color: #fff;
            padding: 0.35rem 0.75rem;
            font-weight: 700;
        }
        @media (max-width: 575.98px) {
            .gallery-image {
                height: 235px;
            }
        }
    </style>
@endpush

@section('content')
    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Visual Tour</p>
            <h1 class="mb-1">Hotel Gallery</h1>
            <p class="text-secondary mb-0">Click any room photo to open that room's full details and booking page.</p>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta">Book a room</a>
    </section>

    @php
        $roomTypes = $rooms->pluck('type')->filter()->unique()->sort()->values();
        $fallbackImage = asset(\App\Models\Room::FALLBACK_IMAGE_PATH);
    @endphp

    @if($roomTypes->count() > 1)
        <nav class="gallery-filters mb-3" aria-label="Filter gallery by room type">
            <button type="button" class="gallery-filter is-active" data-gallery-filter="all" aria-pressed="true">All rooms</button>
            @foreach($roomTypes as $roomType)
                <button type="button" class="gallery-filter" data-gallery-filter="{{ Str::slug($roomType) }}" aria-pressed="false">
                    {{ $roomType }}
                </button>
            @endforeach
        </nav>
    @endif

    <section class="row g-3" id="gallery-grid" aria-live="polite">
        @forelse($rooms as $room)
            <div class="col-md-6 col-lg-4 gallery-item" data-room-type="{{ Str::slug($room->type ?: 'room') }}">
                <article class="gallery-card soft-card">
                    <button
                        type="button"
                        class="gallery-preview"
                        data-gallery-preview
                        data-image="{{ $room->image_url }}"
                        data-title="{{ $room->name }}"
                        aria-label="Preview a larger photo of {{ $room->name }}"
                    >
                        <span class="gallery-zoom-label">View photo</span>
                        <img
                            class="gallery-image"
                            src="{{ $room->image_url }}"
                            data-fallback="{{ $fallbackImage }}"
                            alt="{{ $room->name }}"
                            loading="lazy"
                            decoding="async"
                        >
                    </button>
                    <a href="{{ route('rooms.show', $room) }}" class="gallery-link" aria-label="View details and book {{ $room->name }}">
                        <div class="gallery-details">
                            <p class="gallery-room-name">{{ $room->name }}</p>
                            <p class="gallery-room-meta">
                                {{ $room->type ?? 'Room' }}
                                @if(filled($room->view_type))
                                    &middot; {{ $room->view_type }}
                                @endif
                                &middot; Standard occupancy: {{ $room->capacity }} guests
                            </p>
                            <p class="gallery-room-price">&#8369;{{ \App\Support\Money::display($room->price_per_night) }} / night</p>
                            <span class="gallery-room-action" aria-hidden="true">
                                View room <span>&rarr;</span>
                            </span>
                        </div>
                    </a>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="gallery-empty p-4 text-center">
                    <p class="mb-2">No rooms are available for gallery preview yet.</p>
                    <a href="{{ route('rooms.index') }}" class="btn btn-ta btn-sm">Browse Rooms</a>
                </div>
            </div>
        @endforelse
    </section>

    <p id="gallery-no-results" class="gallery-empty p-4 text-center mt-3 d-none">
        No rooms match this gallery filter.
    </p>

    <dialog class="gallery-dialog" id="gallery-dialog" aria-labelledby="gallery-dialog-title">
        <img class="gallery-dialog-image" id="gallery-dialog-image" src="" alt="">
        <div class="gallery-dialog-footer">
            <strong id="gallery-dialog-title"></strong>
            <button type="button" class="gallery-dialog-close" data-gallery-close>Close</button>
        </div>
    </dialog>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = [...document.querySelectorAll('[data-gallery-filter]')];
            const items = [...document.querySelectorAll('.gallery-item')];
            const noResults = document.getElementById('gallery-no-results');
            const dialog = document.getElementById('gallery-dialog');
            const dialogImage = document.getElementById('gallery-dialog-image');
            const dialogTitle = document.getElementById('gallery-dialog-title');

            document.querySelectorAll('.gallery-image').forEach((image) => {
                image.addEventListener('error', () => {
                    if (image.src !== image.dataset.fallback) {
                        image.src = image.dataset.fallback;
                    }
                });
            });

            filters.forEach((button) => {
                button.addEventListener('click', () => {
                    const selectedType = button.dataset.galleryFilter;
                    let visibleCount = 0;

                    filters.forEach((filter) => {
                        const active = filter === button;
                        filter.classList.toggle('is-active', active);
                        filter.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });

                    items.forEach((item) => {
                        const visible = selectedType === 'all' || item.dataset.roomType === selectedType;
                        item.classList.toggle('d-none', !visible);
                        visibleCount += visible ? 1 : 0;
                    });

                    noResults?.classList.toggle('d-none', visibleCount > 0);
                });
            });

            document.querySelectorAll('[data-gallery-preview]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!dialog || typeof dialog.showModal !== 'function') {
                        return;
                    }

                    const cardImage = button.querySelector('img');
                    dialogImage.src = cardImage?.currentSrc || button.dataset.image;
                    dialogImage.alt = button.dataset.title;
                    dialogTitle.textContent = button.dataset.title;
                    dialog.showModal();
                });
            });

            document.querySelector('[data-gallery-close]')?.addEventListener('click', () => dialog?.close());
            dialog?.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    dialog.close();
                }
            });
        });
    </script>
@endpush
