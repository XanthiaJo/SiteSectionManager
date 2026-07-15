param(
	[string]$OutputDir = "dist"
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Invoke-Git {
	param(
		[Parameter(Mandatory = $true)]
		[string[]]$Arguments
	)

	$output = & git @Arguments 2>$null
	if ($LASTEXITCODE -ne 0) {
		throw "git $($Arguments -join ' ') failed with exit code $LASTEXITCODE"
	}

	return @($output)
}

function Try-ParseVersionTag {
	param(
		[Parameter(Mandatory = $true)]
		[string]$TagName,
		[Parameter(Mandatory = $true)]
		[ref]$Version
	)

	$Version.Value = $null
	$raw = $TagName.Trim()
	if ($raw.StartsWith('v', [System.StringComparison]::OrdinalIgnoreCase)) {
		$raw = $raw.Substring(1)
	}

	$parts = $raw.Split('.', [System.StringSplitOptions]::RemoveEmptyEntries)
	if ($parts.Length -lt 3) {
		return $false
	}

	$major = 0
	$minor = 0
	$patch = 0

	if (-not [int]::TryParse($parts[0], [ref]$major) -or
		-not [int]::TryParse($parts[1], [ref]$minor) -or
		-not [int]::TryParse($parts[2], [ref]$patch)) {
		return $false
	}

	$Version.Value = [System.Version]::new($major, $minor, $patch)
	return $true
}

function Format-Version {
	param(
		[Parameter(Mandatory = $true)]
		[System.Version]$Version,
		[int]$Revision = 0
	)

	if ($Revision -gt 0) {
		return ('{0}.{1}.{2}.{3}' -f $Version.Major, $Version.Minor, $Version.Build, $Revision)
	}

	return ('{0}.{1}.{2}' -f $Version.Major, $Version.Minor, $Version.Build)
}

function Get-CommitType {
	param(
		[Parameter(Mandatory = $true)]
		[string]$Subject,
		[Parameter(Mandatory = $true)]
		[string]$Body
	)

	if ($Subject -match '^(\w+)(\([^)]+\))?!:' -or $Subject -match '^BREAKING CHANGE:' -or $Body -match 'BREAKING CHANGE:') {
		return 'major'
	}

	if ($Subject -match '^feat(\([^)]+\))?:') {
		return 'minor'
	}

	if ($Subject -match '^fix(\([^)]+\))?:') {
		return 'patch'
	}

	return 'none'
}

function Get-LatestTaggedVersion {
	$tags = Invoke-Git -Arguments @('tag', '--list', 'v[0-9]*.[0-9]*.[0-9]*', '--sort=-v:refname')
	foreach ($tag in $tags) {
		$version = $null
		if (Try-ParseVersionTag -TagName $tag -Version ([ref]$version)) {
			return [pscustomobject]@{
				TagName = $tag.Trim()
				Version = $version
			}
		}
	}

	return $null
}

function Get-ReleaseVersion {
	$latestTagInfo = Get-LatestTaggedVersion
	$baseVersion = if ($null -ne $latestTagInfo) { $latestTagInfo.Version } else { [System.Version]::new(0, 0, 0) }
	$revision = 0

	$commitLogArgs = @('log', '--reverse', '--pretty=format:%H%x1f%s%x1f%B%x1e')
	if ($null -ne $latestTagInfo) {
		$commitLogArgs += @("$($latestTagInfo.TagName)..HEAD")
	} else {
		$commitLogArgs += @('--all')
	}

	$commitLog = Invoke-Git -Arguments $commitLogArgs
	$records = ($commitLog -join "`n") -split ([char]0x1e)

	foreach ($record in $records) {
		if ([string]::IsNullOrWhiteSpace($record)) {
			continue
		}

		$parts = $record.Split([char]0x1f, 3)
		if ($parts.Length -lt 3) {
			continue
		}

		$subject = $parts[1].Trim()
		$body = $parts[2]
		$commitType = Get-CommitType -Subject $subject -Body $body

		switch ($commitType) {
			'major' {
				$baseVersion = [System.Version]::new($baseVersion.Major + 1, 0, 0)
				$revision = 0
			}
			'minor' {
				$baseVersion = [System.Version]::new($baseVersion.Major, $baseVersion.Minor + 1, 0)
				$revision = 0
			}
			'patch' {
				$baseVersion = [System.Version]::new($baseVersion.Major, $baseVersion.Minor, $baseVersion.Build + 1)
				$revision = 0
			}
			default {
				$revision += 1
			}
		}
	}

	return [pscustomobject]@{
		Version = $baseVersion
		Revision = $revision
	}
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$packageName = 'simple-section-manager'
$versionInfo = Get-ReleaseVersion
$releaseVersion = Format-Version -Version $versionInfo.Version -Revision $versionInfo.Revision
$gitDescribe = ((Invoke-Git -Arguments @('describe', '--tags', '--always', '--dirty')) -join '').Trim()
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

Write-Output "Built package: $zipPath"
Write-Output "Git describe: $gitDescribe"
Write-Output "Release version: $releaseVersion"
