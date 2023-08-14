#!/usr/bin/bash

# 此脚本的作用为在要部署服务的目标主机上执行镜像拉取和运行操作
# 目标机器必须要安装 git docker docker-compose
# 目前对私有项目和共有项目是否都需要登录还未进行测试

# 当前所处目录为 登录用户的家目录

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

cd "${JOB_NAME}/03.cicd"

TAG=$5
if [ "${TAG}" == '' ]; then
  echo 'TAG 为空'
  exit 1
fi

GIT_BRANCH=$6
if [ "${GIT_BRANCH}" == '' ]; then
  echo 'GIT_BRANCH 为空'
  exit 1
fi

# 注：完整的标签名称为 ip[:port]/project/image:tag

# 对每次构建都发生变动的镜像进行主动拉取 docker-compose 不会检测本地的镜像与仓库端是否一致 所以不主动拉取会导致无法使用最新构建的镜像

docker login --username ${MY_HARBOR_USER} --password ${MY_HARBOR_PASS} ${MY_HARBOR_HOST}

# 清理没有标签的镜像
image_ids=$(docker images | grep "<none>" | awk "{print \$3}")
if [ "${image_ids}" != '' ]; then
  docker image rm ${image_ids}
fi

# php_version_array=(php55 php56 php70 php71 php72 php73 php74 php80 php81 php82)
php_version_array=(php74)
# svn_version_array=(1.9 1.10 1.11 1.14)
svn_version_array=(1.10)

http_port_start=8000
svn_port_start=3690

for php_version in "${php_version_array[@]}"; do
  for svn_version in "${svn_version_array[@]}"; do

    sign=${TAG}-${php_version}-svn${svn_version}

    # 重新拉取镜像
    image_svnadmin="${MY_HARBOR_HOST}/${project_name}/svnadmin-${GIT_BRANCH}:${sign}"

    docker pull ${image_svnadmin}

    docker run -d --name ${sign} -p ${http_port_start}:80 -p ${svn_port_start}:3690 --privileged ${image_svnadmin}

    http_port_start=$((http_port_start + 1))
    svn_port_start=$((svn_port_start + 1))

  done
done

exit 0
