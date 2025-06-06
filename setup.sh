#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting MeowNow setup..."

# Detect OS
OS=$(uname)
if [[ "$OS" != "Darwin" && "$OS" != "Linux" ]]; then
    echo "❌ This setup script is currently only supported on macOS and Linux"
    exit 1
fi

# Function to install Docker on Linux
install_docker_linux() {
    echo "🐳 Installing Docker on Linux..."
    
    # Remove any old versions
    sudo apt-get remove -y docker docker-engine docker.io containerd runc || true
    
    # Install prerequisites
    sudo apt-get update
    sudo apt-get install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        lsb-release

    # Add Docker's official GPG key
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

    # Set up the stable repository
    echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
        $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

    # Install Docker Engine
    sudo apt-get update
    sudo apt-get install -y docker-ce docker-ce-cli containerd.io

    # Add current user to docker group
    sudo usermod -aG docker $USER
    echo "⚠️ Please log out and back in for group changes to take effect"
}

# Function to install Docker on macOS
install_docker_macos() {
    echo "🐳 Installing Docker Desktop for Mac..."
    brew install --cask docker
    echo "⚠️ Please open Docker Desktop and complete the installation"
    echo "Press Enter when Docker Desktop is running..."
    read
}

# Install Docker based on OS
if ! command -v docker &> /dev/null; then
    if [[ "$OS" == "Darwin" ]]; then
        # Check if Homebrew is installed on macOS
        if ! command -v brew &> /dev/null; then
            echo "📦 Installing Homebrew..."
            /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        fi
        install_docker_macos
    else
        install_docker_linux
    fi
fi

# Install Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo "📦 Installing Docker Compose..."
    if [[ "$OS" == "Darwin" ]]; then
        brew install docker-compose
    else
        sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
    fi
fi

# Check if .env file exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cat > .env << EOL
APP_ENV=production
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_REGION=your_region
S3_RAW_BUCKET=your_raw_bucket
S3_COMPRESSED_BUCKET=your_compressed_bucket
S3_PREFIX=your_prefix
MAX_UPLOAD_SIZE=10M
MAX_TOTAL_UPLOAD=100M
LOG_DIRECTORY=/var/www/logs
EOL
    echo "⚠️ Please update the .env file with your AWS credentials and configuration"
    echo "Press Enter when you've updated the .env file..."
    read
fi

# Build and start the containers
echo "🏗️ Building and starting containers..."
docker-compose up --build -d

echo "✅ Setup complete! The server should now be running at http://localhost:8080"
echo "To view logs, run: docker-compose logs -f"
echo "To stop the server, run: docker-compose down"

# Additional Linux-specific instructions
if [[ "$OS" == "Linux" ]]; then
    echo "
⚠️ Important Linux-specific notes:
1. Make sure your firewall allows port 8080 if you want to access the server from other machines
2. To allow external access, you may need to run:
   sudo ufw allow 8080
3. If you're using a cloud provider, make sure to configure their firewall/security group to allow port 8080
4. The server will start automatically on system boot if you add the following to /etc/systemd/system/meownow.service:
   [Unit]
   Description=MeowNow Docker Compose
   Requires=docker.service
   After=docker.service

   [Service]
   Type=oneshot
   RemainAfterExit=yes
   WorkingDirectory=$(pwd)
   ExecStart=/usr/local/bin/docker-compose up -d
   ExecStop=/usr/local/bin/docker-compose down
   TimeoutStartSec=0

   [Install]
   WantedBy=multi-user.target

   Then run:
   sudo systemctl enable meownow
   sudo systemctl start meownow"
fi 