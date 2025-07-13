#!/bin/bash
# create_artifacts.sh – Run this from the dev VM

DATE=$(date +%Y%m%d_%H%M%S)
VERSION="release_$DATE"
ARTIFACT_DIR=~/deploy/artifacts/$VERSION

mkdir -p $ARTIFACT_DIR

# App Code
tar -czf $ARTIFACT_DIR/code.tar.gz -C /var/www barkbuddy

# API Config
tar -czf $ARTIFACT_DIR/api-config.tar.gz -C /etc app-config

# DB Schema only (no data)
mysqldump -u root -pYourPassword --no-data BARKBUDDY > $ARTIFACT_DIR/schema.sql

echo "Created deployment artifact at $ARTIFACT_DIR"
