#!/bin/bash
# Script to hit the site 20 times and capture which server responds
echo "Testing load balancer by hitting the site 20 times..."
for i in {1..20}; do 
    echo "Request $i:"
    response=$(curl -s http://178.156.166.191/ | grep -o "Server [AB]")
    echo "  Response: $response"
done | tee /tmp/balancer_test.log
echo ""
echo "Summary of responses:"
grep "Response:" /tmp/balancer_test.log | cut -d' ' -f4 | sort | uniq -c