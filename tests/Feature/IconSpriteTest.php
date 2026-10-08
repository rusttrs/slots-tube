<?php

namespace Tests\Feature;

use App\Support\IconSprite;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class IconSpriteTest extends TestCase
{
    public function test_committed_sprite_matches_sources(): void
    {
        $this->assertSame(
            IconSprite::build(),
            file_get_contents(public_path(IconSprite::SPRITE_PATH)),
            'Спрайт устарел: запусти `php artisan icons:build` и закоммить public/assets/icons/sprite.svg.',
        );
    }

    public function test_every_icon_used_in_templates_exists(): void
    {
        $known = array_flip(IconSprite::names());
        $used = [];

        $files = Finder::create()->files()->in([resource_path('views'), app_path('Http')])->name('*.php');
        foreach ($files as $file) {
            $source = $file->getContents();
            $where = $file->getRelativePathname();

            preg_match_all('/<x-site-icon\b[^>]*?\sname="([^"]+)"/', $source, $static);
            preg_match_all('/<x-site-icon\b[^>]*?\s:name="([^"]+)"/', $source, $dynamic);
            preg_match_all('/\'icon\' => \'([a-z0-9-]+)\'|\'((?:info|toc|section)-[a-z0-9-]+)\'/', $source, $mapped);

            foreach ($static[1] as $name) {
                $used[$name][] = $where;
            }
            foreach ($dynamic[1] as $expression) {
                preg_match_all('/(?<!\[)\'([a-z0-9-]+)\'/', $expression, $literals);
                foreach ($literals[1] as $name) {
                    $used[$name][] = $where;
                }
            }
            foreach (array_filter([...$mapped[1], ...$mapped[2]]) as $name) {
                $used[$name][] = $where;
            }
        }

        $this->assertNotEmpty($used);
        $missing = array_diff_key($used, $known);
        $this->assertSame([], array_map(fn (array $files): string => implode(', ', array_unique($files)), $missing), 'Иконок нет в resources/icons');
    }

    public function test_component_renders_versioned_sprite_reference(): void
    {
        $html = Blade::render('<x-site-icon name="search" class="header__search-icon" width="20" height="20" />');

        $this->assertStringContainsString('class="icon header__search-icon"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertMatchesRegularExpression('#<use href="/assets/icons/sprite\.svg\?v=[0-9a-f]{10}\#search"></use>#', $html);
    }

    public function test_internal_ids_are_scoped_to_their_icon(): void
    {
        $symbol = IconSprite::symbol('demo', '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"><g id="Group" clip-path="url(#clip0)"><path d="M0 0h16v16H0z"/></g><defs><clipPath id="clip0"><rect width="16" height="16"/></clipPath></defs></svg>');

        $this->assertStringStartsWith('<symbol id="demo" viewBox="0 0 16 16" fill="none">', $symbol);
        $this->assertStringContainsString('clip-path="url(#demo--clip0)"', $symbol);
        $this->assertStringContainsString('<clipPath id="demo--clip0">', $symbol);
        $this->assertStringNotContainsString('id="Group"', $symbol);
    }
}
