# Simple LMS - Production Build Script
$ErrorActionPreference = "Stop"

Write-Host "==========================================="
Write-Host "  Simple LMS - Production Build"
Write-Host "==========================================="
Write-Host ""

# Read version from plugin file
$pluginFile = Get-Content "simple-lms.php" -Raw
if ($pluginFile -match 'Version:\s+(\d+\.\d+\.\d+)') {
    $version = $matches[1]
} else {
    Write-Host "ERROR: Cannot read version from simple-lms.php"
    exit 1
}

# Output file with date
$date = Get-Date -Format "yyyyMMdd"
$outputDir = Join-Path $PSScriptRoot "dist"
$outputFile = "simple-lms-v$version-$date.zip"
$outputPath = Join-Path $outputDir $outputFile

# Create dist folder
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir | Out-Null
}

Write-Host "Plugin version: $version"
Write-Host "Build date: $date"
Write-Host "Output file: $outputFile"
Write-Host ""

# Temp folder
$tempDir = Join-Path $env:TEMP "simple-lms-build-$(Get-Date -Format 'yyyyMMddHHmmss')"
$tempPluginDir = Join-Path $tempDir "simple-lms"

Write-Host "Preparing files..."

# Create temp folder
New-Item -ItemType Directory -Path $tempPluginDir -Force | Out-Null

# Copy main files
$mainFiles = @("simple-lms.php", "README.md", "CHANGELOG.md")
foreach ($file in $mainFiles) {
    if (Test-Path $file) {
        Copy-Item $file -Destination $tempPluginDir
        Write-Host "  OK $file"
    }
}

# Copy folders
$folders = @("assets", "includes", "languages")
foreach ($folder in $folders) {
    if (Test-Path $folder) {
        Copy-Item $folder -Destination $tempPluginDir -Recurse -Force
        $fileCount = (Get-ChildItem (Join-Path $tempPluginDir $folder) -Recurse -File).Count
        Write-Host "  OK $folder/ ($fileCount files)"
    }
}

Write-Host ""
Write-Host "Creating ZIP archive..."

# Remove old file
if (Test-Path $outputPath) {
    Remove-Item $outputPath -Force
}

# Create ZIP
Compress-Archive -Path $tempPluginDir -DestinationPath $outputPath -CompressionLevel Optimal

# Calculate checksum
$hash = (Get-FileHash -Path $outputPath -Algorithm SHA256).Hash
$fileSize = [math]::Round((Get-Item $outputPath).Length / 1MB, 2)

# Remove temp folder
Remove-Item $tempDir -Recurse -Force

Write-Host ""
Write-Host "==========================================="
Write-Host "  BUILD SUCCESSFUL!"
Write-Host "==========================================="
Write-Host ""
Write-Host "File: $outputPath"
Write-Host "Size: $fileSize MB"
Write-Host "SHA256: $hash"
Write-Host ""

# Save build info
$buildInfo = "Simple LMS v$version - Production Build`n"
$buildInfo += "Build Date: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')`n"
$buildInfo += "File: $outputFile`n"
$buildInfo += "Size: $fileSize MB`n"
$buildInfo += "SHA256: $hash`n"

$buildInfoFile = Join-Path $outputDir "build-info-v$version-$date.txt"
$buildInfo | Out-File -FilePath $buildInfoFile -Encoding UTF8

Write-Host "Build info: $buildInfoFile"
Write-Host ""
Write-Host "DONE! Ready to deploy."
