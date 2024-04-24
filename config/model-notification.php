<?php

return [
    "fallback_lang" => env("MODEL_NOTIFICATION_FALLBACK_LANG", "ar"),

    "variable_starter" => "[",
    "variable_ender" => "]",

    "prevent_including_file" => false,

    "file_variables" => [
        "file",
        "file_path",
        "attachment"
    ]
];
