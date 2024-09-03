# aws ec2 create-key-pair --key-name MCCEC2 --query KeyMaterial --output text
aws ec2 create-vpc --cidr-block 10.3.0.0/16 --tag-specifications ResourceType=vpc,Tags='[{Key=Name,Value=ta}]' --query Vpc.VpcId --output text
aws ec2 create-subnet --vpc-id vpc-06ccabdecac7b623e --cidr-block 10.3.0.0/24 --query Subnet.SubnetId --output text
aws ec2 create-internet-gateway --query InternetGateway.InternetGatewayId --output text
aws ec2 attach-internet-gateway --internet-gateway-id igw-0147f3abb0281f605 --vpc-id vpc-06ccabdecac7b623e
aws ec2 describe-route-tables --filters "Name=vpc-id,Values=vpc-06ccabdecac7b623e" --query RouteTables[0].RouteTableId --output text
aws ec2 create-route --route-table-id rtb-035ab26b98cbed9e4 --destination-cidr-block 0.0.0.0/0 --gateway-id igw-0147f3abb0281f605
aws ec2 associate-route-table --route-table-id rtb-035ab26b98cbed9e4 --subnet-id subnet-02bf6a9b79796d71f
# aws ec2 allocate-address --query PublicIp --output text
aws ec2 run-instances --image-id ami-0fcb42819bdb48f8f --block-device-mapping DeviceName=/dev/xvda,Ebs={VolumeSize=256} --count 1 --instance-type t4g.small --subnet-id subnet-02bf6a9b79796d71f --key-name MCCEC2 --query Instances[0].InstanceId --output text
aws ec2 modify-instance-attribute --instance-id i-08a26762b68534179 --no-source-dest-check
aws ec2 associate-address --instance-id i-08a26762b68534179 --public-ip 18.169.61.181
ssh-add
# allow PG, SSH, HTTPS through security group
# ssh-copy-id -i ./.ssh/id_rsa.pub admin@18.169.61.181
ssh -t admin@18.169.61.181 "sudo su - root"
apt-get update
iptables -F
apt remove iptables iptables-persistent
apt install --no-install-recommends -y nftables tmux
cat > /etc/nftables.conf <<"EOF"
#!/usr/sbin/nft -f
flush ruleset
table ip nat {
	chain prerouting {
		type nat hook prerouting priority 0; policy accept;
	}
	chain postrouting {
		type nat hook postrouting priority 100; policy accept;
		oifname "ens5" masquerade
	}
}
EOF
service nftables restart
apt install --no-install-recommends -y postgresql postgresql-contrib postgresql-plperl
cat > /etc/postgresql/15/main/pg_ident.conf <<"EOF"
# MAPNAME     SYSTEM-USERNAME   PG-USERNAME
map           postgres          postgres
map           root              postgres
EOF
cat > /etc/postgresql/15/main/pg_hba.conf <<"EOF"
#TYPE   DATABASE    USER        CIDR-ADDRESS          METHOD
local   all         postgres                          ident map=map
host    all         all         all                   scram-sha-256
local   all         all                               password
EOF
dd if=/dev/zero of=/var/swap bs=1M count=2048
chmod 600 /var/swap
mkswap /var/swap
swapon /var/swap
echo "/var/swap   swap    swap    defaults        0   0" >> /etc/fstab
cat > /etc/postgresql/15/main/postgresql.conf <<"EOF"
data_directory = '/var/lib/postgresql/15/main'
hba_file = '/etc/postgresql/15/main/pg_hba.conf'
ident_file = '/etc/postgresql/15/main/pg_ident.conf'
listen_addresses = '*'
ssl=on
ssl_cert_file = '/etc/ssl/certs/ssl-cert-snakeoil.pem'
ssl_key_file = '/etc/ssl/private/ssl-cert-snakeoil.key'
unix_socket_directories = '/var/run/postgresql'
log_line_prefix = '%t '
log_timezone = 'GB'
datestyle = 'iso, dmy'
timezone = 'GB'
default_text_search_config = 'pg_catalog.english'
logging_collector = on
max_parallel_workers_per_gather = 4
EOF
service postgresql restart
cat > /etc/postgresql-common/psqlrc <<"EOF"
\pset format wrapped
\pset linestyle unicode
\pset border 2
EOF
time pg_dump -d "host=cluster1.cluster-c8l1itv3i2dg.eu-west-2.rds.amazonaws.com dbname=ta user=postgres sslmode=require" -F c > dump.sql
psql template1 postgres <<"EOF"
drop database if exists ta;
drop role if exists ta_email;
drop role if exists ta_get;
drop role if exists ta_post;
create user ta_get;
create user ta_post;
create user ta_email;
create database ta;
revoke connect on database ta from public;
grant connect on database ta to ta_get;
grant connect on database ta to ta_post;
grant connect on database ta to ta_email;
alter database ta set search_path to '$user';
\c ta
create extension if not exists plperl;
EOF
time pg_restore --username postgres --dbname ta dump.sql
psql template1 postgres <<"EOF"
\password
EOF
psql ta postgres <<"EOF"
drop database postgres;
EOF
#from devcontainer:
psql "host=18.169.61.181 dbname=ta user=postgres sslmode=require" <<EOF
alter role ta_get password '$PGAPIPASSWORD';
alter role ta_post password '$PGAPIPASSWORD';
alter role ta_email password '$PGAPIPASSWORD';
EOF
sed -i 's/#net.ipv4.ip_forward=1/net.ipv4.ip_forward=1/g' /etc/sysctl.conf
sysctl -p
# run api.sh
apt install -y certbot python3-certbot-dns-route53
mkdir ~/.aws
cat > ~/.aws/config <<"EOF"
[default]
aws_access_key_id=AKIAYYQWL57X5UBPKEXB
aws_secret_access_key=********
EOF
chmod 600 ~/.aws/config
certbot certonly --agree-tos -m jack@maidenheadcentre.org.uk --no-eff-email --dns-route53 --dns-route53-propagation-seconds 30 -d 'topanswers.xyz,*.topanswers.xyz'
apt install -y apache2 php php-cli php-pgsql uuid-runtime php-gd php-dom php-mbstring php-curl
sed -i "s/^short_open_tag = Off$/short_open_tag = On/g" /etc/php/8.2/apache2/php.ini
a2enmod rewrite ssl headers dump_io
echo -e "Listen 80\nListen 443" > /etc/apache2/ports.conf
cat <<"EOF" > /etc/apache2/conf-enabled/security.conf
<Directory />
  AllowOverride None
  Order Deny,Allow
  Deny from all
