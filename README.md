# Add notifications Template into Models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abather/model-notification.svg?style=flat-square)](https://packagist.org/packages/abather/model-notification)
[![Total Downloads](https://img.shields.io/packagist/dt/abather/model-notification.svg?style=flat-square)](https://packagist.org/packages/abather/model-notification)

This package helps you organized and save template messages for each model that included it. each message depends
upon `key`, `language`, and `channel`.

## Installation

You can install the package via composer:

```bash
composer require abather/model-notification
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="model-notification-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="model-notification-config"
```

This is the contents of the published config file:

```php
return [
    "fallback_lang" => env("MODEL_NOTIFICATION_FALLBACK_LANG", "ar"),

    "variable_starter" => "[",

    "variable_ender" => "]",

    "relationship_variable_symbol" => "->",

    "file_name" => "file",

    "prevent_including_file" => false,

    "file_variables" => [
        "file",
        "file_path",
        "attachment"
    ]
];
```

### Override Global Configuration:

You can prevent including files for specific models if you wish by adding the variable `prevent_including_file` in the model:

```php
public static $prevent_including_file = true;
```

If you want to use specific file variables for the model, you can add the `file_variables` variable:

```php
public static $file_variables = ["document"];
```

## Usage

You can use this package with any `Model` by implementing the `Notifier` interface and using the `Notifier` trait:

```php
namespace App\Models;

use Abather\ModelNotification\Contracts\Notifier;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model implements Notifier
{
    use \Abather\ModelNotification\Notifier;
}
```

Now you can create, call, or update message templates as described below.

### Create new template for a model:

To create a new template for any model, you can use the `makeTemplateMessage()` method. You must specify `key`, `lang`, `channel`, and `template`:

```php
Bill::makeTemplateMessage()
    ->key("new")
    ->channel("sms")
    ->lang("ar")
    ->template("You have a new bill [id], with an amount of: [amount]")
    ->save();
```

#### Including variables in the template text:

The `template()` method can include any attribute present in the model. The attribute value will replace the attribute name. For example, `[amount]` will retrieve the value of the attribute named `amount`. To change the symbols representing the variable names, you can modify the `variable_starter` and `variable_ender` in the config file.

#### Including attributes from relationships:

The `template()` method can also include values from supported relationships, such as `[user->name]`. It behaves like including attributes and supports `belongsTo` and `hasOne` relationships.

#### Including file path in the template text:

You can include the file URL in the template by adding any key defined in the `config("model-notification.file_variables")` or defined in the model `$file_variables`. Additionally, you need to use `includeFile()` when creating the template message:

```php
Bill::makeTemplateMessage()
    ->includeFile();
```

You can change the URL returned by overriding the `getFilePath()` method in your model.

#### Adding extra data for the template message:

You can pass any extra data to `prob([$key => $value])`. Each value will go through the same process as the template to replace the attributes with data for attributes, relationship attributes, or file URLs.

```php
Bill::makeTemplateMessage()
    ->prob(["title" => "New bill generated with number [id]", "icon" => "bill"]);
```

### Getting Template Messages:

You can use the `getTemplateMessages()` method to get all messages related to the model:

```php
Bill::getTemplateMessages();
```

If you want to get messages for a specific channel, language, or key, you can use the `forChannel($channel)`, `forLang($lang)`, or `forKey($key)` scopes with the `notificationTemplates()` query builder:

```php
Bill::notificationTemplates()
    ->forChannel("sms")
    ->forLang("ar")
    ->get();
```

This will return a collection of `NotificationTemplate`.

If you want to get a specific Template Message, you can use the `getTemplateMessage` method by passing `key`, `lang`, and `channel`:

```php
Bill::getTemplateMessage("anyKey", "ar", "sms");
```

This method will return a `NotificationTemplate` instance.

If you want to get the text message, you can use the `getTemplateMessageText()` method by passing `key`, `lang`, and `channel`:

```php
Bill::getTemplateMessageText("anyKey", "ar", "sms");
```

This will return a string ready to use with your notification, with all variables, relationship variables, and file paths replaced.

To get `prob` for a specific template, you can use the `getTemplateMessageProb()` method and pass `key`, `lang`, and `channel`:

```php
Bill::getTemplateMessageProb("anyKey", "ar", "sms");
```

This will return an array with each `prob` ready to use and handled. If you want to get the value of a specific `prob`, you can pass a fourth parameter, `prob`:

```php
Bill::getTemplateMessageProb("anyKey", "ar", "sms", "title");
```

This will return a string of the value of the given `prob` or an empty string if it does not exist.

If the file is included with the template, you can get the file URL or the file object by using the `()` method and passing `key`, `lang`, and `channel`:

```php
Bill::getFile("anyKey", "ar", "sms");
```

This will return the file URL. If you want to get the file parameter, you have to pass a fourth parameter as `false`:

```php
Bill::getFile("anyKey", "ar", "sms", false);
```

This will return a file parameter. Keep in mind that you have to configure the `getFileObject()` method inside your model.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mohammed Sadiq](https://github.com/Abather)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
