export default {
    zh: '中文',
    cancel: '取消',
    confirm: '确定',
    save: '保存',
    add: '添加',
    edit: '编辑',
    modify: '修改',
    confirmModify: '确认修改',
    delete: '删除',
    username: '用户名',
    password: '密码',
    newPassword: '新密码',
    confirmNewPassword: '确认新密码',
    note: '备注',
    serial: '序号',
    status: '启用状态',
    others: '其它',
    createTime: '创建时间',
    退出登录成功: '退出登录成功',
    loginPage: {
        inputUsername: '请输入用户名',
        inputPassword: '请输入密码',
        inputCode: '请输入验证码',
        login: '登录',
        usernameCannotBeEmpty: '用户名不能为空',
        passwordCannotBeEmpty: '密码不能为空',
        codeCannotBeEmpty: '验证码不能为空',
        userAlreadyLogin: '已有登录信息 自动跳转中...',
        登陆成功: '登录成功',
        验证码错误: '登录失败[验证码错误]',
        验证码失效: '登录失败[验证码失效]',
        验证码过期: '登录失败[验证码过期]',
        账号或密码错误: '登录失败[账号或密码错误]',
        ldap账户未同步: '登录失败[ldap账户未同步]',
        ldap账户认证失败: '登录失败[ldap账户认证失败]',
        ldap账户名不合法: '登录失败[ldap账户名不合法]',
        账号或密码错误: '登录失败[账号或密码错误]',
        用户已过期: '登录失败[用户已过期]',
        用户未同步: '登录失败[用户未同步]',
    },
    menus: {
        SVNAdmin: 'SVNAdmin',
        backendTasks: '后台任务',
        仓库: '仓库',
        信息统计: '信息统计',
        SVN仓库: 'SVN仓库',
        SVN用户: 'SVN用户',
        SVN分组: 'SVN分组',
        运维: '运维',
        系统日志: '系统日志',
        任务计划: '任务计划',
        高级: '高级',
        个人中心: '个人中心',
        子管理员: '子管理员',
        系统配置: '系统配置',
        logout: '退出登录',
    },
    roles: {
        管理员: '管理员',
        SVN用户: 'SVN用户',
        子管理员: '子管理员',
        未知: '未知',
    },
    backendTasks: {
        realtimeBackendTasks: '实时后台任务',
        currentTasks: '当前任务',
        tasksInQueue: '排队任务',
        historyTasks: '历史任务',
        noTasksRunning: '当前没有后台任务运行（如遇任务堆积不执行可重启守护进程解决）',
    },
    errors: {
        contactAdmin: '出错了 请联系管理员！',
    },
    crond: {
        plsCheckCrondAtd: '请确保依赖的 crond atd 服务安装并正常运行',
        addCrond: '添加任务计划',
        searchByNameAndDesc: '通过任务名称和描述搜索...',
        noNotice: '通知关闭',
        successNotice: '仅成功通知',
        failureNotice: '仅失败通知',
        allNotice: '全部通知',
        viewLog: '日志',
        tipCheckByTrigger: '不确定任务是否配置成功可手动执行一次通过分析日志查看具体情况',
        trigger: '执行',
        type: '任务类型',
        name: '任务名称',
        cycleType: '执行周期',
        changeRepo: '仓库选择',
        notice: '消息通知',
        noticeSuccess: '成功通知',
        noticeFailure: '失败通知',
        saveCount: '保存数量',
        scriptContent: '脚本内容',
        inputScriptContent: '请输入脚本内容',
        viewCrondLog: '查看任务计划日志',
        logFile: '日志文件',
        dumpFull: '仓库备份[dump-全量]',
        dumpDeltas: '仓库备份[dump-增量-deltas]',
        hotcopyFull: '仓库热备份[hotcopy-全量]',
        hotcopyDeltas: '仓库热备份[hotcopy-增量]',
        allRepos: '所有仓库',
        checkRepo: '仓库检查',
        shellScript: 'Shell脚本',
        syncSvnUser: '同步SVN用户',
        syncSvnGroup: '同步SVN分组',
        syncSvnRepo: '同步SVN仓库',
        minute: '每分钟',
        minute_n:'每隔N分钟',
        hour: '每小时',
        hour_n: '每隔N小时',
        day: '每天',
        day_n: '每隔N天',
        week: '每周',
        month: '每月',
        Monday: '周一',
        Tuesday: '周二',
        Wednesday: '周三',
        Thursday: '周四',
        Friday: '周五',
        Saturday: '周六',
        Sunday: '周日',
        monthDay: '{0}日',
        dayDay: '天',
        hourHour: '小时',
        minuteMinute: '分钟',
        cycleDesc: '执行周期描述',
        lastExecTime: '上次执行时间',
        time: '时间',
        content: '内容',
        editCrond: '编辑计划任务',
        deleteCrond: '删除计划任务',
        confirmDelCrond: '确定要删除该记录吗？此操作不可逆！',
        triggerCrond: '执行计划任务',
        confirmTriggerCrond: '确定要立即执行该任务计划吗？该操作可用于测试任务计划配置的正确性！',
    },
    index: {
        loadStatus: '负载状态',
        cpuLoad1Min: '最近1分钟平均负载：',
        cpuLoad5Min: '最近5分钟平均负载：',
        cpuLoad15Min: '最近15分钟平均负载：',
        cpuUsage: 'CPU使用率',
        memUsage: '内存使用率',
        cpuPhysical: '个物理CPU',
        cpuCore: '个物理核心',
        cpuProcessor: '个逻辑核心/线程',
        fileSystem: '文件系统：',
        fsSize: '容量：',
        fsUsed: '已使用+系统占用：',
        fsAvail: '可使用：',
        fsPercent: '使用率：',
        mountOn: '挂载点：',
        statistics: '统计',
        svnRepo: 'SVN仓库',
        repoSize: '仓库占用',
        repoBackup: '仓库备份',
        backupSize: '备份占用',
        logs: '运行日志',
        svnAlias: 'SVN别名',
        运行堵塞: '运行堵塞',
        运行缓慢: '运行缓慢',
        运行正常: '运行正常',
        运行流畅: '运行流畅',
        未知: '未知',
    },
    personal: {
        changePassword: '修改密码',
        adminAccount: '管理员账户',
        adminPassword: '管理员密码',
        subadminAccount: '子管理员账户',
        subadminPassword: '子管理员密码',
        modifyAdminAccount: '修改管理员账户',
        newAccount: '新账户',
        modifyAdminPassword: '修改管理员密码',
        modifySubadminPassword: '修改子管理员密码',
    },
    subadmin: {
        createSubadmin: '新建子管理员',
        searchByNameAndDesc: '通过用户名、备注信息搜索...',
        priTree: '配置',
        online: '在线',
        offline: '离线',
        resetPassword: '重置密码',
        delete: '删除',
        permissionConfig: '子管理员权限配置',
        reAuth: '由于版本升级-权限节点调整-请参考旧权限树-重新为子管理员授权',
        oldPriTree: '旧权限树',
        newPriTree: '权限树',
        lastLogin: '上次登录',
        onlineStatus: '在线状态',
        sysPermission: '系统权限',
        deleteSubadmin: '删除子管理员',
        confirmDeleteSubadmin: '确定要删除该子管理员吗？<br/>该操作不可逆！',
    },
    repositoryInfo: {
        noDataNow: '暂无数据',
    }
}