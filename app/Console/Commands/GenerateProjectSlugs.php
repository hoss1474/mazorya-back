<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\ProjectTranslation;

class GenerateProjectSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generate-project-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $items = ProjectTranslation::whereNull('slug')->get();

        foreach ($items as $item) {
            $baseSlug = Str::slug($item->name);
            $slug = $baseSlug;
            $i = 1;

            while (ProjectTranslation::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i;
                $i++;
            }

            $item->slug = $slug;
            $item->save();
        }

        $this->info('Slugs generated successfully!');
    }
}
