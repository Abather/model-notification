<?php

return [
    /**
     * This is the language that will be used when the provided language doesn't exist.
     */
    "fallback_lang" => env("MODEL_NOTIFICATION_FALLBACK_LANG", "ar"),

    /**
     * Cache configuration.
     */
    "cache" => [
        "enabled" => env("MODEL_NOTIFICATION_CACHE_ENABLED", true),
        "driver" => env("MODEL_NOTIFICATION_CACHE_DRIVER", null), // null = use default cache driver
        "ttl" => env("MODEL_NOTIFICATION_CACHE_TTL", 86400), // 24 hours
        "prefix" => env("MODEL_NOTIFICATION_CACHE_PREFIX", "model_notification"),
        "tags" => [
            "template" => "template",
            "model" => "model",
        ],
    ],

    /**
     * Variable configuration.
     */
    "variables" => [
        "starter" => "[",
        "ender" => "]",
        "relationship_symbol" => "->",
        "method_symbol" => "()",
        "strict_mode" => env("MODEL_NOTIFICATION_STRICT_MODE", false),
        "allow_method_calls" => env("MODEL_NOTIFICATION_ALLOW_METHOD_CALLS", true),
        "whitelisted_methods" => ["*"], // or ['fullName', 'formatDate']
        "fallback_value" => "",
        "max_depth" => 10,
    ],


    /**
     * Formatting configuration.
     */
    "formatters" => [
        "currency" => ["symbol" => "$", "decimals" => 2],
        "date" => ["format" => "Y-m-d H:i:s"],
        "number" => ["decimals" => 0, "thousands_separator" => ","],
        "truncate" => ["length" => 100, "suffix" => "..."],
    ],

    /**
     * Validation configuration.
     */
    "validation" => [
        "enabled" => env("MODEL_NOTIFICATION_VALIDATION", true),
        "check_undefined_variables" => false,
        "max_template_length" => 10000,
    ],

    /**
     * The symbol that represents the start of a variable name.
     * Avoid using spaces or common symbols for attribute naming, such as "-", "_",
     * as well as symbols commonly used in text, like ".", or ",".
     */
    "variable_starter" => "[",

    /**
     * The symbol that represents the end of a variable name.
     * Avoid using spaces or common symbols for attribute naming, such as "-", "_",
     * as well as symbols commonly used in text, like ".", or ",".
     */
    "variable_ender" => "]",

    /**
     * Indicates that the variable is an attribute for a belongsTo relation.
     * Ensure that you avoid using any commonly used symbols or characters.
     */
    "relationship_variable_symbol" => "->",

    /**
     * The file name used to fetch the file URL or file object.
     * You can override this configuration by setting a variable in each model to specify a different name.
     * public static $file_name = "attachment";
     */
    "file_name" => "file",

    /**
     * If set to true, files will be globally prevented from being included.
     * You can override this configuration by setting a variable in each model where you want it to act differently.
     * public static $prevent_including_file = true;
     */
    "prevent_including_file" => false,

    /**
     * These are the global variables that represent file attributes. Whenever one of these is included in the
     * template message, it will be replaced with the file URL. You need to define getFilePath() for each model.
     * You can specify a specific array for each model that will override this global setting.
     *
     * public static $file_variables = [
     *      'file_url',
     *  ];
     */
    "file_variables" => [
        "file",
        "file_path",
        "attachment"
    ]
];
