@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', $page->title.' · '.config('app.name'))
@section('description', (string) $page->meta_description)

@section('content')
    <article class="cm-prose">
        <h1>{{ $page->title }}</h1>

        {!! $page->renderedContent() !!}
    </article>
@endsection
