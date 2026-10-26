#!/usr/bin/bash
#
# SVNAdmin2 2.5.11 冒烟测试
#
# 用法：
#   bash 03.cicd/smoke_test.sh [容器名] [访问地址] [管理员账号] [管理员密码]
#
# 默认：
#   bash 03.cicd/smoke_test.sh svnadmin-smoke http://127.0.0.1:18080 admin admin
#
# 说明：
#   - 只读测试，不创建/删除仓库或用户
#   - 默认登录验证码开启时，会从容器内 SQLite 读取最新验证码
#   - 需要容器内 PHP 可执行（镜像自带）

set -euo pipefail

CONTAINER=${1:-svnadmin-smoke}
BASE_URL=${2:-http://127.0.0.1:18080}
ADMIN_USER=${3:-admin}
ADMIN_PASS=${4:-admin}

TOKEN=""
FAILED=0

php_json() {
    # 从 stdin 读取 JSON，用容器内 PHP 提取字段
    docker exec -i "$CONTAINER" php -r '
        $j = json_decode(stream_get_contents(STDIN), true);
        $path = $argv[1];
        $v = $j;
        foreach (explode(".", $path) as $k) {
            if (!is_array($v) || !array_key_exists($k, $v)) { echo ""; exit(0); }
            $v = $v[$k];
        }
        if (is_bool($v)) { echo $v ? "1" : "0"; }
        elseif (is_scalar($v)) { echo $v; }
    ' "$1"
}

api() {
    local query=$1
    local data=${2:-'{}'}
    curl -sS -X POST "${BASE_URL}/api.php?${query}" \
        -H 'Content-Type: application/json' \
        -H "token: ${TOKEN}" \
        --data "$data"
}

check() {
    local name=$1
    local query=$2
    local data=${3:-'{}'}
    local resp status
    resp=$(api "$query" "$data")
    status=$(printf '%s' "$resp" | php_json "status")
    if [ "$status" = "1" ]; then
        echo "OK   $name"
    else
        echo "FAIL $name"
        echo "     $resp"
        FAILED=1
    fi
}

echo "== 等待服务启动 =="
for i in $(seq 1 60); do
    if curl -fsS "${BASE_URL}/" >/dev/null 2>&1; then
        break
    fi
    sleep 2
done
curl -fsS "${BASE_URL}/" >/dev/null

echo "== 版本与扩展 =="
docker exec "$CONTAINER" php -v | head -1
docker exec "$CONTAINER" php -r '
    foreach (["pcntl","sockets","posix","pdo_sqlite","pdo_mysql","ldap","gd","mbstring","curl"] as $e) {
        if (!extension_loaded($e)) { echo "missing extension: $e\n"; exit(1); }
    }
    echo "extensions: OK\n";
'
docker exec "$CONTAINER" sh -c 'test -S /run/php-fpm/www.sock' || { echo "FAIL php-fpm socket 不存在"; exit 1; }
echo "php-fpm socket: OK"
docker exec "$CONTAINER" httpd -M | grep -q proxy_fcgi_module || { echo "FAIL Apache 未加载 proxy_fcgi"; exit 1; }
echo "apache proxy_fcgi: OK"

echo "== 验证码与登录 =="
VERIFY=$(api 'c=Setting&a=GetVerifyOption&t=web' '{}')
echo "GetVerifyOption: $VERIFY"
VERIFY_ENABLE=$(printf '%s' "$VERIFY" | php_json "data.enable")

UUID=""
CODE=""
if [ "$VERIFY_ENABLE" = "1" ]; then
    VC=$(api 'c=Common&a=GetVerifyCode&t=web' '{}')
    UUID=$(printf '%s' "$VC" | php_json "data.uuid")
    CODE=$(docker exec "$CONTAINER" php -r '$p=new PDO("sqlite:/home/svnadmin/svnadmin.db"); echo $p->query("select code from verification_code order by code_id desc limit 1")->fetchColumn();')
    if [ -z "$UUID" ] || [ -z "$CODE" ]; then
        echo "FAIL 无法获取验证码 uuid/code"
        exit 1
    fi
fi

LOGIN=$(api 'c=Common&a=Login&t=web' "{\"user_name\":\"${ADMIN_USER}\",\"user_pass\":\"${ADMIN_PASS}\",\"user_role\":\"1\",\"uuid\":\"${UUID}\",\"code\":\"${CODE}\"}")
LOGIN_STATUS=$(printf '%s' "$LOGIN" | php_json "status")
if [ "$LOGIN_STATUS" != "1" ]; then
    echo "FAIL 登录失败: $LOGIN"
    exit 1
fi
TOKEN_RAW=$(printf '%s' "$LOGIN" | php_json "data.token")
TOKEN=$(printf '%s' "$TOKEN_RAW" | python3 -c 'import sys,urllib.parse; print(urllib.parse.quote(sys.stdin.read(), safe=""))' 2>/dev/null || true)
if [ -z "$TOKEN" ]; then
    # 没有 python3 时用容器内 PHP 做 URL 编码
    TOKEN=$(printf '%s' "$TOKEN_RAW" | docker exec -i "$CONTAINER" php -r 'echo rawurlencode(stream_get_contents(STDIN));')
fi
if [ -z "$TOKEN" ]; then
    echo "FAIL 无法提取 token"
    exit 1
fi
echo "OK   登录成功"

echo "== 只读接口 =="
check "Statistics/GetStatisticsInfo" 'c=Statistics&a=GetStatisticsInfo&t=web' '{}'
check "Svnrep/GetRepList" 'c=Svnrep&a=GetRepList&t=web' '{}'
check "Svnuser/GetUserList" 'c=Svnuser&a=GetUserList&t=web' '{"sync":false,"page":false,"searchKeyword":"","sortName":"svn_user_id","sortType":"ASC"}'
check "Svngroup/GetGroupList" 'c=Svngroup&a=GetGroupList&t=web' '{"sync":false,"page":false,"searchKeyword":"","sortName":"svn_group_id","sortType":"ASC"}'
check "Svnaliase/GetAliaseList" 'c=Svnaliase&a=GetAliaseList&t=web' '{"searchKeyword":""}'
check "Logs/GetLogList" 'c=Logs&a=GetLogList&t=web' '{"pageSize":10,"currentPage":1,"searchKeyword":""}'
check "Tasks/GetTaskQueue" 'c=Tasks&a=GetTaskQueue&t=web' '{}'
check "Setting/GetDirInfo" 'c=Setting&a=GetDirInfo&t=web' '{}'
check "Setting/GetSvnInfo" 'c=Setting&a=GetSvnInfo&t=web' '{}'
check "Setting/GetApacheInfo" 'c=Setting&a=GetApacheInfo&t=web' '{}'
check "Setting/GetSafeInfo" 'c=Setting&a=GetSafeInfo&t=web' '{}'

echo "== 日志检查 =="
if docker exec "$CONTAINER" sh -c 'test -f /home/svnadmin/logs/php_error.log && grep -Eq "PHP Fatal|PHP Parse error|Uncaught" /home/svnadmin/logs/php_error.log'; then
    echo "FAIL php_error.log 中发现致命错误"
    docker exec "$CONTAINER" tail -n 50 /home/svnadmin/logs/php_error.log
    FAILED=1
else
    echo "OK   php_error.log 无致命错误"
fi

if [ "$FAILED" = "1" ]; then
    echo "== 冒烟测试失败 =="
    exit 1
fi

echo "== 冒烟测试通过 =="
