<?php

namespace App\Console\Commands;

use App\Support\IconSprite;
use Illuminate\Console\Command;

class BuildIconSpriteCommand extends Command
{
    protected $signature = 'icons:build';

    protected $description = 'Собирает SVG-спрайт public/assets/icons/sprite.svg из resources/icons/*.svg';

    public function handle(): int
    {
        $sprite = IconSprite::build();
        file_put_contents(public_path(IconSprite::SPRITE_PATH), $sprite);

        $this->info('Icons: '.count(IconSprite::names()).', sprite: '.number_format(strlen($sprite) / 1024, 1).' KB → public/'.IconSprite::SPRITE_PATH);

        return self::SUCCESS;
    }
}
