<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OneSignalMailTransportTest extends TestCase
{
    public function test_sends_email_via_onesignal_api(): void
    {
        config([
            'snapp.onesignal.app_id' => 'app-1',
            'snapp.onesignal.rest_api_key' => 'key-1',
            'snapp.onesignal.api_url' => 'https://onesignal.com/api/v1/notifications',
        ]);
        Http::fake(['*' => Http::response(['id' => 'e1'], 200)]);

        Mail::mailer('onesignal')->html('<p>Codice: 123456</p>', function ($m) {
            $m->to('mario@example.com')->subject('Reset')->from('no-reply@snappanel.it', 'SNApp');
        });

        Http::assertSent(function ($request) {
            return $request->url() === 'https://onesignal.com/api/v1/notifications'
                && $request['app_id'] === 'app-1'
                && $request['email_subject'] === 'Reset'
                && str_contains($request['email_body'], '123456')
                && $request['include_email_tokens'] === ['mario@example.com']
                && $request['email_from_address'] === 'no-reply@snappanel.it'
                && $request['email_from_name'] === 'SNApp'
                && $request->hasHeader('Authorization', 'Key key-1');
        });
    }
}