</Directory>

ServerTokens Prod
ServerSignature Off
TraceEnable Off
EOF
a2dissite 000-default
cat <<"EOF" > /etc/apache2/sites-available/prod.conf
<VirtualHost *:80>
  ServerName topanswers.xyz
  Redirect permanent / https://topanswers.xyz/
</VirtualHost>
<VirtualHost *:80>
  ServerName www.topanswers.xyz
  Redirect permanent / https://topanswers.xyz/
</VirtualHost>
<VirtualHost *:443>
  ServerName www.topanswers.xyz
  Redirect permanent / https://topanswers.xyz/
  SSLEngine  on
  SSLCertificateFile /etc/letsencrypt/live/topanswers.xyz/cert.pem
  SSLCACertificateFile /etc/letsencrypt/live/topanswers.xyz/chain.pem
  SSLCertificateKeyFile /etc/letsencrypt/live/topanswers.xyz/privkey.pem
</VirtualHost>
<VirtualHost *:443>

  ServerName topanswers.xyz
  UseCanonicalName on
  DocumentRoot /srv/all
  AddDefaultCharset UTF-8
  LogLevel warn
  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/access.log combined

  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  Header unset ETag
  FileETag None

  RewriteEngine On
 #LogLevel alert rewrite:trace5

  RewriteRule ^/?$ /index
  RewriteRule ^(.*)[0-9a-f]{16}.(css|js)$ $1$2

  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=test(;.*)?$
  RewriteRule ^/(.*)$ /test/get/$1 [nocase,skip=3]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=jack(;.*)?$
  RewriteRule ^/(.*)$ /jack/get/$1 [nocase,skip=2]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=james(;.*)?$
  RewriteRule ^/(.*)$ /james/get/$1 [nocase,skip=1]
  RewriteRule ^/(.*)$ /prod/get/$1 [nocase]

  RewriteCond %{DOCUMENT_ROOT}/$1/page/$2 -d
  RewriteRule ^(.*?)([^/]+)/?$ $1/page/$2/$2.php [END]

  RewriteCond %{DOCUMENT_ROOT}/$0 -f
  RewriteRule ^.*$ - [END]

  RewriteCond %{DOCUMENT_ROOT}/$0.php -f
  RewriteRule ^.*$ $0.php [END]

  RewriteRule ^/(test|jack|james|prod)/get/([-a-z]*)$ /$1/get/page/community/community.php?community=$2 [qsappend,nocase,END]

  SSLEngine  on
  SSLCertificateFile /etc/letsencrypt/live/topanswers.xyz/cert.pem
  SSLCACertificateFile /etc/letsencrypt/live/topanswers.xyz/chain.pem
  SSLCertificateKeyFile /etc/letsencrypt/live/topanswers.xyz/privkey.pem

 #DumpIOInput On
 #LogLevel dumpio:notice

  <Directory /srv/all/>
    Options -Indexes
    AllowOverride None
    Order allow,deny
    allow from all
    Require all granted
    Header unset X-Powered-By
    <FilesMatch "\.(css|js)$">
      Header set Cache-Control "public, max-age=31536000, immutable"
    </FilesMatch>
  </Directory>

  <DirectoryMatch "^/srv/all/(test|jack|james|prod)/get/(?!(lib|fonts))">
    <FilesMatch "\.css$">
      SetHandler application/x-httpd-php
      Header set Content-type "text/css"
    </FilesMatch>
    <FilesMatch "\.js$">
      SetHandler application/x-httpd-php
      Header set Content-type "text/javascript"
    </FilesMatch>
    <FilesMatch "\.json$">
      SetHandler application/x-httpd-php
      Header set Content-type "application/json"
    </FilesMatch>
  </DirectoryMatch>

