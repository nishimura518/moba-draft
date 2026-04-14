$ErrorActionPreference = 'Stop'

$dir = Join-Path (Get-Location) 'public/images/characters'
if (-not (Test-Path -LiteralPath $dir)) {
  throw "Directory not found: $dir"
}

$files = Get-ChildItem -LiteralPath $dir -File | Sort-Object Name
if ($files.Count -eq 0) {
  throw "No files found in: $dir"
}

# 1) Rename to temp names to avoid collisions
$i = 1
foreach ($f in $files) {
  $tmpName = "__tmp__${i}$($f.Extension)"
  Rename-Item -LiteralPath $f.FullName -NewName $tmpName -Force
  $i++
}

# 2) Rename temp files to sequential numbers
$tmpFiles =
  Get-ChildItem -LiteralPath $dir -File -Filter '__tmp__*' |
  Sort-Object { [int](($_.BaseName -replace '^__tmp__','')) }

$i = 1
foreach ($f in $tmpFiles) {
  $newName = "${i}$($f.Extension)"
  Rename-Item -LiteralPath $f.FullName -NewName $newName -Force
  $i++
}

Write-Host "Renamed $($tmpFiles.Count) file(s) in $dir"
