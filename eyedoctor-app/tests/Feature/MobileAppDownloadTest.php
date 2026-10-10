<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('doctor', 'web');

    $this->doctor = User::factory()->create();
    $this->doctor->assignRole('doctor');

    config([
        'retina.mobile.apk_path' => 'releases/android/test/RETINA-Android.apk',
        'retina.mobile.download_name' => 'RETINA-Android.apk',
        'retina.windows.package_path' => 'releases/windows/test/RETINA-Windows.zip',
        'retina.windows.download_name' => 'RETINA-Windows.zip',
    ]);

    // Laravel rebinds the callback's $this, so capture through an object.
    $captured = new ArrayObject;
    $this->signedUrlOptions = $captured;

    Storage::fake('app_downloads')->buildTemporaryUrlsUsing(
        function (string $path, $expiration, array $options) use ($captured) {
            $captured->exchangeArray($options);

            return 'https://downloads.test/'.$path.'?signature=test';
        }
    );
});

test('guests are redirected to login from the application pages', function () {
    $this->get(route('mobile-app'))->assertRedirect(route('login'));
    $this->get(route('mobile-app.download'))->assertRedirect(route('login'));
    $this->get(route('mobile-app.download.windows'))->assertRedirect(route('login'));
});

test('users without a RETINA role cannot open or download the applications', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('mobile-app'))->assertForbidden();
    $this->actingAs($user)->get(route('mobile-app.download'))->assertForbidden();
    $this->actingAs($user)->get(route('mobile-app.download.windows'))->assertForbidden();
});

test('missing application packages fail gracefully with a message', function () {
    $this->actingAs($this->doctor)
        ->get(route('mobile-app.download'))
        ->assertRedirect(route('mobile-app'))
        ->assertSessionHas('apk_error');

    $this->actingAs($this->doctor)
        ->get(route('mobile-app.download.windows'))
        ->assertRedirect(route('mobile-app'))
        ->assertSessionHas('apk_error');
});

test('android download issues a short-lived signed link with download headers', function () {
    Storage::disk('app_downloads')->put('releases/android/test/RETINA-Android.apk', 'apk-bytes');

    $this->actingAs($this->doctor)
        ->get(route('mobile-app.download'))
        ->assertRedirect('https://downloads.test/releases/android/test/RETINA-Android.apk?signature=test');

    expect($this->signedUrlOptions->getArrayCopy())->toBe([
        'ResponseContentType' => 'application/vnd.android.package-archive',
        'ResponseContentDisposition' => 'attachment; filename="RETINA-Android.apk"',
    ]);
});

test('windows download issues a short-lived signed link with download headers', function () {
    Storage::disk('app_downloads')->put('releases/windows/test/RETINA-Windows.zip', 'zip-bytes');

    $this->actingAs($this->doctor)
        ->get(route('mobile-app.download.windows'))
        ->assertRedirect('https://downloads.test/releases/windows/test/RETINA-Windows.zip?signature=test');

    expect($this->signedUrlOptions['ResponseContentDisposition'])
        ->toBe('attachment; filename="RETINA-Windows.zip"');
});

test('application page never renders private storage paths', function () {
    Storage::disk('app_downloads')->put('releases/android/test/RETINA-Android.apk', 'apk-bytes');
    Storage::disk('app_downloads')->put('releases/windows/test/RETINA-Windows.zip', 'zip-bytes');

    $this->actingAs($this->doctor)
        ->get(route('mobile-app'))
        ->assertOk()
        ->assertSee(route('mobile-app.download'), false)
        ->assertSee(route('mobile-app.download.windows'), false)
        ->assertDontSee('releases/android')
        ->assertDontSee('releases/windows')
        ->assertSee('separate')
        ->assertDontSee('Not verified');
});
