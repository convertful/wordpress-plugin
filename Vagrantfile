# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  # Available Boxes: https://atlas.hashicorp.com/search
  config.vm.box = "ubuntu/xenial64"

  # Virtual Machine will be available at 10.10.10.11
  config.vm.network "private_network", ip: "10.10.10.11"

  # Synced folder
  config.vm.synced_folder "./", "/vagrant", disabled: true
  config.vm.synced_folder "./", "/srv/og-wordpress", create: true, group: "www-data", owner: "www-data"

  # VirtualBox settings
  config.vm.provider "virtualbox" do |v|
    # Don't boot with headless mode
    v.gui = false

    # Use VBoxManage to customize the VM. For example to change memory:
    v.customize ["modifyvm", :id, "--memory",               "512"]
    v.customize ["modifyvm", :id, "--cpuexecutioncap",      "50"]
    v.customize ["modifyvm", :id, "--natdnshostresolver1",  "on"]
    v.customize ["modifyvm", :id, "--natdnsproxy1",         "on"]
  end

  # Installing the required packages and internal workflow
  config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"
  config.vm.provision "shell", inline: <<-SHELL
########################################################################################################################
################################################# SHELL START ##########################################################
########################################################################################################################

# Force a blank root password for mysql
export DEBIAN_FRONTEND="noninteractive"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password password password"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password_again password password"
# Install mysql, nginx, php-fpm
sudo apt-get install -y -f mysql-server mysql-client nginx php-fpm
# Install required used php packages
sudo apt-get install -y -f php-mysql php-curl php-gd
# Creating required folders
sudo rm -rf /srv/wp.convertful.local
sudo mkdir -p /srv/wp.convertful.local/{www,logs}
sudo chmod -R 7777 /srv/wp.convertful.local
# Nginx virtual host
cat << 'EOF' | sudo tee /etc/nginx/sites-enabled/default
server {
    listen 80;
    server_name wp.convertful.local;
    root /srv/wp.convertful.local/www;
    error_log /srv/wp.convertful.local/logs/error.log error;
    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$args;
        # Required for compatibility with Virualbox
        sendfile off;
    }
    rewrite /wp-admin$ $scheme://$host$uri/ permanent;
    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+?\.php)(/.*)$;
        if (!-f $document_root$fastcgi_script_name) {
            return 404;
        }
        include fastcgi.conf;
        fastcgi_index index.php;
        fastcgi_pass unix:/run/php/php7.0-fpm.sock;
    }
}
EOF
sudo service nginx restart
sudo service php7.0-fpm restart
# Proper database credentials
echo 'create database `wp`;' | mysql -uroot -ppassword
# Installing WP CLI
curl -s -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar > /dev/null
chmod +x /usr/local/bin/wp
sudo chown ubuntu:ubuntu /usr/local/bin/wp
# Creating site
cd /srv/wp.convertful.local/www
wp core download --allow-root
wp core config --dbname=wp --dbuser=root --dbpass=password --allow-root
wp core install --url="wp.convertful.local" --title="Local OG WordPress Site" --admin_user=admin --admin_password=admin --admin_email="admin@example.com" --allow-root
sudo chown -R www-data:www-data /srv/wp.convertful.local
# Adding plugin
ln -s /srv/og-wordpress /srv/wp.convertful.local/www/wp-content/plugins/convertful
echo "Done"

########################################################################################################################
################################################## SHELL END ###########################################################
########################################################################################################################
  SHELL


end