</VirtualHost>
EOF
cat <<"EOF" > /etc/apache2/sites-available/localhost.conf
<VirtualHost *:80>

  ServerName 127.0.0.1
  UseCanonicalName on
  DocumentRoot /srv/all
  AddDefaultCharset UTF-8
  LogLevel warn
  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/access.log combined

  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  Header unset ETag
  FileETag None

  RewriteEngine On
 #LogLevel alert rewrite:trace5

  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=test(;.*)?$
  RewriteRule ^/(.*)$ /test/get/$1 [nocase,skip=3]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=jack(;.*)?$
  RewriteRule ^/(.*)$ /jack/get/$1 [nocase,skip=2]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=james(;.*)?$
  RewriteRule ^/(.*)$ /james/get/$1 [nocase,skip=1]
  RewriteRule ^/(.*)$ /prod/get/$1 [nocase]

  RewriteCond %{DOCUMENT_ROOT}/$1/page/$2 -d
  RewriteRule ^(.*?)([^/]+)/?$ $1/page/$2/$2.php [END]

  RewriteCond %{DOCUMENT_ROOT}/$0 -f
  RewriteRule ^.*$ - [END]

  RewriteCond %{DOCUMENT_ROOT}/$0.php -f
  RewriteRule ^.*$ $0.php [END]

 #DumpIOInput On
 #LogLevel dumpio:notice

  <Directory /srv/all/>
    Options -Indexes
    AllowOverride None
    Order allow,deny
    allow from all
    Require all granted
  </Directory>

</VirtualHost>
EOF
cat <<"EOF" > /etc/apache2/sites-available/post.conf
<VirtualHost *:443>

  ServerName post.topanswers.xyz
  UseCanonicalName on
  DocumentRoot /srv/all
  AddDefaultCharset UTF-8
  LogLevel warn
  ErrorLog ${APACHE_LOG_DIR}/error.log
  CustomLog ${APACHE_LOG_DIR}/post.log combined

  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  Header unset ETag
  FileETag None

  RewriteEngine On
 #LogLevel alert rewrite:trace5

  RewriteRule ^/$ /index.php [nocase]

  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=test(;.*)?$
  RewriteRule ^/(.*)$ /test/post/$1 [nocase,skip=3]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=jack(;.*)?$
  RewriteRule ^/(.*)$ /jack/post/$1 [nocase,skip=2]
  RewriteCond %{HTTP_COOKIE}     ^(.*;\s)?environment=james(;.*)?$
  RewriteRule ^/(.*)$ /james/post/$1 [nocase,skip=1]
  RewriteRule ^/(.*)$ /prod/post/$1 [nocase]

  RewriteRule ^/([^.]+)$ /$1.php [nocase]

  SSLEngine  on
  SSLCertificateFile /etc/letsencrypt/live/topanswers.xyz/cert.pem
  SSLCACertificateFile /etc/letsencrypt/live/topanswers.xyz/chain.pem
  SSLCertificateKeyFile /etc/letsencrypt/live/topanswers.xyz/privkey.pem

 #DumpIOInput On
 #LogLevel dumpio:notice

  <Directory /srv/all/>
    Options -Indexes
    AllowOverride None
    Order allow,deny
    allow from all
    Require all granted
  </Directory>

</VirtualHost>
EOF
a2ensite prod post localhost
mkdir -p /srv/all/prod /srv/all/test /srv/uploads
chown www-data:www-data /srv/uploads
service apache2 restart
#scp -3rp admin@3.9.77.187:/srv/uploads admin@18.169.61.181:/home/admin
apt install postfix
sed -i "s/^smtpd_banner = .*$/smtpd_banner = topanswers.xyz/g" /etc/postfix/main.cf
sed -i "s/^myhostname = .*$/myhostname = topanswers.xyz/g" /etc/postfix/main.cf
service postfix restart
apt install cron
cat <<"EOF" > /etc/cron.d/ta
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

*  *  * * * root  php -f /srv/all/prod/email.php
EOF
cat <<"EOF" > /etc/cron.d/tabackup
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

0  *  * * * root  pg_dump --username=postgres ta -F c > /root/backup.$(date +"\%H").sql
EOF

