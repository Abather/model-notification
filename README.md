# 🔔 Model Notification

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abather/model-notification.svg?style=flat-square)](https://packagist.org/packages/abather/model-notification)
[![Total Downloads](https://img.shields.io/packagist/dt/abather/model-notification.svg?style=flat-square)](https://packagist.org/packages/abather/model-notification)
[![License](https://img.shields.io/packagist/l/abather/model-notification.svg?style=flat-square)](LICENSE.md)

**Stop hardcoding notification strings.** Model Notification gives you a powerful, database-driven way to manage dynamic notification templates for your Eloquent models.

Supports variable replacement, multi-language fallbacks, and file attachments—all with a fluent, developer-friendly API.

---

## ✨ Features

- 📝 **Dynamic Templates** - Manage templates in your database, not your code.
- 🔄 **Smart Variables** - Inject model attributes `[id]`, relationships `[user->name]`, and even method results `[total()]`.
- 🌍 **Localization** - Automatic language handling with fallback support.
- 📎 **Attachments** - Easily include files (like invoices or reports) in your messages.
- 💾 **Caching & Performance** - Built-in caching for high-speed retrieval.
- ✅ **Validation** - Catch syntax errors before they reach your users.

---

## 🚀 Quick Start

### 1. Install via Composer

```bash
composer require abather/model-notification
```

### 2. Publish Migrations

```bash
php artisan vendor:publish --tag="model-notification-migrations"
php artisan migrate
```

### 3. Prepare Your Model

Add the `Notifier` trait and interface to any model you want to send notifications for (e.g., `Invoice`, `Order`, `User`).

```php
use Abather\ModelNotification\Contracts\Notifier;
use Abather\ModelNotification\Notifier as NotifierTrait;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model implements Notifier
{
    use NotifierTrait;
}
```

### 4. Create & Use a Template

```php
// 1. Create a template (usually in a seeder or admin panel)
Invoice::makeTemplateMessage()
    ->key('invoice_paid')
    ->channel('email')
    ->lang('en')
    ->template('Hi [client->name], your payment of [formatted_amount] for invoice #[id] was received.')
    ->save();

// 2. Fetch the message for a specific invoice
$invoice = Invoice::find(1);
$message = $invoice->getTemplateMessageText('invoice_paid', 'en', 'email');

// Output: "Hi John Doe, your payment of $150.00 for invoice #1001 was received."
```

---

## 📚 Variable Syntax

The real power lies in how you can use variables in your templates.

| Type | Syntax | Description | Example |
| :--- | :--- | :--- | :--- |
| **Attribute** | `[column_name]` | Value of a model column | `Invoice #[id]` |
| **Relationship** | `[relation->col]` | Value from a related model | `Thanks, [user->name]` |
| **Method** | `[method()]` | Result of a method call | `Total: [calculateTotal()]` |
| **File** | `[file_path]` | Link to an attached file | `Download: [file_path]` |

> **Note:** You can customize the start (`[`) and end (`]`) delimiters in the config file.

---

## 🛠️ Advanced Usage

### Including Files
Need to send a PDF or image? Just chain `includeFile()` when creating the template.

```php
Invoice::makeTemplateMessage()
    ->key('invoice_sent')
    ->template('Here is your invoice #[id].')
    ->includeFile() // <--- Enables file attachment
    ->save();

// Retrieve file URL later
$url = $invoice->getFile('invoice_sent', 'en', 'email');
```

### Extra Data (Prob)
Sometimes you need dynamic data that isn't on the model, like a custom subject line or icon.

```php
Invoice::makeTemplateMessage()
    ->key('push_notification')
    ->template('New Order Received')
    ->prob([
        'title'   => 'Order #[id]',
        'icon'    => 'cart',
        'deep_link' => 'app://orders/[id]'
    ])
    ->save();

// Retrieve parsed data
$data = $invoice->getTemplateMessageProb('push_notification', 'en', 'push');
// Result: ['title' => 'Order #1001', 'icon' => 'cart', 'deep_link' => 'app://orders/1001']
```


## ⚙️ Configuration

Publish the config file to customize caching, validation rules, and strict mode settings.

```bash
php artisan vendor:publish --tag="model-notification-config"
```

Useful options:
- `fallback_lang`: Default language if the requested one is missing (default: `ar`).
- `max_depth`: Maximum depth for nested variable resolution (default: `10`).
- `strict_mode`: Throw exceptions if a variable is missing (default: `false`).
- `cache.ttl`: How long to cache templates (default: `24 hours`).

---

## 🧪 Testing

We use **Pest** for testing. Run the suite to ensure everything is working correctly.

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
