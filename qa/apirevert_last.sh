#!/bin/bash
# revert_last.sh – for qa-api

LAST_BACKUP=$(ls -td ~/deploy/backups/ | head -n 1)
if [ -z "$LAST_BACKUP" ]; then echo "No backup found."; exit 1; fi

tar -xzf $LAST_BACKUP/backup-config.tar.gz -C "/home/fm369/it490/it490" .

echo "API config restored from $LAST_BACKUP"
