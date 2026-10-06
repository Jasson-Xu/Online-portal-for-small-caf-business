param([string]$XamppRoot = $env:XAMPP_ROOT)
$ErrorActionPreference = 'Stop'

$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$candidates = if ($XamppRoot) { @($XamppRoot) } else { @('D:\XAMPP', 'C:\xampp') }
$xampp = $null
foreach ($candidate in $candidates) {
    $absolute = [System.IO.Path]::GetFullPath($candidate)
    if ((Test-Path -LiteralPath (Join-Path $absolute 'php\php.exe') -PathType Leaf) -and
        (Test-Path -LiteralPath (Join-Path $absolute 'htdocs') -PathType Container)) {
        $xampp = $absolute
        break
    }
}
if (-not $xampp) { throw 'XAMPP was not found. Set XAMPP_ROOT to its installation folder, then run setup-xampp.cmd again.' }

$php = Join-Path $xampp 'php\php.exe'
$installer = Join-Path $PSScriptRoot 'setup-xampp.php'
Write-Output "Using XAMPP at $xampp"
& $php $installer
if ($LASTEXITCODE -ne 0) { throw 'Database setup failed. Confirm MySQL is running in the XAMPP Control Panel and review the error above.' }

$public = [System.IO.Path]::GetFullPath((Join-Path $projectRoot 'public'))
$link = Join-Path $xampp 'htdocs\cafe'
$existing = Get-Item -LiteralPath $link -Force -ErrorAction SilentlyContinue
if ($existing) {
    $target = @($existing.Target)[0]
    if ($existing.LinkType -ne 'Junction' -or -not $target -or
        -not [string]::Equals([System.IO.Path]::GetFullPath($target), $public, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "The XAMPP path $link already exists and points elsewhere. Move it manually before rerunning setup."
    }
    Write-Output 'The XAMPP website link already points to this project.'
} else {
    New-Item -ItemType Junction -Path $link -Target $public -ErrorAction Stop | Out-Null
    Write-Output 'Linked the project public folder into XAMPP.'
}

Write-Output 'Setup complete. Start Apache in the XAMPP Control Panel, then open http://localhost/cafe/'
