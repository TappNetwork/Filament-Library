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

        $this->command->info('Clearing existing library data...');

        // Force delete all existing library items and permissions
        LibraryItemPermission::query()->forceDelete();
        LibraryItem::query()->forceDelete();

        $this->command->info('Creating sample library structure...');

        // Create root folders with descriptive names
        $documents = LibraryItem::create([
            'name' => 'Project Documents & Files',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        $images = LibraryItem::create([
            'name' => 'Images & Media Assets',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        $resources = LibraryItem::create([
            'name' => 'External Links & Resources',
            'type' => 'folder',
            'created_by' => $user->id,
        ]);

        // Create subfolders under Documents
        $projects = LibraryItem::create([
            'name' => 'Active Projects',
            'type' => 'folder',
            'parent_id' => $documents->id,
            'created_by' => $user->id,
        ]);

        $templates = LibraryItem::create([
            'name' => 'Document Templates',
            'type' => 'folder',
            'parent_id' => $documents->id,
            'created_by' => $user->id,
        ]);

        $meetings = LibraryItem::create([
            'name' => 'Meeting Notes & Minutes',
            'type' => 'folder',
            'parent_id' => $documents->id,
            'created_by' => $user->id,
        ]);

        // Create project subfolders
        $projectA = LibraryItem::create([
            'name' => 'Website Redesign Project',
            'type' => 'folder',
            'parent_id' => $projects->id,
            'created_by' => $user->id,
        ]);

        $projectB = LibraryItem::create([
            'name' => 'Mobile App Development',
            'type' => 'folder',
            'parent_id' => $projects->id,
            'created_by' => $user->id,
        ]);

        // Create subfolders under Images
        $photos = LibraryItem::create([
            'name' => 'Team Photos & Events',
            'type' => 'folder',
            'parent_id' => $images->id,
            'created_by' => $user->id,
        ]);

        $graphics = LibraryItem::create([
            'name' => 'Graphics & Design Assets',
            'type' => 'folder',
            'parent_id' => $images->id,
            'created_by' => $user->id,
        ]);

        $logos = LibraryItem::create([
            'name' => 'Logos & Branding',
            'type' => 'folder',
            'parent_id' => $images->id,
            'created_by' => $user->id,
        ]);

        // Create subfolders under Resources
        $videoResources = LibraryItem::create([
            'name' => 'Video Tutorials & Demos',
            'type' => 'folder',
            'parent_id' => $resources->id,
            'created_by' => $user->id,
        ]);

        $documentation = LibraryItem::create([
            'name' => 'Documentation & Guides',
            'type' => 'folder',
            'parent_id' => $resources->id,
            'created_by' => $user->id,
        ]);

        $tools = LibraryItem::create([
            'name' => 'Development Tools & Links',
            'type' => 'folder',
            'parent_id' => $resources->id,
            'created_by' => $user->id,
        ]);

        // Create some sample files (without actual media attachments for now)
        $sampleFiles = [
            // Website Redesign Project files
            [
                'name' => 'Project Overview & Requirements.md',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Technical Specifications.pdf',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Design Mockups & Wireframes.zip',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Development Timeline.xlsx',
                'parent_id' => $projectA->id,
            ],
            // Mobile App Development files
            [
                'name' => 'App Architecture Document.pdf',
                'parent_id' => $projectB->id,
            ],
            [
                'name' => 'User Stories & Requirements.docx',
                'parent_id' => $projectB->id,
            ],
            [
                'name' => 'API Documentation.md',
                'parent_id' => $projectB->id,
            ],
            // Template files
            [
                'name' => 'Project Proposal Template.docx',
                'parent_id' => $templates->id,
            ],
            [
                'name' => 'Meeting Agenda Template.docx',
                'parent_id' => $templates->id,
            ],
            [
                'name' => 'Technical Review Checklist.pdf',
                'parent_id' => $templates->id,
            ],
            // Meeting notes
            [
                'name' => 'Weekly Team Standup - Jan 15, 2024.txt',
                'parent_id' => $meetings->id,
            ],
            [
                'name' => 'Project Kickoff Meeting Notes.docx',
                'parent_id' => $meetings->id,
            ],
            [
                'name' => 'Client Feedback Session - Jan 20, 2024.pdf',
                'parent_id' => $meetings->id,
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
            // Video Tutorials & Demos
            [
                'name' => 'Laravel 11 Complete Tutorial - YouTube',
                'external_url' => 'https://www.youtube.com/watch?v=ImtZ5yENzgE',
                'link_icon' => 'heroicon-o-play',
                'link_description' => 'Complete Laravel 11 tutorial covering all features',
                'parent_id' => $videoResources->id,
            ],
            [
                'name' => 'Filament Admin Panel Demo - Vimeo',
                'external_url' => 'https://vimeo.com/123456789',
                'link_icon' => 'heroicon-o-video-camera',
                'link_description' => 'Comprehensive Filament admin panel demonstration',
                'parent_id' => $videoResources->id,
            ],
            [
                'name' => 'Wistia Video Testing Example',
                'external_url' => 'https://tappnetwork.wistia.com/medias/abc123def',
                'link_icon' => 'heroicon-o-film',
                'link_description' => 'Example Wistia video for testing video embedding',
                'parent_id' => $videoResources->id,
            ],
            [
                'name' => 'React vs Vue.js Comparison - YouTube',
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'link_icon' => 'heroicon-o-play',
                'link_description' => 'Detailed comparison between React and Vue.js frameworks',
                'parent_id' => $videoResources->id,
            ],
            // Documentation & Guides
            [
                'name' => 'Laravel Official Documentation',
                'external_url' => 'https://laravel.com/docs',
                'link_icon' => 'heroicon-o-book-open',
                'link_description' => 'Official Laravel framework documentation',
                'parent_id' => $documentation->id,
            ],
            [
                'name' => 'Filament Documentation',
                'external_url' => 'https://filamentphp.com/docs',
                'link_icon' => 'heroicon-o-academic-cap',
                'link_description' => 'Complete Filament admin panel documentation',
                'parent_id' => $documentation->id,
            ],
            [
                'name' => 'PHP Best Practices Guide',
                'external_url' => 'https://www.php.net/manual/en/',
                'link_icon' => 'heroicon-o-document-text',
                'link_description' => 'PHP official manual and best practices',
                'parent_id' => $documentation->id,
            ],
            [
                'name' => 'MySQL Database Design Guide',
                'external_url' => 'https://dev.mysql.com/doc/',
                'link_icon' => 'heroicon-o-circle-stack',
                'link_description' => 'MySQL database design and optimization guide',
                'parent_id' => $documentation->id,
            ],
            // Development Tools & Links
            [
                'name' => 'GitHub Repository - Filament Library',
                'external_url' => 'https://github.com/TappNetwork/Filament-Library',
                'link_icon' => 'heroicon-o-code-bracket',
                'link_description' => 'Source code for this Filament Library plugin',
                'parent_id' => $tools->id,
            ],
            [
                'name' => 'Stack Overflow - Laravel Questions',
                'external_url' => 'https://stackoverflow.com/questions/tagged/laravel',
                'link_icon' => 'heroicon-o-question-mark-circle',
                'link_description' => 'Laravel questions and answers community',
                'parent_id' => $tools->id,
            ],
            [
                'name' => 'Composer Package Manager',
                'external_url' => 'https://packagist.org/',
                'link_icon' => 'heroicon-o-puzzle-piece',
                'link_description' => 'PHP package repository and manager',
                'parent_id' => $tools->id,
            ],
            [
                'name' => 'Laravel Forge - Server Management',
                'external_url' => 'https://forge.laravel.com/',
                'link_icon' => 'heroicon-o-server',
                'link_description' => 'Laravel Forge for server deployment and management',
                'parent_id' => $tools->id,
            ],
            // Project-specific links
            [
                'name' => 'Website Design Inspiration - Dribbble',
                'external_url' => 'https://dribbble.com/shots/example',
                'link_icon' => 'heroicon-o-paint-brush',
                'link_description' => 'Design inspiration for website redesign project',
                'parent_id' => $projectA->id,
            ],
            [
                'name' => 'Mobile App UI Kit - Figma',
                'external_url' => 'https://figma.com/example-ui-kit',
                'link_icon' => 'heroicon-o-device-phone-mobile',
                'link_description' => 'UI kit for mobile app development project',
                'parent_id' => $projectB->id,
            ],
            [
                'name' => 'Brand Guidelines - Company Wiki',
                'external_url' => 'https://company-wiki.com/brand-guidelines',
                'link_icon' => 'heroicon-o-identification',
                'link_description' => 'Company brand guidelines and logo usage',
                'parent_id' => $logos->id,
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
        $this->command->info('Project Documents & Files/');
        $this->command->info('  Active Projects/');
        $this->command->info('    Website Redesign Project/ (with 4 files + 1 link)');
        $this->command->info('    Mobile App Development/ (with 3 files + 1 link)');
        $this->command->info('  Document Templates/ (with 3 template files)');
        $this->command->info('  Meeting Notes & Minutes/ (with 3 meeting files)');
        $this->command->info('Images & Media Assets/');
        $this->command->info('  Team Photos & Events/');
        $this->command->info('  Graphics & Design Assets/');
        $this->command->info('  Logos & Branding/ (with 1 brand guidelines link)');
        $this->command->info('External Links & Resources/');
        $this->command->info('  Video Tutorials & Demos/ (with 4 video links)');
        $this->command->info('  Documentation & Guides/ (with 4 documentation links)');
        $this->command->info('  Development Tools & Links/ (with 4 tool links)');

        if ($otherUsers->count() > 0) {
            $this->command->info('Sample permissions created for other users.');
        }
    }
}
