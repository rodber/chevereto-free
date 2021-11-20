#!/usr/bin/env bash
set -e
SOURCE=/var/www/source/
INSTALLER=/var/www/installer/
TARGET=/var/www/html/
EXCLUDE="\.git|\.DS_Store|\/docs"
mkdir -p $INSTALLER
cp "${SOURCE}".gitignore "${INSTALLER}".gitignore
function sync() {
    rsync -r -I -og \
        --chown=www-data:www-data \
        --info=progress2 \
        --filter=':- .gitignore' \
        --exclude '.git' \
        --delete \
        $SOURCE $INSTALLER
    # php "${INSTALLER}"src/build.php
    # cp "${INSTALLER}"build/installer.php "${TARGET}"installer.php
    # chown www-data: $TARGET -R
}
sync
inotifywait \
    --event create \
    --event delete \
    --event modify \
    --event move \
    --format "%e %w%f" \
    --exclude $EXCLUDE \
    --monitor \
    --recursive \
    $SOURCE |
    while read CHANGED; do
        sync
    done
