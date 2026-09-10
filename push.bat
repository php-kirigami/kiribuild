git tag -a v2.0.0 -m "v2: composite action trimmed to node/cli/export; checkout and commit moved to the caller"
git push origin v2.0.0
git tag -f v2 v2.0.0
git push origin v2 --force
