#!/bin/bash
# promote_to_qa.sh - run from dev to push to qa

<<<<<<< HEAD
QA_USER=fm369
QA_IP=10.0.0.183
=======
QA_USER=qa_db
QA_IP=100.91.149.60
>>>>>>> f2ed240598af22bd8a5303f8ad04f335b796f556
ARTIFACT=$(ls -td ~/deploy/artifacts/* | head -n 1)

scp "$ARTIFACT" "$QA_USER@$QA_IP:~/deploy/releases/"
ssh "$QA_USER@$QA_IP" "~/deploy/releases/$(basename $ARTIFACT)/deploy_release.sh ~/deploy/releases/$(basename $ARTIFACT)"

