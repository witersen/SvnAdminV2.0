module.exports = {
    en: 'English',
    cancel: 'Cancel',
    confirm: 'Confirm',
    save: 'Save',
    add: 'Add',
    edit: 'Edit',
    modify: 'Modify',
    confirmModify: 'Confirm Modify',
    delete: 'Delete',
    copy: 'Copy',
    action: 'Action',
    apply: 'Apply',
    close: 'Close',
    reset: 'Reset',
    view: 'View',
    config: 'Config',
    advance: 'Advanced',
    username: 'Username',
    password: 'Password',
    newPassword: 'New Password',
    confirmNewPassword: 'Confirm New Password',
    note: 'Note',
    serial: 'ID',
    status: 'Status',
    others: 'Others',
    success: 'Success',
    fail: 'Fail',
    createTime: 'Create Time',
    noDataNow: 'No data now',
    operator: 'Operator',
    退出登录成功: 'Logout success',
    roles: {
        管理员: 'Administrator',
        SVN用户: 'SVN User',
        子管理员: 'Sub-Admin',
        未知: 'Unknown',
    },
    backendTasks: {
        realtimeBackendTasks: 'Real-time Backend Tasks',
        currentTasks: 'Current Tasks',
        tasksInQueue: 'Tasks in Queue',
        historyTasks: 'History Tasks',
        noTasksRunning: 'Currently no background tasks running (if there is a backlog of tasks, please restart the daemon process to resolve the issue)',
        running: 'Running',
        waiting: 'Waiting',
        stopTask: 'Stop Task',
        cancelTask: 'Cancel Task',
        completed: 'Completed',
        cancelled: 'Cancelled',
        stopped: 'Stopped',
        viewLog: 'Log',
        taskLog: 'Task Log',
        taskName: 'Task Name',
        endTime: 'End Time',
        stopConfirm: 'Confirm to stop task',
        stopConfirmContent: 'Are you sure to stop this task?<br/>Cannot garrantee the task will be stopped successfully!',
    },
    errors: {
        contactAdmin: 'There was an error. Please contact the administrator.',
    },
    crond: {
        plsCheckCrondAtd: 'Please check if crond/atd is installed correctly and running',
        addCrond: 'Add Crond',
        searchByNameAndDesc: 'Search by task name and description',
        noNotice: 'No notice',
        successNotice: 'Only success notice',
        failureNotice: 'Only failure notice',
        allNotice: 'All notice',
        viewLog: 'Log',
        tipCheckByTrigger: 'Trigger the task manually to check the specific situation by analyzing the log',
        trigger: 'Trigger',
        type: 'Task Type',
        name: 'Task Name',
        cycleType: 'Task Cycle',
        changeRepo: 'Change Repository',
        notice: 'Notification',
        noticeSuccess: 'Success notification',
        noticeFailure: 'Failure notification',
        saveCount: 'Save Count',
        scriptContent: 'Script Content',
        inputScriptContent: 'Input script content',
        viewCrondLog: 'View Crond Log',
        logFile: 'Log File',
        dumpFull: 'Repository Backup [dump-full]',
        dumpDeltas: 'Repository Backup [dump-deltas]',
        hotcopyFull: 'Repository Backup [hotcopy-full]',
        hotcopyDeltas: 'Repository Backup [hotcopy-deltas]',
        allRepos: 'All Repositories',
        checkRepo: 'Check Repository',
        shellScript: 'Shell Script',
        syncSvnUser: 'Sync Svn User',
        syncSvnGroup: 'Sync Svn Group',
        syncSvnRepo: 'Sync Svn Repository',
        minute: 'Minute',
        minute_n:'Every N minutes',
        hour: 'Hourly',
        hour_n: 'Every N hours',
        day: 'Daily',
        day_n: 'Every N days',
        week: 'Weekly',
        month: 'Monthly',
        Monday: 'Monday',
        Tuesday: 'Tuesday',
        Wednesday: 'Wednesday',
        Thursday: 'Thursday',
        Friday: 'Friday',
        Saturday: 'Saturday',
        Sunday: 'Sunday',
        monthDay: 'Day {0}',
        dayDay: ' days',
        hourHour: ' hours',
        minuteMinute: ' minutes',
        cycleDesc: 'Cycle Description',
        lastExecTime: 'Last Execution Time',
        time: 'Time',
        content: 'Content',
        editCrond: 'Edit Crontab',
        deleteCrond: 'Delete Crontab',
        confirmDelCrond: 'Are you sure to delete this crontab task? All tasks related to this record will be deleted!',
        triggerCrond: 'Trigger Crontab',
        confirmTriggerCrond: 'Are you sure to trigger this crontab task? This operation can be used to test the correctness of the crontab task configuration!',
    },
    index: {
        loadStatus: 'Load Status',
        cpuLoad1Min: 'Last 1 minute average load:',
        cpuLoad5Min: 'Last 5 minute average load:',
        cpuLoad15Min: 'Last 15 minute average load:',
        cpuUsage: 'CPU Usage',
        memUsage: 'Memory Usage',
        cpuPhysical: ' Physical CPU',
        cpuCore: ' CPU Cores',
        cpuProcessor: ' Logical Processors',
        fileSystem: 'File System: ',
        fsSize: 'Size: ',
        fsUsed: 'Used: ',
        fsAvail: 'Available: ',
        fsPercent: 'Usage: ',
        mountOn: 'Mounted on: ',
        statistics: 'Statistics',
        svnRepo: 'SVN Repository',
        repoSize: 'Repository Size',
        repoBackup: 'Repository Backup',
        backupSize: 'Backup Size',
        logs: 'Logs',
        svnAlias: 'SVN Aliases',
        运行堵塞: 'Running Jammed',
        运行缓慢: 'Running Slow',
        运行正常: 'Running Normal',
        运行流畅: 'Running Quick',
        未知: 'Unknown',
    },
    layout: {
        SVNAdmin: 'SVNAdmin',
        backendTasks: 'Backend Tasks',
        仓库: 'Repository',
        信息统计: 'Statics',
        SVN仓库: 'SVN Repository',
        SVN用户: 'SVN Users',
        SVN分组: 'SVN Groups',
        运维: 'Operations',
        系统日志: 'System Logs',
        任务计划: 'Cron Tasks',
        高级: 'Advanced',
        个人中心: 'Personal',
        子管理员: 'Sub-Admin',
        系统配置: 'System Config',
        logout: 'Logout',
    },
    login: {
        inputUsername: 'Please enter username',
        inputPassword: 'Please enter password',
        inputCode: 'Please enter verification code',
        login: 'Login',
        usernameCannotBeEmpty: 'Username cannot be empty',
        passwordCannotBeEmpty: 'Password cannot be empty',
        codeCannotBeEmpty: 'Verification code cannot be empty',
        userAlreadyLogin: 'User already login，redirecting...',
        登陆成功: 'Login success',
        验证码错误: 'Login failed[Verification code error]',
        验证码失效: 'Login failed[Verification code expired]',
        验证码过期: 'Login failed[Verification code expired]',
        账号或密码错误: 'Login failed[Account or password error]',
        ldap账户未同步: 'Login failed[ldap account not synchronized]',
        ldap账户认证失败: 'Login failed[ldap account authentication failed]',
        ldap账户名不合法: 'Login failed[ldap account name is illegal]',
        用户已过期: 'Login failed[User has expired]',
        用户未同步: 'Login failed[User not synchronized]',
    },
    logs: {
        clearLogs: 'Clear Logs',
        exportLogs: 'Export Logs',
        logName: 'SVNAdmin2-Logs',
        searchLogs: 'Search Logs',
        logType: 'Log Type',
        content: 'Content',
        addTime: 'Add Time',
    },
    personal: {
        changePassword: 'Change Password',
        adminAccount: 'Admin Account',
        adminPassword: 'Admin Password',
        subadminAccount: 'Sub-Admin Account',
        subadminPassword: 'Sub-Admin Password',
        modifyAdminAccount: 'Modify Admin Account',
        newAccount: 'New Account',
        modifyAdminPassword: 'Modify Admin Password',
        modifySubadminPassword: 'Modify Sub-Admin Password',
    },
    repositoryGroup: {
        createGroup: 'Create SVN Group',
        syncGroupTip: 'Sync to update the SVN Group List',
        syncGroupList: 'Sync SVN Group List',
        searchGroup: 'Search SVN Group',
        groupMember: 'Member',
        groupName: 'Group Name',
        groupNameTip: 'Group name can only contain letters, numbers, hyphens, underscores, and dots.',
        addMember: 'Add Member',
        searchMember: 'Search Member',
        user: 'SVN User',
        group: 'SVN Group',
        aliase: 'SVN Aliase',
        scanGroupTitle: 'Step 1: Group Recognition',
        authzContent: `Please pasete the content of the authz file

Example:  

[groups]
group1=user1,user2,@group2
group2=user3
group3=user4,&aliase1`,
        scanGroup: 'Scan Group',
        includeUserCount: 'User Count',
        includeGroupCount: 'Group Count',
        includeAliaseCount: 'Aliase Count',
        objectType: 'Object Type',
        objectName: 'Object Name',
        editGroupName: 'Edit SVN Group Name',
        deleteGroup: 'Delete SVN Group',
        deleteGroupConfirm: 'Confirm to delete SVN Group?<br/>It will remove the group from all repositories and groups!<br/>This operation cannot be undone!',
        editGroupMember: 'Edit SVN Group Member',
    },
    repositoryInfo: {
        createRepo: 'Create SVN Repository',
        syncRepListTip: 'This operation will scan the valid repository list on the disk',
        syncRepList: 'Sync SVN Repository List',
        syncRepListInfoTip: 'This operation will scan the valid repository list on the disk and read the size and version information of each repository, which is a time-consuming operation',
        syncRepListInfo: 'Sync SVN Repository Info',
        syncUserRepListTip: 'Get the latest permission list by synchronizing',
        syncUserRepList: 'Sync User Repository List',
        checkAuthzTip: `Inadvertent configuration can cause the authz profile to become invalid
For example, in svnserve 1.10, an empty grouped authorization repository may cause the configuration to become invalid
If the configuration file fails, the user cannot check out or browse the configuration file
This tool allows you to check online for problems with your Autz profile
This feature relies on svnauthz-validate`,
        checkAuthz: 'authz validate',
        searchRepByNameDesc: 'Search by repository name or description',
        searchRepByName: 'Search by repository name',
        viewRaw: 'View Raw',
        repoName: 'Repository Name',
        repoNameTip: 'Repository name can only contain Chinese, letters, numbers, hyphens, underscores, and dots. It cannot start or end with a dot.',
        repoType: 'Repository Type',
        emptyRepo: 'Empty Repository',
        standardRepo: 'Repository with "trunk" "branches" "tags" folders',
        repoHooksAlert: 'If SVN client is triggering related hooks, update action may block or fail until client finishes related processes',
        repoHooks: 'Repo Hooks',
        introduce: 'Introduction',
        recommendHooks: 'Recommend Hooks',
        recommendHooksAlert1: 'If you want to display your commonly used hooks here, ',
        recommendHooksAlert2: 'Follow the steps below to add a pre-commit hook as an example:',
        recommendHooksAlert3: '1. Create a folder in the /home/svnadmin/hooks/ directory with any name',
        recommendHooksAlert4: '2. Create a file named hookDescription in the folder and write the main function description of the hook',
        recommendHooksAlert5: '3. Create a file named hookName in the folder and write the hook type',
        recommendHooksAlert6: '4. Create a file named pre-commit in the folder and write the hook script content',
        hookFilePlaceHolder: 'Please refer to the hook introduction for details',
        repoAttribute: 'Repository Attribute',
        repoBackup: 'Repository Backup',
        cannotUploadAlert: 'PHP file upload function is not enabled',
        backupByCrondDump: 'Backup by add crond task with svnadmin dump',
        backupNow: 'Backup Now',
        uploadBackup: 'Upload Backup',
        loadBackup: 'Load Backup',
        downloadBackup: 'Download Backup',
        resetUUID: 'Reset Repository UUID',
        inputUUID: 'Auto-generate new UUID if left blank',
        authzCheckResult: 'authz Check Result',
        repoLoadError: 'Repository Load Error',
        uploadBackupFile: 'Upload Backup File',
        uploadFile: 'Upload File',
        chooseFile: 'Choose File',
        uploadProgress: 'Upload Progress',
        filename: 'Filename',
        filesize: 'File Size',
        uploadStatus: 'Upload Status',
        chunkSize: 'Chunk Size',
        timeleft: 'Time Left',
        clearChunks: 'Clear Chunks',
        deleteOnMerge: 'Auto-delete chunks after merging',
        keepOnMerge: 'Do not delete chunks after merging',
        uploadControl: 'Upload Control',
        pause: 'Pause',
        pauseTips: 'You can resume the upload later - the uploaded chunks will not be deleted',
        repoRev: 'Revision',
        repoSize: 'Size',
        repoScan: 'Content',
        repoPri: 'Permission',
        pathFile: 'Path/File',
        secondPri: 'Second Authorize',
        resourceType: 'Type',
        resourceName: 'File',
        revAuthor: 'Author',
        revNum: 'Revision',
        revTime: 'Time',
        revLog: 'Log',
        fileEditTime: 'Modified Time',
        userPri: 'Permission',
        groupName: 'Group Name',
        groupPri: 'Permission',
        repoInfo: 'Repository Info',
        noDataTextRepCon: 'Since the svnserve service is not started, SVN users can only copy checkout addresses and cannot browse the repository content',
        copySuccess: 'Copied successfully',
        copyFailed: 'Failed to copy, please try manually',
        mergingChunks: 'Merging chunks',
        mergeSuccess: 'Merged successfully',
        chunksUploading: ' chunks uploading',
        hours: ' H',
        minutes: ' M',
        seconds: ' S',
        chunksMd5Calculating: ' chuns\' md5 under calculation',
        deleteFile: 'Delete File',
        deleteFileConfirm: 'Are you sure to delete this file?<br/>This operation cannot be undone!',
        modifyRepoName: 'Modify Repository Name',
        deleteRepo: 'Delete Repository',
        deleteRepoConfirm: 'Are you sure to delete this repository?<br/>This operation cannot be undone!<br/>If the repository is being used by other users, please make sure to stop the network transfer before deleting the repository!',
    },
    repositoryUser: {
        createUser: 'Create SVN User',
        userScan: 'Scan User',
        syncListTip: `1. You need to synchronize to get the latest user list
2. Users need to be synchronized to be able to login the system who are manually added to the passwd file`,
        syncList: 'Sync SVN User List',
        searchUser: 'Search SVN User',
        online: 'Online',
        offline: 'Offline',
        userNameAlert: 'Username can only contain letters, numbers, hyphens, underscores, and dots. It cannot start or end with a dot.',
        userRecogonize: 'Step 1: User Recognition',
        userPasswdTips: `Please paste the contents of the passwd file

If it is checked out by the svn protocol  (password plaintext), the following is an example:  

[users]
user1=passwd1
user2=passwd2

If it is checked out by the HTTP protocol (cipher redaction), the following is an example:  

user1:passwd1
user2:passwd2`,
        userScanResult: 'Step 2: Result Confirmation',
        enabled: 'Enabled',
        disabled: 'Disabled',
        userImport: 'Import',
        userImportResult: 'Step 3: Import Result',
        secondPriObj: 'Second Authorize Object',
        userPriPath: 'User Permission Path',
        lastLogin: 'Last Login',
        onlineStatus: 'Online Status',
        secondPriStatus: 'Second Authorize Status',
        secondPriTips: 'Second-level authorization allows SVN users to grant path permissions to ordinary SVN users',
        forExample: 'For example:',
        secondPriTips1: 'projects repository contains projects: project1 project2 ...',
        secondPriTips2: 'user1 user2 user3 is in charge of project1',
        secondPriTips3: 'user1 is the project mananger',
        secondPriTips4: 'user2 is developer',
        secondPriTips5: 'user2 is tester',
        secondPriTips6: '(1) Administrator enables the second authorization swith to user1 for this path',
        secondPriTips7: '(2) Administrator select the second-level authorization object (in this case, user2 user3)',
        secondPriTips8: 'user1 can authorize ordinary users to manage projects without administrator intervention',
        secondPriTips9: 'Closing the second-level authorization will synchronize the destruction of the second-level authorization object configured',
        userStatus: 'User Status',
        importResult: 'Import Result',
        reason: 'Reason',
        deleteUser: 'Delete User',
        deleteUserConfirm: 'Are you sure to delete this user?<br/>It will remove the user from all repositories and groups!<br/>This operation cannot be undone!',
        userPriPathList: 'Path Permission List',
    },
    setting: {
        serverConfig: 'Server Config',
        serverConfigDesc: 'This information is mainly used to construct the repository checkout address',
        serverNameIp: 'Host IP/Domain',
        serverNameIpTip: 'This value is maintained only through the database and does not affect the running of the business',
        info: 'Information',
        hostSvnPort: 'Host SVN Port',
        hostPortTip: 'This value is only maintained by the database - does not affect the business operation - the function is that when you are in a container environment, you usually do port mapping, resulting in different ports in the container and the host port - in this case, for display convenience - you can configure this value - when you are not in the container environment, this value is consistent with the actual port value',
        hostWebPort: 'Host Web Port',
        pathInfo: 'Path Info',
        pathInfoDesc: 'You can perform server/install.php directory replacement operations in command-line mode',
        checkoutBySvnProtocol: 'Checkout By Svn Protocol',
        protocolStatus: 'Protocol Status',
        disable: 'Disabled',
        enabled: 'Enabled',
        protocolStatusTip: 'For the sake of repository data security - SVN and HTTP checkout are not recommended to be provided at the same time - so only one service can be enabled at the same time',
        enable: 'Enable',
        svnserveInfo: 'svnserve Info',
        svnserveTip: 'When checking out using the SVN protocol, it must go through the SVNSERVE service',
        runningStatus: 'Running Status',
        runningStatusTip: 'The running status is determined by the PID file and the PID value - if there is a mistake, please check how the SVNSERVE program is started',
        notStart: 'Stopped',
        running: 'Running',
        start: 'Start',
        stop: 'Stop',
        listeningPort: 'Listening Port',
        listeningPortTip: 'If your application is deployed in container, you do not need to modify this value, only the port mapping from the host to the container',
        listeningAddress: 'Listening Address',
        listeningAddressTip: '(1) Note that this value defaults to 0.0.0.0 and is the actual default binding address for the svnserve server. You don\'t need to modify this default value if you don\'t have a special reason. If you want to change the IP address to a public IP address and your machine is a public server and is not an elastic IP address, the binding may fail. The reason is related to the way the ECS vendor allocates public IP addresses to servers. (2) If your app is deployed as a container, you do not need to modify this value',
        passwordDb: 'User/Password Database',
        passwordDbTip: 'User authentication file uses plain text password by default if use SVN protocol checks out',
        saslauthdService: 'saslauthd Service',
        saslauthdServiceTip: 'When checking out using the SVN protocol, in order to access third-party authentication, such as LDAP, we need to use Saslauthd service',
        supportInfo: 'Support Info',
        runningStatusTip: 'The running status is determined by the PID file and the PID value - if there is a mistake, please check how the SASLAUTHD program is started',
        userSource: 'User Source',
        svnUserSource: 'SVN User Source',
        passwdFile: 'passwd File',
        svnGroupSource: 'SVN Group Source',
        authzFile: 'authz File',
        ldapSourceTip: 'If you want to set the SVN group source to LDAP, you must set the SVN user source to LDAP',
        ldapServer: 'LDAP Server',
        ldapPort: 'LDAP Port',
        ldapServerAddress: 'LDAP Server Address',
        ldapVersion: 'LDAP Version',
        ldapBindDnTip: 'e.g. CN=blue,CN=Users,DC=witersen,DC=com',
        ldapTest: 'Test LDAP',
        ldapUser: 'LDAP User',
        ldapBaseDnTip: 'e.g. CN=Users,DC=witersen,DC=com',
        ldapAttributesTip: 'e.g. sAMAccountName (Note: If you do not filter to the results, you can switch this property to all lowercase for testing)',
        ldapGroup: 'LDAP Group',
        ldapGroupBaseDnTip: 'e.g. DC=witersen,DC=com',
        ldapGroupSearchFilterTip: 'e.g. (objectClass=group)',
        ldapGroupAttributesTip: 'e.g. sAMAccountName',
        ldapGroupsToUserAttributeTip: 'Represents a multi-valued attribute in a group, which contains 0 to more than 1 users',
        ldapGroupsToUserAttributeValueTip: 'Generally, when traversing the Lerdapu user, the value corresponding to the [Groups to user attribute value] attribute of a user is equal to one of the items corresponding to the [Groups to user attribute] of a certain group, and the group includes the user',
        checkoutByHttpProtocol: 'Checkout By Http Protocol',
        httpProtocolTip: 'SVN protocol checkout and HTTP protocol checkout can be provided at the same time - just the management system is only recommended to manage one set of user data at the same time - so the management switch is made through this button',
        apacheServiceInfo: 'apache Service',
        apacheServiceInfoTip: 'When checking out using the HTTP protocol, it must go through Apache and mod_dav_svn modules',
        modulesInfo: 'SVN Related Modules',
        modulesPathInfo: 'Modules Path',
        apacheListeningPortTip: '(1) This value is only maintained by the database - does not affect the business operation - due to the complexity of the actual situation, it is inconvenient to monitor and manage the running port of the Apache server - so when the system apache is actually configured with the port value, fill in the port value here - if the filling is incorrect, it will affect the user\'s online warehouse browsing function in the system in the HTTP protocol checkout mode (2) If your application is deployed in container mode, you do not need to modify this value',
        httpRepoPrefix: 'Path Prefix',
        httpRepoPrefixTip: 'This value defaults to /svn as the path to access the repository when checking out using the http protocol - if set to / Please note the address conflict with the management system - the repository can be prefixed by configuring a virtual path such as /manage for the system',
        httpPasswordDbTip: 'User authentication file uses encrypted password by default if use HTTP protocol checks out',
        httpPasswdFile: 'httpPasswd File',
        emailSetting: 'Email Setting',
        smtpServerInfo: 'SMTP Server',
        encryption: 'Encryption',
        none: 'None',
        ssl: 'SSL',
        tls: 'TLS',
        smtpEncryptionTip: 'For most servers, TLS is recommended. If your SMTP provider offers both SSL and TLS options, we recommend using TLS.',
        smtpPort: 'SMTP Port',
        autoTls: 'Auto TLS',
        smtpAutoTlsTip: 'By default, if the server supports TLS encryption, TLS encryption is automatically used (recommended). In some cases, it needs to be disabled due to a server misconfiguration that can cause problems.',
        auth: 'Authentication',
        smtpUser: 'SMTP User',
        smtpUserTip: 'If you are using the QQ mail service, please note that for @qq.com email address, only enter the part before @, and for @vip.qq.com email address, you may need to enter the full address',
        smtpPass: 'SMTP Password',
        fromEmailAddress: 'From Email Address',
        fromEmailAddressTip: 'By default, it is the same as the username and needs to be in email format',
        toEmailAddress: 'To Email Address',
        toEmailAddressTip: 'The recipient mailbox will only receive the message if the push message option is triggered and the mail service is enabled',
        testEmailAddress: 'Test Email Address',
        testEmailAddressDesc: 'Test email address will not be saved',
        testEmailAddressTip: 'Sending a test email will use the configuration information entered in the current form instead of the configuration information that has already been saved. The global default sending timeout is 10s, please modify it if necessary.',
        send: 'Send',
        smtpSendTimeout: 'SMTP Send Timeout',
        smtpStatus: 'SMTP Status',
        pushSetting: 'Push Setting',
        pushSettingTip: 'Since the email sending does not use the asynchronous task, <br /><br /> the response time of the mail push module will be delayed accordingly<br /><br /> for example, the response time of the user clicking login ~ login successfully redirected = normal processing time + email sending time',
        safeSetting: 'Security Setting',
        currentVersion: 'Current Version',
        phpVersion: 'PHP Version',
        supportedDatabase: 'Supported Database',
        codeSource: 'Source Code',
        checkUpdate: 'Check Update',
        updateInfo: 'Version Update',
        latestVersion: 'Latest Version',
        fixedContent: 'Fixed',
        addContent: 'Addedd',
        removeContent: 'Removed',
        releaseDownload: 'Release Download',
        node: 'Node',
        download: 'Download',
        updateDownload: 'Update Download',
        updateStep: 'Update Steps',
        addToEmail: 'Add Recipients',
        systemUpdate: 'System Update',
        emailEmpty: 'Email address cannot be empty',
        emailRepeat: 'Email address already exists',
        startSvnserveDaemon: 'Start svnserve daemon',
        startSvnserveConfirm: 'Are you sure to start svnserve daemon?',
        stopSvnserve: 'Stop svnserve daemon',
        stopSvnserveConfirm: 'Are you sure to stop svnserve daemon?',
        changeSvnservePort: 'Change svnserve service port',
        changeSvnservePortConfirm: 'Are you sure you to change the SVNSERVE service binding port? This will cause the SVNSERVE service to restart!',
        changeSvnserveHost: 'Change svnserve service host',
        changeSvnserveHostConfirm: 'Are you sure you to change svnserve service host? This will cause svnserve service to restart!',
        testLdapResult1: 'LDAP Users total ',
        testLdapResult2: ' : success ',
        testLdapResult3: ' , failed ',
        testLdapResult4: ' .',
        testLdapResult5: 'LDAP Groups total ',
        warning: 'Warning',
        changeSvnUsersourceConfirm: 'If you want to switch to an LDAP server, please read the following carefully and make a choice:<br/> 1. This operation will clear the SVN user information in the database, and write the LDAP user to the database during the subsequent manual synchronization. <br/>2. Access to LDAP will not modify the PASSWD file in the system. <br/>3. If the packet source is set to ldap, this operation will clear the SVN packets in the database but not the authz packets immediately. During subsequent manual synchronization, the grouping information of AUTHZ is automatically cleared, and then the grouping information is synchronized to the AUTHZ file and database. <br/>4. This operation will not clear the repository path permissions that have been configured for the cleaned group and the user',
        startSaslauthdDaemon: 'Start saslauthd daemon',
        startSaslauthdConfirm: 'Are you sure to start saslauthd daemon?',
        stopSaslauthd: 'Stop saslauthd daemon',
        stopSaslauthdConfirm: 'Are you sure to stop saslauthd daemon?',
        enableHttpProtocol: 'Enabling HTTP checkout will use a different user password file, empty the current user information in the database, and stop SVN checkout. Do you want to continue?',
        waitingHttpdRestart: 'Waiting for httpd restart',
        enableSvnProtocol: 'Enabling SVN checkout will use a different user password file, empty the current user information in the database, and stop HTTP checkout. Do you want to continue?',
        restartHttpdConfirm: 'Are you sure to restart httpd service?',
    },
    subadmin: {
        createSubadmin: 'Create Sub-Admin',
        searchByNameAndDesc: 'Search by username and description',
        priTree: 'Config',
        online: 'Online',
        offline: 'Offline',
        resetPassword: 'Reset',
        delete: 'Delete',
        permissionConfig: 'Config Permission of Sub-Admin',
        reAuth: 'Please refer to the old permission tree and re-authorize the sub-admin for the new permission nodes.',
        oldPriTree: 'Old Permission Tree',
        newPriTree: 'Permission Tree',
        lastLogin: 'Last Login',
        onlineStatus: 'Online Status',
        sysPermission: 'System Permission',
        deleteSubadmin: 'Delete Sub-Admin',
        confirmDeleteSubadmin: 'Are you sure to delete this sub-admin? <br/>This operation cannot be undone!',
    },
}