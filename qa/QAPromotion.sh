#!/bin/bash
# promote_to_qa.sh – run from dev to push to qa

QA_USER=fm369
QA_IP=10.0.0.183
ARTIFACT=$(ls -td ~/deploy/artifacts/* | head -n 1)

scp -r "$ARTIFACT" "$QA_USER@$QA_IP:~/deploy/releases/"

ssh "$QA_USER@$QA_IP" "~/deploy/releases/$(basename $ARTIFACT)/deploy_release.sh ~/deploy/releases/$(basename $ARTIFACT)"

