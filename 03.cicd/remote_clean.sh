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

php_version_array=(php55 php56 php70 php71 php72 php73 php74 php80 php81 php82)
svn_version_array=(1.9 1.10 1.11 1.14)

http_port_start=8000
svn_port_start=3690

#快速停止+删除
docker stop $(docker ps -aq)
docker rm $(docker ps -aq -f status=exited)

#精准停止+删除
# for php_version in "${php_version_array[@]}"; do
#   for svn_version in "${svn_version_array[@]}"; do

#     sign=${TAG}-${php_version}-svn${svn_version}

#     docker stop ${sign} && docker rm ${sign}

#   done
# done

exit 0
