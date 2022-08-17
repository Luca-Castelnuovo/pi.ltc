# pi.ltc
Manage code stored on my raspberrypi

### ISO
1. Get image from https://www.raspberrypi.com/software/operating-systems/#:~:text=Archive-,Raspberry%20Pi%20OS%20(Legacy),-A%20stable%20legacy
2. Burn to (relatively new) SD card using balenaEtcher

### Install OS
```bash
sudo apt update -y
sudo apt upgrade -y

sudo apt install ufw
sudo ufw default deny incoming
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443

sudo usermod -a -G gpio www-data

sudo apt remove apache
sudo apt install nginx

sudo mkdir /etc/nginx/certificates
sudo nano /etc/nginx/certificates/cloudflare.key
sudo nano /etc/nginx/certificates/cloudflare.cert

sudo rm /etc/nginx/sites-available/default
sudo rm /etc/nginx/sites-enabled/default

sudo nano /etc/nginx/sites-available/vosstraat
sudo ln -s /etc/nginx/sites-available/vosstraat /etc/nginx/sites-enabled/vosstraat

sudo apt install php php-fpm php-gmp

wget -O composer-setup.php https://getcomposer.org/installer
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
rm -rf composer-setup.php

sudo apt install git
sudo mkdir -p /var/www/vosstraat
sudo chown -R $USER:$USER /var/www/vosstraat
cd /var/www/vosstraat
git clone https://github.com/Luca-Castelnuovo/pi.ltc.git .
cp .env.example .env
composer install

sudo nginx -t
sudo service nginx restart

sudo nano /root/cloudflare.sh
sudo chmod +x /root/cloudflare.sh
sudo crontab -e # 0 3 * * * /bin/bash /root/cloudflare.sh >/dev/null 2>&1
```

**/etc/nginx/sites-available/vosstraat**
```
server {
    listen 80 default_server;

    server_name _;

    return 301 https://vosstraat.castelnuovo.xyz$request_uri;
}

server {
    listen 443 ssl http2;

    server_name vosstraat.castelnuovo.xyz;

    ssl_certificate /etc/nginx/certificates/cloudflare.cert;
    ssl_certificate_key /etc/nginx/certificates/cloudflare.key;

    root /var/www/vosstraat/public;
    index index.php;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
    }
}
```

**/root/cloudflare.sh**
```bash
#!/bin/bash

auth_key="" # Your API Token
zone_identifier="02016a92e84cabf590316c675de757da"  # Can be found in the "Overview" tab of your domain
record_name="vosstraat.castelnuovo.xyz"             # Which record you want to be synced
ttl="3600"                                          # Set the DNS TTL (seconds)
proxy="true"                                        # Set the proxy to true or false
slackchannel="#raspberry"                           # Slack Channel #example
slackuri=""                                         # URI for Slack WebHook "https://hooks.slack.com/services/xxxxx"

###########################################
## Check if we have a public IP
###########################################
ipv4_regex='([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])\.([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])'
ip=$(curl -s -4 https://cloudflare.com/cdn-cgi/trace | grep -E '^ip'); ret=$?
if [[ ! $ret == 0 ]]; then # In the case that cloudflare failed to return an ip.
    # Attempt to get the ip from other websites.
    ip=$(curl -s https://api.ipify.org || curl -s https://ipv4.icanhazip.com)
else
    # Extract just the ip from the ip line from cloudflare.
    ip=$(echo $ip | sed -E "s/^ip=($ipv4_regex)$/\1/")
fi

# Use regex to check for proper IPv4 format.
if [[ ! $ip =~ ^$ipv4_regex$ ]]; then
    logger -s "DDNS Updater: Failed to find a valid IP."
    exit 2
fi

###########################################
## Set the auth header
###########################################
auth_header="Authorization: Bearer"

###########################################
## Seek for the A record
###########################################

logger "DDNS Updater: Check Initiated"
record=$(curl -s -X GET "https://api.cloudflare.com/client/v4/zones/$zone_identifier/dns_records?type=A&name=$record_name" \
                      -H "$auth_header $auth_key" \
                      -H "Content-Type: application/json")

###########################################
## Check if the domain has an A record
###########################################
if [[ $record == *"\"count\":0"* ]]; then
  logger -s "DDNS Updater: Record does not exist, perhaps create one first? (${ip} for ${record_name})"
  exit 1
fi

###########################################
## Get existing IP
###########################################
old_ip=$(echo "$record" | sed -E 's/.*"content":"(([0-9]{1,3}\.){3}[0-9]{1,3})".*/\1/')
# Compare if they're the same
if [[ $ip == $old_ip ]]; then
  logger "DDNS Updater: IP ($ip) for ${record_name} has not changed."
  exit 0
fi

###########################################
## Set the record identifier from result
###########################################
record_identifier=$(echo "$record" | sed -E 's/.*"id":"(\w+)".*/\1/')

###########################################
## Change the IP@Cloudflare using the API
###########################################
update=$(curl -s -X PATCH "https://api.cloudflare.com/client/v4/zones/$zone_identifier/dns_records/$record_identifier" \
                     -H "$auth_header $auth_key" \
                     -H "Content-Type: application/json" \
                     --data "{\"type\":\"A\",\"name\":\"$record_name\",\"content\":\"$ip\",\"ttl\":\"$ttl\",\"proxied\":${proxy}}")

###########################################
## Report the status
###########################################
case "$update" in
*"\"success\":false"*)
  echo -e "DDNS Updater: $ip $record_name DDNS failed for $record_identifier ($ip). DUMPING RESULTS:\n$update" | logger -s
  if [[ $slackuri != "" ]]; then
    curl -L -X POST $slackuri \
    --data-raw '{
      "channel": "'$slackchannel'",
      "text" : "'"$sitename"' DDNS Update Failed: '$record_name': '$record_identifier' ('$ip')."
    }'
  fi
  exit 1;;
*)
  logger "DDNS Updater: $ip $record_name DDNS updated."
  if [[ $slackuri != "" ]]; then
    curl -L -X POST $slackuri \
    --data-raw '{
      "channel": "'$slackchannel'",
      "text" : "'"$sitename"' Updated: '$record_name''"'"'s'""' new IP Address is '$ip'"
    }'
  fi
  exit 0;;
esac
```
