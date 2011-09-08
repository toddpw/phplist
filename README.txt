To import a new version of PHPList:

1. rm -r phplist-x.y.z phplist-*.tgz
2. wget <sourceforge direct tarball link>
3. phpver=`basename phplist-*.tgz .tgz`; echo $phpver
4. tar xzf $phpver.tgz
5. mv $phpver phplist-x.y.z
6. git add .
7. git commit -m "Import PHPList $phpver"
8. git tag $phpver
9. phpbranch=`echo $phpver | sed s/phplist/local/`; echo $phpbranch
10. git checkout -b $phpbranch

Install/Upgrade

1. rsync -av phplist-x.y.z/public_html/lists ../..
2. diff -r phplist-x.y.z/public_html/lists ../../lists
  (fix or sign off on anything that shows up, like site-local .htaccess)
3. Create database or back it up if this is an upgrade.
4. Edit lists/config/config.php
5. Browse to /lists/admin/ and if this is an upgrade, use Upgrade link.
