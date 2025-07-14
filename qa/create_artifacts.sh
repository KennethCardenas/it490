#!/bin/bash
# create_artifacts.sh – Run this from the dev VM

DATE=$(date +%Y%m%d_%H%M%S)
VERSION="release_$DATE"
ARTIFACT_DIR=~/deploy/artifacts/$VERSION

mkdir -p $ARTIFACT_DIR

# API Config
tar -czf $ARTIFACT_DIR/api-config.tar.gz -C "/home/fm369/it490/it490" .

echo "Created deployment artifact at $ARTIFACT_DIR"
