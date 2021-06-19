#!/bin/bash

apt-get -y install apache2 libapache2-mod-php php php-mbstring php7.4-xml php-mysql mysql-server

DB_EXISTS_CHECK=`mysqlshow wbmachine | grep -v Wildcard | grep -o wbmachine`

if [ "$DB_EXISTS_CHECK" != "wbmachine" ]; then
  mysql < setup/db_create.sql
fi

useradd --create-home wbmachine
mkdir -v /home/wbmachine/.aws
touch /home/wbmachine/.aws/config
touch /home/wbmachine/.aws/credentials
chown -Rv wbmachine:wbmachine /home/wbmachine/.aws
mkdir -v /var/lib/wbmachine
chown -v wbmachine:wbmachine /var/lib/wbmachine
mkdir -v /usr/share/wbmachine
cp -rv . /usr/share/wbmachine
mkdir -pv /usr/share/wbmachine/views/public
chown -Rv wbmachine:wbmachine /usr/share/wbmachine
touch /var/log/wbmachinelog
chown -v wbmachine:wbmachine /var/log/wbmachinelog
ln -sv /usr/share/wbmachine/config/wbmachine-process-pending-archives.service /etc/systemd/system/wbmachine-process-pending-archives.service
ln -sv /usr/share/wbmachine/config/wbmachine-process-pending-archives.timer /etc/systemd/system/wbmachine-process-pending-archives.timer
systemctl enable wbmachine-process-pending-archives.timer
systemctl start wbmachine-process-pending-archives.timer
ln -sv /usr/share/wbmachine/config/wbmachine.conf /etc/apache2/sites-available/wbmachine.conf
a2ensite wbmachine
a2enmod php7.4
systemctl restart apache2
