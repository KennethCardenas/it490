# Load Balancer Deployment Guide

## Overview
This setup creates an Apache load balancer that distributes traffic between two backend servers running your IT490 application.

## Architecture
- **Load Balancer VM**: Current VM (distributes traffic)
- **Backend Server A**: 178.156.159.246:8080 (shows "SERVER A")
- **Backend Server B**: 178.156.166.21:8080 (shows "SERVER B")

## Deployment Steps

### 1. Setup Load Balancer (Current VM)
```bash
cd /home/cja48/it490
./setup_load_balancer.sh
```

### 2. Setup Backend Server A (178.156.159.246)
SSH to the first backend server and run:
```bash
cd /home/cja48/it490
./setup_backend_server.sh SERVER_A 8080
```

### 3. Setup Backend Server B (178.156.166.21)
SSH to the second backend server and run:
```bash
cd /home/cja48/it490
./setup_backend_server.sh SERVER_B 8080
```

### 4. Test the Setup
```bash
cd /home/cja48/it490
./test_load_balancer.sh
```

## Monitoring URLs
- **Balancer Manager**: http://LOAD_BALANCER_IP/balancer-manager
- **Server Status**: http://LOAD_BALANCER_IP/server-status
- **Application**: http://LOAD_BALANCER_IP/pages/login.php

## Testing Scenarios

### 1. Normal Operation
- Both servers healthy
- Traffic distributed between SERVER A and SERVER B
- Check balancer manager for distribution stats

### 2. Failover Test
1. SSH to one backend server
2. Stop Apache: `sudo systemctl stop apache2`
3. Monitor requests still work via remaining server
4. Check balancer manager shows failed server
5. Restart Apache: `sudo systemctl start apache2`
6. Verify traffic redistribution

### 3. Load Distribution Monitoring
```bash
# Watch real-time distribution
watch -n 1 'curl -s http://LOAD_BALANCER_IP/pages/login.php | grep "Served by"'

# Check access logs
tail -f /var/log/apache2/load_balancer_access.log
```

## Screenshots to Capture

1. **Healthy State**: Balancer manager showing both servers active
2. **Login Page SERVER A**: Application served by first backend
3. **Login Page SERVER B**: Application served by second backend
4. **Failover**: One server down, traffic on remaining server
5. **Recovery**: Both servers back up, traffic redistributed
6. **Logs**: Apache access logs showing request distribution

## Troubleshooting

### Check Backend Server Status
```bash
curl -I http://178.156.159.246:8080/pages/login.php
curl -I http://178.156.166.21:8080/pages/login.php
```

### Check Load Balancer Configuration
```bash
sudo apache2ctl configtest
sudo systemctl status apache2
```

### View Logs
```bash
sudo tail -f /var/log/apache2/load_balancer_error.log
sudo tail -f /var/log/apache2/load_balancer_access.log
```

## Files Created
- `load-balancer.conf`: Apache load balancer configuration
- `setup_load_balancer.sh`: Load balancer setup script
- `setup_backend_server.sh`: Backend server setup script
- `test_load_balancer.sh`: Testing and monitoring script