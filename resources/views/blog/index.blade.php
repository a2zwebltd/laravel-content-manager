@extends(config('content-manager.layout', 'content-manager::layouts.app'))

@section('title', 'Blog · '.config('app.name'))

@section('content')
    <header>
        <h1>Blog</h1>

        <form method="GET" action="{{ route(config('content-manager.routes.names.index')) }}">
            <label class="cm-muted" for="cm-search">Search the archive</label><br>
            <input id="cm-search" type="search" name="q" value="{{ $search }}" placeholder="Search posts…">
            <button type="submit">Search</button>
        </form>

        @if ($search !== '')
            <p class="cm-muted">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('result', $posts->total()) }} for “{{ $search }}”.</p>
        @endif
    </header>

    @include('content-manager::blog.partials.post-grid', ['posts' => $posts])

    @include('content-manager::blog.partials.browse-sidebar')
@endsection
