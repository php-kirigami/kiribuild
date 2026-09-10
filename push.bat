git add . && git commit -m "..." && git push origin main
git tag -a v2.0.4 -m "..."
git push origin v2.0.4
git tag -f v2 v2.0.4
git push origin v2 --force
