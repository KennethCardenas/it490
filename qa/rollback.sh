#!/bin/bash

# Configuration
REMOTE_QA="cja48@178.156.159.246"
REMOTE_DIR="/var/www/html/it490"
BACKUP_DIR="/var/www/html/deployment/backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

list_backups() {
  echo -e "\n${YELLOW}Available backups (newest first - New York Time):${NC}"
  ssh "$REMOTE_QA" "
    cd $BACKUP_DIR
    for file in it490_backup_*.tar.gz; do
      if [[ \$file =~ EDT ]]; then
        # Already in EDT format
        timestamp=\$(echo \$file | grep -oE '[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}')
        echo \" - \$timestamp EDT → \$file\"
      else
        # Convert other formats to EDT for display
        utc_time=\$(echo \$file | grep -oE '[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}-[0-9]{2}')
        ny_time=\$(TZ=\"America/New_York\" date -d \"\$utc_time\" +'%Y-%m-%d_%H-%M-%SEDT' 2>/dev/null || echo \$utc_time)
        echo \" - \$ny_time → \$file\"
      fi
    done | sort -r
  " || echo -e "  ${RED}No backups found${NC}"
}

restore_backup() {
  local backup_file="$1"
  
  echo -e "\n${YELLOW}=== Preparing to restore ===${NC}"
  echo -e "Backup: ${GREEN}$backup_file${NC}"
  echo -e "Target: ${GREEN}$REMOTE_DIR${NC}"
  echo -e "Current NY Time: ${GREEN}$(TZ="America/New_York" date +'%Y-%m-%d %H:%M:%S %Z')${NC}"
  
  read -p "Are you sure you want to continue? (y/n) " -n 1 -r
  echo
  if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${RED}Restore cancelled${NC}"
    exit 1
  fi

  echo -e "${YELLOW}Restoring backup...${NC}"
  
  ssh "$REMOTE_QA" "
    echo 'Stopping services...'
    # sudo systemctl stop apache2 php-fpm
    
    echo 'Clearing current deployment...'
    sudo rm -rf $REMOTE_DIR/* 2>/dev/null || true
    
    echo 'Extracting backup...'
    sudo tar -xzf $BACKUP_DIR/$backup_file -C $REMOTE_DIR/../
    
    echo 'Fixing permissions...'
    sudo chown -R cja48:www-data $REMOTE_DIR
    sudo chmod -R 775 $REMOTE_DIR
    
    echo 'Restarting services...'
    # sudo systemctl start apache2 php-fpm
  "
  
  echo -e "\n${GREEN}Restore completed successfully at $(TZ="America/New_York" date +'%Y-%m-%d %H:%M:%S %Z')${NC}"
}

# Main execution
echo -e "\n${YELLOW}=== QA Environment Rollback Utility ===${NC}"
list_backups

echo -e "\n${YELLOW}Enter backup filename to restore (e.g. it490_backup_2025-07-09_14-28-18EDT.tar.gz)${NC}"
echo -e "${YELLOW}Or press enter to use the most recent backup:${NC}"
read -p "Backup file: " backup_file

if [[ -z "$backup_file" ]]; then
  backup_file=$(ssh "$REMOTE_QA" "ls -t $BACKUP_DIR/it490_backup_*EDT.tar.gz 2>/dev/null | head -n 1 | xargs basename")
  if [[ -z "$backup_file" ]]; then
    echo -e "${RED}Error: No EDT-format backups found${NC}"
    exit 1
  fi
  echo -e "${GREEN}Auto-selected most recent backup: $backup_file${NC}"
fi

if ssh "$REMOTE_QA" "[ -f $BACKUP_DIR/$backup_file ]"; then
  restore_backup "$backup_file"
else
  echo -e "${RED}Error: Backup file $backup_file not found${NC}"
  exit 1
fi