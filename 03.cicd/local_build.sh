#!/usr/bin/bash
set -e

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

# 构建 php8.3 + svn1.14 的组合镜像
# 注意：REMI EL7 的 php83 仓库停在 8.3.8，不含 CVE-2026-109618/109619 修复，
# 因此基础镜像必须是 EL9（当前为 Rocky Linux 9）

php_version_array=(php83)
svn_version_array=(1.14)

for php_version in "${php_version_array[@]}"; do
    for svn_version in "${svn_version_array[@]}"; do

        #1.构建容器部署包

        sign=${TAG}-${php_version}-svn${svn_version}

        rm -rf 03.cicd/${sign}
        cp -r 03.cicd/svnadmin_docker 03.cicd/${sign}
        php_version=${php_version} svn_version=${svn_version} envsubst '${php_version} ${svn_version}' <03.cicd/${sign}/dockerfile >03.cicd/${sign}/dockerfile.temp
        mv 03.cicd/${sign}/dockerfile.temp 03.cicd/${sign}/dockerfile

        image_svnadmin="${MY_HARBOR_HOST}/${project_name}/svnadmin-${GIT_BRANCH}:${sign}"
        docker build --pull -f 03.cicd/${sign}/dockerfile . -t="${image_svnadmin}"
        docker push ${image_svnadmin}

        #2.构建源码部署包

        if [ "${php_version}" == 'php83' ]; then
            if [ "${svn_version}" == '1.14' ]; then
                docker rm -f "${TAG}" 2>/dev/null || true

                rm -rf source
                mkdir source
                docker create --name "${TAG}" "${image_svnadmin}"
                docker cp "${TAG}:/var/www/html" source/ && mv source/html source/"${TAG}"

                tar -zcf "${TAG}.tar.gz" -C source/"${TAG}" .

                cd source/"${TAG}" && zip -qr "../../${TAG}.zip" . && cd ../../

                docker rm -f "${TAG}"

                curl ftp://192.168.31.206/ -T "${TAG}.zip" -u "svnadmin:svnadmin" && rm -f "${TAG}.zip"
                curl ftp://192.168.31.206/ -T "${TAG}.tar.gz" -u "svnadmin:svnadmin" && rm -f "${TAG}.tar.gz"

                rm -rf source
            fi
        fi

        rm -rf 03.cicd/${sign}

    done
done

exit 0
