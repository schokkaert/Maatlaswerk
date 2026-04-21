param(
    [string]$BaseUrl = "http://localhost/maatlaswerk/",
    [string]$OutputDir = (Join-Path $PSScriptRoot "..\_generated-export")
)

$ErrorActionPreference = "Stop"

function New-CanonicalUri {
    param([Uri]$UriObject)
    $builder = [UriBuilder]::new($UriObject)
    $builder.Fragment = ""
    $builder.Query = ""
    return $builder.Uri
}

function Get-LocValues {
    param([string]$XmlText)
    $matches = [regex]::Matches($XmlText, "<loc>([^<]+)</loc>", "IgnoreCase")
    foreach ($m in $matches) {
        [System.Net.WebUtility]::HtmlDecode($m.Groups[1].Value.Trim())
    }
}

function Get-HtmlOutputPath {
    param([Uri]$UriObject, [string]$BasePath, [string]$RootDir)
    $relative = $UriObject.AbsolutePath.Substring($BasePath.Length).TrimStart("/")
    if ([string]::IsNullOrWhiteSpace($relative)) {
        return Join-Path $RootDir "index.html"
    }
    if ($relative.EndsWith("/")) {
        return Join-Path $RootDir (($relative.TrimEnd("/") -replace "/", "\") + "\index.html")
    }
    $ext = [IO.Path]::GetExtension($relative)
    if ([string]::IsNullOrWhiteSpace($ext)) {
        return Join-Path $RootDir (($relative -replace "/", "\") + "\index.html")
    }
    return Join-Path $RootDir ($relative -replace "/", "\")
}

function Get-AssetOutputPath {
    param([Uri]$UriObject, [string]$BasePath, [string]$RootDir)
    $relative = $UriObject.AbsolutePath.Substring($BasePath.Length).TrimStart("/")
    if ([string]::IsNullOrWhiteSpace($relative)) {
        return $null
    }
    if ($relative.EndsWith("/")) {
        $relative = $relative.TrimEnd("/") + "/index"
    }
    return Join-Path $RootDir ($relative -replace "/", "\")
}

function Add-Page {
    param(
        [Uri]$UriObject,
        [Uri]$SiteRoot,
        [string]$BasePath,
        [System.Collections.Generic.Queue[Uri]]$Queue,
        [System.Collections.Generic.HashSet[string]]$Seen
    )
    if ($UriObject.Host -ne $SiteRoot.Host) { return }
    if (-not $UriObject.AbsolutePath.StartsWith($BasePath, [System.StringComparison]::OrdinalIgnoreCase)) { return }
    if ($UriObject.AbsolutePath -match "/wp-json(/|$)") { return }
    if ($UriObject.AbsolutePath -match "/wp-admin(/|$)") { return }
    if ($UriObject.AbsolutePath -match "/wp-login\.php$") { return }
    if ($UriObject.AbsolutePath -match "/xmlrpc\.php$") { return }
    $clean = New-CanonicalUri -UriObject $UriObject
    $key = $clean.AbsoluteUri
    if ($Seen.Add($key)) { $Queue.Enqueue($clean) }
}

function Add-Asset {
    param(
        [Uri]$UriObject,
        [Uri]$SiteRoot,
        [string]$BasePath,
        [System.Collections.Generic.Queue[Uri]]$Queue,
        [System.Collections.Generic.HashSet[string]]$Seen
    )
    if ($UriObject.Host -ne $SiteRoot.Host) { return }
    if (-not $UriObject.AbsolutePath.StartsWith($BasePath, [System.StringComparison]::OrdinalIgnoreCase)) { return }
    if ($UriObject.AbsolutePath -match "/wp-json(/|$)") { return }
    $clean = New-CanonicalUri -UriObject $UriObject
    $key = $clean.AbsoluteUri
    if ($Seen.Add($key)) { $Queue.Enqueue($clean) }
}

$siteRoot = [Uri]$BaseUrl
if (-not $siteRoot.AbsolutePath.EndsWith("/")) {
    $siteRoot = [Uri]($siteRoot.AbsoluteUri + "/")
}
$basePath = $siteRoot.AbsolutePath

if (Test-Path $OutputDir) {
    Remove-Item -Path $OutputDir -Recurse -Force
}
New-Item -ItemType Directory -Path $OutputDir | Out-Null

$pageQueue = [System.Collections.Generic.Queue[Uri]]::new()
$assetQueue = [System.Collections.Generic.Queue[Uri]]::new()
$seenPages = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)
$seenAssets = [System.Collections.Generic.HashSet[string]]::new([System.StringComparer]::OrdinalIgnoreCase)

# Seed from sitemap index if available.
$sitemapUrl = [Uri]::new($siteRoot, "wp-sitemap.xml")
try {
    $sitemapIndex = (Invoke-WebRequest -Uri $sitemapUrl.AbsoluteUri -UseBasicParsing -TimeoutSec 30).Content
    $locs = @(Get-LocValues -XmlText $sitemapIndex)
    foreach ($loc in $locs) {
        if ($loc -match "\.xml($|\?)") {
            $xmlContent = (Invoke-WebRequest -Uri $loc -UseBasicParsing -TimeoutSec 30).Content
            foreach ($pageLoc in (Get-LocValues -XmlText $xmlContent)) {
                Add-Page -UriObject ([Uri]$pageLoc) -SiteRoot $siteRoot -BasePath $basePath -Queue $pageQueue -Seen $seenPages
            }
        } else {
            Add-Page -UriObject ([Uri]$loc) -SiteRoot $siteRoot -BasePath $basePath -Queue $pageQueue -Seen $seenPages
        }
    }
} catch {
    Write-Warning "Sitemap kon niet geladen worden, ik val terug op link-crawl vanaf home."
}

Add-Page -UriObject $siteRoot -SiteRoot $siteRoot -BasePath $basePath -Queue $pageQueue -Seen $seenPages

while ($pageQueue.Count -gt 0) {
    $pageUri = $pageQueue.Dequeue()
    try {
        $response = Invoke-WebRequest -Uri $pageUri.AbsoluteUri -UseBasicParsing -TimeoutSec 30
        $html = $response.Content
        $outputPath = Get-HtmlOutputPath -UriObject $pageUri -BasePath $basePath -RootDir $OutputDir
        $outputDirPath = Split-Path -Path $outputPath -Parent
        if (-not (Test-Path $outputDirPath)) { New-Item -ItemType Directory -Path $outputDirPath -Force | Out-Null }
        Set-Content -Path $outputPath -Value $html -Encoding UTF8

        $attrMatches = [regex]::Matches($html, "(?i)(?:href|src)\s*=\s*[""'']([^""'#>]+)[""'']")
        foreach ($m in $attrMatches) {
            $raw = $m.Groups[1].Value.Trim()
            if ($raw -match "^(#|mailto:|tel:|javascript:|data:)") { continue }
            if ($raw.StartsWith("//")) { $raw = "$($pageUri.Scheme):$raw" }
            try {
                $resolved = [Uri]::new($pageUri, $raw)
            } catch {
                continue
            }

            $ext = [IO.Path]::GetExtension($resolved.AbsolutePath).ToLowerInvariant()
            if ([string]::IsNullOrWhiteSpace($ext) -or $ext -in @(".html", ".htm", ".php")) {
                Add-Page -UriObject $resolved -SiteRoot $siteRoot -BasePath $basePath -Queue $pageQueue -Seen $seenPages
            } else {
                Add-Asset -UriObject $resolved -SiteRoot $siteRoot -BasePath $basePath -Queue $assetQueue -Seen $seenAssets
            }
        }

        $srcsetMatches = [regex]::Matches($html, "(?i)srcset\s*=\s*[""'']([^""']+)[""'']")
        foreach ($m in $srcsetMatches) {
            $parts = $m.Groups[1].Value.Split(",")
            foreach ($part in $parts) {
                $piece = $part.Trim()
                if ([string]::IsNullOrWhiteSpace($piece)) { continue }
                $rawSrc = $piece.Split(" ")[0]
                if ($rawSrc -match "^(data:|#)") { continue }
                try {
                    $resolved = [Uri]::new($pageUri, $rawSrc)
                    Add-Asset -UriObject $resolved -SiteRoot $siteRoot -BasePath $basePath -Queue $assetQueue -Seen $seenAssets
                } catch {}
            }
        }
    } catch {
        Write-Warning "Kon pagina niet ophalen: $($pageUri.AbsoluteUri)"
    }
}

while ($assetQueue.Count -gt 0) {
    $assetUri = $assetQueue.Dequeue()
    try {
        $assetPath = Get-AssetOutputPath -UriObject $assetUri -BasePath $basePath -RootDir $OutputDir
        if ([string]::IsNullOrWhiteSpace($assetPath)) { continue }
        $assetDir = Split-Path -Path $assetPath -Parent
        if (-not (Test-Path $assetDir)) { New-Item -ItemType Directory -Path $assetDir -Force | Out-Null }

        Invoke-WebRequest -Uri $assetUri.AbsoluteUri -OutFile $assetPath -UseBasicParsing -TimeoutSec 60

        if ([IO.Path]::GetExtension($assetPath).ToLowerInvariant() -eq ".css") {
            $cssText = Get-Content -Path $assetPath -Raw
            $urlMatches = [regex]::Matches($cssText, "(?i)url\(([^)]+)\)")
            foreach ($m in $urlMatches) {
                $rawCssUrl = $m.Groups[1].Value.Trim().Trim("'").Trim('"')
                if ($rawCssUrl -match "^(data:|#)") { continue }
                try {
                    $resolvedCssAsset = [Uri]::new($assetUri, $rawCssUrl)
                    Add-Asset -UriObject $resolvedCssAsset -SiteRoot $siteRoot -BasePath $basePath -Queue $assetQueue -Seen $seenAssets
                } catch {}
            }
        }
    } catch {
        Write-Warning "Kon asset niet ophalen: $($assetUri.AbsoluteUri)"
    }
}

$rootIndexPath = Join-Path $OutputDir "index.html"
if (-not (Test-Path $rootIndexPath)) {
    try {
        $homeHtml = (Invoke-WebRequest -Uri $siteRoot.AbsoluteUri -UseBasicParsing -TimeoutSec 30).Content
        Set-Content -Path $rootIndexPath -Value $homeHtml -Encoding UTF8
    } catch {
        Write-Warning "Kon homepage niet apart wegschrijven naar root index."
    }
}

# Rewrite links for root-based static hosting.
$replacePairs = @(
    @("$($siteRoot.Scheme)://$($siteRoot.Host)$basePath", "/"),
    @("http://www.maatlaswerk.be/", "/"),
    @("https://www.maatlaswerk.be/", "/"),
    @("$basePath", "/")
)

Get-ChildItem -Path $OutputDir -Recurse -File | Where-Object { $_.Extension -in @(".html", ".css", ".js", ".xml", ".txt") } | ForEach-Object {
    $content = Get-Content -Path $_.FullName -Raw
    foreach ($pair in $replacePairs) {
        $content = $content.Replace($pair[0], $pair[1])
    }
    Set-Content -Path $_.FullName -Value $content -Encoding UTF8
}

# Force a stable public menu in all exported pages that contain Elementor nav menus.
$menuFiles = Get-ChildItem -Path $OutputDir -Recurse -File -Filter *.html | Where-Object {
    (Get-Content -Path $_.FullName -Raw) -match "elementor-nav-menu"
}
foreach ($file in $menuFiles) {
    $content = Get-Content -Path $file.FullName -Raw

    $mainPattern = '(?s)(<ul id="menu-1-[^"]+" class="elementor-nav-menu">)(.*?)(</ul>)'
    $mainList = @'
<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="/" class="elementor-item">Home</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/about/" class="elementor-item">About</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/services/" class="elementor-item">Services</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/sample-page/" class="elementor-item">Sample Page</a></li>
'@.Trim()
    $content = [regex]::Replace($content, $mainPattern, { param($m) $m.Groups[1].Value + $mainList + $m.Groups[3].Value })

    $dropPattern = '(?s)(<ul id="menu-2-[^"]+" class="elementor-nav-menu">)(.*?)(</ul>)'
    $dropList = @'
<li class="menu-item menu-item-type-custom menu-item-object-custom"><a href="/" class="elementor-item" tabindex="-1">Home</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/about/" class="elementor-item" tabindex="-1">About</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/services/" class="elementor-item" tabindex="-1">Services</a></li>
<li class="menu-item menu-item-type-post_type menu-item-object-page"><a href="/sample-page/" class="elementor-item" tabindex="-1">Sample Page</a></li>
'@.Trim()
    $content = [regex]::Replace($content, $dropPattern, { param($m) $m.Groups[1].Value + $dropList + $m.Groups[3].Value })

    Set-Content -Path $file.FullName -Value $content -Encoding UTF8
}

# Remove CMS engine references from exported content.
$textFiles = Get-ChildItem -Path $OutputDir -Recurse -File | Where-Object {
    $_.Extension -in @(".html", ".php", ".css", ".js", ".xml", ".txt", ".json")
}
foreach ($file in $textFiles) {
    $content = Get-Content -Path $file.FullName -Raw

    # Neutralize common engine endpoints and folder names in content.
    $content = [regex]::Replace($content, "wp-content", "assets")
    $content = [regex]::Replace($content, "wp-includes", "assets-core")
    $content = [regex]::Replace($content, "wp-admin", "api")
    $content = [regex]::Replace($content, "wp-json", "api-json")
    $content = [regex]::Replace($content, "xmlrpc\.php", "rpc.php")
    $content = [regex]::Replace($content, "api\.w\.org", "api.site.local")
    $content = [regex]::Replace($content, "s\.w\.org", "cdn.site.local")
    $content = [regex]::Replace($content, "\bWordPress\b", "SiteCMS")
    $content = [regex]::Replace($content, "\bwordpress\b", "sitecms")
    $content = [regex]::Replace($content, "wpop_", "sitepop_")
    $content = [regex]::Replace($content, "wpadminbar", "siteadminbar")
    $content = [regex]::Replace($content, "--wp--", "--site--")
    $content = [regex]::Replace($content, "wp-", "site-")
    $content = [regex]::Replace($content, "_wp", "_site")
    $content = [regex]::Replace($content, "wp_", "site_")

    # Drop discovery/oEmbed lines that only exist for WordPress APIs.
    $content = [regex]::Replace($content, "(?im)^.*<link[^>]+api\.site\.local[^>]*>.*\r?\n?", "")
    $content = [regex]::Replace($content, "(?im)^.*<link[^>]+api-json[^>]*>.*\r?\n?", "")
    $content = [regex]::Replace($content, "(?im)^.*<link[^>]+rpc\.php[^>]*>.*\r?\n?", "")
    $content = [regex]::Replace($content, "(?im)^.*oEmbed[^\r\n]*\r?\n?", "")

    Set-Content -Path $file.FullName -Value $content -Encoding UTF8
}

# Rename engine directory names to neutral names.
$wpContentDir = Join-Path $OutputDir "wp-content"
$assetsDir = Join-Path $OutputDir "assets"
if (Test-Path $wpContentDir) {
    if (Test-Path $assetsDir) { Remove-Item -Path $assetsDir -Recurse -Force }
    Move-Item -Path $wpContentDir -Destination $assetsDir
}

$wpIncludesDir = Join-Path $OutputDir "wp-includes"
$assetsCoreDir = Join-Path $OutputDir "assets-core"
if (Test-Path $wpIncludesDir) {
    if (Test-Path $assetsCoreDir) { Remove-Item -Path $assetsCoreDir -Recurse -Force }
    Move-Item -Path $wpIncludesDir -Destination $assetsCoreDir
}

$pagesExported = (Get-ChildItem -Path $OutputDir -Recurse -File -Filter *.html | Measure-Object).Count
$assetsExported = (Get-ChildItem -Path $OutputDir -Recurse -File | Where-Object { $_.Extension -ne ".html" } | Measure-Object).Count

Write-Host "Static export klaar."
Write-Host "Output: $OutputDir"
Write-Host "HTML files: $pagesExported"
Write-Host "Other assets: $assetsExported"
