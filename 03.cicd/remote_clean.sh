#!/usr/bin/bash

# 此脚本的作用为在要部署服务的目标主机上执行之前的服务清理操作
# 目标机器必须要安装 docker docker-compose

# 当前所处目录为 登录用户的家目录

JOB_NAME=$1
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

GIT_BRANCH=$2
if [ "${GIT_BRANCH}" == '' ]; then
    echo 'GIT_BRANCH 为空'
    exit 1
fi

TAG=$3
if [ "${TAG}" == '' ]; then
    echo 'TAG 为空'
    exit 1
fi

cd "${JOB_NAME}/03.cicd"

docker stop ${TAG} && docker rm ${TAG}

exit 0
