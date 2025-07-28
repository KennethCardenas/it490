#!/bin/bash

# Load balancer testing script
LOAD_BALANCER_IP=$(hostname -I | awk '{print $1}')
BACKEND_A="178.156.159.246:8080"
BACKEND_B="178.156.166.21:8080"

echo "====================================="
echo "Load Balancer Testing Script"
echo "====================================="
echo "Load Balancer: http://$LOAD_BALANCER_IP"
echo "Backend A: http://$BACKEND_A"
echo "Backend B: http://$BACKEND_B"
echo "====================================="

# Function to test a URL and show response info
test_url() {
    local url=$1
    local label=$2
    echo -e "\n--- Testing $label ---"
    curl -s -w "Response Code: %{http_code}\nTotal Time: %{time_total}s\n" \
         -H "User-Agent: LoadBalancerTest" \
         "$url" | head -20
    echo "---"
}

# Function to show server status
show_status() {
    echo -e "\n=== Server Status Check ==="
    
    echo "Backend A Status:"
    curl -s -o /dev/null -w "HTTP %{http_code} - %{time_total}s\n" "http://$BACKEND_A/pages/login.php" || echo "FAILED"
    
    echo "Backend B Status:"
    curl -s -o /dev/null -w "HTTP %{http_code} - %{time_total}s\n" "http://$BACKEND_B/pages/login.php" || echo "FAILED"
    
    echo "Load Balancer Status:"
    curl -s -o /dev/null -w "HTTP %{http_code} - %{time_total}s\n" "http://$LOAD_BALANCER_IP/pages/login.php" || echo "FAILED"
}

# Function to demonstrate load distribution
test_load_distribution() {
    echo -e "\n=== Testing Load Distribution ==="
    echo "Making 10 requests to see distribution..."
    
    for i in {1..10}; do
        echo -n "Request $i: "
        response=$(curl -s "http://$LOAD_BALANCER_IP/pages/login.php" | grep -o "Served by: SERVER [AB]" || echo "No server info found")
        echo "$response"
        sleep 1
    done
}

# Function to check balancer manager
check_balancer_manager() {
    echo -e "\n=== Balancer Manager Status ==="
    echo "Balancer Manager URL: http://$LOAD_BALANCER_IP/balancer-manager"
    
    if curl -s "http://$LOAD_BALANCER_IP/balancer-manager" | grep -q "balancer://"; then
        echo "✓ Balancer manager is accessible"
        echo "Active workers:"
        curl -s "http://$LOAD_BALANCER_IP/balancer-manager" | grep -o "http://[^<]*" | head -5
    else
        echo "✗ Balancer manager not accessible"
    fi
}

# Function to test failover
test_failover() {
    echo -e "\n=== Failover Test Instructions ==="
    echo "To test failover:"
    echo "1. SSH to one of the backend servers"
    echo "2. Stop Apache: sudo systemctl stop apache2"
    echo "3. Monitor requests continue to work via: watch -n 1 'curl -s http://$LOAD_BALANCER_IP/pages/login.php | grep \"Served by\"'"
    echo "4. Restart Apache: sudo systemctl start apache2"
    echo "5. Observe traffic redistribution"
}

# Main execution
case "${1:-all}" in
    "status")
        show_status
        ;;
    "distribution")
        test_load_distribution
        ;;
    "manager")
        check_balancer_manager
        ;;
    "failover")
        test_failover
        ;;
    "all")
        show_status
        check_balancer_manager
        test_load_distribution
        test_failover
        ;;
    *)
        echo "Usage: $0 [status|distribution|manager|failover|all]"
        exit 1
        ;;
esac