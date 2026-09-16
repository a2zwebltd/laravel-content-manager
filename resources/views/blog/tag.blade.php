@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', $tag->name.' · '.config('app.name'))

@section('content')
    <header>
        <h1>Posts tagged “{{ $tag->name }}”</h1>
        @if ($tag->content)
            <div class="cm-prose">{!! \A2ZWeb\ContentManager\Support\Markdown::render($tag->content) !!}</div>
        @endif
    </header>

    @include('content-manager::blog.partials.post-grid', ['posts' => $posts])

    @include('content-manager::blog.partials.browse-sidebar')
@endsection
