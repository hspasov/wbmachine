#!/bin/bash

a2dissite wbmachine
rm -v /etc/apache2/sites-available/wbmachine.conf
systemctl stop wbmachine-process-pending-archives.timer
systemctl disable wbmachine-process-pending-archives.timer
rm -v /etc/systemd/system/wbmachine-process-pending-archives.service
systemctl restart apache2
mysql < setup/db_drop.sql
rm -rv /usr/share/wbmachine
rm -rv /var/lib/wbmachine
userdel --remove wbmachine
