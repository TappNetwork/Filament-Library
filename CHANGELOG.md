# Changelog

All notable changes to `:package_name` will be documented in this file.

## v1.3.7 - 2026-07-13

### What's Changed

* Assign Users / Manage Permissions: add **Select all matching users** toggle to select every user matching the current filters (not just the first 50 search results)
* Replace built-in `user_filters` config with host-owned `assignment.filter_provider` contract for domain-specific assignment filters
* Fix `sharing.show_nested_shared_in_library` so it also gates root-level shared items in the main Library view (classic default `false` restores pre-v1.3.4 behavior)

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.5...v1.3.7

## v1.3.5 - 2026-07-10

### What's Changed

* Opt-in sharing/permission UX for host apps (defaults preserve classic behavior):
  * `sharing.shared_with_me` (default `true`)
  * `sharing.show_nested_shared_in_library` (default `false`)
  * `permissions.bulk_manage_action` (default `false`)
  * `permissions.filter_based_assignment` (default `false`)
  * optional `user_filters` for community / role / signup date

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.4...v1.3.5

## v1.3.4 - 2026-07-09

### What's Changed

* feat: show shared items in main Library view by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/19

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.3...v1.3.4

## v1.3.3 - 2026-06-11

### What's Changed

* Update JSON types preview by @andreia in https://github.com/TappNetwork/Filament-Library/pull/28

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.2...v1.3.3

## v1.3.2 - 2026-06-10

### What's Changed

* Add file restored event by @andreia in https://github.com/TappNetwork/Filament-Library/pull/27

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.1...v1.3.2

## v1.3.1 - 2026-06-09

### What's Changed

* Fix download preview test for Filament href escaping by @swilla in https://github.com/TappNetwork/Filament-Library/pull/26

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.3.0...v1.3.1

## v1.3.0 - 2026-06-09

### What's Changed

* Add rich file previews for markdown and JSON exports. by @swilla in https://github.com/TappNetwork/Filament-Library/pull/25

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.2.1...v1.3.0

## v1.2.1 - 2026-06-06

### What's Changed

* Add configurable resources and personal folder by @andreia in https://github.com/TappNetwork/Filament-Library/pull/24

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.2.0...v1.2.1

## v1.2.0 - 2026-04-26

### What's Changed

* Bump dependabot/fetch-metadata from 3.0.0 to 3.1.0 by @dependabot[bot] in https://github.com/TappNetwork/Filament-Library/pull/22
* Add LibraryFileStored event by @andreia in https://github.com/TappNetwork/Filament-Library/pull/23

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.1.0...v1.2.0

## v1.1.0 - 2026-04-14

### What's Changed

* Bump dependabot/fetch-metadata from 2.5.0 to 3.0.0 by @dependabot[bot] in https://github.com/TappNetwork/Filament-Library/pull/18
* Bump ramsey/composer-install from 3 to 4 by @dependabot[bot] in https://github.com/TappNetwork/Filament-Library/pull/17
* Add Laravel 13 support by @swilla in https://github.com/TappNetwork/Filament-Library/pull/20
* Upgrade Tailwind CSS from v3 to v4 by @swilla in https://github.com/TappNetwork/Filament-Library/pull/21

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.6...v1.1.0

## v1.0.6 - 2026-02-05

### What's Changed

* Fix: Use migration stubs and avoid duplicate migrations on republish by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/16

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.5...v1.0.6

## v1.0.5 - 2026-01-22

### What's Changed

* Update media library to v5 by @andreia in https://github.com/TappNetwork/Filament-Library/pull/15

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.4...v1.0.5

## v1.0.4 - 2026-01-22

### What's Changed

* build(deps): bump actions/checkout from 4 to 6 by @dependabot[bot] in https://github.com/TappNetwork/Filament-Library/pull/10
* Add Filament 5 support by @andreia in https://github.com/TappNetwork/Filament-Library/pull/14

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.3...v1.0.4

## v1.0.3 - 2026-01-07

### What's Changed

* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/TappNetwork/Filament-Library/pull/13
* Multi-tenancy support by @andreia in https://github.com/TappNetwork/Filament-Library/pull/6

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/TappNetwork/Filament-Library/pull/13

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.2...v1.0.3

## v1.0.2 - 2025-12-12

### What's Changed

* Update navigation items and permissions check by @andreia in https://github.com/TappNetwork/Filament-Library/pull/12

### New Contributors

* @andreia made their first contribution in https://github.com/TappNetwork/Filament-Library/pull/12

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.1...v1.0.2

## v1.0.1 - 2025-12-03

### What's Changed

* Fix: Add validation for duplicate tags when creating new tags by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/11

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/compare/v1.0.0...v1.0.1

## v1.0.0 - 2025-11-13

### What's Changed

* feat: Plugin Foundation & Basic Models by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/2
* Feature/migration by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/3
* Laravel 12 Support, and Dependencies by @swilla in https://github.com/TappNetwork/Filament-Library/pull/5
* Increase file upload size limits from 10MB to 500MB by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/8
* Add tags field to edit resource forms (EditFile, EditLink, EditFolder) by @scottgrayson in https://github.com/TappNetwork/Filament-Library/pull/9

### New Contributors

* @scottgrayson made their first contribution in https://github.com/TappNetwork/Filament-Library/pull/2
* @swilla made their first contribution in https://github.com/TappNetwork/Filament-Library/pull/5

**Full Changelog**: https://github.com/TappNetwork/Filament-Library/commits/v1.0.0

## 1.0.0 - 2025-01-16

### Added

- Initial release of Filament Library plugin
- File and folder management with Google Drive-style permissions
- External link support
- Advanced permission system with inheritance
- Multiple library views (Public, My Documents, Shared with Me, Created by Me, Search All, Favorites)
- Bulk permission management
- Configurable admin access
- Filament integration with native UI components
