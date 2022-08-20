#Lambda base image Amazon Linux
FROM public.ecr.aws/lambda/provided:al2 as builder
# Set desired PHP Version
ARG php_version="8.1.7"
RUN yum clean all && \
    yum install -y autoconf \
                bison \
                bzip2-devel \
                gcc \
                gcc-c++ \
                git \
                gzip \
                libcurl-devel \
                libxml2-devel \
                make \
                openssl-devel \
                tar \
                unzip \
                zip \
                re2c \
                sqlite-devel \
                oniguruma-devel

# Download the PHP source, compile, and install both PHP and Composer
RUN curl -sL https://github.com/php/php-src/archive/php-${php_version}.tar.gz | tar -xvz && \
    cd php-src-php-${php_version} && \
    ./buildconf --force && \
    ./configure --prefix=/opt/php/ --with-config-file-path=/var/lang/lib --with-openssl --with-curl --with-zlib --without-pear --enable-bcmath --with-bz2 --enable-mbstring --with-mysqli && \
    make -j 5 && \
    make install && \
    /opt/php/bin/php -v && \
    curl -sS https://getcomposer.org/installer | /opt/php/bin/php -- --install-dir=/opt/php/bin/ --filename=composer

# Prepare runtime files
RUN mkdir -p /lambda-runtime
COPY bootstrap /lambda-runtime/
RUN chmod 0755 /lambda-runtime/bootstrap

###### Create runtime image ######
FROM public.ecr.aws/lambda/provided:al2 as runtime
# Layer 0: bootstrap image & dependencies
RUN mkdir -p /opt/extensions
COPY --from=builder /usr/lib64/libonig.so.2 /usr/lib64
# Layer 1: PHP
COPY --from=builder /opt/php /var/lang
COPY php/php.ini /var/lang/lib
# Layer 2: Runtime Interface Client
COPY --from=builder /lambda-runtime /var/runtime

COPY php /var/task/
RUN cd /var/task && \
    rm -rf vendor && \
    /var/lang/bin/php /var/lang/bin/composer install

CMD [ "index" ]
