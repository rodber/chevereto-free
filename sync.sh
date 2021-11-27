#!/usr/bin/env bash
set -e
SOURCE=/var/www/chevereto-free/
TARGET=/var/www/html/
EXCLUDE="\.git|\.DS_Store|\/docs"
cp "${SOURCE}".gitignore "${TARGET}".gitignore
function sync() {
    cp "${SOURCE}"sync.sh /var/www/sync.sh
    rsync -r -I -og \
        --chown=www-data:www-data \
        --info=progress2 \
        --exclude '.git' \
        --include 'content/images/system/default/*' \
        --include 'content/pages/default/*' \
        --exclude 'sync.sh' \
        --exclude 'content/images/system/*' \
        --exclude 'content/images/users/*' \
        --exclude 'content/pages/*' \
        --filter=':- .gitignore' \
        --filter=':- .dockerignore' \
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
