#!/bin/bash

# === CONFIGURATION ===
DEV_DB_HOST="100.70.204.26"
DEV_DB_PORT="3306"
DEV_DB_USER="BARKBUDDYUSER"
DEV_DB_PASS="new_secure_password"
DEV_DB_NAME="BARKBUDDY"

QA_USER="qa-db"
QA_IP="100.91.149.60"
QA_DB_USER="BARKBUDDYUSER"
QA_DB_PASS="test12"  # QA password
DUMP_FILE="dev_dump.sql"
REMOTE_PATH="/tmp/$DUMP_FILE"

echo "[*] Dumping database from DEV ($DEV_DB_HOST)..."
mysqldump -h $DEV_DB_HOST -P $DEV_DB_PORT -u $DEV_DB_USER -p"$DEV_DB_PASS" $DEV_DB_NAME > $DUMP_FILE

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to dump database from DEV."
  exit 1
fi

echo "[*] Copying dump to QA ($QA_IP)..."
scp $DUMP_FILE $QA_USER@$QA_IP:$REMOTE_PATH

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to copy dump to QA."
  exit 1
fi

echo "[*] Restoring dump on QA..."
ssh $QA_USER@$QA_IP "mysql -u $QA_DB_USER -p'$QA_DB_PASS' $DEV_DB_NAME < $REMOTE_PATH"

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to restore database on QA."
  exit 1
fi

echo "[✓] Database promoted from DEV to QA successfully."

