<?php

use Abather\ModelNotification\Exceptions\VariableResolutionException;
use Abather\ModelNotification\Resolvers\MethodVariableResolver;
use Abather\ModelNotification\Resolvers\RelationshipVariableResolver;
use Abather\ModelNotification\Resolvers\SimpleVariableResolver;
use Abather\ModelNotification\Resolvers\VariableResolver;
use Abather\ModelNotification\Tests\TestCase;
use Workbench\App\Models\Invoice;

uses(TestCase::class);

beforeEach(function () {
    // Clear any existing templates
    \Abather\ModelNotification\Models\NotificationTemplate::query()->delete();
});

it('resolves simple variables', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Invoice #[id] for [amount]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Invoice #123 for 500');
});

it('resolves relationship variables', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new RelationshipVariableResolver(),
    ]);

    $client = new class {
        public $name = 'John Doe';
    };

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);
    $invoice->setRelation('client', $client);

    $result = $resolver->resolve('Dear [client->name]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Dear John Doe');
});

it('resolves method variables', function () {
    config(['model-notification.variables.allow_method_calls' => true]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new MethodVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Total: [formattedAmount()]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Total: $500.00');
});

it('resolves mixed variables', function () {
    config(['model-notification.variables.allow_method_calls' => true]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new RelationshipVariableResolver(),
        new MethodVariableResolver(),
    ]);

    $client = new class {
        public $name = 'John Doe';
    };

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);
    $invoice->setRelation('client', $client);

    $result = $resolver->resolve(
        'Dear [client->name], invoice #[id] for [formattedAmount()]',
        $invoice,
        'test',
        'en',
        'email'
    );

    expect($result)->toBe('Dear John Doe, invoice #123 for $500.00');
});

it('returns fallback value for missing variables', function () {
    config(['model-notification.variables.fallback_value' => 'N/A']);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Hello [missing_var]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Hello N/A');
});

it('throws exception in strict mode for missing variables', function () {
    config([
        'model-notification.variables.strict_mode' => true,
        'model-notification.variables.fallback_value' => '',
    ]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    expect(fn() => $resolver->resolve('Hello [missing_var]', $invoice, 'test', 'en', 'email'))
        ->toThrow(VariableResolutionException::class);
});

it('resolves method with arguments', function () {
    config(['model-notification.variables.allow_method_calls' => true]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new MethodVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Amount: [formattedAmount()]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Amount: $500.00');
});

it('respects method whitelisting', function () {
    config([
        'model-notification.variables.allow_method_calls' => true,
        'model-notification.variables.whitelisted_methods' => ['formattedAmount'],
    ]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new MethodVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Amount: [formattedAmount()]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Amount: $500.00');
});

it('uses fallback for non-whitelisted methods', function () {
    config([
        'model-notification.variables.allow_method_calls' => true,
        'model-notification.variables.whitelisted_methods' => ['allowedMethod'],
        'model-notification.variables.fallback_value' => 'N/A',
    ]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new MethodVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Amount: [formattedAmount()]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Amount: N/A');
});

it('resolves nested relationship variables', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new RelationshipVariableResolver(),
    ]);

    $address = new class {
        public $city = 'New York';
    };

    $client = new class {
        public $name = 'John Doe';
    };
    $client->address = $address;

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);
    $invoice->setRelation('client', $client);

    $result = $resolver->resolve('City: [client->address->city]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('City: New York');
});

it('handles multiple occurrences of same variable', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Invoice #[id] - #[id] - #[id]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Invoice #123 - #123 - #123');
});

it('resolves variables in correct order', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new RelationshipVariableResolver(),
    ]);

    $client = new class {
        public $name = 'John Doe';
    };

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);
    $invoice->setRelation('client', $client);

    $result = $resolver->resolve(
        '[id] [amount] [client->name]',
        $invoice,
        'test',
        'en',
        'email'
    );

    expect($result)->toBe('123 500 John Doe');
});

it('handles empty template', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('');
});

it('handles template with no variables', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Hello World', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Hello World');
});

it('respects custom variable delimiters', function () {
    config([
        'model-notification.variables.starter' => '{{',
        'model-notification.variables.ender' => '}}',
    ]);

    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Invoice {{id}} for {{amount}}', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Invoice 123 for 500');
});

it('handles null relationship values', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
        new RelationshipVariableResolver(),
    ]);

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Client: [client->name]', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Client: ');
});

it('handles special characters in variable values', function () {
    $resolver = new VariableResolver([
        new SimpleVariableResolver(),
    ]);

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $resolver->resolve('Hello [id]!', $invoice, 'test', 'en', 'email');

    expect($result)->toBe('Hello 123!');
});
