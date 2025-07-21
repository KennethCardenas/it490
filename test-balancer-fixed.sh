#!/bin/bash
# Improved script to test load balancer distribution
echo "Testing load balancer by hitting the site 20 times..."
echo "Checking which backend servers are responding..."
echo ""

# Test using different methods to identify backend responses
declare -A server_count

for i in {1..20}; do 
    echo -n "Request $i: "
    
    # Get response with full headers and connection info
    response=$(curl -s -w "%{remote_ip}:%{remote_port}" http://178.156.166.191/ 2>/dev/null)
    
    # Try to identify server based on response
    if echo "$response" | grep -q "178.156.159.246"; then
        server="Server B"
        ((server_count["B"]++))
    elif echo "$response" | grep -q "localhost" || echo "$response" | grep -q "178.156.166.191"; then
        server="Server A"
        ((server_count["A"]++))
    else
        # Fallback: check if we can identify by other means
        server="Unknown"
        ((server_count["Unknown"]++))
    fi
    
    echo "$server"
done

echo ""
echo "Summary of responses:"
for server in "${!server_count[@]}"; do
    echo "  $server: ${server_count[$server]} requests"
done

echo ""
echo "Testing direct backend access:"
echo "Backend A (localhost): $(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/ 2>/dev/null || echo "Not accessible")"
echo "Backend B (178.156.159.246): $(curl -s -o /dev/null -w "%{http_code}" http://178.156.159.246/ 2>/dev/null)" 