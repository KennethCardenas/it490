#!/bin/bash

DB_USER="BARKBUDDYUSER"
DB_PASS="new_secure_password"
DB_NAME="BARKBUDDY"
BACKUP_DIR="~/deploy/backups"

echo "Available backups:"
ls -lh $BACKUP_DIR

read -p "Enter the exact filename to restore: " FILE
if [ -f "$BACKUP_DIR/$FILE" ]; then
  mysql -u $DB_USER -p$DB_PASS $DB_NAME < $BACKUP_DIR/$FILE && \
  echo "[✓] Restore complete" || \
  echo "[✗] Restore failed"
else
  echo "[✗] File not found"
fi
