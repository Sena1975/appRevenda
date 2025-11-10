mkdir -p tools

#!/usr/bin/env bash
set -euo pipefail

usage(){ echo "Uso: $0 -v v1.0.1 [-r] [-d] [-u root] [-h host] [-p /var/www/appRevenda]"; exit 1; }

VERSION=""
UPDATE_README=false
DEPLOY=false
VPS_USER="root"
VPS_HOST="srv1109062"     # ajuste
VPS_PATH="/var/www/appRevenda"

while getopts ":v:rdu:h:p:" opt; do
  case $opt in
    v) VERSION="$OPTARG";;
    r) UPDATE_README=true;;
    d) DEPLOY=true;;
    u) VPS_USER="$OPTARG";;
    h) VPS_HOST="$OPTARG";;
    p) VPS_PATH="$OPTARG";;
    *) usage;;
  esac
done

[[ -z "$VERSION" ]] && usage
[[ ! "$VERSION" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] && { echo "Versão inválida. Use vMAJOR.MINOR.PATCH"; exit 1; }

git checkout main
git pull

if [[ -n "$(git status --porcelain)" ]]; then
  echo "Há alterações locais não commitadas. Interrompendo."; exit 1
fi

if $UPDATE_README; then
  cat >> README.md <<EOF

## 🚀 Deploy por TAG (VPS + Nginx + PHP-FPM 8.3)

### Como lançar
\`\`\`bash
git checkout main
git pull
git tag -a $VERSION -m "Release $VERSION"
git push origin $VERSION
# No VPS:
# cd $VPS_PATH && ./deploy.sh
\`\`\`
EOF
  git add README.md
  git commit -m "docs: guia de deploy por TAG ($VERSION)"
  git push origin main
fi

if ! git tag --list "$VERSION" >/dev/null | grep -q "$VERSION"; then
  git tag -a "$VERSION" -m "Release $VERSION"
fi

if ! git ls-remote --tags origin "$VERSION" | grep -q "$VERSION"; then
  git push origin "$VERSION"
else
  echo "Tag $VERSION já existe no remoto."
fi

if $DEPLOY; then
  ssh "$VPS_USER@$VPS_HOST" "cd $VPS_PATH && ./deploy.sh"
else
  echo "No VPS, rode:  cd $VPS_PATH && ./deploy.sh"
fi
BASH

chmod +x tools/release.sh
