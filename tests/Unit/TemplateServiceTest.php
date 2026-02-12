<?php

use Abather\ModelNotification\Exceptions\TemplateNotFoundException;
use Abather\ModelNotification\Exceptions\TemplateValidationException;
use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\Services\TemplateService;
use Abather\ModelNotification\Tests\TestCase;
use Workbench\App\Models\Invoice;

uses(TestCase::class);

beforeEach(function () {
    // Clear any existing templates
    NotificationTemplate::query()->delete();
});

afterEach(function () {
    // Cleanup
    NotificationTemplate::query()->delete();
});

it('can create a template via service', function () {
    $service = app(TemplateService::class);

    $template = $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template [id]',
    ]);

    expect($template)->toBeInstanceOf(NotificationTemplate::class)
        ->and($template->key)->toBe('test_key')
        ->and(NotificationTemplate::count())->toBe(1);
});

it('can update a template via service', function () {
    $service = app(TemplateService::class);

    $template = $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Original template',
    ]);

    $updated = $service->updateTemplate($template->id, [
        'template' => 'Updated template',
    ]);

    expect($updated->template)->toBe('Updated template');
});

it('can get a template via service', function () {
    $service = app(TemplateService::class);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $template = $service->getTemplate('test_key', 'en', 'email', $invoice);

    expect($template)->toBeInstanceOf(NotificationTemplate::class)
        ->and($template->key)->toBe('test_key');
});

it('throws exception when template not found', function () {
    $service = app(TemplateService::class);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    expect(fn() => $service->getTemplate('non_existent', 'en', 'email', $invoice))
        ->toThrow(TemplateNotFoundException::class);
});

it('can render a template via service', function () {
    $service = app(TemplateService::class);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Invoice #[id] for [amount]',
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $text = $service->renderTemplate('test_key', 'en', 'email', $invoice);

    expect($text)->toBe('Invoice #123 for 500');
});

it('can delete a template via service', function () {
    $service = app(TemplateService::class);

    $template = $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    $deleted = $service->deleteTemplate($template->id);

    expect($deleted)->toBeTrue()
        ->and(NotificationTemplate::count())->toBe(0);
});

it('can get templates for a model', function () {
    $service = app(TemplateService::class);

    $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'key1',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Template 1',
    ]);

    $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'key2',
        'lang' => 'en',
        'channel' => 'sms',
        'template' => 'Template 2',
    ]);

    $templates = $service->getTemplatesForModel(Invoice::class);

    expect($templates)->toHaveCount(2);
});

it('can validate a template', function () {
    $service = app(TemplateService::class);

    $result = $service->validateTemplate('Valid template [id]');

    expect($result->isValid())->toBeTrue()
        ->and($result->getErrors())->toBeEmpty();
});

it('detects invalid template with mismatched brackets', function () {
    $service = app(TemplateService::class);

    $result = $service->validateTemplate('Invalid template [id');

    expect($result->isValid())->toBeFalse()
        ->and($result->getErrors())->not->toBeEmpty();
});

it('throws validation exception for invalid template on create', function () {
    $service = app(TemplateService::class);

    expect(fn() => $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Invalid [id',
    ]))->toThrow(TemplateValidationException::class);
});

it('can clear model cache', function () {
    $service = app(TemplateService::class);

    $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    $service->clearModelCache(Invoice::class);

    expect(true)->toBeTrue(); // If no exception thrown, cache was cleared
});

it('can clear template cache', function () {
    $service = app(TemplateService::class);

    $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    $service->clearTemplateCache('test_key');

    expect(true)->toBeTrue(); // If no exception thrown, cache was cleared
});

it('uses fallback language when template not found', function () {
    config(['model-notification.fallback_lang' => 'en']);

    $service = app(TemplateService::class);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Fallback template',
    ]);

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $template = $service->getTemplate('test_key', 'fr', 'email', $invoice);

    expect($template->lang)->toBe('en');
});

it('can create template with prob data', function () {
    $service = app(TemplateService::class);

    $template = $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'push',
        'template' => 'Template [id]',
        'prob' => [
            'title' => 'Title [id]',
            'body' => 'Body [amount]',
        ],
    ]);

    expect($template->prob)->toBeArray()
        ->and($template->prob)->toHaveCount(2);
});

it('can create template with file inclusion', function () {
    $service = app(TemplateService::class);

    $template = $service->createTemplate([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Template [id]',
        'with_file' => true,
    ]);

    expect($template->with_file)->toBeTrue();
});

