<?php

namespace Tests\Unit;

use App\Support\FilamentR2;
use Tests\TestCase;

class FilamentR2Test extends TestCase
{
    public function test_admin_preview_url_does_not_depend_on_app_url_host(): void
    {
        config(['filesystems.disks.public.url' => 'http://localhost:8000/storage']);

        $this->assertSame('/storage/page-about/guide-1.webp', FilamentR2::publicPreviewUrl('page-about/guide-1.webp'));
        $this->assertSame('/storage/bonuses/logo.png', FilamentR2::publicPreviewUrl('/bonuses/logo.png'));
    }
}
