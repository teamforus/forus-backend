const path = __dirname;
const logsPath = path + "/storage/logs/";
const projectName = 'forus';

const ignore_watch = [
    ".git",
    ".idea",
    ".htaccess",
    "storage/app",
    "storage/framework",
    "storage/clockwork",
    "storage/debugbar",
    "storage/logs",
    "public/storage",
    "public/assets",
    "public/.htaccess",
    "node_modules"
];

const makeProcess = (name, args, options = {}) => {
    return ({
        name,
        args,
        namespace: `${projectName}`,
        script: "./artisan",
        interpreter: "php",
        watch: true,
        cwd: path,
        ignore_watch,
        watch_delay: 1000,
        out_file: logsPath + "laravel-worker.log",
        error_file: logsPath + "laravel-worker-err.log",
        autorestart: true,
        ...options
    });
}

module.exports = {
    apps: [
        makeProcess(`${projectName}-emails`, "queue:listen --queue=emails --sleep=3 --tries=3 --timeout=1200"),
        makeProcess(`${projectName}-notifications`, "queue:listen --queue=push_notifications --sleep=3 --tries=3 --timeout=1200"),
        makeProcess(`${projectName}-media`, "queue:listen --queue=media --sleep=3 --tries=3 --timeout=1200"),
    ]
};
