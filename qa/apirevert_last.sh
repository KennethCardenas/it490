#!/bin/bash
# revert_last.sh – for qa-api

LAST_BACKUP=$(ls -td ~/deploy/backups/config/* | head -n 1)
if [ -z "$LAST_BACKUP" ]; then echo "No backup found."; exit 1; fi

rm -rf /etc/app-config
cp -r $LAST_BACKUP/app-config /etc/

echo "API config restored from $LAST_BACKUP"
