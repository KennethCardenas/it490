#!/bin/bash

# Update & install basic packages
sudo apt update && sudo apt upgrade -y
sudo apt install -y php php-cli curl unzip git erlang rabbitmq-server openssh-server

# Enable RabbitMQ Management Plugin
sudo rabbitmq-plugins enable rabbitmq_management
sudo systemctl enable rabbitmq-server
sudo systemctl start rabbitmq-server

# Clone project repo
if [ ! -d "IT490" ]; then
  git clone https://github.com/MattToegel/IT490.git
fi

echo "Setup complete. Use two terminals to run:"
echo "php IT490/RabbitMQServerSample.php"
echo "php IT490/RabbitMQClientSample.php"
