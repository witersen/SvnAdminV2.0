#!/usr/bin/bash

# 此脚本的作用为
# 通过编写好的 dockerfile 来构建镜像并提交到镜像仓库

# 当前所处目录为 ${WORKSPACE}

MY_HARBOR_HOST=$1
if [ "${MY_HARBOR_HOST}" == '' ]; then
    echo 'MY_HARBOR_HOST 为空'
    exit 1
fi

MY_HARBOR_USER=$2
if [ "${MY_HARBOR_USER}" == '' ]; then
    echo 'MY_HARBOR_USER 为空'
    exit 1
fi

MY_HARBOR_PASS=$3
if [ "${MY_HARBOR_PASS}" == '' ]; then
    echo 'MY_HARBOR_PASS 为空'
    exit 1
fi

JOB_NAME=$4
if [ "${JOB_NAME}" == '' ]; then
    echo 'JOB_NAME 为空'
    exit 1
fi

# 项目名称
# 对于单分支流水线 JOB_NAME 为项目名称
# 对于多分支流水线 JOB_NAME 为项目名称/分支名称
# 多分支流水线 JOB_NAME       xxx/develop (即 项目名/分支名)
# 多分支流水线 JOB_BASE_NAME  develop                     (即 分支名)
if [[ ${JOB_NAME} =~ '/' ]]; then
    project_name=${JOB_NAME%/*}
else
    project_name=${JOB_NAME}
fi

TAG=$5
if [ "${TAG}" == '' ]; then
    echo 'TAG 为空'
    exit 1
fi

WORKSPACE=$6
if [ "${WORKSPACE}" == '' ]; then
    echo 'WORKSPACE 为空'
    exit 1
fi

GIT_BRANCH=$7
if [ "${GIT_BRANCH}" == '' ]; then
    echo 'GIT_BRANCH 为空'
    exit 1
fi

docker login --username ${MY_HARBOR_USER} --password ${MY_HARBOR_PASS} ${MY_HARBOR_HOST}

# 构建 php 5.5 5.6 7.0 7.1 7.2 7.3 7.4 8.0 8.1 8.2 和 svn 1.9 1.10 1.11 1.14 的组合镜像(php7.4 + svn1.10是最稳定的组合)

# php_version_array=(php55 php56 php70 php71 php72 php73 php74 php80 php81 php82)
php_version_array=(php74)
# svn_version_array=(1.9 1.10 1.11 1.14)
svn_version_array=(1.10)

for php_version in "${php_version_array[@]}"; do
    for svn_version in "${svn_version_array[@]}"; do

        #1.构建容器部署包

        sign=${TAG}-${php_version}-svn${svn_version}

        cp -r 03.cicd/svnadmin_docker 03.cicd/${sign}
        php_version=${php_version} svn_version=${svn_version} envsubst <03.cicd/${sign}/dockerfile >03.cicd/${sign}/dockerfile.temp
        mv 03.cicd/${sign}/dockerfile.temp 03.cicd/${sign}/dockerfile

        image_svnadmin="${MY_HARBOR_HOST}/${project_name}/svnadmin-${GIT_BRANCH}:${sign}"
        docker build -f 03.cicd/${sign}/dockerfile . -t="${image_svnadmin}"
        docker push ${image_svnadmin}

        #2.构建源码部署包

        if [ "${php_version}" == 'php74' ]; then
            if [ "${svn_version}" == '1.10' ]; then
                docker stop ${TAG} && docker rm ${TAG}

                mkdir source
                docker run -d --name ${TAG} --privileged "${image_svnadmin}" /usr/sbin/init
                docker cp ${TAG}:/var/www/html source/ && mv source/html source/"${TAG}"

                tar -zcf "${TAG}.tar.gz" -C source/"${TAG}" .

                cd source/"${TAG}" && zip -qr "../../${TAG}.zip" . && cd ../../

                docker stop ${TAG} && docker rm ${TAG}

                curl ftp://192.168.31.206/ -T "${TAG}.zip" -u "svnadmin:svnadmin" && rm -f "${TAG}.zip"
                curl ftp://192.168.31.206/ -T "${TAG}.tar.gz" -u "svnadmin:svnadmin" && rm -f "${TAG}.tar.gz"

                rm -rf source
            fi
        fi

        rm -rf 03.cicd/${sign}

    done
done

exit 0
