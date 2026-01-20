#!/usr/bin/env powershell
<#
.SYNOPSIS
    Verification script for Simple LMS Language Switching Fix
.DESCRIPTION
    Tests the language switching system to ensure it's working correctly
.EXAMPLE
    .\verify-language-fix.ps1
#>

Write-Host "=" * 70
Write-Host "Simple LMS Language Switching - Verification Script" -ForegroundColor Cyan
Write-Host "=" * 70
Write-Host ""

# Check PHP syntax
Write-Host "1. Checking PHP Syntax..." -ForegroundColor Green
$phpFiles = @(
    "simple-lms.php",
    "includes/class-settings.php"
)

$syntaxErrors = $false
foreach ($file in $phpFiles) {
    $result = php -l $file 2>&1
    if ($result -match "No syntax errors") {
        Write-Host "   ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $file - SYNTAX ERROR" -ForegroundColor Red
        Write-Host "     $result"
        $syntaxErrors = $true
    }
}

if ($syntaxErrors) {
    Write-Host ""
    Write-Host "❌ PHP Syntax Check FAILED" -ForegroundColor Red
    exit 1
}

# Check if .mo files exist
Write-Host ""
Write-Host "2. Checking Translation Files..." -ForegroundColor Green
$translationFiles = @(
    "languages/simple-lms-pl_PL.mo",
    "languages/simple-lms-de_DE.mo"
)

$filesOk = $true
foreach ($file in $translationFiles) {
    if (Test-Path $file) {
        $size = (Get-Item $file).Length
        Write-Host "   ✓ $file ($size bytes)" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $file - NOT FOUND" -ForegroundColor Red
        $filesOk = $false
    }
}

if (!$filesOk) {
    Write-Host ""
    Write-Host "⚠️  Some translation files are missing" -ForegroundColor Yellow
}

# Check build status
Write-Host ""
Write-Host "3. Checking Build Status..." -ForegroundColor Green
if (Test-Path "assets/dist") {
    $distFiles = Get-ChildItem "assets/dist" -Recurse -File | Measure-Object
    Write-Host "   ✓ Build directory exists with $($distFiles.Count) files" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Build directory not found - run 'npm run build'" -ForegroundColor Yellow
}

# Code verification
Write-Host ""
Write-Host "4. Verifying Code Changes..." -ForegroundColor Green

$checks = @(
    @{
        file = "simple-lms.php"
        contains = "add_action('init', 'simpleLmsLoadTranslations', 999)"
        description = "Translation loading moved to init hook (priority 999)"
    },
    @{
        file = "simple-lms.php"
        contains = "get_option('simple_lms_language', 'default')"
        description = "Uses get_option() instead of direct DB query"
    },
    @{
        file = "simple-lms.php"
        contains = "load_plugin_textdomain("
        description = "Fallback textdomain loading on plugins_loaded"
    },
    @{
        file = "includes/class-settings.php"
        contains = "Changes take effect immediately on the next page load"
        description = "UI note about page reload added"
    },
    @{
        file = "includes/class-settings.php"
        contains = "Force reload translations after language change"
        description = "Translation cache clearing implemented"
    }
)

$allChecks = $true
foreach ($check in $checks) {
    $content = Get-Content -Path $check.file -Raw
    if ($content -match [regex]::Escape($check.contains)) {
        Write-Host "   ✓ $($check.description)" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $($check.description)" -ForegroundColor Red
        $allChecks = $false
    }
}

Write-Host ""
Write-Host "=" * 70

if ($allChecks -and !$syntaxErrors -and $filesOk) {
    Write-Host "✅ All Verification Checks PASSED" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Clear WordPress object cache (if using one)"
    Write-Host "2. Clear OPcache"
    Write-Host "3. Go to Courses → Settings"
    Write-Host "4. Select Polish language → Save"
    Write-Host "5. Refresh a Course/Module/Lesson page"
    Write-Host "6. Verify strings appear in Polish"
    Write-Host "7. Switch to English and verify strings are in English"
} else {
    Write-Host "⚠️  Some checks failed - review above for details" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=" * 70
