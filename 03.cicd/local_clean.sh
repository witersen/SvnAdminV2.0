#!/usr/bin/bash

# 此脚本的作用为
# 清理所在平台指定项目的指定分支的历史镜像
# 注意要在构建镜像结束后执行

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

TAG=$2
if [ "${TAG}" == '' ]; then
  echo 'TAG 为空'
  exit 1
fi

GIT_BRANCH=$3
if [ "${GIT_BRANCH}" == '' ]; then
  echo 'GIT_BRANCH 为空'
  exit 1
fi

# 清理没有标签的镜像
image_ids=$(docker images | grep "<none>" | awk "{print \$3}")
if [ "${image_ids}" != '' ]; then
  docker image rm ${image_ids} 2>/dev/null
fi

# 清理本项目的本分支的旧镜像
image_tags=$(docker images | grep "/${project_name}/" | grep ${GIT_BRANCH} | grep -v ${TAG} | awk '{print $1":"$2}')
if [ "${image_tags}" != '' ]; then
  docker image rm ${image_tags} 2>/dev/null
fi

exit 0
