@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', $category->name.' · '.config('app.name'))

@section('content')
    <header>
        <h1>{{ $category->name }}</h1>
        @if ($category->content)
            <div class="cm-prose">{!! \A2ZWeb\ContentManager\Support\Markdown::render($category->content) !!}</div>
        @endif
    </header>

    @include('content-manager::blog.partials.post-grid', ['posts' => $posts])

    @include('content-manager::blog.partials.browse-sidebar')
@endsection
