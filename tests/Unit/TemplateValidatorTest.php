<?php

use Abather\ModelNotification\Tests\TestCase;
use Abather\ModelNotification\Validators\TemplateValidator;

uses(TestCase::class);

beforeEach(function () {
    // Clear any existing templates
    \Abather\ModelNotification\Models\NotificationTemplate::query()->delete();
});

it('validates a simple template', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name]');

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('validates template with multiple variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name], your invoice #[id] is [amount]');

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('detects mismatched brackets', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->not->toBeEmpty();
});

it('detects empty variable', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello []');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->toContain('Empty variable found');
});

it('validates relationship variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [client->name]');

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('validates method variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Total: [formattedAmount()]');

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('detects invalid characters in variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name@domain.com]');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->not->toBeEmpty();
});

it('detects consecutive relationship symbols', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [client->->name]');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->toContain("Variable 'client->->name' has consecutive relationship symbols");
});

it('detects empty method name', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [()]');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->toContain("Variable '()' has empty method name");
});

it('validates template exceeding max length', function () {
    config(['model-notification.validation.max_template_length' => 100]);

    $validator = new TemplateValidator();

    $longTemplate = str_repeat('a', 101);

    $result = $validator->validate($longTemplate);

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors()->first(fn($e) => str_contains($e, 'exceeds maximum length')))->not->toBeNull();
});

it('returns warnings for undefined variables when enabled', function () {
    config(['model-notification.validation.check_undefined_variables' => true]);

    $validator = new TemplateValidator();

    $invoice = new \Workbench\App\Models\Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $result = $validator->validate('Hello [name]', $invoice, ['id', 'amount']);

    expect($result->getWarnings())->not->toBeEmpty();
});

it('skips validation when disabled', function () {
    config(['model-notification.validation.enabled' => false]);

    $validator = new TemplateValidator();

    $result = $validator->validate('Invalid [name');

    expect($result->isValid())->toBeTrue();
});

it('validates nested brackets correctly', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name] and [amount]');

    expect($result->isValid())->toBeTrue();
});

it('detects unclosed brackets', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello [name and [amount]');

    expect($result->isValid())->toBeFalse();
});

it('validates template with no variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Hello World');

    expect($result->isValid())->toBeTrue();
});

it('validates template with file variables', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Download: [file_path]');

    expect($result->isValid())->toBeTrue();
});

it('validates template with method arguments', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Date: [formatDate("Y-m-d")]');

    expect($result->isValid())->toBeTrue();
});

it('validates complex template', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate(
        'Dear [client->name], your invoice #[id] for [formattedAmount()] is due on [due_date]. ' .
        'Download: [file_path]'
    );

    expect($result->isValid())->toBeTrue();
});

it('returns validation result with errors and warnings', function () {
    $validator = new TemplateValidator();

    $result = $validator->validate('Invalid [name and valid [amount]');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->getWarnings())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
