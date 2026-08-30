param(
	[string]$OutputDir = "dist"
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Get-ReleaseVersionFromOutput {
	param(
		[Parameter(Mandatory = $true)]
		[string[]]$BuildOutput
	)

	foreach ($line in $BuildOutput) {
		if ($line -match '^Release version:\s*(?<version>[0-9]+(?:\.[0-9]+){2,3})$') {
			return $Matches.version
		}
	}

	throw 'Could not determine release version from build output.'
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$status = @(git -C $repoRoot status --short)
if ($status.Count -gt 0) {
	throw "Working tree is dirty. Commit or stash changes before creating a release.`n$($status -join [Environment]::NewLine)"
}

$initialBuild = & (Join-Path $PSScriptRoot 'build.ps1') -OutputDir $OutputDir
$releaseVersion = Get-ReleaseVersionFromOutput -BuildOutput @($initialBuild)
$tagName = "v$releaseVersion"

$existingTag = @(git -C $repoRoot tag --list $tagName)
if ($existingTag.Count -eq 0) {
	& git -C $repoRoot tag $tagName
	if ($LASTEXITCODE -ne 0) {
		throw "Failed to create tag $tagName."
	}
	Write-Host "Created tag $tagName"
} else {
	Write-Host "Tag $tagName already exists"
}

$finalBuild = & (Join-Path $PSScriptRoot 'build.ps1') -OutputDir $OutputDir
$finalVersion = Get-ReleaseVersionFromOutput -BuildOutput @($finalBuild)

Write-Host "Release complete: $finalVersion"
