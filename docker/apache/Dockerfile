FROM httpd:2.4.33-alpine

RUN apk update; \
    apk upgrade;

RUN sed -i '/LoadModule rewrite_module/s/^#//g' /usr/local/apache2/conf/httpd.conf
RUN sed -i '/LoadModule ssl_module/s/^#//g' /usr/local/apache2/conf/httpd.conf
RUN sed -i '/LoadModule socache_shmcb_module/s/^#//g' /usr/local/apache2/conf/httpd.conf

COPY cert /etc/cert/topanswers.local

COPY ssl.apache.conf /usr/local/apache2/conf/ssl.apache.conf
RUN echo "Include /usr/local/apache2/conf/ssl.apache.conf" \
    >> /usr/local/apache2/conf/httpd.conf

# Copy apache vhost file to proxy php requests to php-fpm container
COPY get.apache.conf /usr/local/apache2/conf/get.apache.conf
RUN echo "Include /usr/local/apache2/conf/get.apache.conf" \
    >> /usr/local/apache2/conf/httpd.conf

COPY post.apache.conf /usr/local/apache2/conf/post.apache.conf
RUN echo "Include /usr/local/apache2/conf/post.apache.conf" \
    >> /usr/local/apache2/conf/httpd.conf


