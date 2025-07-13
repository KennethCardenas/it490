#!/bin/bash
# deploy_release.sh – for qa-app

ARTIFACT=$1
if [ -z "$ARTIFACT" ]; then echo "Usage: $0 /path/to/artifact"; exit 1; fi

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=~/deploy/backups/code/$DATE

mkdir -p $BACKUP_DIR

# Backup current code
cp -r /var/www/barkbuddy $BACKUP_DIR

# Deploy new code
tar -xzf $ARTIFACT/code.tar.gz -C /var/www/

echo "Deployed app code from $ARTIFACT"
