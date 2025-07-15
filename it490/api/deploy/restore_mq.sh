#!/bin/bash

QA_DIR="/var/www/qa/mq"
BACKUP_DIR="/var/backups/mq"

echo "[INFO] Available Backups:"
ls -1 "$BACKUP_DIR"

echo ""
read -p "Enter the exact backup filename to restore: " BACKUP_FILE

if [ -f "$BACKUP_DIR/$BACKUP_FILE" ]; then
  echo "[INFO] Restoring QA MQ..."
  sudo rm -rf "$QA_DIR"/*
  sudo tar -xzf "$BACKUP_DIR/$BACKUP_FILE" -C "$QA_DIR"
  echo "[DONE] Restore complete from $BACKUP_FILE"
else
  echo "[ERROR] Backup file not found!"
fi