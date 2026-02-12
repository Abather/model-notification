<?php

use Abather\ModelNotification\Exceptions\DuplicatedTemplateException;
use Abather\ModelNotification\Models\NotificationTemplate;
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

it('can create a notification template', function () {
    $template = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    expect($template)->toBeInstanceOf(NotificationTemplate::class)
        ->and($template->exists)->toBeTrue();
});

it('prevents duplicate templates', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'duplicate_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'First template',
    ]);

    expect(fn() => NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'duplicate_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Second template',
    ]))->toThrow(DuplicatedTemplateException::class);
});

it('allows same key for different languages', function () {
    $template1 = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'English template',
    ]);

    $template2 = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'fr',
        'channel' => 'email',
        'template' => 'French template',
    ]);

    expect($template1->exists)->toBeTrue()
        ->and($template2->exists)->toBeTrue();
});

it('allows same key for different channels', function () {
    $template1 = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Email template',
    ]);

    $template2 = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'sms',
        'template' => 'SMS template',
    ]);

    expect($template1->exists)->toBeTrue()
        ->and($template2->exists)->toBeTrue();
});

it('can query templates with forModel scope', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'key1',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Template 1',
    ]);

    NotificationTemplate::create([
        'model' => 'App\Models\Order',
        'key' => 'key2',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Template 2',
    ]);

    $templates = NotificationTemplate::forModel(Invoice::class)->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->model)->toBe(Invoice::class);
});

it('can query templates with forKey scope', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'target_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Target template',
    ]);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'other_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Other template',
    ]);

    $templates = NotificationTemplate::forKey('target_key')->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->key)->toBe('target_key');
});

it('can query templates with forLang scope', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'key1',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'English template',
    ]);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'key2',
        'lang' => 'fr',
        'channel' => 'email',
        'template' => 'French template',
    ]);

    $templates = NotificationTemplate::forLang('en')->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->lang)->toBe('en');
});

it('can query templates with forChannel scope', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'key1',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Email template',
    ]);

    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'key2',
        'lang' => 'en',
        'channel' => 'sms',
        'template' => 'SMS template',
    ]);

    $templates = NotificationTemplate::forChannel('email')->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->channel)->toBe('email');
});


it('casts with_file to boolean', function () {
    $template = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
        'with_file' => 1,
    ]);

    expect($template->with_file)->toBeBool()
        ->and($template->with_file)->toBeTrue();
});

it('casts prob to array', function () {
    $template = NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
        'prob' => ['title' => 'Test', 'body' => 'Body'],
    ]);

    expect($template->prob)->toBeArray()
        ->and($template->prob)->toHaveCount(2);
});




it('can check if template exists', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'test_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Test template',
    ]);

    $exists = NotificationTemplate::templateExists(
        Invoice::class,
        'test_key',
        'en',
        'email'
    );

    expect($exists)->toBeTrue();
});

it('returns false when template does not exist', function () {
    $exists = NotificationTemplate::templateExists(
        Invoice::class,
        'non_existent',
        'en',
        'email'
    );

    expect($exists)->toBeFalse();
});

it('can chain scopes', function () {
    NotificationTemplate::create([
        'model' => Invoice::class,
        'key' => 'target_key',
        'lang' => 'en',
        'channel' => 'email',
        'template' => 'Target template',
    ]);

    $templates = NotificationTemplate::forModel(Invoice::class)
        ->forKey('target_key')
        ->forLang('en')
        ->forChannel('email')
        ->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->lang)->toBe('en');
});

