@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', 'All tags · '.config('app.name'))

@section('content')
    <h1>All tags</h1>

    @forelse ($groupedTags as $letter => $tags)
        <section>
            <h2 style="font-size:1rem">{{ $letter }}</h2>
            <p>
                @foreach ($tags as $tag)
                    <a class="cm-pill" href="{{ route(config('content-manager.routes.names.tag'), $tag->slug) }}">
                        {{ $tag->name }} ({{ $tag->blog_posts_count }})
                    </a>
                @endforeach
            </p>
        </section>
    @empty
        <p class="cm-muted">No tags in use yet.</p>
    @endforelse
@endsection
