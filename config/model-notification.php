<?php

return [
    /**
     * This is the language that will be used when the provided language doesn't exist.
     */
    "fallback_lang" => env("MODEL_NOTIFICATION_FALLBACK_LANG", "ar"),

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
