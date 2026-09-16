<?php

declare(strict_types=1);

namespace A2ZWeb\ContentManager\Concerns;

/**
 * Drains a streamed agent response into the same shape a blocking prompt()
 * would return: the full text plus token usage.
 *
 * Long-form generation is streamed rather than awaited because a request that
 * produces no bytes for a minute gets its idle TLS connection dropped
 * ("SSL_read: unexpected eof", cURL 56). Streaming keeps bytes moving, so the
 * connection never goes idle however long the model thinks.
 */
trait DrainsAgentStream
{
    /**
     * @param  iterable<mixed>  $stream
     * @return array{text: string, promptTokens: int, completionTokens: int}
     */
    protected function drainStream(iterable $stream): array
    {
        foreach ($stream as $event) {
            // Iterating to completion is what populates ->text and ->usage, and
            // the bytes flowing through here are what keep the connection alive.
        }

        return [
            'text' => $stream->text ?? '',
            'promptTokens' => (int) ($stream->usage->promptTokens ?? 0),
            'completionTokens' => (int) ($stream->usage->completionTokens ?? 0),
        ];
    }

    /**
     * Decode the draft JSON.
     *
     * Deliberately not a "strip the first fenced block" regex: a valid draft's
     * own content field may contain fenced code blocks, and that approach
     * mangles them. Strip a fence that wraps the whole response, then slice
     * from the first '{' to the last '}'.
     *
     * @return array<string, mixed>
     */
    protected function parseDraftJson(string $text): array
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = (string) preg_replace('/^```[a-z]*\s*/i', '', $text);
            $text = (string) preg_replace('/\s*```$/', '', $text);
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new \JsonException('Agent response contains no JSON object');
        }

        return json_decode(
            substr($text, $start, $end - $start + 1),
            true,
            512,
            JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
}
