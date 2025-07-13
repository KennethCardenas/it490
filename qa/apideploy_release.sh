#!/bin/bash
# deploy_release.sh – for qa-api

ARTIFACT=$1
if [ -z "$ARTIFACT" ]; then echo "Usage: $0 /path/to/artifact"; exit 1; fi

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR=~/deploy/backups/config/$DATE

mkdir -p $BACKUP_DIR

# Backup current API config
cp -r /etc/app-config $BACKUP_DIR

# Deploy new config
tar -xzf $ARTIFACT/api-config.tar.gz -C /etc/

echo "API config deployed from $ARTIFACT"
