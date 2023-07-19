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

#1.构建容器部署包

docker login --username ${MY_HARBOR_USER} --password ${MY_HARBOR_PASS} ${MY_HARBOR_HOST}
image_svnadmin="${MY_HARBOR_HOST}/${project_name}/svnadmin-${GIT_BRANCH}:${TAG}"
docker build -f 03.cicd/svnadmin_docker/dockerfile . -t="${image_svnadmin}"
docker push ${image_svnadmin}

#2.构建源码部署包

docker stop svnadmintemp && docker rm svnadmintemp

mkdir source
docker run -d --name svnadmintemp --privileged "${image_svnadmin}" /usr/sbin/init
docker cp svnadmintemp:/var/www/html source/ && mv source/html source/"${TAG}"

tar -zcf "${TAG}.tar.gz" -C source/"${TAG}" .

cd source/"${TAG}" && zip -qr "../../${TAG}.zip" . && cd ../../

rm -rf source
docker stop svnadmintemp && docker rm svnadmintemp

curl ftp://192.168.31.206/ -T "${TAG}.zip" -u "svnadmin:svnadmin" && rm -f "${TAG}.zip"
curl ftp://192.168.31.206/ -T "${TAG}.tar.gz" -u "svnadmin:svnadmin" && rm -f "${TAG}.tar.gz"

exit 0
