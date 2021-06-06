a2dissite wbmachine
rm /etc/apache2/sites-available/wbmachine.conf
systemctl stop wbmachine-process-pending-archives.timer
systemctl disable wbmachine-process-pending-archives.timer
rm /etc/systemd/system/wbmachine-process-pending-archives.service
rm /etc/systemd/system/wbmachine-process-pending-archives.timer
systemctl restart apache2
mysql < install/db_drop.sql
rmdir -p /usr/share/wbmachine
rmdir -p /var/lib/wbmachine
