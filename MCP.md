# MCP reference

What an AI agent connected to this server can do, and how to do it well.

This is the *usage* reference. Turning the server on is covered in
[INTEGRATION.md §7](INTEGRATION.md#7-the-mcp-server).

Raw URL, for an agent that needs to fetch this file:
`https://raw.githubusercontent.com/a2zwebltd/laravel-content-manager/main/MCP.md`

---

## What this server controls

One website's published content, and nothing else. There is no access to users,
orders, settings or any other part of the host application.

| Content type | What it is |
|---|---|
| Posts | The blog. Markdown body, SEO meta, categories, tags, a featured image, draft/published/scheduled state, soft deletes. |
| Categories | A small, deliberate taxonomy. Editorial structure — do not invent new ones casually. |
| Tags | A large, free-form taxonomy. Created on demand when attached to a post. |
| Pages | Standing CMS pages (about, privacy, terms and the like). |
| FAQ | Question/answer entries, ordered, optionally grouped per page. |
| Chunks | Named snippets templates pull in by code — banner copy, a CTA line. Edits go live immediately. |

**Changes are live.** Publishing a post puts it on a public website, and
updating a chunk changes whatever page renders it, right away. There is no
staging step and no review queue. When in doubt, leave `published_at` empty:
drafts are safe, visible in the admin, and a human can publish them.

## Connecting

```bash
claude mcp add --transport http <name> https://<site>/mcp/content \
  --header "Authorization: Bearer <your-key>"
```

Keys come from the site operator. A key carries either `read` or `read + write`;
a read-only key that calls a write tool gets a clear error back rather than a
failure. If the endpoint 404s, no key is configured on that site and the route
does not exist — that is the operator's side to fix.

## Start here, every session

1. Read `content://guidelines` — the site's own house style: voice, audience,
   the rules a post must follow, and character limits for titles and meta
   fields. It is written by the site owner and it is not boilerplate.
2. Read `content://taxonomy` — the category and tag slugs that already exist,
   with post counts. Use these rather than coining near-duplicates.
3. Call `content-stats` if you need a sense of the archive's size and what was
   published most recently.

The `draft-post` prompt combines the guidelines with a topic, keyword and angle
you supply, and returns a brief ready to write from.

## Tools

`*` marks a required argument. Every tool that identifies a post or page takes
either `slug` or `id` — pass one.

### Posts

| Tool | Arguments |
|---|---|
| `list-posts` | `status` (any·published·draft·trashed), `search`, `category`, `tag`, `per_page` (1–100, default 25), `page` |
| `get-post` | `slug` / `id`, `with_content` (default true) |
| `create-post` | `title`*, `content`*, `slug`, `subtitle`, `intro`, `meta_title`, `meta_description`, `meta_keywords`, `youtube_embed`, `is_promoted`, `published_at`, `category_slugs[]`, `tag_slugs[]` |
| `update-post` | `slug` / `id`, plus any field above; `new_slug` renames |
| `publish-post` | `slug` / `id`, `published_at` (future date schedules it) |
| `unpublish-post` | `slug` / `id` |
| `delete-post` | `slug` / `id`, `force` (default false) |
| `restore-post` | `slug` / `id` |
| `set-post-image` | `slug` / `id`, `url` or `base64`, `filename`, `collection` (main·small) |

### Taxonomy

| Tool | Arguments |
|---|---|
| `list-categories` | — |
| `create-category` | `name`*, `slug`, `content` |
| `list-tags` | `type`, `search` |
| `create-tag` | `name`*, `slug`, `type`, `content`, `meta_title`, `meta_description`, `meta_keywords` |

### Pages, FAQ, chunks

| Tool | Arguments |
|---|---|
| `list-pages` | `status` (any·published·draft) |
| `get-page` | `slug` / `id` |
| `upsert-page` | `slug`*, `title`, `content`, `meta_description`, `sort_order`, `published_at`, `publish` |
| `list-faqs` | `status`, `group` |
| `upsert-faq` | `id` (omit to create), `question`, `answer`, `group`, `sort_order`, `publish` |
| `delete-faq` | `id`* |
| `list-chunks` | `with_content` (default false) |
| `upsert-chunk` | `code`*, `name`, `content`, `is_published` |
| `content-stats` | — |

`tools/list` is paginated, so a client that shows 15 tools at first is behaving
correctly; page through for the rest.

## Writing a post, end to end

```
1. read content://guidelines          → voice, rules, field limits
2. read content://taxonomy            → which categories and tags exist
3. list-posts search:"<topic>"        → has this been covered already?
4. create-post                        → markdown body, no published_at
5. set-post-image                     → optional featured image
6. get-post slug:"…"                  → read back what was stored
7. publish-post                       → only when a human asked you to publish
```

What the body should look like: markdown, GitHub-flavoured — `##` and `###`
headings, lists, tables and fenced code all render. **Do not repeat the title
as an H1**; the template renders it. `intro` is the teaser shown in listings,
`meta_title` and `meta_description` are the search-result snippet, and the
guidelines resource states this site's character limits for each.

## Conventions that keep an archive coherent

- **Categories are editorial, tags are free.** `create-post` and `update-post`
  create missing tags automatically, but an unmatched category slug is reported
  back in `unknown_categories` rather than invented. Ask a human before adding
  a category.
- **Slugs are URLs.** A slug derived from the title is fine on creation.
  Changing one later with `new_slug` breaks every existing link to that post —
  do it only when explicitly asked.
- **Prefer `update-post` over delete-and-recreate.** It patches only the fields
  you pass, so everything you leave out keeps its value.
- **Never fabricate facts, statistics, quotes or testimonials.** This content is
  published under someone's name on their own domain.
- **`is_promoted` is the site owner's editorial call**, not a way to give your
  own post more prominence.

## Things that will surprise you otherwise

- **Soft-deleted posts keep their slug.** `delete-post` hides a post but the
  unique index still holds its slug, so `create-post` with that slug is refused
  and tells you so. Use `restore-post`, or pass a different slug.
- **`force: true` is permanent.** There is no undo, and no backup on this side.
- **A future `published_at` schedules a post** rather than publishing it. That
  is the intended way to queue something up.
- **Pages are usually legal or policy text.** Editing privacy or terms wording
  has consequences beyond the website; do it only on explicit instruction.
- **Chunks render live on real pages.** A typo in a chunk is a typo on the site
  a second later.
- **Deleting an FAQ entry is permanent** — FAQ entries are not soft-deleted.
- **Images are fetched server-side** from the URL you pass, which must be
  `http(s)`, or supplied as base64. Setting an image replaces whatever was in
  that slot.
- **Every write is logged** with the name of the key that made it. Work as
  though someone will read that log, because they will.

## Errors

Tool errors come back as readable text, not exceptions. The ones worth knowing:

| Message | Meaning |
|---|---|
| "This API key is read-only…" | Your key lacks `write`. Ask the operator; do not retry. |
| "No post matches …" | Wrong slug or id. `list-posts` to find the right one. |
| "The slug … is already taken by post #N (soft-deleted)" | Restore it, or choose another slug. |
| "A new page needs both a title and a markdown body" | `upsert-page` creating a page needs both. |
| "The image could not be stored: …" | The URL was unreachable or was not an image. |
| HTTP 401 | Key missing or wrong. |
| HTTP 404 on the endpoint | No key configured on that site; the route does not exist. |
| HTTP 429 | Rate limited; back off and retry. |

## Reading before writing

The archive is someone's work. Before adding to it: search for existing coverage
of the topic, read one or two published posts with `get-post` to hear the voice,
and check the taxonomy. A post that duplicates an existing one, or lands in a
new near-duplicate category, costs a human more time than it saves.
