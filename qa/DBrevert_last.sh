#!/bin/bash
# revert_last.sh – for qa-db

LAST=$(ls -t ~/deploy/backups/db/*.sql | head -n 1)
if [ -z "$LAST" ]; then echo "No backup found."; exit 1; fi

mysql -u root -pYourPassword BARKBUDDY < $LAST
echo "Restored schema from $LAST"
