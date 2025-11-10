param(
  [Parameter(Mandatory=$true)][string]$Version,           # ex: v1.0.1
  [switch]$UpdateReadme,                                  # adiciona bloco ao README
  [switch]$Deploy,                                        # executa deploy no VPS (opcional)
  [string]$VpsUser = "root",                              # ajuste
  [string]$VpsHost = "srv1109062",                        # ajuste: IP ou domÃ­nio do VPS
  [string]$VpsPath = "/var/www/appRevenda"                # caminho no VPS
)

$ErrorActionPreference = "Stop"

function Exec($cmd){ & $cmd; if($LASTEXITCODE -ne 0){ throw "Falha: $cmd" } }

# 0) Valida versÃ£o
if($Version -notmatch '^v\d+\.\d+\.\d+$'){ throw "Use formato semÃ¢ntico: vMAJOR.MINOR.PATCH (ex: v1.0.1)" }

# 1) Garante main atualizado e sem pendÃªncias
Exec { git checkout main }
Exec { git pull }
$changed = (git status --porcelain)
if($changed){
  Write-Host "HÃ¡ alteraÃ§Ãµes locais nÃ£o commitadas. FaÃ§a commit antes ou remova-as." -ForegroundColor Yellow
  git status
  exit 1
}

# 2) (Opcional) atualiza README com bloco de deploy por TAG, apenas se flag passada
if($UpdateReadme){
$block = @"
## ðŸš€ Deploy por TAG (VPS + Nginx + PHP-FPM 8.3)

### Como lanÃ§ar
\`\`\`bash
git checkout main
git pull
git tag -a $Version -m "Release $Version"
git push origin $Version
# No VPS:
# cd /var/www/appRevenda && ./deploy.sh
\`\`\`

### Rollback
\`\`\`bash
cd /var/www/appRevenda
./deploy-tag.sh $Version
\`\`\`

### Checklist rÃ¡pido
\`\`\`bash
cd /var/www/appRevenda && ./deploy.sh
systemctl status nginx --no-pager
systemctl status php8.3-fpm --no-pager
tail -n 100 storage/logs/laravel.log
\`\`\`
"@

  Add-Content -Path "README.md" -Value "`n$block"
  Exec { git add README.md }
  Exec { git commit -m "docs: guia de deploy por TAG ($Version)" }
  Exec { git push origin main }
}

# 3) Cria TAG e envia
# evita recriar se jÃ¡ existir localmente
$existsLocal = (git tag --list $Version)
$existsRemote = (git ls-remote --tags origin $Version)

if(!$existsLocal){
  Exec { git tag -a $Version -m "Release $Version" }
}
if(!$existsRemote){
  Exec { git push origin $Version }
}else{
  Write-Host "Tag $Version jÃ¡ existe no remoto." -ForegroundColor Yellow
}

# 4) (Opcional) dispara deploy no VPS usando a Ãºltima tag
if($Deploy){
  $ssh = "ssh $VpsUser@$VpsHost 'cd $VpsPath && ./deploy.sh'"
  Write-Host "Executando: $ssh"
  Exec { Invoke-Expression $ssh }
}

Write-Host "Pronto! Tag $Version criada/enviada." -ForegroundColor Green
if(!$Deploy){
  Write-Host "No VPS, rode:  cd $VpsPath && ./deploy.sh" -ForegroundColor Cyan
}
