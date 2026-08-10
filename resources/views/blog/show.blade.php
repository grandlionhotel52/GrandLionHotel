@extends('layouts.app')

@section('title', $post['title'])

@push('head')
    <style>
        .article-progress { position: fixed; inset: 0 0 auto; z-index: 1100; height: 3px; }
        .article-progress-bar { width: 0; height: 100%; background: #b89254; }
        .article-hero { position: relative; border-radius: 8px; }
        .article-hero-image { height: clamp(300px, 48vw, 540px); }
        .article-title-wrap { max-width: 900px; }
        .article-author { display: flex; align-items: center; gap: .75rem; color: #647084; font-size: .88rem; }
        .article-author-mark { display: inline-grid; place-items: center; width: 2.5rem; height: 2.5rem; border-radius: 50%; background: #182538; color: #fff; font-weight: 800; }
        .article-body { max-width: 820px; font-size: 1.08rem; line-height: 1.85; }
        .article-body-section { scroll-margin-top: 100px; }
        .article-body-section + .article-body-section {
            border-top: 1px solid var(--line);
            margin-top: 2rem;
            padding-top: 2rem;
        }
        .article-section-number {
            display: inline-grid;
            place-items: center;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 50%;
            background: #f2e6d3;
            color: #74552a;
            font-size: .8rem;
            font-weight: 800;
        }
        .article-toc {
            position: sticky;
            top: 94px;
            border-left: 2px solid #dbc49e;
            padding-left: 1rem;
        }
        .article-toc a {
            display: block;
            color: #647084;
            text-decoration: none;
            padding: .35rem 0;
            font-size: .86rem;
        }
        .article-toc a:hover { color: #76572b; }
        .article-takeaway {
            border-radius: 8px;
            background: #fbf7f0;
            border: 1px solid #e5d4b8;
        }
        .article-takeaway li::marker { color: #a77d3e; }
        .article-note {
            border-left: 4px solid #b89254;
            border-radius: 0 8px 8px 0;
            background: #f7f3ec;
        }
        .planning-list { list-style: none; padding: 0; margin: 0; counter-reset: planning; }
        .planning-list li {
            counter-increment: planning;
            display: grid;
            grid-template-columns: 2.35rem 1fr;
            gap: .8rem;
            padding-block: 1rem;
            border-top: 1px solid var(--line);
        }
        .planning-list li::before {
            content: counter(planning);
            display: grid;
            place-items: center;
            width: 2.1rem;
            height: 2.1rem;
            border: 1px solid #cdbb9d;
            border-radius: 50%;
            color: #76572b;
            font-size: .8rem;
            font-weight: 800;
        }
        @media (max-width: 991.98px) {
            .article-toc { position: static; border-left: 0; padding-left: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="article-progress" aria-hidden="true"><div class="article-progress-bar" id="article-progress-bar"></div></div>
    <section class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <a href="{{ route('blog.index') }}" class="ta-chip text-decoration-none">
            <i class="bi bi-arrow-left-short"></i>
            Back to blog
        </a>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="ta-chip">{{ $post['category'] }}</span>
            <span class="ta-chip">{{ $post['read_time'] }}</span>
            <span class="ta-chip">{{ $post['date'] }}</span>
            <button type="button" class="btn btn-sm btn-ta-outline" id="share-article" aria-label="Share this article"><i class="bi bi-share" aria-hidden="true"></i> <span class="d-none d-sm-inline">Share</span></button>
        </div>
    </section>

    <article class="article-hero soft-card overflow-hidden mb-4">
        <img src="{{ $post['image'] }}" alt="{{ $post['title'] }}" class="article-hero-image w-100 object-cover">
        <div class="p-4 p-lg-5">
            <div class="article-title-wrap">
                <h1 class="display-5 mb-3">{{ $post['title'] }}</h1>
                <p class="lead text-secondary mb-4">{{ $post['intro'] }}</p>
                <div class="article-author">
                    <span class="article-author-mark" aria-hidden="true">{{ collect(explode(' ', $post['author']['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>
                    <span><strong class="d-block text-dark">{{ $post['author']['name'] }}</strong>{{ $post['author']['role'] }}</span>
                </div>
            </div>
        </div>
    </article>

    <div class="row g-4 align-items-start mb-4">
        <aside class="col-lg-3">
            <div class="article-toc">
                <p class="ta-eyebrow mb-2">In This Guide</p>
                <a href="#takeaways">Key takeaways</a>
                @foreach($post['sections'] as $index => $section)
                    <a href="#section-{{ $index + 1 }}">{{ $index + 1 }}. {{ $section['heading'] }}</a>
                @endforeach
                <a href="#final-thoughts">Final thoughts</a>
            </div>
        </aside>
        <div class="col-lg-9">
            <article class="article-body mx-auto">
                <section id="takeaways" class="article-takeaway p-4 mb-5 article-body-section">
                    <p class="ta-eyebrow mb-2">Quick Takeaways</p>
                    <h2 class="h3 mb-3">What To Remember</h2>
                    <ul class="mb-0 text-secondary">
                        @foreach($post['highlights'] as $highlight)
                            <li class="mb-2">{{ $highlight }}</li>
                        @endforeach
                    </ul>
                </section>

                @foreach($post['sections'] as $index => $section)
                    <section id="section-{{ $index + 1 }}" class="article-body-section">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="article-section-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            <h2 class="h3 mb-0">{{ $section['heading'] }}</h2>
                        </div>
                        <p class="text-secondary mb-0">{{ $section['body'] }}</p>
                    </section>
                @endforeach

                <section id="final-thoughts" class="article-body-section">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="article-section-number"><i class="bi bi-check2" aria-hidden="true"></i></span>
                        <h2 class="h3 mb-0">Final Thoughts</h2>
                    </div>
                    <p class="text-secondary mb-0">{{ $post['conclusion'] }}</p>
                </section>

                <aside class="article-note p-4 mt-5">
                    <p class="ta-eyebrow mb-1">Grand Lion Tip</p>
                    <p class="mb-0 text-secondary">Save your preferred dates and room requirements before comparing options. A clear checklist makes it easier to judge the total value, not just the nightly rate.</p>
                </aside>
            </article>
        </div>
    </div>

    <section class="py-4 py-lg-5 border-top border-bottom mb-4" aria-labelledby="planning-checklist-title">
        <div class="row g-4 align-items-start">
            <div class="col-lg-4">
                <p class="ta-eyebrow mb-2">Put It Into Practice</p>
                <h2 class="h3 mb-2" id="planning-checklist-title">Your stay-planning checklist</h2>
                <p class="text-secondary mb-0">Use this quick sequence to turn the article’s advice into a reservation that fits your trip.</p>
            </div>
            <div class="col-lg-8">
                <ol class="planning-list">
                    <li><div><strong class="d-block mb-1">Set your priorities</strong><span class="text-secondary">Write down your dates, budget, guests, and the two amenities that matter most.</span></div></li>
                    <li><div><strong class="d-block mb-1">Compare the complete stay</strong><span class="text-secondary">Review room size, inclusions, policies, and location alongside the nightly rate.</span></div></li>
                    <li><div><strong class="d-block mb-1">Confirm the details</strong><span class="text-secondary">Check arrival times, bed configuration, payment terms, and any special requests.</span></div></li>
                    <li><div><strong class="d-block mb-1">Keep essentials together</strong><span class="text-secondary">Save your confirmation, hotel contact, identification, and travel schedule in one place.</span></div></li>
                </ol>
            </div>
        </div>
    </section>

    <section class="soft-card p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <p class="ta-eyebrow mb-1">Filed Under</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($post['tags'] as $tag)
                        <span class="ta-chip">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-ta">Find rooms</a>
        </div>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h3 mb-0">Related Articles</h2>
            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-ta-outline">View all posts</a>
        </div>
        <div class="row g-3 mb-4">
            @foreach($related as $item)
                <div class="col-md-6">
                    <article class="soft-card h-100 result-card overflow-hidden position-relative">
                        <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" class="w-100 object-cover" style="height: 190px;">
                        <div class="p-4 d-flex flex-column h-100">
                            <p class="ta-eyebrow mb-2">{{ $item['category'] }} &middot; {{ $item['read_time'] }}</p>
                            <h3 class="h4 mb-2"><a href="{{ route('blog.show', $item['slug']) }}" class="stretched-link text-dark text-decoration-none">{{ $item['title'] }}</a></h3>
                            <p class="text-secondary mb-3">{{ \Illuminate\Support\Str::limit($item['excerpt'], 120) }}</p>
                            <span class="btn btn-ta mt-auto" aria-hidden="true">Read article <i class="bi bi-arrow-right ms-1"></i></span>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Ready To Plan Your Stay?</p>
            <h2 class="mb-1">Apply these tips on your next booking.</h2>
            <p class="text-secondary mb-0">Compare available rooms and confirm your reservation in minutes.</p>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta">Start booking</a>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const progress = document.getElementById('article-progress-bar');
            const share = document.getElementById('share-article');
            const updateProgress = () => {
                const height = document.documentElement.scrollHeight - window.innerHeight;
                progress.style.width = `${height > 0 ? Math.min(100, (window.scrollY / height) * 100) : 0}%`;
            };
            window.addEventListener('scroll', updateProgress, { passive: true });
            updateProgress();
            share.addEventListener('click', async () => {
                try {
                    if (navigator.share) {
                        await navigator.share({ title: document.title, url: window.location.href });
                    } else {
                        await navigator.clipboard.writeText(window.location.href);
                        const label = share.querySelector('span');
                        label.textContent = 'Copied';
                        setTimeout(() => label.textContent = 'Share', 1800);
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') window.prompt('Copy this article link:', window.location.href);
                }
            });
        })();
    </script>
@endpush
