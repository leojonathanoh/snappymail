#!/bin/sh
set -eu

UPLOAD_MAX_SIZE=${UPLOAD_MAX_SIZE:-25M}
MEMORY_LIMIT=${MEMORY_LIMIT:-128M}
LOG_TO_STDERR=${LOG_TO_STDERR:-true}
SECURE_COOKIES=${SECURE_COOKIES:-true}

# Set attachment size limit
sed -i "s/<UPLOAD_MAX_SIZE>/$UPLOAD_MAX_SIZE/g" /usr/local/etc/php-fpm.d/php-fpm.conf /etc/nginx/nginx.conf
sed -i "s/<MEMORY_LIMIT>/$MEMORY_LIMIT/g" /usr/local/etc/php-fpm.d/php-fpm.conf

# Secure cookies
if [ "${SECURE_COOKIES}" = 'true' ]; then
    echo "[INFO] Secure cookies activated"
        {
        	echo 'session.cookie_httponly = On';
        	echo 'session.cookie_secure = On';
        	echo 'session.use_only_cookies = On';
        } > /usr/local/etc/php/conf.d/cookies.ini;
fi

# Copy snappymail default config if absent
SNAPPYMAIL_CONFIG_FILE=/var/lib/snappymail/_data_/_default_/configs/application.ini
if [ ! -f "$SNAPPYMAIL_CONFIG_FILE" ]; then
    echo "[INFO] Creating default Snappymail configuration"
    mkdir -p $(dirname $SNAPPYMAIL_CONFIG_FILE)
    cp /usr/local/include/application.ini $SNAPPYMAIL_CONFIG_FILE
fi

# Enable output of snappymail logs
sed '/^\; Enable logging/{
N
s/enable = Off/enable = On/
}' -i $SNAPPYMAIL_CONFIG_FILE
# Redirect snappymail logs to stderr /stdout
sed 's/^filename = .*/filename = "stderr"/' -i $SNAPPYMAIL_CONFIG_FILE
sed 's/^write_on_error_only = .*/write_on_error_only = Off/' -i $SNAPPYMAIL_CONFIG_FILE
sed 's/^write_on_php_error_only = .*/write_on_php_error_only = On/' -i $SNAPPYMAIL_CONFIG_FILE

# Always enable snappymail Auth logging
sed 's/^auth_logging = .*/auth_logging = On/' -i $SNAPPYMAIL_CONFIG_FILE
sed 's/^auth_logging_filename = .*/auth_logging_filename = "auth.log"/' -i $SNAPPYMAIL_CONFIG_FILE
sed 's/^auth_logging_format = .*/auth_logging_format = "[{date:Y-m-d H:i:s}] Auth failed: ip={request:ip} user={imap:login} host={imap:host} port={imap:port}"/' -i $SNAPPYMAIL_CONFIG_FILE
sed 's/^auth_syslog = .*/auth_syslog = Off/' -i $SNAPPYMAIL_CONFIG_FILE
chown -R www-data:www-data /var/lib/snappymail/

# Fix permissions
# chown -R $UID:$GID /var/lib/snappymail /var/log /var/lib/nginx
# chmod o+w /dev/stdout
# chmod o+w /dev/stderr


# Touch supervisord PID file in order to fix permissions
# touch /run/supervisord.pid
# chown php-cli:php-cli /run/supervisord.pid

# RUN !
exec /usr/bin/supervisord -c /supervisor.conf --pidfile /run/supervisord.pid
