{{--
    The package's own shell, used when the host has not pointed
    `content-manager.layout` at one of its own layouts. Deliberately plain: a
    host with Tailwind gets a tidy page, a host without one still gets
    readable, semantic HTML.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    @stack('head')
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; line-height: 1.6; }
        .cm-wrap { max-width: 72rem; margin: 0 auto; padding: 2rem 1rem; }
        .cm-prose { max-width: 42rem; }
        .cm-prose img { max-width: 100%; height: auto; }
        .cm-prose pre { overflow-x: auto; padding: 1rem; background: rgba(127,127,127,.12); border-radius: .5rem; }
        .cm-prose table { border-collapse: collapse; width: 100%; }
        .cm-prose td, .cm-prose th { border: 1px solid rgba(127,127,127,.3); padding: .5rem; }
        .cm-grid { display: grid; gap: 1.5rem; grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr)); }
        .cm-card { border: 1px solid rgba(127,127,127,.3); border-radius: .75rem; padding: 1.25rem; }
        .cm-muted { opacity: .7; font-size: .875rem; }
        .cm-pill { display: inline-block; border: 1px solid rgba(127,127,127,.3); border-radius: 999px; padding: .125rem .625rem; margin: 0 .25rem .25rem 0; font-size: .8125rem; text-decoration: none; }
    </style>
</head>
<body>
    <div class="cm-wrap">
        @yield('content')
    </div>
</body>
</html>
