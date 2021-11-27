#!/usr/bin/env bash
set -e
SOURCE=/var/www/chevereto-free/
TARGET=/var/www/html/
EXCLUDE="\.git|\.DS_Store|\/docs"
cp "${SOURCE}".gitignore "${TARGET}".gitignore
function sync() {
    rsync -r -I -og \
        --chown=www-data:www-data \
        --info=progress2 \
        --filter=':- .gitignore' \
        --filter=':- .dockerignore' \
        --exclude '.git' \
        --exclude 'sync.sh' \
        --delete \
        $SOURCE $TARGET
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
