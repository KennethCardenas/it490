#!/bin/bash

# Configuration
DEV_DB_NAME="BARKBUDDY"
DEV_DB_USER="root"
DEV_DB_PASS="Linklinkm1!"

QA_DB_USER="root"
QA_DB_PASS="Linklinkm1!"
QA_IP="100.91.149.60"
QA_TMP_PATH="/tmp"

DUMP_FILE="/tmp/${DEV_DB_NAME}_dump.sql"

echo "[*] Dumping database from DEV..."
mysqldump -u "$DEV_DB_USER" -p"$DEV_DB_PASS" "$DEV_DB_NAME" > "$DUMP_FILE"
if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to dump database."
  exit 1
fi

echo "[*] Transferring dump to QA..."
scp "$DUMP_FILE" qa_db@"$QA_IP":"$QA_TMP_PATH"
if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to transfer dump to QA."
  exit 1
fi

echo "[*] Restoring dump on QA..."
ssh qa_db@"$QA_IP" "mysql -u $QA_DB_USER -p$QA_DB_PASS $DEV_DB_NAME < $QA_TMP_PATH/${DEV_DB_NAME}_dump.sql"
if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to restore dump on QA."
  exit 1
fi

echo "[✓] DB promotion from DEV to QA complete."
