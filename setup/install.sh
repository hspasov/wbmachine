#!/bin/bash

apt-get -y install apache2 libapache2-mod-php php php-mysql mysql-server
mysql < setup/db_create.sql
mkdir -v /var/lib/wbmachine
mkdir -v /usr/share/wbmachine
cp -rv . /usr/share/wbmachine
mkdir -pv /usr/share/wbmachine/views/public
ln -sv /usr/share/wbmachine/config/wbmachine-process-pending-archives.service /etc/systemd/system/wbmachine-process-pending-archives.service
ln -sv /usr/share/wbmachine/config/wbmachine-process-pending-archives.timer /etc/systemd/system/wbmachine-process-pending-archives.timer
systemctl enable wbmachine-process-pending-archives.timer
systemctl start wbmachine-process-pending-archives.timer
ln -sv /usr/share/wbmachine/config/wbmachine.conf /etc/apache2/sites-available/wbmachine.conf
a2ensite wbmachine
a2enmod php7.4
systemctl restart apache2
