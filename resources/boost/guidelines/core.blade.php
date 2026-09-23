## Laravel Content Manager (a2zwebltd/laravel-content-manager)

Blog posts, categories, tags, CMS pages, FAQs and content chunks, with Nova resources and a remote MCP server at `/mcp/content`.

@verbatim
- The models are `A2ZWeb\ContentManager\Models\{BlogPost,ContentCategory,Tag,Page,Faq,Chunk}`, resolved through `config('content-manager.models')` (`A2ZWeb\ContentManager\Support\Models::blogPost()` and friends). Never recreate `App\Models\BlogPost` and its siblings. To add behaviour, subclass the package model and register the subclass in that map.
- Write content only through Eloquent, one record and one `save()`/`create()`/`delete()` at a time. `DB::table()`, raw SQL and query-builder `->update()`/`->delete()` fire no `ContentSaved`/`ContentDeleted`, so the response cache, the `events.on_change` hooks and the chunk cache all stay stale.
- Pivot and media writes don't dirty the post. After a direct `$post->tags()->sync()`, `categories()->sync()` or `addMedia…()`, call `$post->announceContentChange()`.
- `taggables.taggable_type` and `media.model_type` store morph aliases (`blog_post`, `content_page`), never class names. A raw insert must write the alias.
- Package controllers take string route params: `/blog/{blogPost}`, never `{blogPost:slug}`. Declare `/blog/tags` before `/blog/{blogPost}`. The `/{page}` catch-all goes last behind a slug whitelist, so a new CMS page needs its slug added to that whitelist.
- `events.on_change`, `ai.topic_provider` and `ai.before_call` take invokable class-strings, never closures, so `config:cache` keeps working.
- The site's `/mcp/content` server (bearer key) is not Boost's MCP. To write content through it, use the `content-manager-authoring` skill.
@endverbatim

For integration details, use the `content-manager-integration` skill.
