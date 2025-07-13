#!/bin/bash

QA_USER=kac63
QA_DB_IP=10.0.2.15
LATEST_ARTIFACT=$(ls -td ~/deploy/artifacts/* | head -n 1)

# Ensure schema.sql exists
if [ ! -f "$LATEST_ARTIFACT/schema.sql" ]; then
    echo "No schema.sql found in $LATEST_ARTIFACT"
    exit 1
fi

# Transfer artifact
scp "$LATEST_ARTIFACT/schema.sql" "$QA_USER@$QA_DB_IP:~/deploy/releases/"

# Trigger deploy script on QA
ssh "$QA_USER@$QA_DB_IP" "~/deploy/deploy_release.sh ~/deploy/releases/schema.sql"
