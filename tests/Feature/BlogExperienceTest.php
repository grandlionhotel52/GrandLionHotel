<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlogExperienceTest extends TestCase
{
    public function test_blog_index_includes_article_discovery_controls(): void
    {
        $this->get(route('blog.index'))
            ->assertSuccessful()
            ->assertSee('Search the journal')
            ->assertSee('All stories')
            ->assertSee('data-topic="booking-strategy"', false)
            ->assertSee('id="blog-results-count"', false)
            ->assertSee('7 articles')
            ->assertSee('How To Plan A Stress-Free Family Hotel Stay')
            ->assertSee('A Smart Packing Guide For Hotel Stays')
            ->assertSee('class="stretched-link"', false)
            ->assertDontSee('Food &amp; Dining', false);
    }

    public function test_blog_article_includes_reading_and_sharing_controls(): void
    {
        $this->get(route('blog.show', 'best-time-to-book-a-city-hotel'))
            ->assertSuccessful()
            ->assertSee('id="article-progress-bar"', false)
            ->assertSee('id="share-article"', false)
            ->assertSee('Lara Mendoza')
            ->assertSee('Quick Takeaways')
            ->assertSee('Your stay-planning checklist');
    }

    public function test_unknown_blog_article_returns_not_found(): void
    {
        $this->get(route('blog.show', 'missing-article'))->assertNotFound();
    }
}
