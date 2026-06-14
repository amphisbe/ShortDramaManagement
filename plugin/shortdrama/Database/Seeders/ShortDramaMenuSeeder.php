<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Plugin\ShortDrama\Installer\ModelInstallStore;
use Plugin\ShortDrama\Installer\ShortDramaInstaller;

class ShortDramaMenuSeeder extends Seeder
{
    public function run(): void
    {
        (new ShortDramaInstaller(new ModelInstallStore()))->install();
    }
}
