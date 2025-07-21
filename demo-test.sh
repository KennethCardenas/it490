#!/bin/bash
echo "=== IT490 Load Balancer Demonstration ==="
echo ""

# Step 1: Show initial health
echo "1. INITIAL HEALTH CHECK:"
echo "   Balancer Manager Status:"
curl -s http://178.156.166.191/balancer-manager | grep -A 1 "Status" | tail -2
echo ""

# Step 2: Test load distribution  
echo "2. LOAD DISTRIBUTION TEST (20 requests):"
declare -A server_count
for i in {1..20}; do 
    response=$(curl -s http://178.156.166.191/)
    if echo "$response" | grep -q "SERVER A"; then
        ((server_count["A"]++))
        echo -n "A"
    elif echo "$response" | grep -q "Ubuntu Default" || echo "$response" | grep -q "Apache"; then
        ((server_count["B"]++))
        echo -n "B"
    else
        ((server_count["?"]++))
        echo -n "?"
    fi
done
echo ""
echo "   Results:"
for server in "${!server_count[@]}"; do
    echo "     Server $server: ${server_count[$server]} requests"
done
echo ""

# Step 3: Backend health check
echo "3. BACKEND HEALTH:"
echo "   Server A (localhost:8080): $(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/)"
echo "   Server B (178.156.159.246): $(curl -s -o /dev/null -w "%{http_code}" http://178.156.159.246/)"
echo ""

# Step 4: Balancer manager summary
echo "4. BALANCER STATUS:"
curl -s http://178.156.166.191/balancer-manager | grep -E "localhost:8080|178.156.159.246" | grep -o "Ok\|Err\|Dis"
echo ""

echo "=== Demo Complete ==="
echo ""
echo "FOR ASSIGNMENT SCREENSHOTS:"
echo "1. Open: http://178.156.166.191/balancer-manager"  
echo "2. Open: http://178.156.166.191/server-status"
echo "3. Use this script to show load distribution"
echo "4. Disable servers in balancer-manager to show failover" 