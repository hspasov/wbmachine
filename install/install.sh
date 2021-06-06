apt -y install apache2
apt -y install libapache2-mod-php
apt -y install php
apt -y install php-mysql
apt -y install mysql-server
mysql < install/db_create.sql
mkdir /usr/share/wbmachine
cp -r . /usr/share/wbmachine
mkdir -p /usr/share/wbmachine/views/public
ln -s /usr/share/wbmachine/config/wbmachine-process-pending-archives.service /etc/systemd/system/wbmachine-process-pending-archives.service
ln -s /usr/share/wbmachine/config/wbmachine-process-pending-archives.timer /etc/systemd/system/wbmachine-process-pending-archives.timer
systemctl enable wbmachine-process-pending-archives.timer
systemctl start wbmachine-process-pending-archives.timer
ln -s /usr/share/wbmachine/config/wbmachine.conf /etc/apache2/sites-available/wbmachine.conf
a2ensite wbmachine
a2enmod php7.4
systemctl restart apache2
