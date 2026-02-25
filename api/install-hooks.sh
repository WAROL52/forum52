#!/bin/bash

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "Installing Git hooks..."
echo ""

# Check if .githooks directory exists
if [ ! -d ".githooks" ]; then
    echo "Error: .githooks directory not found"
    exit 1
fi

# Check if pre-commit hook exists
if [ ! -f ".githooks/pre-commit" ]; then
    echo "Error: .githooks/pre-commit not found"
    exit 1
fi

# Option 1: Configure Git to use .githooks directory (recommended)
echo "Option 1: Configure Git to use .githooks directory (recommended)"
echo "This will make Git automatically use all hooks in .githooks/"
echo ""
read -p "Use this option? [Y/n] " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
    git config core.hooksPath .githooks
    chmod +x .githooks/pre-commit
    echo -e "${GREEN}✓ Git hooks configured successfully!${NC}"
    echo "Git will now use hooks from .githooks/"
    exit 0
fi

# Option 2: Copy hooks manually
echo ""
echo "Option 2: Copy pre-commit hook to .git/hooks/"
read -p "Use this option? [Y/n] " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
    cp .githooks/pre-commit .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    echo -e "${GREEN}✓ Pre-commit hook installed successfully!${NC}"
    echo "Hook copied to .git/hooks/pre-commit"
    exit 0
fi

echo ""
echo -e "${YELLOW}No option selected. Hooks not installed.${NC}"
exit 1
