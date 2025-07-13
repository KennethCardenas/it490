#!/bin/bash
# revert_last.sh – for qa-app

LAST_BACKUP=$(ls -td ~/deploy/backups/code/* | head -n 1)
if [ -z "$LAST_BACKUP" ]; then echo "No backup found."; exit 1; fi

rm -rf /var/www/barkbuddy
cp -r $LAST_BACKUP/barkbuddy /var/www/

echo "Restored backup from $LAST_BACKUP"
