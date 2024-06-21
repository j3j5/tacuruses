<?php

declare(strict_types=1);

namespace App\Http\Controllers\oEmbed;

use App\Http\Controllers\Controller;
use App\Http\Requests\oEmbed\PreviewRequest;
use App\Models\ActivityPub\LocalNote;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{

    private const int TTL = 86400;

    /**
     * Handle the incoming request.
     */
    public function __invoke(PreviewRequest $request) : JsonResponse
    {
        $url = $request->validated('url');
        $maxWidth = $request->validated('maxwidth', 400);
        if (!preg_match(LocalNote::NOTE_REGEX, $url, $matches)) {
            return response()->json(['error' => 'Record not found on URL'], 404);
        }
        try {
            $note = LocalNote::findOrFail($matches['noteId']);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        $embed = '<iframe src="' . route('note.show.embed', [$note->actor, $note]);
        $embed .= '" class="embed" style="max-width: 100%; border: 0" ';
        $embed .= "width=\"{$maxWidth}\" allowfullscreen=\"allowfullscreen\"></iframe>";

        $data = [
            'type' => 'rich',
            'version' => '1.0',
            'author_name' => $note->actor->name,
            'author_url' => route('actor.show', [$note->actor]),
            'provider_name' => config('app.name'),
            'provider_url' => 'https://' . $request->host(),
            'cache_age' => self::TTL,
            'html' => $embed,
            'width' => $maxWidth,
            'height' => null,
        ];

        return response()->json($data);
    }
}
