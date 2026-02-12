<?php

use Abather\ModelNotification\Contracts\Notifier;
use Abather\ModelNotification\Models\NotificationTemplate;
use Abather\ModelNotification\Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\Invoice;

uses(TestCase::class);

beforeEach(function () {
    // Setup fake storage
    Storage::fake('local');

    // Clear any existing templates
    NotificationTemplate::query()->delete();
});

afterEach(function () {
    // Cleanup
    NotificationTemplate::query()->delete();
});

it('can create a template message', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Test template [id]')
        ->save();

    expect(NotificationTemplate::count())->toBe(1)
        ->and(NotificationTemplate::first()->key)->toBe('test_key');
});

it('prevents duplicate templates', function () {
    Invoice::makeTemplateMessage()
        ->key('duplicate_key')
        ->channel('email')
        ->lang('en')
        ->template('First template')
        ->save();

    expect(
        fn() => Invoice::makeTemplateMessage()
            ->key('duplicate_key')
            ->channel('email')
            ->lang('en')
            ->template('Second template')
            ->save()
    )->toThrow(\Abather\ModelNotification\Exceptions\DuplicatedTemplateException::class);
});

it('can get template message', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Test template [id]')
        ->save();

    $template = Invoice::getTemplateMessage('test_key', 'en', 'email');

    expect($template)->not->toBeNull()
        ->and($template->key)->toBe('test_key');
});

it('returns null when template does not exist', function () {
    $template = Invoice::getTemplateMessage('non_existent', 'en', 'email');

    expect($template)->toBeNull();
});

it('can get all template messages', function () {
    Invoice::makeTemplateMessage()
        ->key('key1')
        ->channel('email')
        ->lang('en')
        ->template('Template 1')
        ->save();

    Invoice::makeTemplateMessage()
        ->key('key2')
        ->channel('sms')
        ->lang('en')
        ->template('Template 2')
        ->save();

    $templates = Invoice::getTemplateMessages();

    expect($templates)->toHaveCount(2);
});

it('can query templates with scopes', function () {
    Invoice::makeTemplateMessage()
        ->key('key1')
        ->channel('email')
        ->lang('en')
        ->template('Template 1')
        ->save();

    Invoice::makeTemplateMessage()
        ->key('key2')
        ->channel('sms')
        ->lang('en')
        ->template('Template 2')
        ->save();

    $templates = Invoice::notificationTemplates()
        ->forChannel('email')
        ->get();

    expect($templates)->toHaveCount(1)
        ->and($templates->first()->channel)->toBe('email');
});

it('can get template message text with variable replacement', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Invoice #[id] for amount [amount]')
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $text = $invoice->getTemplateMessageText('test_key', 'en', 'email');

    expect($text)->toBe('Invoice #123 for amount 500');
});

it('can replace relationship variables', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Dear [client->name], invoice #[id]')
        ->save();

    $client = new class {
        public $name = 'John Doe';
    };

    $invoice = new Invoice([
        'id' => 123,
        'amount' => 500.00,
    ]);
    $invoice->setRelation('client', $client);

    $text = $invoice->getTemplateMessageText('test_key', 'en', 'email');

    expect($text)->toBe('Dear John Doe, invoice #123');
});

it('can replace method variables', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Invoice [formattedAmount()]')
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $text = $invoice->getTemplateMessageText('test_key', 'en', 'email');

    expect($text)->toBe('Invoice $500.00');
});

it('can get template message prob', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('push')
        ->lang('en')
        ->template('New invoice #[id]')
        ->prob([
            'title' => 'Invoice #[id] created',
            'body' => 'Amount: [amount]',
        ])
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $probs = $invoice->getTemplateMessageProb('test_key', 'en', 'push');

    expect($probs)->toBeArray()
        ->and($probs)->toHaveKey('title')
        ->and($probs['title'])->toBe('Invoice #123 created');
});

it('can get specific prob value', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('push')
        ->lang('en')
        ->template('New invoice #[id]')
        ->prob([
            'title' => 'Invoice #[id] created',
            'body' => 'Amount: [amount]',
        ])
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $title = $invoice->getTemplateMessageProb('test_key', 'en', 'push', 'title');

    expect($title)->toBe('Invoice #123 created');
});

it('returns empty string for non-existent prob', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('push')
        ->lang('en')
        ->template('New invoice #[id]')
        ->prob([
            'title' => 'Invoice #[id] created',
        ])
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $body = $invoice->getTemplateMessageProb('test_key', 'en', 'push', 'body');

    expect($body)->toBe('');
});

it('can include file in template', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Invoice #[id] attached')
        ->includeFile()
        ->save();

    Storage::disk('local')->put('invoices/123.pdf', 'fake pdf content');

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
        'file' => 'invoices/123.pdf',
    ]);

    $url = $invoice->getFile('test_key', 'en', 'email');

    expect($url)->not->toBeNull()
        ->and($url)->toBeString();
});

it('returns null when file is not included', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Invoice #[id]')
        ->save();

    $invoice = Invoice::create([
        'id' => 123,
        'amount' => 500.00,
    ]);

    $url = $invoice->getFile('test_key', 'en', 'email');

    expect($url)->toBeNull();
});

it('uses fallback language when template not found', function () {
    config(['model-notification.fallback_lang' => 'en']);

    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Fallback template [id]')
        ->save();

    $template = Invoice::getTemplateMessage('test_key', 'fr', 'email');

    expect($template)->not->toBeNull()
        ->and($template->lang)->toBe('en');
});

it('can identify file variables', function () {
    $isFileVar = Invoice::isFileVariable('file_path');

    expect($isFileVar)->toBeTrue();
});

it('can identify relationship variables', function () {
    $isRelVar = Invoice::isRelationshipVariable('client->name');

    expect($isRelVar)->toBeTrue();
});

it('can identify simple variables', function () {
    $isRelVar = Invoice::isRelationshipVariable('amount');

    expect($isRelVar)->toBeFalse();
});

it('can update existing template', function () {
    $template = Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('email')
        ->lang('en')
        ->template('Original template')
        ->save();

    $updated = Invoice::makeTemplateMessage($template->id)
        ->template('Updated template')
        ->update();

    expect($updated->template)->toBe('Updated template');
});

it('can create template with prob data', function () {
    Invoice::makeTemplateMessage()
        ->key('test_key')
        ->channel('push')
        ->lang('en')
        ->template('Template [id]')
        ->prob([
            'title' => 'Title [id]',
            'body' => 'Body [amount]',
        ])
        ->save();

    $template = Invoice::getTemplateMessage('test_key', 'en', 'push');

    expect($template->prob)->toBeArray()
        ->and($template->prob)->toHaveCount(2);
});
