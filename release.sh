#!/usr/bin/env sh

#### it requires new version number
if [ -z "$1" ]; then
    echo "Error: please provide a version string"
    exit 1
fi
version="$1"

#### Build new changelog
echo "" >> Changelog.md.0
echo "$version 发布日期：`date +%Y-%m-%d`" >> Changelog.md.0
echo "----" >> Changelog.md.0
git log `git describe --tags --abbrev=0`..HEAD | \
    grep "^(changelog)" >> Changelog.md.0

#### prepend changelog
cat Changelog.md >> Changelog.md.0
mv  Changelog.md.0 Changelog.md

# portable solution in perl
perl -pi -e "s/const VERSION = .*\;/const VERSION = \'$version\'\;/" \
    src/RestAPI/Client.php

perl -pi -e "s/const VERSION = .*\;/const VERSION = \'$version\'\;/" \
    src/RestAPI/RestServiceClient.php

perl -pi -e "s/const VERSION = .*\;/const VERSION = \'$version\'\;/" \
    src/RestAPI/RestPayServiceClient.php

echo "Done! Ready to commit and release $version!"

