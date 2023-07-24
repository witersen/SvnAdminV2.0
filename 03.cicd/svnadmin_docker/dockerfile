FROM centos:centos7.9.2009

LABEL MAINTAINER "www.witersen.com 2023-07-23"

# 时间同步
ENV TZ=Asia/Shanghai \
    DEBIAN_FRONTEND=noninteractive

RUN ln -fs /usr/share/zoneinfo/${TZ} /etc/localtime \
    && echo ${TZ} > /etc/timezone

# 编码修改
RUN localedef -c -i en_US -f UTF-8 C.UTF-8 \
    && echo 'LANG="C.UTF-8"' >> /etc/sysconfig/i18n \
    && echo 'LC_ALL="C.UTF-8"' >> /etc/sysconfig/i18n \
    && echo 'export LANG="C.UTF-8"' >> /etc/profile \
    && echo 'export LC_ALL="C.UTF-8"' >> /etc/profile

RUN echo -e "[WandiscoSVN]\nname=Wandisco SVN Repo\nbaseurl=http://opensource.wandisco.com/centos/${releasever}/svn-${svn_version}/RPMS/${basearch}/\nenabled=1\ngpgcheck=0" > /etc/yum.repos.d/wandisco-svn.repo \
    && yum install -y epel-release yum-utils \
    && rpm -Uvh https://mirrors.aliyun.com/remi/enterprise/remi-release-7.rpm \
    && yum-config-manager --enable remi-${php_version} \
    && yum install -y php php-common php-cli php-fpm php-json php-mysqlnd php-mysql php-pdo php-process php-json php-gd php-bcmath php-ldap php-mbstring\
    && yum install -y httpd mod_dav_svn mod_ldap mod_php subversion subversion-tools \
    && yum install -y cyrus-sasl cyrus-sasl-lib cyrus-sasl-plain \
    && yum install -y which \
    && yum install -y cronie at \
    && yum clean all

# 配置文件
ADD 03.cicd/svnadmin_docker/data/ /home/svnadmin/
RUN cd /home/svnadmin/ \
    && mkdir -p backup \
    && mkdir -p crond \
    && mkdir -p rep \
    && mkdir -p temp \
    && mkdir -p templete/initStruct/01/branches \
    && mkdir -p templete/initStruct/01/tags \
    && mkdir -p templete/initStruct/01/trunk 
RUN chown -R apache:apache /home/svnadmin/ && mkdir -p /run/php-fpm/

# 关闭PHP彩蛋
RUN sed -i 's/expose_php = On/expose_php = Off/g' /etc/php.ini 

# 前端处理

RUN curl -L -o /usr/local/node-v14.18.2-linux-x64.tar.gz https://registry.npmmirror.com/-/binary/node/latest-v14.x/node-v14.18.2-linux-x64.tar.gz \
    && tar -xvf /usr/local/node-v14.18.2-linux-x64.tar.gz -C /usr/local/ \
    && ln -s /usr/local/node-v14.18.2-linux-x64/bin/node /usr/local/bin/node \
    && ln -s /usr/local/node-v14.18.2-linux-x64/bin/npm /usr/local/bin/npm \
    && npm config set registry https://registry.npm.taobao.org \

RUN mkdir /root/svnadmin_web 

COPY 01.web/package.json /root/svnadmin_web/
COPY 01.web/package-lock.json /root/svnadmin_web/
RUN cd /root/svnadmin_web && npm install

COPY 01.web/ /root/svnadmin_web/

RUN cd /root/svnadmin_web/ \
    && npm run build \
    && mv dist/* /var/www/html/ \
    && rm -rf /root/svnadmin_web \
    && rm -rf /usr/local/node-v14.18.2-linux-x64*

# 后端处理
ADD 02.php/ /var/www/html/

ADD 03.cicd/svnadmin_docker/start.sh /root/start.sh
RUN chmod +x /root/start.sh

EXPOSE 80
EXPOSE 443
EXPOSE 3690

CMD ["/root/start.sh"]
