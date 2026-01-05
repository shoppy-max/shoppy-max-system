# Server Utilities

This directory contains utility scripts and files for server maintenance and debugging.

## Files

### `database_fresh_install.sql`

A SQL dump file for a fresh installation of the ShoppyMax database schema. This can be used to reset the database to a known state.

### `debug_login.php`

A standalone PHP script to debug login issues directly against the database, bypassing the Laravel application.

## Usage

**WARNING: These files contain sensitive information or powerful capabilities.**

-   Do not leave `debug_login.php` accessible on a public server for longer than necessary.
-   Ensure `database_fresh_install.sql` is not downloadable by unauthorized users.
