#!/bin/bash

# Colors for output
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Log file path
LOG_FILE="debug.log"
LOG_PATH="$(pwd)/wp-content/${LOG_FILE}"

echo -e "${BLUE}WordPress Debug Log Monitor${NC}"
echo -e "${BLUE}Monitoring: ${LOG_PATH}${NC}"
echo -e "${BLUE}Press Ctrl+C to stop monitoring${NC}\n"

# Create log file if it doesn't exist
if [ ! -f "$LOG_PATH" ]; then
    touch "$LOG_PATH"
    echo -e "${YELLOW}Created new log file: ${LOG_PATH}${NC}"
fi

# Monitor the log file
tail -f "$LOG_PATH" | while read -r line; do
    # Highlight different types of messages
    if [[ $line == *"Fatal error"* ]]; then
        echo -e "${RED}$line${NC}"
    elif [[ $line == *"Warning"* ]]; then
        echo -e "${YELLOW}$line${NC}"
    else
        echo "$line"
    fi
done
