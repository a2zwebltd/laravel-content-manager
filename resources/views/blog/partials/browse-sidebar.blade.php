{{-- @param \Illuminate\Support\Collection $categories, $tags; @param int $totalPosts --}}
<aside style="margin-top:3rem">
    @if ($categories->isNotEmpty())
        <h2 style="font-size:1rem">Categories</h2>
        <p>
            @foreach ($categories as $category)
                <a class="cm-pill" href="{{ route(config('content-manager.routes.names.category'), $category->slug) }}">
                    {{ $category->name }} ({{ $category->blog_posts_count }})
                </a>
            @endforeach
        </p>
    @endif

    @if ($tags->isNotEmpty())
        <h2 style="font-size:1rem">Tags</h2>
        <p>
            @foreach ($tags->take(30) as $tag)
                <a class="cm-pill" href="{{ route(config('content-manager.routes.names.tag'), $tag->slug) }}">
                    {{ $tag->name }}
                </a>
            @endforeach
            <a class="cm-pill" href="{{ route(config('content-manager.routes.names.tags')) }}">All tags →</a>
        </p>
    @endif

    <p class="cm-muted">{{ $totalPosts }} {{ \Illuminate\Support\Str::plural('post', $totalPosts) }} in the archive.</p>
</aside>
