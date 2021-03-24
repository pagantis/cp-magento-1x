#!/bin/bash
while true; do
    read -p "Do you want to install magento 1.9.x or 1.6.x or 1.14.x[19|16|114]? " version
    case $version in
        [19]* ) break;;
        [16]* ) break;;
        [114]* ) break;;
        * ) echo "Please answer 19 or 16.";;
    esac
done
while true; do
    read -p "Do you wish to run dev or test [test|dev]? " devtest
    case $devtest in
        [dev]* ) container="magento$version-dev";test=false; break;;
        [test]* ) container="magento$version-test";test=true; break;;
        * ) echo "Please answer dev or test.";;
    esac
done

while true; do
    read -p "You have chosen to start ${container}, are you sure [y/n]? " yn
    case $yn in
        [Yy]* ) break;;
        [Nn]* ) exit;;
        * ) echo "Please answer yes or no.";;
    esac
done

composer install

# Prepare environment and build package
docker-compose up -d ${container}
sleep 10

if [ $version = "16" ];
then
    echo "copying ./resources/Mysql4.php into ${container}:/var/www/html/app/code/core/Mage/Install/Model/Installer/Db/"
    docker cp ./resources/Mysql4.php ${container}:/var/www/html/app/code/core/Mage/Install/Model/Installer/Db/
fi
# Install magento and sample data
docker-compose exec ${container} install-sampledata
docker-compose exec ${container} install-magento

# Install modman and enable the link creation
docker-compose exec ${container} modman init
docker-compose exec ${container} modman link /clearpay

# Install n98-magerun to enable automatically dev:symlinks so that modman works
docker-compose exec ${container} curl -O https://files.magerun.net/n98-magerun.phar
docker-compose exec ${container} chmod +x n98-magerun.phar
docker-compose exec ${container} ./n98-magerun.phar dev:symlinks 1

sleep 10
set -e

docker-compose exec ${container} ./n98-magerun.phar cache:flush
