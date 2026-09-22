<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    public function test_public_about_page_is_accessible_without_authentication(): void
    {
        $response = $this->get('/about');

        $response
            ->assertOk()
            ->assertSee('RETINA')
            ->assertSee('AI-Assisted Diabetic Retinopathy Screening');
    }


    public function test_public_about_page_is_indexable(): void
    {
        $response = $this->get('/about');

        $response
            ->assertOk()
            ->assertSee('content="index, follow"', false)
            ->assertSee(
                'href="'.url('/about').'"',
                false
            );
    }


    public function test_login_page_is_marked_noindex(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('content="noindex, nofollow"', false);
    }


    public function test_screening_remains_protected_from_guests(): void
    {
        $this->get('/screening')
            ->assertRedirect(route('login'));
    }


    public function test_evaluation_remains_protected_from_guests(): void
    {
        $this->get('/evaluation')
            ->assertRedirect(route('login'));
    }


    public function test_sitemap_contains_only_intended_public_page(): void
    {
        $sitemap = file_get_contents(
            public_path('sitemap.xml')
        );

        $this->assertNotFalse($sitemap);

        $this->assertStringContainsString(
            'https://eyedoctor-app.onrender.com/about',
            $sitemap
        );

        $privatePaths = [
            '/screening',
            '/evaluation',
            '/history',
            '/predictions/',
            '/images/',
            '/mobile-app',
            '/profile',
            '/admin/',
        ];

        foreach ($privatePaths as $path) {
            $this->assertStringNotContainsString(
                $path,
                $sitemap
            );
        }
    }


    public function test_robots_file_blocks_private_application_routes(): void
    {
        $robots = file_get_contents(
            public_path('robots.txt')
        );

        $this->assertNotFalse($robots);

        $expectedRules = [
            'Allow: /about',
            'Disallow: /admin/',
            'Disallow: /screening',
            'Disallow: /predict',
            'Disallow: /evaluation',
            'Disallow: /history',
            'Disallow: /predictions/',
            'Disallow: /images/',
            'Disallow: /mobile-app',
            'Disallow: /profile',
            'Disallow: /internal/',
            'Sitemap: https://eyedoctor-app.onrender.com/sitemap.xml',
        ];

        foreach ($expectedRules as $rule) {
            $this->assertStringContainsString(
                $rule,
                $robots
            );
        }
    }
}