param([string]$OutputDirectory = (Join-Path $PSScriptRoot '..\dist'))
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$project = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$output = [System.IO.Path]::GetFullPath($OutputDirectory)
[System.IO.Directory]::CreateDirectory($output) | Out-Null

$groups = [ordered]@{
    Jasson = @('.gitattributes','.gitignore','README.md','app/views.php','docs/TEAM_PLAN.md','docs/WEEKLY_CHECKPOINTS.md','public/assets/app.js','public/assets/mark.svg','public/assets/styles.css','public/index.php','scripts/package-team.ps1')
    Mandip_Rijal = @('.env.example','Dockerfile','compose.yaml','app/actions.php','app/bootstrap.php','app/domain.php','config/config.php','config/local.example.php','database/schema.sql','database/seed.sql','scripts/create-staff.php','scripts/install.php','tests/database.php','tests/domain.php','tests/failure-cases.mjs','tests/fault-fixture.php','var/.gitkeep')
    Rudesh = @('.dockerignore','app/staff_actions.php','app/staff_views.php','docs/IMPLEMENTATION.md','docs/TEST_REPORT.md','docs/evidence/browser-results-chrome.json','docs/evidence/browser-results-msedge.json','docs/evidence/failure-results.json','docs/evidence/integration-results.json','tests/browser.cjs','tests/integration.mjs')
}

Push-Location $project
try {
    $tracked = @(git ls-files)
    if ($LASTEXITCODE -ne 0) { throw 'The project must be a Git checkout.' }
    $assigned = @($groups.Values | ForEach-Object { $_ })
    $duplicates = @($assigned | Group-Object | Where-Object Count -ne 1)
    $missing = @($tracked | Where-Object { $_ -notin $assigned })
    $untracked = @($assigned | Where-Object { $_ -notin $tracked })
    if ($duplicates.Count -or $missing.Count -or $untracked.Count) {
        throw "Package assignment is incomplete. Duplicate: $($duplicates.Name -join ', '); unassigned: $($missing -join ', '); not tracked: $($untracked -join ', ')."
    }
    $commit = (git rev-parse --short HEAD).Trim()
    foreach ($member in $groups.Keys) {
        $archivePath = Join-Path $output ("Folks-Cafe-Portal-{0}.zip" -f $member)
        $stream = [System.IO.File]::Open($archivePath,[System.IO.FileMode]::Create)
        try {
            $archive = New-Object System.IO.Compression.ZipArchive($stream,[System.IO.Compression.ZipArchiveMode]::Create,$false)
            try {
                foreach ($file in $groups[$member]) {
                    $full = [System.IO.Path]::GetFullPath((Join-Path $project $file))
                    if (-not $full.StartsWith($project + [System.IO.Path]::DirectorySeparatorChar,[System.StringComparison]::OrdinalIgnoreCase)) { throw "Invalid package path: $file" }
                    if (-not [System.IO.File]::Exists($full)) { throw "Missing source file: $file" }
                    $entryName = 'Folks-Cafe-Portal/' + $file.Replace('\','/')
                    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive,$full,$entryName,[System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
                }
                $readme = $archive.CreateEntry('Folks-Cafe-Portal/PACKAGE-' + $member + '.txt')
                $writer = New-Object System.IO.StreamWriter($readme.Open())
                try {
                    $writer.WriteLine("Assigned component package: $member")
                    $writer.WriteLine("Source revision: $commit")
                    $writer.WriteLine('This is a proposed work allocation, not evidence of individual authorship.')
                    $writer.WriteLine('Extract all three ZIPs into the same destination to form the runnable project.')
                    $writer.WriteLine('Then follow README.md for database setup and staff account creation.')
                    $writer.WriteLine('No local database password, runtime data or Git history is included.')
                } finally { $writer.Dispose() }
            } finally { $archive.Dispose() }
        } finally { $stream.Dispose() }
        Write-Output ("{0}: {1} assigned source files -> {2}" -f $member,$groups[$member].Count,$archivePath)
    }
} finally { Pop-Location }
