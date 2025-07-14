#!/bin/bash

echo "[*] Dumping database from DEV..."

mysqldump -h 100.70.204.26 -P 3306 -u BARKBUDDYUSER -p'new_secure_password' BARKBUDDY > dev_dump.sql

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to dump database."
  exit 1
fi

echo "[*] Copying dump to QA..."

scp dev_dump.sql qa_db@100.91.149.60:/tmp/dev_dump.sql

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to copy dump to QA."
  exit 1
fi

echo "[*] Restoring dump on QA..."

ssh qa_db@100.91.149.60 "mysql -u BARKBUDDYUSER -p'new_secure_password' BARKBUDDY < /tmp/dev_dump.sql"

if [ $? -ne 0 ]; then
  echo "[!] Error: Failed to import dump on QA."
  exit 1
fi

echo "[✓] Database promoted from DEV to QA successfully."
