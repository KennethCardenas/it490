#!/bin/bash
# setup_mq.sh – for qa-mq

sudo apt update
sudo apt install -y rabbitmq-server

sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server

sudo rabbitmqctl add_user kac63 Linklinkm1!
sudo rabbitmqctl set_user_tags kac63 administrator
sudo rabbitmqctl set_permissions -p / kac63 ".*" ".*" ".*"

echo "RabbitMQ setup complete"
