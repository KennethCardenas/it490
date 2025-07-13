#!/bin/bash
# promote_to_qa.sh – run from dev to push to qa

QA_USER=kac63
QA_IP=100.87.203.201
ARTIFACT=$(ls -td ~/deploy/artifacts/* | head -n 1)

scp -r "$ARTIFACT" "$QA_USER@$QA_IP:~/deploy/releases/"

ssh "$QA_USER@$QA_IP" "~/deploy/releases/$(basename $ARTIFACT)/deploy_release.sh ~/deploy/releases/$(basename $ARTIFACT)"
