rm -rf lib
rm -f app/composer.lock
rsync -av --progress ../../ lib/ --exclude tests --exclude=".*"  --exclude="vendor/"
