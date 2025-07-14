#!/bin/bash

DB_USER="BARKBUDDYUSER"
DB_PASS="new_secure_password"
DB_NAME="BARKBUDDY"
BACKUP_NAME="barkbuddy_backup_$(date +%Y%m%d_%H%M%S).sql"
BACKUP_DIR="~/deploy/backups"

mkdir -p $BACKUP_DIR
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/$BACKUP_NAME && \
echo "[✓] Backup complete: $BACKUP_NAME" || \
echo "[✗] Backup failed"
