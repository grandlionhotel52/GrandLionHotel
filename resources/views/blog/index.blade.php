@extends('layouts.app')

@section('title', 'Blog')

@push('head')
    <style>
        .blog-header {
            position: relative;
            border-bottom: 1px solid var(--line);
            text-align: left;
        }
        .blog-header-copy {
            max-width: 820px;
            margin-right: auto;
        }
        .blog-header-mark {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            color: #80612f;
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }
        .blog-header-mark::before {
            content: '';
            width: 2.2rem;
            height: 2px;
            background: #b89254;
        }
        .blog-header h1 {
            max-width: 780px;
            color: #182538;
            font-size: clamp(2.25rem, 5vw, 4.25rem);
            letter-spacing: -.045em;
            line-height: .98;
        }
        .blog-masthead-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: .7rem 1.25rem;
            margin-top: 1.5rem;
            color: #6b7280;
            font-size: .82rem;
            font-weight: 700;
        }
        .blog-masthead-meta span {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
        }
        .blog-masthead-meta i { color: #9a7438; }
        @media (max-width: 767.98px) {
            .blog-header h1 { line-height: 1.05; }
        }
        .blog-feature { border-radius: 8px; }
        .blog-feature-image { min-height: 430px; }
        .blog-card-image { height: 230px; transition: transform .35s ease; }
        .blog-card:hover .blog-card-image { transform: scale(1.035); }
        .blog-card-meta { color: #7b6541; font-size: .78rem; font-weight: 700; }
        .blog-topic {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid var(--line);
            border-radius: 6px;
            background: #fff;
            padding: .42rem .78rem;
            color: #435066;
            font-size: .8rem;
            font-weight: 700;
        }
        .blog-topic[aria-pressed="true"] {
            border-color: #9a7438;
            background: #182538;
            color: #fff;
        }
        .blog-tools {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            padding-block: 1.25rem;
            border-block: 1px solid var(--line);
        }
        .blog-search { width: min(100%, 320px); }
        .blog-search .input-group-text { background: #fff; border-right: 0; }
        .blog-search .form-control { border-left: 0; padding-left: 0; }
        .blog-results-count { color: #647084; font-size: .86rem; font-weight: 700; }
        .blog-card { position: relative; border-radius: 8px; }
        .blog-card:focus-within { outline: 3px solid rgba(184, 146, 84, .35); outline-offset: 3px; }
        .blog-card h3 a { color: #182538; text-decoration: none; }
        .blog-card h3 a:hover { color: #80612f; }
        .blog-empty { display: none; border: 1px dashed #cdbb9d; border-radius: 8px; }
        @media (max-width: 767.98px) {
            .blog-tools { align-items: stretch; flex-direction: column; }
            .blog-search { width: 100%; }
            .blog-feature-image { min-height: 280px; }
        }
    </style>
@endpush

@section('content')
    <section class="blog-header py-4 py-lg-5 mb-4">
        <div class="blog-header-copy">
            <p class="blog-header-mark mb-3">The Grand Lion Journal</p>
            <h1 class="mb-3">Ideas and inspiration for your next stay.</h1>
            <p class="lead text-secondary mb-0">Practical hotel advice, thoughtful travel guides, and simple ideas for a smoother journey from booking to check-out.</p>
            <div class="blog-masthead-meta" aria-label="Journal highlights">
                <span><i class="bi bi-journal-text"></i>{{ $latest->count() + ($featured ? 1 : 0) }} curated guides</span>
                <span><i class="bi bi-clock"></i>Quick, useful reads</span>
                <span><i class="bi bi-stars"></i>Travel thoughtfully</span>
            </div>
        </div>
    </section>

    @if($featured)
        <section class="blog-feature soft-card overflow-hidden mb-5">
            <div class="row g-0">
                <div class="col-lg-7 p-4 p-lg-5 d-flex flex-column justify-content-center">
                    <p class="ta-eyebrow mb-2">Featured Article</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="ta-chip">{{ $featured['category'] }}</span>
                        <span class="ta-chip">{{ $featured['date'] }}</span>
                        <span class="ta-chip">{{ $featured['read_time'] }}</span>
                    </div>
                    <h2 class="display-6 mb-3">{{ $featured['title'] }}</h2>
                    <p class="text-secondary mb-4">{{ $featured['excerpt'] }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('blog.show', $featured['slug']) }}" class="btn btn-ta">Read article <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                        <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline">Book a stay</a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <img src="{{ $featured['image'] }}" alt="{{ $featured['title'] }}" class="blog-feature-image w-100 h-100 object-cover">
                </div>
            </div>
        </section>
    @endif

    <section class="blog-tools mb-4" aria-label="Filter journal articles">
        <div>
            <p class="ta-eyebrow mb-2">Explore By Topic</p>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Article topics">
                <button type="button" class="blog-topic" data-topic="all" aria-pressed="true">All stories</button>
                @foreach($topics as $topic)
                    <button type="button" class="blog-topic" data-topic="{{ Illuminate\Support\Str::slug($topic) }}" aria-pressed="false">{{ $topic }}</button>
                @endforeach
            </div>
        </div>
        <label class="blog-search">
            <span class="visually-hidden">Search articles</span>
            <span class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control" id="blog-search" placeholder="Search the journal" autocomplete="off">
            </span>
        </label>
    </section>

    <section class="d-flex justify-content-between align-items-end gap-3 mb-3">
        <div><p class="ta-eyebrow mb-1">Latest Posts</p><h2 class="mb-0">Recent reads</h2></div>
        <p class="blog-results-count mb-1" id="blog-results-count" aria-live="polite">{{ $latest->count() }} articles</p>
    </section>

    <section class="row g-4 mb-4">
        @forelse($latest as $post)
            <div class="col-md-6 col-xl-4 blog-post" data-topic="{{ \Illuminate\Support\Str::slug($post['category']) }}" data-search="{{ Str::lower($post['title'].' '.$post['excerpt'].' '.$post['category'].' '.implode(' ', $post['tags'])) }}">
                <article class="blog-card soft-card h-100 result-card overflow-hidden">
                    <div class="overflow-hidden"><img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="blog-card-image w-100 object-cover" loading="lazy"></div>
                    <div class="p-3 p-lg-4 d-flex flex-column h-100">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="ta-chip">{{ $post['category'] }}</span>
                            <span class="ta-chip">{{ $post['read_time'] }}</span>
                        </div>
                        <p class="blog-card-meta mb-2"><i class="bi bi-calendar3 me-1"></i>{{ $post['date'] }} &middot; By {{ $post['author']['name'] }}</p>
                        <h3 class="h4 mb-2"><a href="{{ route('blog.show', $post['slug']) }}" class="stretched-link">{{ $post['title'] }}</a></h3>
                        <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($post['excerpt'], 125) }}</p>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            @foreach($post['tags'] as $tag)
                                <span class="badge text-bg-light border">{{ $tag }}</span>
                            @endforeach
                        </div>
                        <span class="btn btn-ta mt-auto" aria-hidden="true">Read article <i class="bi bi-arrow-right ms-1"></i></span>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="soft-card p-4 text-secondary">More blog articles are being prepared. Please check back soon.</div>
            </div>
        @endforelse
    </section>
    <div class="blog-empty p-4 text-center mb-4" id="blog-empty">
        <i class="bi bi-search fs-3 text-secondary" aria-hidden="true"></i>
        <h3 class="h5 mt-2 mb-1">No matching articles</h3>
        <p class="text-secondary mb-0">Try another search or choose a different topic.</p>
    </div>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Planning A Trip?</p>
            <h2 class="mb-1">Turn reading into your next stay.</h2>
            <p class="text-secondary mb-0">Use these guides, then compare room options that match your travel style.</p>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta">Start booking</a>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const search = document.getElementById('blog-search');
            const posts = [...document.querySelectorAll('.blog-post')];
            const topics = [...document.querySelectorAll('[data-topic]')].filter((item) => item.matches('button'));
            const count = document.getElementById('blog-results-count');
            const empty = document.getElementById('blog-empty');
            let activeTopic = 'all';

            const filterPosts = () => {
                const query = search.value.trim().toLowerCase();
                let visible = 0;
                posts.forEach((post) => {
                    const matchesTopic = activeTopic === 'all' || post.dataset.topic === activeTopic;
                    const matchesSearch = !query || post.dataset.search.includes(query);
                    const show = matchesTopic && matchesSearch;
                    post.hidden = !show;
                    visible += show ? 1 : 0;
                });
                count.textContent = `${visible} ${visible === 1 ? 'article' : 'articles'}`;
                empty.style.display = visible ? 'none' : 'block';
            };

            topics.forEach((button) => button.addEventListener('click', () => {
                activeTopic = button.dataset.topic;
                topics.forEach((topic) => topic.setAttribute('aria-pressed', String(topic === button)));
                filterPosts();
            }));
            search.addEventListener('input', filterPosts);
        })();
    </script>
@endpush
