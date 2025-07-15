#!/bin/bash

DEV_DIR="/var/www/dev/mq"
QA_DIR="/var/www/qa/mq"
BACKUP_DIR="/var/backups/mq"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_NAME="mq_qa_backup_$TIMESTAMP.tar.gz"

echo "[INFO] Backing up current QA MQ..."
sudo mkdir -p "$BACKUP_DIR"
sudo tar -czf "$BACKUP_DIR/$BACKUP_NAME" -C "$QA_DIR" .

echo "[INFO] Promoting MQ code from dev to qa..."
sudo cp -r "$DEV_DIR/"* "$QA_DIR/"

echo "[DONE] Promotion complete. Backup saved as $BACKUP_NAME"
