# Bulk Sync Manager

Bulk Sync Manager is a Piwigo plugin for synchronizing large local photo
archives in smaller, safer steps.

It was created for galleries where the standard synchronization can become
slow, hard to observe, or vulnerable to browser and PHP timeouts when a large
directory tree is processed in one long request.

Instead of doing one huge sync request, Bulk Sync Manager breaks the work into
short batches and gives the administrator a clearer view of what is happening.

## The Problem

Large Piwigo installations often run into the same issue:

- many nested directories
- many albums
- large imports after filesystem changes
- browser requests that take too long

Even when the native synchronization works in principle, it can become
difficult to track progress or to test changes safely on just one subtree.

## The Goal

Bulk Sync Manager focuses on one practical goal:

> Make large local synchronizations more manageable, more transparent, and less
> fragile in the browser.

## Key Features

- Batch-based synchronization for local sites
- Optional subtree-based runs using a selected root album
- Incremental directory scanning instead of one large scan request
- Album-by-album processing for better visibility
- Live activity and progress feedback in the admin interface
- Metadata mode selection:
  `only new files` or `re-read all files`
- Resume-friendly browser-driven workflow

## How It Works

The plugin splits the synchronization into two main phases:

### 1. Directory Scan

Directories are scanned in short batches. Missing albums can be discovered
without trying to scan the whole tree in one large request.

### 2. Album Processing

After the scan, albums are processed one by one. This keeps each step smaller
and makes the job status easier to understand.

## Typical Workflow

1. Open the Bulk Sync Manager page in Piwigo administration
2. Select a local site
3. Optionally select a root album for a smaller subtree run
4. Choose a metadata mode
5. Start the synchronization
6. Keep the page open while the job runs

## Metadata Modes

### Only Read Metadata For New Files

This mode is faster and more conservative.

Use it when you mainly want to import new content and do not need to refresh
metadata on already known files.

### Re-read Metadata For All Files

This mode reprocesses metadata for existing files as well.

Use it when metadata has changed outside Piwigo and should be refreshed in the
gallery.

## Current Behaviour

The plugin currently uses a browser-driven job runner.

That means:

- the admin page should remain open while a job is running
- if the page is closed, the job does not continue in the background
- reopening the page allows the stored job state to continue

This approach keeps the implementation simple and effective for interactive
admin workflows, even though it is not yet a full background worker.

## Best Use Cases

Bulk Sync Manager is especially useful when you want to:

- test synchronization on a single subtree first
- avoid long-running sync requests
- refresh a large filesystem import in smaller visible steps
- understand which part of a synchronization is currently active

## Current Limitations

- Local sites only
- No cron or CLI worker yet
- No true server-side background processing yet
- Large installations should still be tested on smaller subtrees first

## Installation

1. Copy the plugin folder into your Piwigo `plugins/` directory
2. Open the Piwigo administration area
3. Activate `Bulk Sync Manager`
4. Open the plugin page in the admin area

## Upgrade Notes

When upgrading an existing installation:

- keep the plugin database table
- open the plugin admin page once so schema updates can be applied
- test on a small subtree before starting a very large sync job

## Compatibility

Bulk Sync Manager targets modern Piwigo installations with local
filesystem-based galleries.

It was developed against a current Piwigo environment, but older versions were
not regression-tested in a full compatibility matrix.

## Project Status

This is an actively evolving utility plugin.

The core idea is already useful in practice, but the project still has room
for future improvements such as:

- cron- or CLI-based background execution
- a persistent job history view
- more advanced cleanup handling for removed directories
- richer technical reporting for administrators

## Repository Overview

- `main.inc.php` - plugin bootstrap and synchronization engine
- `admin.php` - admin controller and API entry point
- `admin.tpl` - admin user interface
- `maintain.inc.php` - install/update schema handling

## README Ideas For GitHub

If you want to grow the GitHub project page further, good additions would be:

- screenshots of the admin interface
- a short changelog or releases section
- a roadmap
- known issues
- example scenarios

## License

See [LICENSE](LICENSE).

## Author

Frank Hommes  
http://www.freanki.net/
