param(
	[string]$OutputDir = "dist"
)

$ErrorActionPreference = 'Stop'

function Get-GitVersion {
	try {
		$version = & git describe --tags --always --dirty 2>$null
		if ($LASTEXITCODE -eq 0 -and $version) {
			return $version.Trim()
		}
	} catch {
	}

	return "0.0.0-dev"
}

function Get-ReleaseVersion {
	$raw = Get-GitVersion
	if ($raw -match '^v(?<ver>\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?)$') {
		return $Matches.ver
	}

	return $raw -replace '^v', ''
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$packageName = 'simple-section-manager'
$releaseVersion = Get-ReleaseVersion
$gitVersion = Get-GitVersion
$stageDir = Join-Path $root $OutputDir
$packageDir = Join-Path $stageDir $packageName
$zipPath = Join-Path $stageDir "$packageName.zip"
$pluginSource = Join-Path $root 'site-section-manager.php'
$pluginTarget = Join-Path $packageDir 'site-section-manager.php'

if (Test-Path $stageDir) {
	Remove-Item -Recurse -Force $stageDir
}

New-Item -ItemType Directory -Path $packageDir -Force | Out-Null

$itemsToCopy = @(
	'includes',
	'README.md'
)

foreach ($item in $itemsToCopy) {
	Copy-Item -Path (Join-Path $root $item) -Destination $packageDir -Recurse -Force
}

Copy-Item -Path $pluginSource -Destination $pluginTarget -Force

$pluginContent = Get-Content -LiteralPath $pluginTarget -Raw
$pluginContent = $pluginContent -replace '(?m)^ \* Version: .*$', " * Version: $releaseVersion"
$pluginContent = $pluginContent -replace "(?m)^define\( 'SSM_VERSION', '.*?' \);$", "define( 'SSM_VERSION', '$releaseVersion' );"
Set-Content -LiteralPath $pluginTarget -Value $pluginContent -NoNewline

if (Test-Path $zipPath) {
	Remove-Item -Force $zipPath
}

Compress-Archive -Path (Join-Path $packageDir '*') -DestinationPath $zipPath

Write-Host "Built package: $zipPath"
Write-Host "Git version: $gitVersion"
Write-Host "Release version: $releaseVersion"
