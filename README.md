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

    "prevent_including_file" => false,

    "file_variables" => [
        "file",
        "file_path",
        "attachment"
    ]
];
```

### Override Global Configuration:

you can prevent including files for specific models if you wish to by adding variable `prevent_including_file` in the
model:

```php
public static $prevent_including_file = true;
```

if you went to use specific file variables for the model you can add `file_variables` variable:

```php
public static $file_variables = ["document"];
```

## Usage

you can use this package with any `Model` you have to implement `Notifier` interface and use `Notifier` trait:

```php
namespace App\Models;

use Abather\ModelNotification\Contracts\Notifier;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model implements Notifier
{
    use \Abather\ModelNotification\Notifier;
}
```

now you can create, call, or update message templates as well be descriped.

### Create new template for a model:

to create new template for any model you can use `makeTemplateMessage()` method you have to
specify `key`, `lang`, `channel` and `template`:

```php
Bill::makeTemplateMessage()
    ->key("new")
    ->channel("sms")
    ->lang("ar")
    ->template("You Have new Bill [id], amount are: [amount] [created_at]")
    ->save();
```

`template()` can have any attribute that present in the model the attribute value well be replace the attribute, also
you can include file url into the template by adding any key that defined in the `config("
model-notification.file_variables")` or that defined in the model `$file_variables` as array.

if you went to include file with the message you can use `includeFile()` method:

```php
Bill::makeTemplateMessage()
    ->key("new")
    ->channel("sms")
    ->lang("ar")
    ->template("You Have new Bill [id], amount are: [amount] [created_at]")
    ->includeFile()
    ->save();
```

also you can pass any extra data to `prob([$key => $value])` each value well ge through the same process as template to
replace the attributes with data.

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
