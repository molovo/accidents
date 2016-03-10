<?php

// Functions for manipulating package versions
group('version', function () {
    // Get the current version number
    $current = explode('.', trim(file_get_contents('.version')));

    // Bump the major version (x.0.0)
    task('major', function () use (&$current) {
        $current[0] = $current[0] + 1;
        $current[1] = 0;
        $current[2] = 0;
        run('version:update_changelog');
        run('version:bump_version');
        run('version:tag_repository');
    });

    // Bump the minor version (1.x.0)
    task('minor', function () use (&$current) {
        $current[1] = $current[1] + 1;
        $current[2] = 0;
        run('version:update_changelog');
        run('version:bump_version');
        run('version:tag_repository');
    });

    // Bump the patch version (1.0.x)
    task('patch', function () use (&$current) {
        $current[2] = $current[2] + 1;
        run('version:update_changelog');
        run('version:bump_version');
        run('version:tag_repository');
    });

    // Update the changelog
    task('update_changelog', function () use (&$current) {
        $version = implode('.', $current);

        // We redefine $EDITOR and $VISUAL to stop git-changelog
        // from trying to open an editor in the middle of our script
        exec('export OLD_EDITOR=${EDITOR}; export EDITOR=cat; export OLD_VISUAL=${VISUAL}; export VISUAL=cat; git changelog -t v'.$version.' > /dev/null; export EDITOR=${OLD_EDITOR}; unset ${OLD_EDITOR}; export VISUAL=${OLD_VISUAL}; unset ${OLD_VISUAL}');
    });

    // Bump the stored version number
    task('bump_version', function () use (&$current) {
        $version = implode('.', $current);
        file_put_contents('.version', $version);
        exec('git add .version CHANGELOG.md');
        exec('git commit -m "Bump Version"');
    });

    // Tag the repository
    task('tag_repository', function () use (&$current) {
        $version = implode('.', $current);
        exec('git tag -a v'.$version.' -m "Release '.$version.'"');
    });
});
