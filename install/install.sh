apt install apache2
apt install php
apt install libapache2-mod-php
a2enmod php7.4
systemctl restart apache2
rm /var/www/html/index.html
ln -s server/index.php /var/www/html/index.php
