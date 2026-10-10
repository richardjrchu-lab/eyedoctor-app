<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function navigationSmokeUser(string $role): User
{
    Role::findOrCreate($role, 'web');

    $user = User::factory()->create();
    $user->assignRole($role);
    acceptCurrentRetinaLegalDocuments($user);

    return $user;
}

beforeEach(function () {
    Storage::fake('app_downloads');
});

test('every doctor navigation page loads and links to the others', function () {
    $doctor = navigationSmokeUser('doctor');

    $pages = ['welcome', 'screening', 'evaluation', 'history', 'mobile-app', 'profile.edit'];

    foreach ($pages as $page) {
        $response = $this->actingAs($doctor)->get(route($page))->assertOk();

        foreach (['welcome', 'screening', 'evaluation', 'history', 'mobile-app', 'profile.edit', 'logout'] as $link) {
            $response->assertSee(route($link), false);
        }
    }
});

test('every administrator navigation page loads', function () {
    $admin = navigationSmokeUser('admin');

    foreach (['history', 'admin.access-requests.index', 'mobile-app', 'profile.edit'] as $page) {
        $this->actingAs($admin)
            ->get(route($page))
            ->assertOk()
            ->assertSee(route('admin.access-requests.index'), false)
            ->assertSee(route('logout'), false);
    }
});

test('password recovery is reachable from the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'), false);

    $this->get(route('password.request'))->assertOk();
});

test('logout ends the session and returns to login', function () {
    $doctor = navigationSmokeUser('doctor');

    $this->actingAs($doctor)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();

    $this->get('/')->assertRedirect(route('login'));
});

test('prediction history renders punctuation without encoding artifacts', function () {
    $admin = navigationSmokeUser('admin');

    $this->actingAs($admin)
        ->get(route('history'))
        ->assertOk()
        ->assertSee('Administrator View &mdash; All Doctors', false)
        ->assertDontSee("\u{00E2}\u{20AC}", false);
});
