param([string]$OutputDirectory = (Join-Path $PSScriptRoot '..\dist'))
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression.FileSystem
Add-Type -AssemblyName System.IO.Compression
$project = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$output = [System.IO.Path]::GetFullPath($OutputDirectory)
[System.IO.Directory]::CreateDirectory($output) | Out-Null

$groups = [ordered]@{
    Jasson = @('.gitattributes','.gitignore','OVer.txt','README.md','app/views.php','docs/DESIGN.md','docs/TEAM_PLAN.md','docs/WEEKLY_CHECKPOINTS.md','docs/word/System-Design-2026.docx','public/.htaccess','public/assets/app.js','public/assets/mark.svg','public/assets/styles.css','public/index.php','scripts/package-team.ps1')
    Mandip_Rijal = @('.env.example','.htaccess','Dockerfile','compose.yaml','app/actions.php','app/bootstrap.php','app/domain.php','config/config.php','config/local.example.php','config/local.xampp.example.php','database/schema.sql','database/seed.sql','database/xampp/01-create-database.sql','database/xampp/02-create-tables.sql','database/xampp/03-demo-data.sql','database/xampp/04-test-data.sql','scripts/create-staff.php','scripts/install.php','scripts/setup-xampp.php','tests/database.php','tests/domain.php','tests/failure-cases.mjs','tests/fault-fixture.php','var/.gitkeep')
    Rudesh = @('.dockerignore','app/staff_actions.php','app/staff_views.php','database/xampp/cafe-portal-complete.sql','docs/IMPLEMENTATION.md','docs/TEST_REPORT.md','docs/evidence/browser-results-chrome.json','docs/evidence/browser-results-msedge.json','docs/evidence/failure-results.json','docs/evidence/integration-results.json','docs/word/Implementation-and-Management-2026.docx','docs/word/Test-Plan-and-Results-2026.docx','setup-xampp.cmd','scripts/setup-xampp.ps1','tests/browser.cjs','tests/integration.mjs')
}

Push-Location $project
try {
    $tracked = @(git ls-files | Where-Object { $_ -notlike 'dist/*.zip' })
    if ($LASTEXITCODE -ne 0) { throw 'The project must be a Git checkout.' }
    $assigned = @($groups.Values | ForEach-Object { $_ })
    $duplicates = @($assigned | Group-Object | Where-Object Count -ne 1)
    $missing = @($tracked | Where-Object { $_ -notin $assigned })
    $untracked = @($assigned | Where-Object { $_ -notin $tracked })
    if ($duplicates.Count -or $missing.Count -or $untracked.Count) {
        throw "Package assignment is incomplete. Duplicate: $($duplicates.Name -join ', '); unassigned: $($missing -join ', '); not tracked: $($untracked -join ', ')."
    }
    git diff --quiet HEAD --
    if ($LASTEXITCODE -ne 0) { throw 'Commit tracked changes before packaging so every ZIP uses the same Git revision.' }
    $commit = (git rev-parse --short HEAD).Trim()
    $sourceArchivePath = Join-Path $project ('var\package-source-' + [guid]::NewGuid().ToString('N') + '.zip')
    $sourceArchive = $null
    try {
        & git archive --format=zip --prefix=Folks-Cafe-Portal/ "--output=$sourceArchivePath" HEAD
        if ($LASTEXITCODE -ne 0) { throw 'Unable to build the temporary source archive from Git.' }
        $sourceArchive = [System.IO.Compression.ZipFile]::OpenRead($sourceArchivePath)
        foreach ($member in $groups.Keys) {
            $archivePath = Join-Path $output ("Folks-Cafe-Portal-{0}.zip" -f $member)
            $stream = [System.IO.File]::Open($archivePath,[System.IO.FileMode]::Create)
            try {
                $archive = New-Object System.IO.Compression.ZipArchive($stream,[System.IO.Compression.ZipArchiveMode]::Create,$false)
                try {
                    foreach ($file in $groups[$member]) {
                        $entryName = 'Folks-Cafe-Portal/' + $file.Replace('\','/')
                        $sourceEntry = $sourceArchive.GetEntry($entryName)
                        if (-not $sourceEntry) { throw "Missing committed source file: $file" }
                        $destinationEntry = $archive.CreateEntry($entryName,[System.IO.Compression.CompressionLevel]::Optimal)
                        $sourceStream = $sourceEntry.Open()
                        $destinationStream = $destinationEntry.Open()
                        try { $sourceStream.CopyTo($destinationStream) }
                        finally { $destinationStream.Dispose(); $sourceStream.Dispose() }
                    }
                    $readme = $archive.CreateEntry('Folks-Cafe-Portal/PACKAGE-' + $member + '.txt')
                    $writer = New-Object System.IO.StreamWriter($readme.Open())
                    try {
                        $writer.WriteLine("Assigned component package: $member")
                        $role = @{ Jasson = 'Customer pages, responsive interface, design and coordination'; Mandip_Rijal = 'Accounts, data model, checkout and database setup'; Rudesh = 'Staff workflow, tests, installation and handover documents' }[$member]
                        $writer.WriteLine("Area: $role")
                        $writer.WriteLine('Balanced planning allocation: 40 effort points per member.')
                        $writer.WriteLine("Source revision: $commit")
                        $writer.WriteLine()
                        $writer.WriteLine('All three ZIPs together contain the complete project. Extract them into one folder before installation. One component ZIP is not a runnable site.')
                        $writer.WriteLine()
                        $writer.WriteLine('For future Git work: clone the shared repository, create your own branch, extract this ZIP to a temporary folder, and copy its assigned files into the clone while preserving paths. Review and change only work you actually complete, run relevant tests, then commit under your own configured Git identity and push your branch for review.')
                        $writer.WriteLine('The remote repository already contains this snapshot. Copying identical files into a clone creates no Git diff or commit; make genuine changes before committing. Do not rewrite author names or commit dates to imply earlier work.')
                        $writer.WriteLine('No local database password, runtime data or Git history is included in this archive.')
                    } finally { $writer.Dispose() }
                } finally { $archive.Dispose() }
            } finally { $stream.Dispose() }
            Write-Output ("{0}: {1} assigned source files -> {2}" -f $member,$groups[$member].Count,$archivePath)
        }
    } finally {
        if ($sourceArchive) { $sourceArchive.Dispose() }
        if (Test-Path -LiteralPath $sourceArchivePath -PathType Leaf) { Remove-Item -LiteralPath $sourceArchivePath -Force }
    }
} finally { Pop-Location }
