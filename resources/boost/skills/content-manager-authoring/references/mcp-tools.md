# /mcp/content tool reference

`*` marks a required argument. Every tool that targets a post or page takes `slug` **or** `id`. Write tools refuse a read-only key with "This API key is read-only…". Every write is logged with the key's name. Canonical long form: `MCP.md` in the package root.

## Resources and prompt

| URI / name | Returns |
|---|---|
| `content://guidelines` | Brand, description, audience, spelling, house rules, character limits for `title`, `meta_title`, `meta_description`, `intro` (config `editorial.*`). |
| `content://taxonomy` | Every category and tag slug with post counts. |
| `draft-post` prompt | Guidelines plus `topic`*, `keyword`, `angle`, as a writing brief. |

## Posts

| Tool | Arguments | Notes |
|---|---|---|
| `list-posts` | `status` (any·published·draft·trashed), `search`, `category`, `tag`, `per_page` (1–100, default 25), `page` | Returns `total`, `page`, `last_page`, `posts[]`. Newest first. |
| `get-post` | `slug`/`id`, `with_content` (default true) | Includes soft-deleted posts. |
| `create-post` | `title`*, `content`*, `slug`, `subtitle`, `intro`, `meta_title`, `meta_description`, `meta_keywords`, `youtube_embed`, `is_promoted`, `published_at`, `category_slugs[]`, `tag_slugs[]` | Slug derived from the title if omitted. No `published_at` means draft. Refuses a slug held by any post, soft-deleted included. |
| `update-post` | `slug`/`id`, any field above, `new_slug` | Patches only the fields passed. `category_slugs`/`tag_slugs` replace the whole set. Returns `updated_fields`. |
| `publish-post` | `slug`/`id`, `published_at` | Defaults to now; a future date schedules. |
| `unpublish-post` | `slug`/`id` | Sets `published_at` to null; row and URL stay. |
| `delete-post` | `slug`/`id`, `force` (default false) | Soft delete keeps the slug reserved. `force: true` is permanent. |
| `restore-post` | `slug`/`id` | Comes back with its old publication state. |
| `set-post-image` | `slug`/`id`, `url` (http/https) or `base64`, `filename`, `collection` (main·small) | Fetched server-side; replaces the slot's image. |

A post in a response carries `id`, `slug`, `title`, `subtitle`, `intro`, `status` (published·draft·trashed), `published_at`, `is_promoted`, `url`, `categories`, `tags`, `meta_*`, `youtube_embed`, `word_count`, `reading_minutes`, `image_url`, `content`. Null fields are omitted. `create-post`/`update-post` add `unknown_categories` when a category slug didn't match.

## Taxonomy

| Tool | Arguments | Notes |
|---|---|---|
| `list-categories` | none | With `published_posts` counts. |
| `create-category` | `name`*, `slug`, `content` | Returns `created: false` if the slug exists. Ask a human first. |
| `list-tags` | `type`, `search` | With `published_posts` counts. |
| `create-tag` | `name`*, `slug`, `type` (default "blog"), `content`, `meta_title`, `meta_description`, `meta_keywords` | Only needed to give a tag its own description/meta; `tag_slugs` creates missing tags anyway. |

## Pages, FAQ, chunks

| Tool | Arguments | Notes |
|---|---|---|
| `list-pages` | `status` (any·published·draft) | |
| `get-page` | `slug`/`id` | Full markdown body. |
| `upsert-page` | `slug`*, `title`, `content`, `meta_description`, `sort_order`, `published_at`, `publish` | Creating needs `title` and `content`. A new page stays a draft unless `publish`/`published_at`. The host must whitelist a new slug before it's routable. |
| `list-faqs` | `status` (any·published·draft), `group` | Display order. |
| `upsert-faq` | `id` (omit to create), `question`, `answer` (HTML allowed), `group`, `sort_order`, `publish` | **A new entry publishes immediately** unless `publish: false`. `group` ties it to a page (e.g. `home`, `pricing`). |
| `delete-faq` | `id`* | Permanent; FAQs have no soft delete. |
| `list-chunks` | `with_content` (default false) | |
| `upsert-chunk` | `code`*, `name`, `content`, `is_published` | Live on save. A new chunk is published by default. Unpublished chunks render empty. |
| `content-stats` | none | Post counts (published, drafts, trashed), categories, tags, pages, FAQs, chunks, latest post. |

## Errors

| Message / status | Meaning |
|---|---|
| "This API key is read-only…" | Key lacks `write`. Don't retry; ask the operator. |
| "No post matches …" / "No page matches…" / "No FAQ entry with id N." | Wrong identifier; list first. |
| "The slug … is already taken by post #N (soft-deleted)" | Restore it or choose another slug. |
| "A new page needs both a title and a markdown body" / "A new FAQ entry needs both a question and an answer." | Missing create fields. |
| "Post … is not deleted, so there is nothing to restore." | `restore-post` on a live post. |
| "The image could not be stored: …" | URL unreachable or not an image. |
| HTTP 401 / 404 / 429 | Wrong key / no key configured on the site / rate limit (`CONTENT_MCP_THROTTLE`, default 60 per minute). |

`tools/list` is paginated (15 per page); page through for all 22 tools.
