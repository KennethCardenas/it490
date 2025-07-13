#!/bin/bash
# deploy_release.sh – for qa-db

ARTIFACT=$1
if [ -z "$ARTIFACT" ]; then echo "Usage: $0 /path/to/artifact"; exit 1; fi

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP=~/deploy/backups/db/schema_backup_$DATE.sql

# Backup current schema
mysqldump -u root -pYourPassword --no-data BARKBUDDY > $BACKUP

# Deploy new schema
mysql -u root -pYourPassword BARKBUDDY < $ARTIFACT/schema.sql

echo "Database schema updated from $ARTIFACT"
