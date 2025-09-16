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
        $this->command->info('    - Project A/ (with 2 files)');
        $this->command->info('    - Project B/ (with 1 file)');
        $this->command->info('  - Templates/ (with 1 file)');
        $this->command->info('  - Meeting Notes.txt');
        $this->command->info('- Images/');
        $this->command->info('  - Photos/');
        $this->command->info('  - Graphics/');

        if ($otherUsers->count() > 0) {
            $this->command->info('Sample permissions created for other users.');
        }
    }
}
