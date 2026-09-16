{{-- @param \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $posts --}}
<div class="cm-grid">
    @forelse ($posts as $post)
        <article class="cm-card">
            @if ($image = $post->getFirstMediaUrl('main', 'preview'))
                <a href="{{ route(config('content-manager.routes.names.show'), $post->slug) }}">
                    <img src="{{ $image }}" alt="{{ $post->title }}" loading="lazy" style="width:100%;border-radius:.5rem;margin-bottom:.75rem">
                </a>
            @endif

            <h2 style="margin:0 0 .5rem;font-size:1.125rem">
                <a href="{{ route(config('content-manager.routes.names.show'), $post->slug) }}">{{ $post->title }}</a>
            </h2>

            @if ($post->intro)
                <p class="cm-muted" style="margin:0 0 .75rem">{{ \Illuminate\Support\Str::limit($post->intro, 140) }}</p>
            @endif

            <p class="cm-muted" style="margin:0">
                <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j M Y') }}</time>
                · {{ $post->readingTime() }} min read
            </p>
        </article>
    @empty
        <p class="cm-muted">Nothing published here yet.</p>
    @endforelse
</div>

@if (method_exists($posts, 'links'))
    <div style="margin-top:2rem">{{ $posts->links() }}</div>
@endif
