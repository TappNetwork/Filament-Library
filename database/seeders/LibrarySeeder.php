<?php

namespace Tapp\FilamentLibrary\Database\Seeders;

use Illuminate\Database\Seeder;
use Tapp\FilamentLibrary\Models\LibraryItem;
use Tapp\FilamentLibrary\Models\LibraryItemPermission;

class LibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user as the creator
        $user = \App\Models\User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        $this->command->info('Creating sample library structure...');

        // Create root folders
        $documents = LibraryItem::create([
            'name' => 'Documents',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        $images = LibraryItem::create([
            'name' => 'Images',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        // Create subfolders under Documents
        $projects = LibraryItem::create([
            'name' => 'Projects',
            'type' => 'folder',
            'parent_id' => $documents->id,
            'created_by' => $user->id,
        ]);

        $templates = LibraryItem::create([
            'name' => 'Templates',
            'type' => 'folder',
            'parent_id' => $documents->id,
            'created_by' => $user->id,
        ]);

        // Create project subfolders
        $projectA = LibraryItem::create([
            'name' => 'Project A',
            'type' => 'folder',
            'parent_id' => $projects->id,
            'created_by' => $user->id,
        ]);

        $projectB = LibraryItem::create([
            'name' => 'Project B',
            'type' => 'folder',
            'parent_id' => $projects->id,
            'created_by' => $user->id,
        ]);

        // Create subfolders under Images
        $photos = LibraryItem::create([
            'name' => 'Photos',
            'type' => 'folder',
            'parent_id' => $images->id,
            'created_by' => $user->id,
        ]);

        $graphics = LibraryItem::create([
            'name' => 'Graphics',
            'type' => 'folder',
            'parent_id' => $images->id,
            'created_by' => $user->id,
        ]);

        // Create a Resources folder for external links
        $resources = LibraryItem::create([
            'name' => 'Resources',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        // Create some sample files (without actual media attachments for now)
        $sampleFiles = [
            [
                'name' => 'README.md',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'requirements.txt',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Documentation.pdf',
                'parent_id' => $projectB->id,
            ],
            [
                'name' => 'Project Brief.docx',
                'parent_id' => $templates->id,
            ],
            [
                'name' => 'Meeting Notes.txt',
                'parent_id' => $documents->id,
            ],
        ];

        foreach ($sampleFiles as $fileData) {
            LibraryItem::create([
                'name' => $fileData['name'],
                'type' => 'file',
                'parent_id' => $fileData['parent_id'],
                'created_by' => $user->id,
            ]);
        }

        // Create sample external links - both video and non-video
        $sampleLinks = [
            // Video links (will be embedded)
            [
                'name' => 'Laravel Tutorial - YouTube',
                'external_url' => 'https://www.youtube.com/watch?v=ImtZ5yENzgE',
                'link_icon' => 'heroicon-o-play',
                'link_description' => 'Complete Laravel tutorial for beginners',
                'parent_id' => $resources->id,
            ],
            [
                'name' => 'Filament Demo - Vimeo',
                'external_url' => 'https://vimeo.com/123456789',
                'link_icon' => 'heroicon-o-video-camera',
                'link_description' => 'Filament admin panel demonstration',
                'parent_id' => $resources->id,
            ],
            [
                'name' => 'Wistia Video Example',
                'external_url' => 'https://tappnetwork.wistia.com/medias/abc123def',
                'link_icon' => 'heroicon-o-film',
                'link_description' => 'Example Wistia video for testing',
                'parent_id' => $resources->id,
            ],
            // Non-video links (will open in new tab)
            [
                'name' => 'Laravel Documentation',
                'external_url' => 'https://laravel.com/docs',
                'link_icon' => 'heroicon-o-book-open',
                'link_description' => 'Official Laravel documentation',
                'parent_id' => $resources->id,
            ],
            [
                'name' => 'Filament Documentation',
                'external_url' => 'https://filamentphp.com/docs',
                'link_icon' => 'heroicon-o-academic-cap',
                'link_description' => 'Filament admin panel documentation',
                'parent_id' => $resources->id,
            ],
            [
                'name' => 'GitHub Repository',
                'external_url' => 'https://github.com/TappNetwork/Filament-Library',
                'link_icon' => 'heroicon-o-code-bracket',
                'link_description' => 'Source code for this library plugin',
                'parent_id' => $resources->id,
            ],
            [
                'name' => 'Stack Overflow',
                'external_url' => 'https://stackoverflow.com/questions/tagged/laravel',
                'link_icon' => 'heroicon-o-question-mark-circle',
                'link_description' => 'Laravel questions and answers',
                'parent_id' => $resources->id,
            ],
            // Links in other folders
            [
                'name' => 'Project Reference',
                'external_url' => 'https://example.com/project-reference',
                'link_icon' => 'heroicon-o-link',
                'link_description' => 'Reference documentation for Project A',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Design Inspiration',
                'external_url' => 'https://dribbble.com/shots/example',
                'link_icon' => 'heroicon-o-paint-brush',
                'link_description' => 'Design inspiration for graphics',
                'parent_id' => $graphics->id,
            ],
        ];

        foreach ($sampleLinks as $linkData) {
            LibraryItem::create([
                'name' => $linkData['name'],
                'type' => 'link',
                'external_url' => $linkData['external_url'],
                'link_icon' => $linkData['link_icon'],
                'link_description' => $linkData['link_description'],
                'parent_id' => $linkData['parent_id'],
                'created_by' => $user->id,
            ]);
        }

        // Create some sample permissions if there are other users
        $otherUsers = \App\Models\User::where('id', '!=', $user->id)->take(2)->get();

        if ($otherUsers->count() > 0) {
            $this->command->info('Creating sample permissions...');

            // Give first other user view access to Documents folder
            if ($otherUsers->count() >= 1) {
                LibraryItemPermission::create([
                    'library_item_id' => $documents->id,
                    'user_id' => $otherUsers[0]->id,
                    'permission' => 'view',
                ]);
            }

            // Give second other user edit access to Project A
            if ($otherUsers->count() >= 2) {
                LibraryItemPermission::create([
                    'library_item_id' => $projectA->id,
                    'user_id' => $otherUsers[1]->id,
                    'permission' => 'edit',
                ]);
            }
        }

        $this->command->info('Sample library structure created successfully!');
        $this->command->info('Created:');
        $this->command->info('- Documents/');
        $this->command->info('  - Projects/');
        $this->command->info('    - Project A/ (with 2 files + 1 link)');
        $this->command->info('    - Project B/ (with 1 file)');
        $this->command->info('  - Templates/ (with 1 file)');
        $this->command->info('  - Meeting Notes.txt');
        $this->command->info('- Images/');
        $this->command->info('  - Photos/');
        $this->command->info('  - Graphics/ (with 1 link)');
        $this->command->info('- Resources/ (with 7 external links)');
        $this->command->info('  - 3 video links (YouTube, Vimeo, Wistia)');
        $this->command->info('  - 4 regular links (documentation, GitHub, etc.)');

        if ($otherUsers->count() > 0) {
            $this->command->info('Sample permissions created for other users.');
        }
    }
}
