#!/bin/bash
# promote_to_qa.sh - run from dev to push to qa

QA_USER=qa_db
QA_IP=100.91.149.60
ARTIFACT=$(ls -td ~/deploy/artifacts/* | head -n 1)

scp "$ARTIFACT" "$QA_USER@$QA_IP:~/deploy/releases/"
ssh "$QA_USER@$QA_IP" "~/deploy/releases/$(basename $ARTIFACT)/deploy_release.sh ~/deploy/releases/$(basename $ARTIFACT)"
