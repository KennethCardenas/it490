#!/bin/bash

# Configuration
LOCAL_DIR="/home/cja48/it490"
REMOTE_QA="cja48@178.156.159.246"
REMOTE_DIR="/var/www/html/it490"
BACKUP_DIR="/var/www/html/deployment/backups"
TIMESTAMP=$(TZ="America/New_York" date +"%Y-%m-%d_%H-%M-%S%Z")  # New York time
BACKUP_NAME="it490_backup_${TIMESTAMP}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

validate_environment() {
  echo -e "${YELLOW}Validating deployment environment...${NC}"
  [ -d "$LOCAL_DIR" ] || { echo -e "${RED}Error: Local directory $LOCAL_DIR not found${NC}"; exit 1; }
  echo -e "${YELLOW}New York timezone: $(TZ="America/New_York" date +%Z)${NC}"
}

create_backup() {
  echo -e "${YELLOW}Creating backup at $(TZ="America/New_York" date +'%Y-%m-%d %H:%M:%S %Z')...${NC}"
  ssh "$REMOTE_QA" "
    if [ -d '$REMOTE_DIR' ] && [ -n \"\$(ls -A '$REMOTE_DIR')\" ]; then
      sudo mkdir -p '$BACKUP_DIR' || true
      sudo chown cja48:www-data '$BACKUP_DIR' || true
      sudo tar -czf '$BACKUP_DIR/$BACKUP_NAME.tar.gz' -C \$(dirname '$REMOTE_DIR') \$(basename '$REMOTE_DIR') || true
      echo 'Backup created: $BACKUP_DIR/$BACKUP_NAME.tar.gz'
    else
      echo 'No files to backup (QA directory empty or does not exist)'
    fi
  "
}

sync_files() {
  echo -e "${YELLOW}Setting permissions on QA server...${NC}"
  ssh "$REMOTE_QA" "sudo chown -R cja48:www-data '$REMOTE_DIR' && sudo chmod -R 775 '$REMOTE_DIR'"
  
  echo -e "${YELLOW}Syncing files to QA server...${NC}"
  rsync -avz --no-times --no-perms --progress --delete \
    --exclude='config.php' \
    --exclude='.env' \
    --exclude='deployment/' \
    -e ssh "$LOCAL_DIR/" "$REMOTE_QA:$REMOTE_DIR/" || true
}

# Main execution
validate_environment
create_backup
sync_files

echo -e "${GREEN}Deployment completed at $(TZ="America/New_York" date +'%Y-%m-%d %H:%M:%S %Z')${NC}"