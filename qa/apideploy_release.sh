#!/bin/bash
# deploy_release.sh – for qa-api

ARTIFACT=$1
if [ -z "$ARTIFACT" ]; then echo "Usage: $0 /path/to/artifact"; exit 1; fi

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=~/deploy/backups/

mkdir -p $BACKUP_DIR

# Backup current API config
tar -czf $BACKUP_DIR/backup-config.tar.gz -C "/home/fm369/it490/it490" .

# Deploy new config
tar -xzf $ARTIFACT/api-config.tar.gz -C "/home/fm369/it490/it490" .

echo "API config deployed from $ARTIFACT"
