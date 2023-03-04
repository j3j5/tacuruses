<?php

return [
    'homepage' => env('FEDI_HOMEPAGE', ''),
    'software_name' => env('FEDI_APP_NAME', 'j3j5-bots'),
    'software_version' => env('FEDI_APP_VERSION', '1.0'),

    'contact_email' => env('FEDI_CONTACT_EMAIL', ''),

    'languages' => [
        'en',
    ],
    "max_characters" =>  500,
    "max_media_attachments" =>  4,
    "characters_reserved_per_url" =>  23,
    "max_featured_tags" =>  10,
    'media_attachments' => [
        "image_size_limit" =>  10485760,
        "image_matrix_limit" =>  16777216,
        "video_size_limit" =>  41943040,
        "video_frame_rate_limit" =>  60,
        "video_matrix_limit" =>  2304000,
    ],
    'supported_mime_types' => [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/heic",
        "image/heif",
        "image/webp",
        "image/avif",
        "video/webm",
        "video/mp4",
        "video/quicktime",
        "video/ogg",
        "audio/wave",
        "audio/wav",
        "audio/x-wav",
        "audio/x-pn-wave",
        "audio/vnd.wave",
        "audio/ogg",
        "audio/vorbis",
        "audio/mpeg",
        "audio/mp3",
        "audio/webm",
        "audio/flac",
        "audio/aac",
        "audio/m4a",
        "audio/x-m4a",
        "audio/mp4",
        "audio/3gpp",
        "video/x-ms-asf"
    ],
];