@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', $post->meta_title ?: $post->title)
@section('description', (string) ($post->meta_description ?: $post->intro))

@push('head')
    @php
        $jsonLd = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->intro ?? $post->subtitle,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'image' => $post->getFirstMediaUrl('main', 'full_size') ?: null,
            'mainEntityOfPage' => url()->current(),
        ]);
    @endphp
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    <article class="cm-prose">
        <h1>{{ $post->title }}</h1>

        @if ($post->subtitle)
            <p class="cm-muted" style="font-size:1.125rem">{{ $post->subtitle }}</p>
        @endif

        <p class="cm-muted">
            <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->format('j F Y') }}</time>
            · {{ $post->readingTime() }} min read
        </p>

        @if ($image = $post->getFirstMediaUrl('main', 'full_size'))
            <img src="{{ $image }}" alt="{{ $post->title }}" style="width:100%;border-radius:.75rem">
        @endif

        {!! $post->renderedContent() !!}

        @if ($post->tags->isNotEmpty())
            <p>
                @foreach ($post->tags as $tag)
                    <a class="cm-pill" href="{{ route(config('content-manager.routes.names.tag'), $tag->slug) }}">{{ $tag->name }}</a>
                @endforeach
            </p>
        @endif
    </article>

    <nav style="margin-top:2rem;display:flex;gap:1rem;justify-content:space-between">
        @if ($previousPost)
            <a href="{{ route(config('content-manager.routes.names.show'), $previousPost->slug) }}">← {{ $previousPost->title }}</a>
        @endif

        @if ($nextPost)
            <a href="{{ route(config('content-manager.routes.names.show'), $nextPost->slug) }}" style="margin-left:auto;text-align:right">{{ $nextPost->title }} →</a>
        @endif
    </nav>

    @if ($related->isNotEmpty())
        <section style="margin-top:3rem">
            <h2 style="font-size:1.125rem">Read next</h2>
            @include('content-manager::blog.partials.post-grid', ['posts' => $related])
        </section>
    @endif
@endsection
